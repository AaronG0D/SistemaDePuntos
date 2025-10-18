# --- Sistema Inteligente de Reciclaje con API Web ---
# Version integrada con Laravel API

# --- Importaciones estandar ---
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

# Cache para evitar consultas repetidas
estudiantes_cache = {}
cache_timeout = 300  # 5 minutos

# Control de frecuencia de verificaciones
ultima_verificacion = {}
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

ultimo_estudiante = {"nombre": None, "codigo_qr": None, "puntos": 0}
# ============================================
# FUNCIONES DE CACHE Y OPTIMIZACION
# ============================================

def debe_verificar_estudiante(qr_codigo):
    """Verifica si debemos hacer una nueva consulta API para este QR"""
    ahora = time.time()
    
    # Si nunca se ha verificado, verificar
    if qr_codigo not in ultima_verificacion:
        return True
    
    # Si ha pasado el intervalo mínimo, verificar
    if ahora - ultima_verificacion[qr_codigo] >= intervalo_verificacion:
        return True
    
    return False

def obtener_estudiante_cache(qr_codigo):
    """Obtiene estudiante del cache si está disponible y válido"""
    if qr_codigo not in estudiantes_cache:
        return None
    
    cache_entry = estudiantes_cache[qr_codigo]
    ahora = time.time()
    
    # Verificar si el cache ha expirado
    if ahora - cache_entry['timestamp'] > cache_timeout:
        del estudiantes_cache[qr_codigo]
        return None
    
    return cache_entry['data']

def guardar_estudiante_cache(qr_codigo, data):
    """Guarda estudiante en el cache"""
    estudiantes_cache[qr_codigo] = {
        'data': data,
        'timestamp': time.time()
    }
    
    # Limpiar cache si tiene demasiadas entradas
    if len(estudiantes_cache) > 50:
        limpiar_cache_expirado()

def limpiar_cache_expirado():
    """Limpia entradas expiradas del cache"""
    ahora = time.time()
    keys_to_remove = []
    
    for qr_codigo, cache_entry in estudiantes_cache.items():
        if ahora - cache_entry['timestamp'] > cache_timeout:
            keys_to_remove.append(qr_codigo)
    
    for key in keys_to_remove:
        del estudiantes_cache[key]
    
    if keys_to_remove:
        print(f"[CACHE] Limpiadas {len(keys_to_remove)} entradas expiradas")

# FUNCIONES DE API WEB
# ============================================

def verificar_estudiante(codigo_qr):
    """
    Verifica si un estudiante existe por su codigo QR usando cache y optimizaciones
    """
    try:
        # Verificar si debemos hacer la consulta
        if not debe_verificar_estudiante(codigo_qr):
            print(f"[CACHE] Evitando consulta repetida para QR: {codigo_qr}")
            return obtener_estudiante_cache(codigo_qr) or {'existe': False}
        
        # Verificar cache primero
        cache_data = obtener_estudiante_cache(codigo_qr)
        if cache_data:
            print(f"[CACHE] Usando datos cacheados para QR: {codigo_qr}")
            return cache_data
        
        print(f"[API] Verificando estudiante: QR={codigo_qr}")
        
        # Actualizar timestamp de última verificación
        ultima_verificacion[codigo_qr] = time.time()
        
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
                print(f"[API] ? Estudiante encontrado: {nombre_completo}")
                resultado = {
                    'existe': True,
                    'nombre': nombre_completo,
                    'puntos': estudiante.get('puntos_actuales', 0),
                    'curso': estudiante.get('curso_info', {})
                }
            else:
                print(f"[API] ? Estudiante no encontrado")
        else:
            print(f"[API] ? Error HTTP: {response.status_code}")
        
        # Guardar en cache (tanto éxito como fallo para evitar consultas repetidas)
        guardar_estudiante_cache(codigo_qr, resultado)
        return resultado
            
    except Exception as e:
        print(f"[ERROR] Error al verificar estudiante: {e}")
        # Guardar fallo en cache para evitar reintentos inmediatos
        resultado = {'existe': False}
        guardar_estudiante_cache(codigo_qr, resultado)
        return resultado

def registrar_deposito(codigo_qr, tipo_basura):
    """
    Funcion equivalente a registrar_puntos() pero usando API
    Mantiene la misma logica de mensajes LCD y audio
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
                
                # Extraer datos como en el codigo original
                nombre_completo = f"{estudiante.get('nombre', '')} {estudiante.get('apellidos', '')}".strip()
                puntos_ganados = deposito.get('puntos_ganados', 0)
                puntos_totales = estudiante.get('puntos_actuales', 0)
                tipo_registrado = deposito.get('tipo_basura', tipo_basura)
                
                print(f"[API] ? Exito: {nombre_completo} +{puntos_ganados} pts (Total: {puntos_totales})")
                
                # Mostrar en LCD y reproducir audio
                lcd_queue.put(f"{nombre_completo} +{puntos_ganados} pts")
                reproducir_audio(f"Gracias {nombre_completo} por reciclar {tipo_registrado}. Has ganado {puntos_ganados} puntos")
                
                return nombre_completo
        
        # Manejar errores (como en codigo original)
        error_data = response.json() if response.headers.get('content-type', '').startswith('application/json') else {}
        mensaje_error = error_data.get('message', 'Error desconocido')
        
        print(f"[API] ? Error: {mensaje_error}")
        
        # Mostrar error en LCD y audio (como en codigo original)
        if 'no encontrado' in mensaje_error.lower():
            lcd_queue.put("QR no registrado")
            reproducir_audio("Codigo QR no registrado")
        elif 'basura no valido' in mensaje_error.lower():
            lcd_queue.put("Tipo basura invalido")
            reproducir_audio("Tipo de basura no valido")
        else:
            lcd_queue.put("Error al registrar")
            reproducir_audio(f"Error: {mensaje_error}")
        
        return None
        
    except requests.exceptions.Timeout:
        print("[API ERROR] Timeout al registrar deposito")
        lcd_queue.put("Error: Timeout API")
        reproducir_audio("Error de conexion con el servidor")
        return None
    except requests.exceptions.ConnectionError:
        print("[API ERROR] No se pudo conectar al servidor")
        lcd_queue.put("Error: Sin conexion")
        reproducir_audio("Sin conexion al servidor")
        return None
    except Exception as e:
        print(f"[API ERROR] Error inesperado: {e}")
        lcd_queue.put("Error inesperado")
        reproducir_audio("Error inesperado del sistema")
        return None
def activar_arduino():
    """
    Envía comando de activación al Arduino para habilitar detección de basura
    """
    try:
        comando = "ACTIVAR\n"
        arduino.write(comando.encode('utf-8'))
        arduino.flush()
        print("[ARDUINO] Comando ACTIVAR enviado")
        
        # Esperar confirmación del Arduino (opcional)
        time.sleep(0.5)  # Dar tiempo al Arduino para responder
        return True
        
    except Exception as e:
        print(f"[ERROR ARDUINO] Error al activar: {e}")
        return False

def desactivar_arduino():
    """
    Envía comando de desactivación al Arduino para detener detección de basura
    """
    try:
        comando = "DESACTIVAR\n"
        arduino.write(comando.encode('utf-8'))
        arduino.flush()
        print("[ARDUINO] Comando DESACTIVAR enviado")
        
        # Esperar confirmación del Arduino (opcional)
        time.sleep(0.5)  # Dar tiempo al Arduino para responder
        return True
        
    except Exception as e:
        print(f"[ERROR ARDUINO] Error al desactivar: {e}")
        return False

def verificar_estado_arduino():
    """
    Verifica el estado actual del Arduino
    """
    try:
        comando = "STATUS\n"
        arduino.write(comando.encode('utf-8'))
        arduino.flush()
        print("[ARDUINO] Consultando estado...")
        return True
    except Exception as e:
        print(f"[ERROR] Error al consultar estado Arduino: {e}")
        return False

def verificar_y_saludar_estudiante(codigo_qr):
    """
    Verifica el estudiante usando la API y lo saluda personalmente
    """
    try:
        print(f"[QR] Verificando y saludando: {codigo_qr}")
        
        # Verificar estudiante usando el endpoint especifico
        resultado = verificar_estudiante(codigo_qr)
        
        if resultado is None:
            # Error de conexion
            return False
        elif resultado.get('existe'):
            # Estudiante encontrado - saludo personalizado
            nombre = resultado['nombre']
            puntos = resultado['puntos']
            
            lcd_queue.put(f"Bienvenido {nombre}")
            reproducir_audio(f"Hola {nombre}, puedes depositar tu basura. Tienes {puntos} puntos")
            
            # ✅ ACTIVAR ARDUINO para que pueda detectar basura
            if activar_arduino():
                print(f"[SISTEMA] Arduino activado para {nombre}")
            else:
                print(f"[ERROR] No se pudo activar Arduino para {nombre}")
            
            return True
        else:
            # Estudiante no encontrado
            lcd_queue.put("QR no registrado")
            reproducir_audio("Codigo QR no registrado")
            return False
        
    except Exception as e:
        print(f"[ERROR] Error al verificar estudiante: {e}")
        lcd_queue.put("Error al verificar")
        reproducir_audio("Error al verificar codigo QR")
        return False
# ============================================
# FUNCIONES AUXILIARES (Sin cambios)
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
        
def guardar_copia_imagen(codigo_qr, tipo_basura, nombre_estudiante, frame):
    """
    Guarda una copia de la imagen del deposito en la carpeta de capturas
    """
    if frame is None or frame.size == 0:
        print("[Imagen] No se pudo guardar: frame vacio")
        return None

    nombre_limpio = limpiar_nombre(nombre_estudiante)
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
    """Lee datos del Arduino (tipo de basura detectado y mensajes de estado)"""
    if arduino.in_waiting > 0:
        try:
            linea = arduino.readline()
            try:
                linea = linea.decode('utf-8').strip()
            except UnicodeDecodeError:
                return None
            
            # Mostrar todos los mensajes del Arduino para debug
            if linea:
                print(f"[ARDUINO] {linea}")
            
            # Procesar mensajes JSON
            if linea.startswith("{") and linea.endswith("}"):
                data = json.loads(linea)
                
                # Si es un mensaje de estado, procesarlo
                if "status" in data:
                    status = data.get("status")
                    if status == "activado":
                        print("[ARDUINO] ✅ Confirmación: Arduino activado correctamente")
                    return None  # No es tipo de basura
                
                # Si es tipo de basura, devolverlo
                if "tipo" in data:
                    return data.get("tipo")
                    
        except Exception as e:
            print(f"[ERROR SERIAL] {e}")
    return None

def leer_tipo_basura_arduino():
    """Función específica para leer tipo de basura del Arduino"""
    try:
        return leer_arduino()
    except Exception as e:
        print(f"[ERROR] Error al leer Arduino: {e}")
        return None

def anunciar_concientizacion():
    """Thread que anuncia mensajes de concientizacion periodicamente"""
    while True:
        if not ultimo_estudiante["codigo_qr"]:
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

    print("=" * 50)
    print("Sistema Inteligente de Reciclaje - Version API Web")
    print("=" * 50)
    print(f"API URL: {API_CONFIG['base_url']}")
    print(f"API Key: {API_CONFIG['api_key'][:20]}...")
    print("=" * 50)
    
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
    tiempo_espera = 5  # Aumentado de 3 a 5 segundos
    hora_ultimo = 0
    contador_frames = 0
    procesar_cada_n_frames = 5  # Procesar solo cada 5 frames para reducir carga

    print("[INFO] Sistema listo. Esperando codigos QR...")
    print(f"[INFO] Cache configurado: {cache_timeout}s, Intervalo verificación: {intervalo_verificacion}s")
    
    while True:
        ret, frame = cap.read()
        if not ret:
            continue

        contador_frames += 1
        
        # Procesar solo cada N frames para reducir carga de CPU
        if contador_frames % procesar_cada_n_frames != 0:
            cv2.imshow("Vista QR en tiempo real", frame)
            if cv2.waitKey(1) & 0xFF == ord('q'):
                signal_handler(None, None)
            continue

        # Detectar codigos QR solo en frames seleccionados
        codigos = decode(frame)
        for code in codigos:
            codigo_qr = code.data.decode('utf-8')
            ahora = time.time()
            
            # Evitar lecturas duplicadas con tiempo de espera más largo
            if codigo_qr != ultimo_codigo or ahora - hora_ultimo > tiempo_espera:
                print(f"\n[QR] Codigo detectado: {codigo_qr}")
                
                # Verificar y saludar estudiante (ahora con cache)
                if verificar_y_saludar_estudiante(codigo_qr):
                    ultimo_estudiante = {"nombre": None, "codigo_qr": codigo_qr}
                else:
                    ultimo_estudiante = {"nombre": None, "codigo_qr": None}
                
                ultimo_codigo = codigo_qr
                hora_ultimo = ahora

        # Verificar si hay tipo de basura detectado por Arduino
        tipo_basura = leer_tipo_basura_arduino()
        
        if tipo_basura and ultimo_estudiante["codigo_qr"]:
            print(f"\n[ARDUINO] Tipo detectado: {tipo_basura}")
            print(f"[DEPOSITO] Registrando para QR: {ultimo_estudiante['codigo_qr']}")
            
            # Registrar deposito usando API
            nombre_completo = registrar_deposito(ultimo_estudiante["codigo_qr"], tipo_basura)
            
            if nombre_completo:
                # Guardar imagen
                guardar_copia_imagen(ultimo_estudiante["codigo_qr"], tipo_basura, nombre_completo, frame)
                
                # ✅ DESACTIVAR ARDUINO después del depósito exitoso
                if desactivar_arduino():
                    print(f"[SISTEMA] Arduino desactivado después del depósito de {nombre_completo}")
                else:
                    print(f"[ERROR] No se pudo desactivar Arduino")
            
            # Resetear estudiante
            ultimo_estudiante = {"nombre": None, "codigo_qr": None}
            
            # Mostrar mensaje de éxito y esperar antes del próximo QR
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

