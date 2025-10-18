# Sistema Inteligente de Reciclaje v2.0

Sistema modular de gestión de residuos con control de compuerta automático, detección mejorada de tipos de basura y validaciones completas.

## 📋 Características Principales

### ✅ Control de Compuerta Inteligente
- **Apertura automática** al verificar estudiante válido
- **Cierre automático** después del depósito
- **Timeout configurable** si no se deposita basura
- **Validaciones de seguridad** para evitar accesos no autorizados

### ✅ Sistema de Estados
- **ESPERANDO**: Sistema listo, compuerta cerrada
- **VERIFICADO**: Estudiante verificado, compuerta abierta
- **DEPOSITANDO**: Esperando depósito del estudiante
- **PROCESANDO**: Analizando tipo de basura
- **COMPLETADO**: Depósito registrado exitosamente
- **TIMEOUT**: Tiempo agotado sin depósito
- **ERROR**: Manejo de errores del sistema

### ✅ Detección Mejorada de Basura
- **Múltiples sensores** trabajando en conjunto
- **Confirmación por lecturas múltiples** (3 lecturas consistentes)
- **Nivel de confianza** en la detección
- **Sensor ultrasónico** para detectar presencia de objetos

### ✅ Arquitectura Modular
- **config.py**: Configuración centralizada
- **api_client.py**: Comunicación con API Laravel
- **arduino_controller.py**: Control de Arduino y compuerta
- **state_manager.py**: Gestión de estados y validaciones
- **ui_manager.py**: Interfaz LCD y audio
- **camera_manager.py**: Gestión de cámara y QR
- **logger.py**: Sistema de logging
- **main.py**: Orquestador principal

## 🚀 Instalación

### Requisitos de Hardware
- Raspberry Pi 4 (recomendado) o 3B+
- Arduino Uno/Mega
- Cámara USB o módulo de cámara Raspberry Pi
- LCD I2C 20x4
- Sensores:
  - Capacitivo
  - Inductivo
  - KY-032 (IR)
  - TCRT5000 (Reflexión)
  - HC-SR04 (Ultrasónico)
- 6 Servomotores:
  - 1x Compuerta principal
  - 1x Cámara de retención
  - 4x Compartimentos (metal, papel, plástico, biodegradable)
- LEDs indicadores (verde, amarillo, rojo, azul)
- Buzzer

### Instalación en Raspberry Pi

1. **Actualizar sistema:**
```bash
sudo apt update && sudo apt upgrade -y
```

2. **Instalar dependencias del sistema:**
```bash
sudo apt install -y python3-pip python3-opencv python3-dev
sudo apt install -y i2c-tools libzbar0 mpg123
sudo apt install -y espeak ffmpeg libatlas-base-dev
```

3. **Habilitar I2C:**
```bash
sudo raspi-config
# Ir a: Interfacing Options > I2C > Enable
```

4. **Instalar librerías Python:**
```bash
cd /home/pi/SistemaDePuntosDarioMontano/resources/Basurero/rasperry
pip3 install -r requirements.txt
```

5. **Configurar permisos:**
```bash
sudo usermod -a -G dialout,i2c,video $USER
# Reiniciar para aplicar cambios
```

### Instalación en Arduino

1. **Instalar librerías en Arduino IDE:**
   - Servo (incluida)
   - ArduinoJson v6

2. **Cargar código:**
   - Abrir `Arduino/sistema_reciclaje_v2.ino`
   - Seleccionar puerto y placa correctos
   - Cargar el código

## ⚙️ Configuración

### 1. Configurar API (config.py)
```python
API_CONFIG = {
    'base_url': 'http://192.168.100.4:8000',  # Tu IP local
    'api_key': 'TU_API_KEY_AQUI',
    'timeout': 10,
}
```

### 2. Configurar Hardware (config.py)
```python
HARDWARE_CONFIG = {
    'arduino_port': '/dev/ttyACM0',  # Verificar con ls /dev/tty*
    'camera_device': '/dev/video0',   # Verificar con ls /dev/video*
    # ...
}
```

### 3. Configurar Tiempos (config.py)
```python
TIMING_CONFIG = {
    'timeout_deposito': 30,      # Segundos para depositar
    'timeout_deteccion': 10,     # Segundos para detectar tipo
    # ...
}
```

## 📦 Uso del Sistema

### Iniciar Sistema
```bash
cd /home/pi/SistemaDePuntosDarioMontano/resources/Basurero/rasperry
python3 main.py
```

### Iniciar Automáticamente al Arranque
```bash
# Crear servicio systemd
sudo nano /etc/systemd/system/reciclaje.service
```

Contenido del archivo:
```ini
[Unit]
Description=Sistema Inteligente de Reciclaje
After=network.target

[Service]
Type=simple
User=pi
WorkingDirectory=/home/pi/SistemaDePuntosDarioMontano/resources/Basurero/rasperry
ExecStart=/usr/bin/python3 main.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Activar servicio:
```bash
sudo systemctl enable reciclaje.service
sudo systemctl start reciclaje.service
```

## 🔄 Flujo del Sistema

1. **Estudiante presenta QR** → Cámara detecta y verifica
2. **Sistema valida con API** → Obtiene datos del estudiante
3. **Compuerta se abre** → LED verde, mensaje de bienvenida
4. **Estudiante deposita basura** → Sensores activos detectan
5. **Arduino clasifica tipo** → Múltiples lecturas para confirmar
6. **Sistema registra en API** → Puntos asignados
7. **Basura se dirige a compartimento** → Servo correspondiente activa
8. **Compuerta se cierra** → Sistema vuelve a ESPERANDO

## 📊 Monitoreo y Logs

### Ver logs en tiempo real:
```bash
tail -f logs/sistema_reciclaje.log
```

### Ver estado del servicio:
```bash
sudo systemctl status reciclaje.service
```

### Ver comunicación con Arduino:
```bash
screen /dev/ttyACM0 9600
```

## 🔧 Solución de Problemas

### Cámara no detecta QR
- Verificar iluminación
- Ajustar distancia (10-30 cm)
- Limpiar lente de cámara
- Verificar permisos: `ls -l /dev/video*`

### Arduino no responde
- Verificar conexión USB
- Revisar puerto: `ls /dev/tty*`
- Resetear Arduino (botón reset)
- Verificar baudrate (9600)

### LCD no muestra información
- Verificar I2C habilitado: `i2cdetect -y 1`
- Verificar dirección (0x27 por defecto)
- Revisar conexiones SDA/SCL

### Sensores no detectan correctamente
- Calibrar umbrales en Arduino
- Verificar conexiones
- Limpiar sensores
- Ajustar LECTURAS_CONFIRMACION

## 📈 Mejoras Implementadas

### v2.0 (Actual)
- ✅ Sistema modular con archivos separados
- ✅ Control de compuerta principal
- ✅ Máquina de estados con validaciones
- ✅ Detección mejorada con múltiples confirmaciones
- ✅ Timeouts configurables
- ✅ Logging completo
- ✅ Manejo robusto de errores
- ✅ Cache inteligente para API
- ✅ Callbacks asíncronos
- ✅ Comunicación JSON bidireccional con Arduino

### v1.0 (Anterior)
- Sistema monolítico en un archivo
- Sin control de compuerta
- Detección básica de basura
- Sin validaciones de estado

## 🤝 Contribuciones

Sistema desarrollado para el Colegio Dario Montaño como parte del Sistema de Puntos de Reciclaje.

## 📝 Licencia

Uso educativo - Colegio Dario Montaño
