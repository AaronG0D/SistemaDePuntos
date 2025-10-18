# 🔑 Configuración de API Key para Raspberry Pi

## 📝 **API Key Generada**

Copia esta API Key y agrégala a tu archivo `.env`:

```env
# API Key para Raspberry Pi
RASPBERRY_API_KEY=RaspberryPi2024_SecureKey_SistemaPuntos_ABC123XYZ789
```

## 🚀 **Pasos para Configurar**

### 1️⃣ **En el Servidor Laravel (tu PC)**

Edita el archivo `.env` y agrega la línea:

```bash
nano .env
```

Agrega al final:
```env
RASPBERRY_API_KEY=RaspberryPi2024_SecureKey_SistemaPuntos_ABC123XYZ789
```

Guarda y reinicia el servidor:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 2️⃣ **En la Raspberry Pi**

Edita el archivo `raspberry_sistema_reciclaje.py` y actualiza:

```python
API_CONFIG = {
    'base_url': 'http://192.168.1.100:8000',  # ← TU IP LOCAL
    'api_key': 'RaspberryPi2024_SecureKey_SistemaPuntos_ABC123XYZ789',  # ← MISMA API KEY
    'timeout': 10,
}
```

## 🔄 **Cambios Principales en el Código**

### ✅ **Lo que se mantiene igual:**
- LCD con iconos y mensajes
- Audio con gTTS
- Arduino para detectar tipo de basura
- Cámara para leer QR
- Guardado de imágenes
- Threads para concientización

### ✨ **Lo que cambió (mejoras):**
- ❌ **Eliminado:** Conexión directa a MySQL
- ✅ **Agregado:** Comunicación con API REST
- ✅ **Agregado:** Autenticación con API Key
- ✅ **Agregado:** Manejo de errores de red
- ✅ **Agregado:** Logs detallados de API
- ✅ **Agregado:** Timeout configurable

## 📡 **Endpoints Utilizados**

### POST /api/raspberry/deposito
```json
{
  "qr_codigo": "EST001",
  "tipo_basura": "plastico"
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "estudiante": {
    "nombre": "Juan",
    "apellidos": "Pérez García",
    "puntos_actuales": 150
  },
  "deposito": {
    "puntos_ganados": 10,
    "tipo_basura": "Plástico"
  }
}
```

## 🧪 **Probar la Integración**

### Desde la Raspberry Pi:

```bash
# 1. Instalar dependencias (si no las tienes)
pip install requests

# 2. Probar conectividad
ping 192.168.1.100

# 3. Probar API con curl
curl -X POST "http://192.168.1.100:8000/api/raspberry/deposito" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: RaspberryPi2024_SecureKey_SistemaPuntos_ABC123XYZ789" \
  -d '{"qr_codigo": "EST001", "tipo_basura": "plastico"}'

# 4. Ejecutar el sistema
python raspberry_sistema_reciclaje.py
```

## 🔒 **Seguridad**

### ✅ **Buenas prácticas implementadas:**
1. API Key en headers (no en URL)
2. Timeout para evitar bloqueos
3. Manejo de errores de conexión
4. Logs detallados para debugging
5. Validación en ambos lados (Raspberry y Laravel)

### ⚠️ **Importante:**
- Cambia la API Key por una única y segura
- No compartas la API Key públicamente
- Usa HTTPS en producción
- Mantén actualizado el sistema

## 📊 **Monitoreo**

Puedes ver todos los eventos en tiempo real desde:
```
http://192.168.1.100:8000/admin/raspberry/eventos
```

## 🐛 **Troubleshooting**

### Error: "Unauthorized - Invalid API Key"
- Verifica que la API Key sea idéntica en `.env` y en el código Python
- Reinicia el servidor Laravel después de cambiar `.env`

### Error: "Sin conexión al servidor"
- Verifica que el servidor Laravel esté corriendo
- Verifica la IP con `ipconfig`
- Verifica el firewall (puerto 8000)

### Error: "Timeout"
- Aumenta el timeout en `API_CONFIG`
- Verifica la velocidad de red
- Revisa logs del servidor

## 📝 **Logs**

### En la Raspberry:
Los logs aparecen en la consola con prefijos:
- `[API]` - Comunicación con API
- `[QR]` - Lectura de códigos QR
- `[ARDUINO]` - Datos del Arduino
- `[✅]` - Operaciones exitosas
- `[❌]` - Errores

### En el Servidor:
```bash
tail -f storage/logs/laravel.log | grep -i raspberry
```

## 🎯 **Ventajas de la Nueva Implementación**

1. ✅ **Centralización:** Todos los datos en un solo lugar
2. ✅ **Escalabilidad:** Múltiples Raspberry Pi pueden conectarse
3. ✅ **Mantenimiento:** Cambios en lógica solo en el servidor
4. ✅ **Seguridad:** API Key + validaciones
5. ✅ **Monitoreo:** Panel web en tiempo real
6. ✅ **Auditoría:** Todos los eventos registrados
7. ✅ **Flexibilidad:** Fácil agregar nuevas funcionalidades

---

¡Tu sistema está listo para funcionar con la API web! 🎉
