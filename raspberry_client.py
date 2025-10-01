#!/usr/bin/env python3
"""
Cliente Python para Raspberry Pi - Sistema de Puntos
Envía datos de depósitos de basura al backend Laravel

Instalación de dependencias:
pip install requests

Uso:
python raspberry_client.py
"""

import requests
import time
import json
from datetime import datetime

# Configuración
API_URL = "http://tu-dominio.com/api/raspberry/deposito"  # Cambia por tu URL real
API_KEY = "tu_api_key_super_secreta_aqui"  # Debe coincidir con RASPBERRY_API_KEY en .env

# Headers para todas las requests
HEADERS = {
    "Content-Type": "application/json",
    "X-API-KEY": API_KEY,
    "User-Agent": "RaspberryPi-Client/1.0"
}

def enviar_deposito(qr_codigo: str, tipo_basura: str, peso: float = None):
    """
    Envía un depósito al backend Laravel
    
    Args:
        qr_codigo (str): Código QR del estudiante
        tipo_basura (str): Tipo de basura (ej: "plastico", "papel", "vidrio")
        peso (float, optional): Peso del depósito en kg
    
    Returns:
        tuple: (success: bool, response_data: dict)
    """
    payload = {
        "qr_codigo": qr_codigo,
        "tipo_basura": tipo_basura,
    }
    
    if peso is not None:
        payload["peso"] = peso
    
    try:
        print(f"[{datetime.now()}] Enviando depósito...")
        print(f"  QR: {qr_codigo}")
        print(f"  Tipo: {tipo_basura}")
        if peso:
            print(f"  Peso: {peso}kg")
        
        response = requests.post(API_URL, json=payload, headers=HEADERS, timeout=10)
        
        print(f"  Status Code: {response.status_code}")
        
        if response.status_code == 201:
            data = response.json()
            print("  ✅ ÉXITO!")
            
            if data.get('success'):
                estudiante = data.get('estudiante', {})
                deposito = data.get('deposito', {})
                
                print(f"  Estudiante: {estudiante.get('nombre')} {estudiante.get('apellidos')}")
                if estudiante.get('curso_info'):
                    curso_info = estudiante['curso_info']
                    print(f"  Curso: {curso_info.get('curso')} - {curso_info.get('paralelo')}")
                print(f"  Puntos actuales: {estudiante.get('puntos_actuales')}")
                print(f"  Puntos ganados: {deposito.get('puntos_ganados')}")
                
            return True, data
            
        else:
            error_data = response.json() if response.headers.get('content-type', '').startswith('application/json') else {}
            print(f"  ❌ ERROR: {error_data.get('message', 'Error desconocido')}")
            return False, error_data
            
    except requests.exceptions.Timeout:
        print("  ❌ ERROR: Timeout - El servidor no respondió a tiempo")
        return False, {"error": "timeout"}
        
    except requests.exceptions.ConnectionError:
        print("  ❌ ERROR: No se pudo conectar al servidor")
        return False, {"error": "connection_error"}
        
    except Exception as e:
        print(f"  ❌ ERROR: {str(e)}")
        return False, {"error": str(e)}

def leer_qr_simulado():
    """
    Simula la lectura de un código QR
    En producción, reemplaza esto con tu lector QR real
    """
    # Códigos QR de ejemplo - reemplaza con códigos reales de tu sistema
    codigos_ejemplo = [
        "EST001",
        "EST002", 
        "EST003",
        "QR123456789",
        "STUDENT_001"
    ]
    
    print("\n=== SIMULADOR DE LECTURA QR ===")
    print("Códigos disponibles:")
    for i, codigo in enumerate(codigos_ejemplo, 1):
        print(f"  {i}. {codigo}")
    
    try:
        opcion = input("\nSelecciona un código (1-5) o escribe uno personalizado: ").strip()
        
        if opcion.isdigit() and 1 <= int(opcion) <= len(codigos_ejemplo):
            return codigos_ejemplo[int(opcion) - 1]
        else:
            return opcion if opcion else codigos_ejemplo[0]
            
    except KeyboardInterrupt:
        return None

def detectar_tipo_basura_simulado():
    """
    Simula la detección del tipo de basura
    En producción, reemplaza esto con tu sensor/lógica real
    """
    tipos_disponibles = [
        "plastico",
        "papel", 
        "vidrio",
        "metal",
        "organico"
    ]
    
    print("\n=== SIMULADOR DE DETECCIÓN DE BASURA ===")
    print("Tipos disponibles:")
    for i, tipo in enumerate(tipos_disponibles, 1):
        print(f"  {i}. {tipo}")
    
    try:
        opcion = input("\nSelecciona un tipo (1-5) o escribe uno personalizado: ").strip()
        
        if opcion.isdigit() and 1 <= int(opcion) <= len(tipos_disponibles):
            return tipos_disponibles[int(opcion) - 1]
        else:
            return opcion if opcion else tipos_disponibles[0]
            
    except KeyboardInterrupt:
        return None

def medir_peso_simulado():
    """
    Simula la medición de peso
    En producción, reemplaza esto con tu sensor de peso real
    """
    try:
        peso_str = input("Peso en kg (opcional, presiona Enter para omitir): ").strip()
        if peso_str:
            return float(peso_str)
        return None
    except ValueError:
        print("Peso inválido, se omitirá")
        return None
    except KeyboardInterrupt:
        return None

def modo_interactivo():
    """
    Modo interactivo para pruebas manuales
    """
    print("=== CLIENTE RASPBERRY PI - MODO INTERACTIVO ===")
    print(f"Conectando a: {API_URL}")
    print("Presiona Ctrl+C para salir\n")
    
    try:
        while True:
            # Leer QR
            qr_codigo = leer_qr_simulado()
            if not qr_codigo:
                break
                
            # Detectar tipo de basura
            tipo_basura = detectar_tipo_basura_simulado()
            if not tipo_basura:
                break
                
            # Medir peso (opcional)
            peso = medir_peso_simulado()
            
            # Enviar al servidor
            success, response = enviar_deposito(qr_codigo, tipo_basura, peso)
            
            print("\n" + "="*50)
            
            # Pausa antes del siguiente ciclo
            try:
                input("\nPresiona Enter para continuar o Ctrl+C para salir...")
            except KeyboardInterrupt:
                break
                
    except KeyboardInterrupt:
        print("\n\n👋 ¡Hasta luego!")

def modo_automatico():
    """
    Modo automático para integración con hardware real
    """
    print("=== CLIENTE RASPBERRY PI - MODO AUTOMÁTICO ===")
    print(f"Conectando a: {API_URL}")
    print("Esperando eventos de hardware...\n")
    
    # En este modo, integrarías con tus sensores reales:
    # - Lector QR/código de barras
    # - Sensor de tipo de basura (cámara + IA, sensores, etc.)
    # - Báscula para peso
    
    try:
        while True:
            # TODO: Reemplazar con lectura real de sensores
            print("Esperando depósito... (simulado)")
            time.sleep(2)
            
            # Simular evento de depósito
            qr_codigo = "EST001"  # Desde lector QR
            tipo_basura = "plastico"  # Desde sensor
            peso = 0.25  # Desde báscula
            
            success, response = enviar_deposito(qr_codigo, tipo_basura, peso)
            
            # Esperar antes del siguiente ciclo
            time.sleep(5)
            
    except KeyboardInterrupt:
        print("\n\n👋 Sistema detenido")

if __name__ == "__main__":
    import sys
    
    if len(sys.argv) > 1 and sys.argv[1] == "--auto":
        modo_automatico()
    else:
        modo_interactivo()
