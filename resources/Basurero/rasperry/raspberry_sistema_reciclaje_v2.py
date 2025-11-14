# --- Sistema Inteligente de Reciclaje con API Web (SIN CACHE) ---
# Version integrada con Laravel API - Simplificada

# --- Importaciones estandar ---
import os
import re
import sys
import time
import cv2
import queue
import serial
import signal
import threading
import requests
from datetime import datetime
from time import sleep

# --- Importaciones de terceros ---
import numpy as np
from pyzbar.pyzbar import decode
from RPLCD.i2c import CharLCD
from gtts import gTTS

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
intervalo_verificacion = 3  # segundos entre verificaciones del mismo QR
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
arduino = serial.Serial('/dev/ttyACM0', 9600, timeout=1)
time.sleep(2) 

ultimo_estudiante = {"nombre": None, "codigo_qr": None, "puntos": 0, "periodo": None}

# ============================================
# FUNCIONES DE API WEB (SIN CACHE)
# ============================================

def verificar_estudiante(codigo_qr):
    """
    Verifica si un estudiante existe por su codigo QR usando la API (SIN CACHE)
    Devuelve el periodo activo y los puntos actuales
    """
    try:
        print(f"[API] Verificando estudiante: QR={codigo_qr}")
        
        response = requests.get(
            f"{API_CONFIG['base_url']}/api/raspberry/verificar/{codigo_qr}",
            headers=API_HEADERS,
            timeout=API_CONFIG['timeout']
        )
        
        print(f"[API] Status Code: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                estudiante = data.get('estudiante', {})
                nombre_completo = f"{estudiante.get('nombre', '')} {estudiante.get('apellidos', '')}".strip()
                periodo_activo = estudiante.get('periodo_activo')
                puntos_actuales = estudiante.get('puntos_actuales', 0)
                
                print(f"[API] ✓ {nombre_completo} | Periodo: {periodo_activo} | Puntos: {puntos_actuales}")
                
                return {
                    'existe': True,
                    'nombre': nombre_completo,
                    'puntos_actuales': puntos_actuales,
                    'periodo_activo': periodo_activo,
                    'curso_info': estudiante.get('curso_info'),
                    'event_id': data.get('event_id')
                }
        
        print(f"[API] ✗ Error: {data.get('message', 'Desconocido')}")
        return {'existe': False}
            
    except Exception as e:
        print(f"[ERROR] Error al verificar: {e}")
        return {'existe': False}

def registrar_deposito(codigo_qr, tipo_basura):
    """
    Registra un deposito y devuelve el puntaje actualizado con el periodo
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
                
                nombre = f"{estudiante.get('nombre', '')} {estudiante.get('apellidos', '')}".strip()
                puntos_ganados = deposito.get('puntos_ganados', 0)
                puntos_totales = puntaje.get('puntos_totales_periodo', 0)
                periodo = puntaje.get('periodo_activo')
                tipo = deposito.get('tipo_basura', tipo_basura)
                
                print(f"[API] ✓ {nombre} | +{puntos_ganados} pts | Total ({periodo}): {puntos_totales} pts")
                
                # Mostrar en LCD
                lcd_queue.put(f"{nombre}")
                lcd_queue.put(f"+{puntos_ganados} pts | {periodo}: {puntos_totales}")
                
                # Audio
                reproducir_audio(f"Gracias {nombre}. Has ganado {puntos_ganados} puntos. Total en {periodo}: {puntos_totales} puntos")
                
                return {
                    'exito': True,
                    'nombre': nombre,
                    'puntos_ganados': puntos_ganados,
                    'puntos_totales': puntos_totales,
                    'periodo': periodo,
                    'tipo': tipo
                }
        
        # Error
        error_data = response.json() if response.headers.get('content-type', '').startswith('application/json') else {}
        mensaje = error_data.get('message', 'Error desconocido')
        print(f"[API] Error: {mensaje}")
        
        lcd_queue.put("Error: " + mensaje[:15])
        reproducir_audio(f"Error: {mensaje}")
        
        return {'exito': False, 'error': mensaje}
        
    except requests.exceptions.Timeout:
        print("[ERROR] Timeout")
        lcd_queue.put("Error: Timeout")
        reproducir_audio("Timeout de conexion")
        return {'exito': False, 'error': 'Timeout'}
    except requests.exceptions.ConnectionError:
        print("[ERROR] Sin conexion")
        lcd_queue.put("Error: Sin conexion")
        reproducir_audio("Sin conexion al servidor")
        return {'exito': False, 'error': 'Sin conexion'}
    except Exception as e:
        print(f"[ERROR] {e}")
        lcd_queue.put("Error del sistema")
        reproducir_audio("Error del sistema")
        return {'exito': False, 'error': str(e)}

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
            tts = gTTS(texto, lang='es', slow=False)
            tts.save("temp_audio.mp3")
            os.system("mpg123 temp_audio.mp3 2>/dev/null")
        except Exception as e:
            print(f"[AUDIO ERROR] {e}")
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
            msg = lcd_queue.get(timeout=1)
            lcd.cursor_pos = (2,0)
            lcd.write_string(centrar(msg))
        except queue.Empty:
            lcd.cursor_pos = (2,0)
            lcd.write_string(centrar("Escaneando QR..."))

        # Scroll fila 3
        if not pausar_scroll.is_set():
            for i in range(len(fila3_msg)):
                lcd.cursor_pos = (3,0)
                lcd.write_string((fila3_msg[i:] + " " + fila3_msg)[:20])
                sleep(0.1)
        sleep(0.5)

def activar_arduino():
    """Envia comando ACTIVAR al Arduino"""
    try:
        arduino.write("ACTIVAR\n".encode('utf-8'))
        arduino.flush()
        print("[ARDUINO] ACTIVAR enviado")
        return True
    except Exception as e:
        print(f"[ARDUINO ERROR] {e}")
        return False

def desactivar_arduino():
    """Envia comando DESACTIVAR al Arduino"""
    try:
        arduino.write("DESACTIVAR\n".encode('utf-8'))
        arduino.flush()
        print("[ARDUINO] DESACTIVAR enviado")
        return True
    except Exception as e:
        print(f"[ARDUINO ERROR] {e}")
        return False

def leer_arduino():
    """Lee datos del Arduino"""
    if arduino.in_waiting > 0:
        try:
            datos = arduino.readline().decode('utf-8').strip()
            if datos:
                print(f"[ARDUINO] {datos}")
                return datos
        except Exception as e:
            print(f"[ARDUINO ERROR] {e}")
    return None

# ============================================
# SIGNAL HANDLER
# ============================================
def signal_handler(sig, frame):
    print("\n[INFO] Cerrando sistema...")
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
    audio_queue.put(None)
    sys.exit(0)

signal.signal(signal.SIGINT, signal_handler)

# ============================================
# MAIN - LOOP PRINCIPAL
# ============================================
def main():
    global ultimo_estudiante, cap

    print("=" * 60)
    print("SISTEMA INTELIGENTE DE RECICLAJE - VERSION API (SIN CACHE)")
    print("=" * 60)
    print(f"API URL: {API_CONFIG['base_url']}")
    print("=" * 60)
    
    lcd.clear()
    lcd.cursor_pos = (0,0); lcd.write_string(centrar("Sistema Inteligente"))
    lcd.cursor_pos = (1,0); lcd.write_string(centrar("de"))
    lcd.cursor_pos = (2,0); lcd.write_string(centrar("Clasificacion"))
    lcd.cursor_pos = (3,0); lcd.write_string(centrar("de Residuos"))
    reproducir_audio("Sistema inteligente de reciclaje activado")
    sleep(3)
    lcd.clear()

    # Iniciar threads
    threading.Thread(target=actualizar_lcd, daemon=True).start()
    threading.Thread(target=reproducir_audio_thread, daemon=True).start()

    # Configurar camara
    cap = cv2.VideoCapture("/dev/video0", cv2.CAP_V4L2)
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
    
    if not cap.isOpened():
        print("[ERROR] No se pudo abrir la camara USB")
        return

    print("[INFO] Sistema listo. Esperando codigos QR...")
    
    ultimo_codigo = None
    tiempo_espera = 5
    hora_ultimo = 0
    contador_frames = 0
    procesar_cada_n_frames = 5

    while True:
        ret, frame = cap.read()
        if not ret:
            print("[ERROR] Error al leer frame")
            continue

        contador_frames += 1
        
        # Procesar solo cada N frames
        if contador_frames % procesar_cada_n_frames != 0:
            continue

        # Detectar codigos QR
        codigos = decode(frame)
        for code in codigos:
            codigo_qr = code.data.decode('utf-8').strip()
            
            # Evitar duplicados rapidos
            ahora = time.time()
            if codigo_qr == ultimo_codigo and (ahora - hora_ultimo) < tiempo_espera:
                continue
            
            ultimo_codigo = codigo_qr
            hora_ultimo = ahora
            
            print(f"\n[QR] Detectado: {codigo_qr}")
            lcd_queue.put("Verificando...")
            
            # Verificar estudiante
            resultado = verificar_estudiante(codigo_qr)
            
            if resultado.get('existe'):
                nombre = resultado.get('nombre')
                puntos = resultado.get('puntos_actuales')
                periodo = resultado.get('periodo_activo')
                
                print(f"[QR] ✓ {nombre} | {periodo} | {puntos} pts")
                lcd_queue.put(f"{nombre[:16]}")
                reproducir_audio(f"Estudiante {nombre}. Periodo: {periodo}. Puntos actuales: {puntos}")
                
                # Guardar info
                ultimo_estudiante = {
                    "nombre": nombre,
                    "codigo_qr": codigo_qr,
                    "puntos": puntos,
                    "periodo": periodo
                }
                
                # Esperar deteccion de basura del Arduino
                activar_arduino()
                print("[ARDUINO] Esperando deteccion de basura...")
                
                tipo_detectado = None
                tiempo_espera_tipo = time.time() + 10  # 10 segundos
                
                while time.time() < tiempo_espera_tipo:
                    tipo = leer_arduino()
                    if tipo and any(basura in tipo.lower() for basura in TIPOS_BASURA):
                        tipo_detectado = tipo.lower()
                        break
                    sleep(0.5)
                
                desactivar_arduino()
                
                if tipo_detectado:
                    print(f"[ARDUINO] Basura detectada: {tipo_detectado}")
                    lcd_queue.put(f"Registrando {tipo_detectado}...")
                    
                    # Registrar deposito
                    deposito_resultado = registrar_deposito(codigo_qr, tipo_detectado)
                    
                    if deposito_resultado.get('exito'):
                        # Guardar imagen
                        guardar_copia_imagen(codigo_qr, tipo_detectado, nombre, frame)
                        
                        print(f"[DEPOSITO] ✓ {nombre} | +{deposito_resultado.get('puntos_ganados')} pts")
                        print(f"[PERIODO] {deposito_resultado.get('periodo')}: {deposito_resultado.get('puntos_totales')} pts")
                        
                        sleep(3)
                    else:
                        print(f"[DEPOSITO] ✗ Error: {deposito_resultado.get('error')}")
                else:
                    print("[ARDUINO] Timeout - No se detecto basura")
                    lcd_queue.put("Timeout - Sin basura")
                    reproducir_audio("Tiempo agotado sin detectar basura")
                
            else:
                print(f"[QR] ✗ Estudiante no encontrado")
                lcd_queue.put("QR no registrado")
                reproducir_audio("Codigo QR no registrado en el sistema")
            
            sleep(2)

if __name__ == "__main__":
    import random
    main()
