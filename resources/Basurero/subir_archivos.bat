@echo off
echo ========================================
echo SUBIENDO ARCHIVOS AL RASPBERRY PI
echo ========================================

echo.
echo 1. Subiendo archivos Python corregidos...
scp rasperry/raspberry_sistema_reciclaje.py pi@192.168.100.22:/home/pi/rasperry/

echo.
echo 2. Subiendo archivos del sistema modular...
scp rasperry/camera_manager.py pi@192.168.100.22:/home/pi/rasperry/
scp rasperry/config.py pi@192.168.100.22:/home/pi/rasperry/
scp rasperry/main.py pi@192.168.100.22:/home/pi/rasperry/

echo.
echo ========================================
echo ARCHIVOS SUBIDOS CORRECTAMENTE
echo ========================================
echo.
echo INSTRUCCIONES:
echo 1. Cargar sketch_oct1a.ino al Arduino (con comando DESACTIVAR)
echo 2. En Raspberry Pi ejecutar: python3 raspberry_sistema_reciclaje.py
echo 3. O ejecutar sistema modular: python3 main.py
echo.
echo NOTA: Los puntos ahora aparecerán correctamente en RaspberryEventos
echo      El bucle se detendrá después del depósito exitoso
echo ========================================

pause
