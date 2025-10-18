# --- Importaciones estándar ---
import os
import sys
import time
import random
import re
import json
import signal
import subprocess
import threading
import queue
from datetime import datetime
from time import sleep

# --- Importaciones de terceros ---
import cv2
import numpy as np
from pyzbar.pyzbar import decode
import mysql.connector
from RPLCD.i2c import CharLCD
from gtts import gTTS
import serial

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': 'pi1234',
    'database': 'sistemaReciclajeDB',
    'charset': 'utf8mb4'
}

TIPOS_BASURA = ['papel', 'plastico', 'biodegradable', 'metal']
PUNTOS = {'papel': 5, 'plastico': 5, 'biodegradable': 3, 'metal': 5}
MENSAJES_RECICLAJE = [
    "Gracias por reciclar con responsabilidad",
    "Sigue cuidando el planeta con tus acciones",
    "Cada botella cuenta!",
    "Reciclar es amar el futuro",
    "Eres parte del cambio",
    "Juntos por un mundo limpio",
]
# --- Configurar LCD ---
lcd = CharLCD(i2c_expander='PCF8574', address=0x27, port=1, cols=20, rows=4, charmap='A00')
lcd.clear()

# Iconos personalizados
iconos = {
    "reloj":[0b01110,0b10001,0b10111,0b10101,0b10001,0b01110,0b00000,0b00000],
    "calendario":[0b11111,0b10101,0b11111,0b10001,0b10001,0b11111,0b00000,0b00000],
    "reciclaje":[0b00000,0b00100,0b01010,0b01010,0b00100,0b00000,0b00000,0b00000],
    "estrella":[0b00100,0b10101,0b01110,0b11111,0b01110,0b10101,0b00100,0b00000]
}
lcd.create_char(0, iconos["reloj"])
lcd.create_char(1, iconos["calendario"])
lcd.create_char(2, iconos["reciclaje"])
lcd.create_char(3, iconos["estrella"])

lcd_queue = queue.Queue()
audio_queue = queue.Queue()
pausar_scroll = threading.Event()
pausar_scroll.clear()
# --- Configurar puerto Serial para Arduino ---
arduino = serial.Serial('/dev/ttyACM0', 9600, timeout=1)
time.sleep(2) 

ultimo_estudiante = {"nombre": None, "codigo_qr": None}

# ---------------- FUNCIONES ----------------
def centrar(texto, ancho=20):
    return texto.center(ancho)[:ancho]

def reproducir_audio_thread():
    while True:
        texto = audio_queue.get()
        if texto is None:
            break
        try:
            tts = gTTS(text=texto, lang='es', tld='com')
            tts.save("voz.mp3")
            subprocess.run(["mpg123","-q","voz.mp3"])
        except Exception as e:
            print("[ERROR AUDIO]:", e)
            os.system(f'espeak -v es "{texto}"')
        audio_queue.task_done()

def reproducir_audio(texto):
    audio_queue.put(texto)

    def limpiar_nombre(nombre):
        return re.sub(r'[^a-zA-Z0-9_-]', '_', nombre)

    def actualizar_lcd():
        while True:
            hora = datetime.now().strftime("%H:%M")
            fecha = datetime.now().strftime("%d/%m/%Y")
            fila3_msg = random.choice(MENSAJES_RECICLAJE)

            # Mostrar hora y fecha (fila 0 y 1)
            lcd.cursor_pos = (0,0)
            lcd.write_string(chr(0) + " " + centrar(f"Hora: {hora}")[2:])
            lcd.cursor_pos = (1,0)
            lcd.write_string(chr(1) + " " + centrar(f"Fecha: {fecha}")[1:])

            # Mostrar mensajes de QR/Arduino (fila 2)
            try:
                mensaje = lcd_queue.get_nowait()
                for _ in range(4):  # Parpadeo
                    lcd.cursor_pos = (2,0)
                    lcd.write_string(chr(2) + " " + centrar(mensaje)[2:-2] + " " + chr(3))
                    sleep(0.3)
                    lcd.cursor_pos = (2,0)
                    lcd.write_string(" " * 20)
                    sleep(0.3)
                lcd.cursor_pos = (2,0)
                lcd.write_string(centrar(mensaje))
                sleep(2)
            except queue.Empty:
                lcd.cursor_pos = (2,0)
                lcd.write_string(centrar("Esperando QR..."))

            # Scroll fila 3
            if not pausar_scroll.is_set():
                scroll_text = "     " + fila3_msg + " " * 20
                for i in range(len(scroll_text) - 19):
                    lcd.cursor_pos = (3,0)
                    lcd.write_string(scroll_text[i:i+20])
                    sleep(0.2)
                    if pausar_scroll.is_set():
                        break
            sleep(0.5)


def registrar_puntos(codigo_qr, tipo, cursor, conexion):
    # Buscar al estudiante por su QR
    cursor.execute("""
        SELECT u.id, CONCAT(u.nombres,' ',u.primerApellido,' ',IFNULL(u.segundoApellido,'')) 
        FROM usuario u
        WHERE u.qr_codigo=%s AND u.rol='estudiante'
    """, (codigo_qr,))
    res = cursor.fetchone()

    if res:
        id_estudiante, nombre_completo = res
        puntos = PUNTOS[tipo]
        fecha_hora = datetime.now()

        # Buscar idTipoBasura
        cursor.execute("SELECT idTipoBasura FROM tipobasura WHERE nombre=%s", (tipo,))
        res_tipo = cursor.fetchone()
        if res_tipo:
            idTipoBasura = res_tipo[0]
        else:
            cursor.execute("INSERT INTO tipobasura (nombre,puntos) VALUES (%s,%s)", (tipo, puntos))
            conexion.commit()
            idTipoBasura = cursor.lastrowid

        cursor.execute("""
            INSERT INTO deposito (idUser, idBasurero, idTipoBasura, fechaHora) 
            VALUES (%s, %s, %s, %s)
        """, (id_estudiante, 1, idTipoBasura, fecha_hora))
        conexion.commit()

        cursor.execute("SELECT puntajeTotal FROM puntaje WHERE idUser=%s", (id_estudiante,))
        res_puntaje = cursor.fetchone()
        if res_puntaje:
            nuevo_puntaje = res_puntaje[0] + puntos
            cursor.execute("UPDATE puntaje SET puntajeTotal=%s WHERE idUser=%s", (nuevo_puntaje, id_estudiante))
        else:
            cursor.execute("INSERT INTO puntaje (idUser, puntajeTotal) VALUES (%s,%s)", (id_estudiante, puntos))
        conexion.commit()

        # Mostrar en LCD y reproducir audio
        lcd_queue.put(f"{nombre_completo} +{puntos} pts")
        reproducir_audio(f"Gracias {nombre_completo} por reciclar {tipo}")

        return nombre_completo
    else:
        lcd_queue.put("QR no registrado")
        reproducir_audio("Codigo QR no registrado")
        return None


def guardar_copia_imagen(codigo_qr, tipo_basura, cursor, frame):
    cursor.execute("SELECT CONCAT(nombres,'_',primerApellido,'_',segundoApellido) FROM usuario WHERE qr_codigo=%s", (codigo_qr,))
    res = cursor.fetchone()
    if not res:
        print("[Imagen] No se encontro usuario para este QR")
        return
    
    if frame is None or frame.size == 0:
        print("[Imagen] No se pudo guardar: frame vacio")
        return None


    nombre_limpio = limpiar_nombre(res[0])
    fecha_dir = datetime.now().strftime("%Y-%m-%d")
    carpeta = f"capturas/{fecha_dir}"
    os.makedirs(carpeta, exist_ok=True)
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    nombre_archivo = f"{carpeta}/{nombre_limpio}_{tipo_basura}_{timestamp}.jpg"

    try:
        if cv2.imwrite(nombre_archivo, frame, [cv2.IMWRITE_JPEG_QUALITY, 90]):
            print(f"[Imagen] Guardada: {nombre_archivo}")
            return nombre_archivo
        else:
            print(f"[Error] No se pudo guardar la imagen: {nombre_archivo}")
            return None
    except Exception as e:
        print(f"[Excepcion] Error al guardar la imagen: {e}")
        return None
                                  
def leer_arduino():
    if arduino.in_waiting > 0:
        try:
            linea = arduino.readline()
            try:
                linea = linea.decode('utf-8').strip()
            except UnicodeDecodeError:
                return None
            if linea.startswith("{") and linea.endswith("}"):
                data = json.loads(linea)
                return data.get("tipo")
        except Exception as e:
            print("[ERROR SERIAL]", e)
    return None

def anunciar_concientizacion():
    while True:
        if not ultimo_estudiante["codigo_qr"]:
            hora = datetime.now().strftime("%H:%M")
            reproducir_audio(f"La hora actual es {hora}")
            sleep(2)
            mensaje = random.choice(MENSAJES_RECICLAJE)
            reproducir_audio(mensaje)
        sleep(60)

# ---------------- Senal handler ----------------
def signal_handler(sig, frame):
    print("\n[INFO] Cerrando sistema de manera segura...")
    try:
        cap.release()
        cv2.destroyAllWindows()
    except: pass
    try:
        lcd.clear()
        lcd.backlight_enabled = False
    except: pass
    try:
        arduino.close()
    except: pass
    try:
        cursor.close()
        conexion.close()
    except: pass
    audio_queue.put(None)
    sys.exit(0)

signal.signal(signal.SIGINT, signal_handler)

# ---------------- MAIN ----------------
def main():
    global ultimo_estudiante, cap, conexion, cursor

    print("Iniciando Sistema Inteligente de Reciclaje...")
    lcd.clear()
    lcd.cursor_pos = (0,0); lcd.write_string(centrar("Sistema Inteligente"))
    lcd.cursor_pos = (1,0); lcd.write_string(centrar("de"))
    lcd.cursor_pos = (2,0); lcd.write_string(centrar("Clasificacion"))
    lcd.cursor_pos = (3,0); lcd.write_string(centrar("de Residuos"))
    reproducir_audio("Sistema inteligente de reciclaje activado")
    sleep(3)
    lcd.clear()

    conexion = mysql.connector.connect(**DB_CONFIG)
    cursor = conexion.cursor()

    threading.Thread(target=actualizar_lcd, daemon=True).start()
    threading.Thread(target=anunciar_concientizacion, daemon=True).start()
    threading.Thread(target=reproducir_audio_thread, daemon=True).start()

    # Camara
    cap = cv2.VideoCapture("/dev/video0", cv2.CAP_V4L2)
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
    if not cap.isOpened():
        print("[ERROR] No se pudo abrir la camara USB")
        return

    ultimo_codigo = None
    tiempo_espera = 3
    hora_ultimo = 0

    while True:
        ret, frame = cap.read()
        if not ret:
            continue

        codigos = decode(frame)
        for code in codigos:
            codigo_qr = code.data.decode('utf-8')
            ahora = time.time()
            if codigo_qr != ultimo_codigo or ahora - hora_ultimo > tiempo_espera:
                cursor.execute("SELECT CONCAT(nombres,' ',primerApellido,' ',segundoApellido) FROM usuario WHERE qr_codigo=%s", (codigo_qr,))
                res = cursor.fetchone()
                if res:
                    nombre = res[0]
                    lcd_queue.put(f"Bienvenido {nombre}")
                    reproducir_audio(f"Hola {nombre}, puedes depositar tu basura")
                    ultimo_estudiante = {"nombre": nombre, "codigo_qr": codigo_qr}
                else:
                    lcd_queue.put("QR no registrado")
                    reproducir_audio("Cï¿½digo QR no registrado")
                    ultimo_estudiante = {"nombre": None, "codigo_qr": None}

                ultimo_codigo = codigo_qr
                hora_ultimo = ahora

            # Dibuo de QR
            pts = np.array([p for p in code.polygon], np.int32).reshape((-1,1,2))
            cv2.polylines(frame,[pts],True,(0,255,0),3)
            cv2.putText(frame, codigo_qr, (code.rect.left, code.rect.top-10),
            cv2.FONT_HERSHEY_SIMPLEX,0.8,(0,255,0),2)

        tipo_basura = leer_arduino()
        if tipo_basura and ultimo_estudiante["codigo_qr"]:
            registrar_puntos(ultimo_estudiante["codigo_qr"], tipo_basura, cursor, conexion)
            guardar_copia_imagen(ultimo_estudiante["codigo_qr"], tipo_basura, cursor, frame)
            ultimo_estudiante = {"nombre": None, "codigo_qr": None}

        cv2.imshow("Vista QR en tiempo real", frame)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            signal_handler(None, None)

        sleep(0.1)

if __name__ == "__main__":
    main()

