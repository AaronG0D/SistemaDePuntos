# --- Sistema Inteligente de Reciclaje con API Web ---
# Versión integrada con Laravel API (corregido: sin 'ultimo_estudiante')

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
import requests  # Para comunicacion con API
from RPLCD.i2c import CharLCD
from gtts import gTTS
import serial

# ============================================
# CONFIGURACION DE API WEB
# ============================================
API_CONFIG = {
    'base_url': 'http://192.168.100.4:8000',  # ? CAMBIA POR TU IP LOCAL
    'api_key': 'RaspberryPi2024_SecureKey_SistemaPuntos_ABC123XYZ789',  # ? CAMBIA POR TU API KEY
    'timeout': 10,
}

# Headers para todas las peticiones
API_HEADERS = {
    'Content-Type': 'application/json',
    'X-API-KEY': API_CONFIG['api_key'],
    'User-Agent': 'RaspberryPi-SistemaReciclaje/1.0'
}

# ============================================
# CONFIGURACION DEL SISTEMA
# ============================================
TIPOS_BASURA = ['papel', 'plastico', 'biodegradable', 'metal']
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
# Ajusta el puerto si tu sistema es Windows (ej: 'COM3') o diferente
try:
    arduino = serial.Serial('/dev/ttyACM0', 9600, timeout=1)
    time.sleep(2)
except Exception as e:
    print(f"[WARN] No se pudo abrir puerto serial: {e}")
    arduino = None

# ============================================
# ESTADO GLOBAL (sin 'ultimo_estudiante')
# ============================================
estudiante_actual_qr = None  # solo existe mientras dura la interacción

# ============================================
# FUNCIONES DE API WEB
# ============================================
def verificar_estudiante(codigo_qr):
    """
    Verifica si un estudiante existe por su codigo QR
    Realiza una llamada directa a la API cada vez
    """
    try:
        print(f"[API] Verificando estudiante: QR={codigo_qr}")
        response = requests.get(
            f"{API_CONFIG['base_url']}/api/raspberry/verificar/{codigo_qr}",
            headers=API_HEADERS,
            timeout=API_CONFIG['timeout']
        )
        print(f"[API] Status Code: {response.status_code}")
        resultado = {'existe': False}

        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                estudiante = data.get('estudiante', {})
                nombre_completo = f"{estudiante.get('nombre', '')} {estudiante.get('apellidos', '')}".strip()
                print(f"[API] ✓ Estudiante encontrado: {nombre_completo}")
                resultado = {
                    'existe': True,
                    'nombre': nombre_completo,
                    'puntos': estudiante.get('puntos_actuales', 0),
                    'periodo_activo': estudiante.get('periodo_activo'),
                    'curso': estudiante.get('curso_info', {})
                }
            else:
                print(f"[API] ✗ Estudiante no encontrado")
        else:
            print(f"[API] ✗ Error HTTP: {response.status_code}")

        return resultado

    except Exception as e:
        print(f"[ERROR] Error al verificar estudiante: {e}")
        return {'existe': False}

def registrar_deposito(codigo_qr, tipo_basura):
    """
    Registra un depósito mediante la API.
    Devuelve el nombre completo del estudiante si fue exitoso, sino None.
    """
    try:
        print(f"[API] Registrando deposito: QR={codigo_qr}, Tipo={tipo_basura}")
        response = requests.post(
            f"{API_CONFIG['base_url']}/api/raspberry/deposito",
            headers=API_HEADERS,
            json={
                'qr_codigo': codigo_qr,
                'tipo_basura': tipo_basura,
            },
            timeout=API_CONFIG['timeout']
        )
        print(f"[API] Status Code: {response.status_code}")

        if response.status_code == 201:
            data = response.json()
            if data.get('success'):
                estudiante = data.get('estudiante', {})
                deposito = data.get('deposito', {})
                puntaje = data.get('puntaje', {})

                # Extraer datos
                nombre_completo = f"{estudiante.get('nombre', '')} {estudiante.get('apellidos', '')}".strip()
                puntos_ganados = deposito.get('puntos_ganados', 0)
                puntos_totales = puntaje.get('puntos_totales_periodo', 0)
                tipo_registrado = deposito.get('tipo_basura', tipo_basura)

                print(f"[API] ✓ Éxito: {nombre_completo} +{puntos_ganados} pts (Total: {puntos_totales})")

                # Mostrar en LCD y reproducir audio
                lcd_queue.put(f"{nombre_completo} +{puntos_ganados} pts")
                reproducir_audio(
                    f"Gracias {nombre_completo} por reciclar {tipo_registrado}. "
                    f"Has ganado {puntos_ganados} puntos, tienes {puntos_totales} puntos totales"
                )
                return nombre_completo

        # Manejo de errores y mensajes de la API
        error_data = {}
        try:
            error_data = response.json()
        except Exception:
            pass
        mensaje_error = error_data.get('message', f'Error HTTP {response.status_code}')

        print(f"[API] ✗ Error: {mensaje_error}")

        # Mensajes amigables en LCD/audio
        if 'no encontrado' in mensaje_error.lower():
            lcd_queue.put("QR no registrado")
            reproducir_audio("Código QR no registrado")
        elif 'basura no valido' in mensaje_error.lower() or 'tipo' in mensaje_error.lower():
            lcd_queue.put("Tipo basura invalido")
            reproducir_audio("Tipo de basura no válido")
        else:
            lcd_queue.put("Error al registrar")
            reproducir_audio(f"Error: {mensaje_error}")

        return None

    except requests.exceptions.Timeout:
        print("[API ERROR] Timeout al registrar deposito")
        lcd_queue.put("Error: Timeout API")
        reproducir_audio("Error de conexión con el servidor")
        return None
    except requests.exceptions.ConnectionError:
        print("[API ERROR] No se pudo conectar al servidor")
        lcd_queue.put("Error: Sin conexión")
        reproducir_audio("Sin conexión al servidor")
        return None
    except Exception as e:
        print(f"[API ERROR] Error inesperado: {e}")
        lcd_queue.put("Error inesperado")
        reproducir_audio("Error inesperado del sistema")
        return None

# ============================================
# FUNCIONES ARDUINO
# ============================================
def activar_arduino():
    """
    Envía comando de activación al Arduino para habilitar detección de basura
    """
    try:
        if not arduino:
            print("[ARDUINO] Puerto no disponible, simulando activación")
            return True
        comando = "ACTIVAR\n"
        arduino.write(comando.encode('utf-8'))
        arduino.flush()
        print("[ARDUINO] Comando ACTIVAR enviado")
        time.sleep(0.5)
        return True
    except Exception as e:
        print(f"[ERROR ARDUINO] Error al activar: {e}")
        return False

def desactivar_arduino():
    """
    Envía comando de desactivación al Arduino para detener detección de basura
    """
    try:
        if not arduino:
            print("[ARDUINO] Puerto no disponible, simulando desactivación")
            return True
        comando = "DESACTIVAR\n"
        arduino.write(comando.encode('utf-8'))
        arduino.flush()
        print("[ARDUINO] Comando DESACTIVAR enviado")
        time.sleep(0.5)
        return True
    except Exception as e:
        print(f"[ERROR ARDUINO] Error al desactivar: {e}")
        return False

def leer_arduino():
    """Lee datos del Arduino (tipo de basura detectado y mensajes de estado)"""
    if not arduino:
        return None
    try:
        if arduino.in_waiting > 0:
            linea = arduino.readline()
            try:
                linea = linea.decode('utf-8').strip()
            except UnicodeDecodeError:
                return None
            if linea:
                print(f"[ARDUINO] {linea}")
            # Mensajes JSON
            if linea.startswith("{") and linea.endswith("}"):
                data = json.loads(linea)
                if "status" in data:
                    status = data.get("status")
                    if status == "activado":
                        print("[ARDUINO] Confirmación: Arduino activado correctamente")
                    return None
                if "tipo" in data:
                    return data.get("tipo")
    except Exception as e:
        print(f"[ERROR SERIAL] {e}")
    return None

def leer_tipo_basura_arduino():
    try:
        return leer_arduino()
    except Exception as e:
        print(f"[ERROR] Error al leer Arduino: {e}")
        return None

# ============================================
# FUNCIONES PARA QR Y SALUDO (cambios)
# ============================================
def verificar_y_saludar_estudiante(codigo_qr):
    """
    Verifica el estudiante usando la API y lo saluda personalmente.
    Si es válido, guarda el QR en 'estudiante_actual_qr' y devuelve True.
    Si no es válido o hay error, devuelve False o None (en caso de error conexión).
    """
    global estudiante_actual_qr
    try:
        print(f"[QR] Verificando y saludando: {codigo_qr}")
        resultado = verificar_estudiante(codigo_qr)

        if resultado is None:
            # error de conexión interna en verificar_estudiante -> tratar como fallo
            return None

        if resultado.get('existe'):
            nombre = resultado['nombre']
            puntos = resultado['puntos']
            periodo = resultado.get('periodo_activo', 'N/A')

            estudiante_actual_qr = codigo_qr  # establecer QR activo

            lcd_queue.put(f"Bienvenido {nombre}")
            reproducir_audio(
                f"Hola {nombre}, puedes depositar tu basura. "
                f"Tienes {puntos} puntos en periodo {periodo}"
            )

            # activar Arduino para detectar tipo de basura
            if activar_arduino():
                print(f"[SISTEMA] Arduino activado para {nombre}")
            else:
                print(f"[ERROR] No se pudo activar Arduino para {nombre}")

            return True
        else:
            lcd_queue.put("QR no registrado")
            reproducir_audio("Código QR no registrado")
            return False

    except Exception as e:
        print(f"[ERROR] Error al verificar estudiante: {e}")
        lcd_queue.put("Error al verificar")
        reproducir_audio("Error al verificar código QR")
        return False

# ============================================
# FUNCIONES AUXILIARES
# ============================================
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
            # fallback
            try:
                os.system(f'espeak -v es "{texto}"')
            except Exception as e2:
                print("[ERROR AUDIO fallback]:", e2)
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
        try:
            lcd.cursor_pos = (0,0)
            lcd.write_string(chr(0) + " " + centrar(f"Hora: {hora}")[2:])
            lcd.cursor_pos = (1,0)
            lcd.write_string(chr(1) + " " + centrar(f"Fecha: {fecha}")[1:])
        except Exception:
            pass

        # Mostrar mensajes de QR/Arduino (fila 2)
        try:
            mensaje = lcd_queue.get_nowait()
            for _ in range(4):  # Parpadeo
                try:
                    lcd.cursor_pos = (2,0)
                    lcd.write_string(chr(2) + " " + centrar(mensaje)[2:-2] + " " + chr(3))
                    sleep(0.3)
                    lcd.cursor_pos = (2,0)
                    lcd.write_string(" " * 20)
                    sleep(0.3)
                except Exception:
                    pass
            try:
                lcd.cursor_pos = (2,0)
                lcd.write_string(centrar(mensaje))
                sleep(2)
            except Exception:
                pass
        except queue.Empty:
            try:
                lcd.cursor_pos = (2,0)
                lcd.write_string(centrar("Esperando QR..."))
            except Exception:
                pass

        # Scroll fila 3
        if not pausar_scroll.is_set():
            scroll_text = "     " + fila3_msg + " " * 20
            for i in range(len(scroll_text) - 19):
                try:
                    lcd.cursor_pos = (3,0)
                    lcd.write_string(scroll_text[i:i+20])
                except Exception:
                    pass
                sleep(0.2)
                if pausar_scroll.is_set():
                    break
        sleep(0.5)

# ============================================
# LECTURA / MANEJO DEL ARDUINO EN LOOP
# ============================================
def anunciar_concientizacion():
    """Thread que anuncia mensajes de concientización periódicamente"""
    while True:
        # Solo anuncia si no hay usuario interactuando
        if not estudiante_actual_qr:
            hora = datetime.now().strftime("%H:%M")
            reproducir_audio(f"La hora actual es {hora}")
            sleep(2)
            mensaje = random.choice(MENSAJES_RECICLAJE)
            reproducir_audio(mensaje)
        sleep(60)

# ============================================
# SIGNAL HANDLER
# ============================================
def signal_handler(sig, frame):
    print("\n[INFO] Cerrando sistema de manera segura...")
    try:
        cap.release()
        cv2.destroyAllWindows()
    except Exception:
        pass
    try:
        lcd.clear()
        lcd.backlight_enabled = False
    except Exception:
        pass
    try:
        if arduino:
            arduino.close()
    except Exception:
        pass
    audio_queue.put(None)
    sys.exit(0)

signal.signal(signal.SIGINT, signal_handler)

# ============================================
# MAIN - LOOP PRINCIPAL
# ============================================
def main():
    global estudiante_actual_qr, cap

    print("=" * 50)
    print("Sistema Inteligente de Reciclaje - Versión API Web (corregido)")
    print("=" * 50)
    print(f"API URL: {API_CONFIG['base_url']}")
    print(f"API Key: {API_CONFIG['api_key'][:20]}...")
    print("=" * 50)

    lcd.clear()
    try:
        lcd.cursor_pos = (0,0); lcd.write_string(centrar("Sistema Inteligente"))
        lcd.cursor_pos = (1,0); lcd.write_string(centrar("de"))
        lcd.cursor_pos = (2,0); lcd.write_string(centrar("Clasificacion"))
        lcd.cursor_pos = (3,0); lcd.write_string(centrar("de Residuos"))
    except Exception:
        pass

    reproducir_audio("Sistema inteligente de reciclaje activado")
    sleep(3)
    lcd.clear()

    # Iniciar threads
    threading.Thread(target=actualizar_lcd, daemon=True).start()
    threading.Thread(target=anunciar_concientizacion, daemon=True).start()
    threading.Thread(target=reproducir_audio_thread, daemon=True).start()

    # Configurar camara
    cap = cv2.VideoCapture("/dev/video0", cv2.CAP_V4L2)
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
    if not cap.isOpened():
        print("[ERROR] No se pudo abrir la camara USB")
        return

    ultimo_codigo = None
    tiempo_espera = 5  # tiempo para evitar lecturas duplicadas
    hora_ultimo = 0
    contador_frames = 0
    procesar_cada_n_frames = 5  # procesar solo cada N frames para reducir carga

    print("[INFO] Sistema listo. Esperando codigos QR...")

    while True:
        ret, frame = cap.read()
        if not ret:
            continue

        contador_frames += 1

        # Procesar solo cada N frames
        if contador_frames % procesar_cada_n_frames != 0:
            cv2.imshow("Vista QR en tiempo real", frame)
            if cv2.waitKey(1) & 0xFF == ord('q'):
                signal_handler(None, None)
            continue

        # Detectar codigos QR
        codigos = decode(frame)
        for code in codigos:
            try:
                codigo_qr = code.data.decode('utf-8')
            except Exception:
                continue
            ahora = time.time()

            # Evitar lecturas duplicadas en corto tiempo
            if codigo_qr != ultimo_codigo or (ahora - hora_ultimo) > tiempo_espera:
                print(f"\n[QR] Codigo detectado: {codigo_qr}")
                ultimo_codigo = codigo_qr
                hora_ultimo = ahora

                # Verificar y saludar al estudiante. Si es válido, estudiante_actual_qr se setea.
                verificar_y_saludar_estudiante(codigo_qr)

        # Verificar si hay tipo de basura detectado por Arduino
        tipo_basura = leer_tipo_basura_arduino()

        # Si hay tipo de basura y hay un estudiante activo, registrar depósito
        if tipo_basura and estudiante_actual_qr:
            print(f"\n[ARDUINO] Tipo detectado: {tipo_basura}")
            print(f"[DEPOSITO] Registrando para QR: {estudiante_actual_qr}")

            nombre_completo = registrar_deposito(estudiante_actual_qr, tipo_basura)

            if nombre_completo:
                # Desactivar Arduino después del depósito exitoso
                if desactivar_arduino():
                    print(f"[SISTEMA] Arduino desactivado después del depósito de {nombre_completo}")
                else:
                    print(f"[ERROR] No se pudo desactivar Arduino")

                # Limpiar estado del estudiante activo para permitir próximo usuario
                estudiante_actual_qr = None

                # Mostrar confirmación final
                lcd_queue.put("Deposito exitoso!")
                reproducir_audio("Depósito registrado exitosamente. Gracias por reciclar.")
                print("[SISTEMA] Esperando próximo estudiante...")
                time.sleep(3)  # Pausa antes de aceptar nuevo QR

        # Mostrar frame
        cv2.imshow("Vista QR en tiempo real", frame)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            signal_handler(None, None)

        sleep(0.1)

if __name__ == "__main__":
    main()
