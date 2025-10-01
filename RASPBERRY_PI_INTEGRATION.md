# Integración Raspberry Pi - Sistema de Puntos

Esta documentación explica cómo configurar y usar la integración con Raspberry Pi para el sistema de puntos de reciclaje.

## 📋 Resumen del Sistema

El sistema permite que una Raspberry Pi envíe datos de depósitos de basura al backend Laravel mediante una API REST segura. Cada vez que un estudiante deposita basura:

1. La Raspberry lee el código QR del estudiante
2. Detecta el tipo de basura (sensor/cámara/manual)
3. Envía los datos al endpoint `/api/raspberry/deposito`
4. El backend valida, registra el depósito y actualiza puntos
5. Responde con información del estudiante para mostrar en pantalla

## 🔧 Configuración del Backend

### 1. Variables de Entorno (.env)

Agrega esta línea a tu archivo `.env`:

```env
# API Key para Raspberry Pi (genera una clave segura)
RASPBERRY_API_KEY=tu_clave_super_secreta_aqui_min_32_caracteres
```

**Importante:** Usa una clave larga y segura. Ejemplo:
```env
RASPBERRY_API_KEY=RaspberryPi2024_SecureKey_SistemaPuntos_ABC123XYZ789
```

### 2. Ejecutar Migraciones

```bash
php artisan migrate
```

Esto creará la tabla `raspberry_events` para registrar todos los eventos.

### 3. Verificar Rutas

Las siguientes rutas están disponibles:

- **POST** `/api/raspberry/deposito` - Endpoint para Raspberry Pi (requiere API Key)
- **GET** `/api/raspberry/eventos` - Lista eventos para administración (requiere auth)
- **GET** `/admin/raspberry/eventos` - Página web de monitoreo (requiere auth admin)

## 🔌 API Endpoints

### POST /api/raspberry/deposito

**Headers requeridos:**
```
Content-Type: application/json
X-API-KEY: tu_clave_super_secreta_aqui_min_32_caracteres
```

**Payload JSON:**
```json
{
  "qr_codigo": "EST001",
  "tipo_basura": "plastico",
  "peso": 0.25
}
```

**Campos:**
- `qr_codigo` (string, requerido): Código QR del estudiante
- `tipo_basura` (string, requerido): Tipo de basura ("plastico", "papel", "vidrio", etc.)
- `peso` (number, opcional): Peso en kilogramos

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "¡Depósito registrado correctamente!",
  "estudiante": {
    "id": 123,
    "nombre": "Juan",
    "apellidos": "Pérez García",
    "curso_info": {
      "curso": "10mo",
      "paralelo": "A"
    },
    "puntos_actuales": 150
  },
  "deposito": {
    "id": 456,
    "tipo_basura": "Plástico",
    "puntos_ganados": 10,
    "fecha": "2024-10-01 14:30:00"
  },
  "event_id": 789
}
```

**Respuesta de error (404):**
```json
{
  "success": false,
  "message": "Usuario no encontrado"
}
```

**Respuesta de error (422):**
```json
{
  "success": false,
  "message": "Tipo de basura no válido"
}
```

**Respuesta de error (401):**
```json
{
  "message": "Unauthorized - Invalid API Key"
}
```

## 🐍 Cliente Python para Raspberry Pi

### Instalación

```bash
pip install requests
```

### Uso del Cliente

El archivo `raspberry_client.py` incluye dos modos:

#### Modo Interactivo (para pruebas)
```bash
python raspberry_client.py
```

#### Modo Automático (para producción)
```bash
python raspberry_client.py --auto
```

### Configuración del Cliente

Edita las variables en `raspberry_client.py`:

```python
API_URL = "https://tu-dominio.com/api/raspberry/deposito"  # Tu URL real
API_KEY = "tu_clave_super_secreta_aqui_min_32_caracteres"   # Misma clave del .env
```

### Integración con Hardware

Para integrar con hardware real, reemplaza las funciones simuladas:

```python
def leer_qr_real():
    """Integra con tu lector QR/código de barras"""
    # Ejemplo con biblioteca pyzbar para cámara:
    # from pyzbar import pyzbar
    # import cv2
    # ...
    pass

def detectar_tipo_basura_real():
    """Integra con tu sensor/cámara de clasificación"""
    # Ejemplo con sensores GPIO:
    # import RPi.GPIO as GPIO
    # ...
    pass

def medir_peso_real():
    """Integra con tu báscula/sensor de peso"""
    # Ejemplo con HX711 load cell:
    # from hx711 import HX711
    # ...
    pass
```

## 🧪 Ejemplos de Prueba con curl

### Depósito exitoso
```bash
curl -X POST "http://localhost:8000/api/raspberry/deposito" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: tu_clave_super_secreta_aqui_min_32_caracteres" \
  -d '{
    "qr_codigo": "EST001",
    "tipo_basura": "plastico",
    "peso": 0.25
  }'
```

### Estudiante no encontrado
```bash
curl -X POST "http://localhost:8000/api/raspberry/deposito" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: tu_clave_super_secreta_aqui_min_32_caracteres" \
  -d '{
    "qr_codigo": "INEXISTENTE",
    "tipo_basura": "plastico"
  }'
```

### API Key inválida
```bash
curl -X POST "http://localhost:8000/api/raspberry/deposito" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: clave_incorrecta" \
  -d '{
    "qr_codigo": "EST001",
    "tipo_basura": "plastico"
  }'
```

## 🖥️ Panel de Administración

### Acceso
Visita: `https://tu-dominio.com/admin/raspberry/eventos`

### Funcionalidades
- ✅ Ver todos los eventos de Raspberry Pi en tiempo real
- 📊 Estadísticas de éxito/fallo
- 🔄 Auto-actualización configurable (3-30 segundos)
- 🔍 Filtros por estado (exitoso/fallido/pendiente)
- 📱 Diseño responsive
- 🕒 Timestamps detallados
- 🌐 Información de IP de origen

### Campos Mostrados
- **Estado**: Exitoso ✅, Fallido ❌, Pendiente ⏳
- **QR Código**: Código leído por la Raspberry
- **Estudiante**: Nombre y apellidos (si se encontró)
- **Tipo Basura**: Tipo detectado y puntos asignados
- **Puntos**: Puntos ganados en el depósito
- **Fecha**: Timestamp de creación y procesamiento
- **IP**: Dirección IP de la Raspberry
- **Mensaje**: Descripción del resultado

## 🔒 Seguridad

### Recomendaciones
1. **API Key segura**: Usa mínimo 32 caracteres aleatorios
2. **HTTPS**: Siempre usa HTTPS en producción
3. **Firewall**: Restringe acceso por IP si es posible
4. **Rate Limiting**: El sistema incluye throttling (60 req/min)
5. **Logs**: Todos los eventos se registran para auditoría

### Configuración de Firewall (opcional)
```nginx
# En nginx, permitir solo IP de Raspberry
location /api/raspberry/ {
    allow 192.168.1.100;  # IP de tu Raspberry
    deny all;
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 🗄️ Base de Datos

### Tabla raspberry_events
Registra todos los eventos para auditoría:

```sql
- id: ID único del evento
- qr_codigo: Código QR leído
- idTipoBasura: ID del tipo de basura (si se encontró)
- tipo_basura_nombre: Nombre del tipo enviado
- idUser: ID del usuario (si se encontró)
- idDeposito: ID del depósito creado (si exitoso)
- status: pending|success|failed
- message: Mensaje descriptivo
- meta: JSON con datos adicionales (peso, etc.)
- ip: IP de origen
- user_agent: User agent de la request
- processed_at: Timestamp de procesamiento
- created_at/updated_at: Timestamps estándar
```

## 🚨 Troubleshooting

### Error: "Unauthorized - Invalid API Key"
- Verifica que `RASPBERRY_API_KEY` esté en `.env`
- Confirma que el header `X-API-KEY` sea exacto
- Reinicia el servidor después de cambiar `.env`

### Error: "Usuario no encontrado"
- Verifica que el código QR exista en la tabla `usuario`
- Confirma que el campo `qr_codigo` esté poblado
- Verifica que el usuario tenga rol 'estudiante'

### Error: "Tipo de basura no válido"
- Verifica que el tipo exista en la tabla `tipoBasura`
- Confirma que el tipo esté activo (`estado = true`)
- Usa nombres exactos o similares a los registrados

### Error de conexión
- Verifica que el servidor Laravel esté ejecutándose
- Confirma la URL del endpoint
- Revisa logs del servidor: `tail -f storage/logs/laravel.log`

### Raspberry Pi no conecta
- Verifica conectividad de red: `ping tu-dominio.com`
- Confirma que el puerto esté abierto
- Revisa configuración de firewall

## 📊 Monitoreo

### Logs de Laravel
```bash
tail -f storage/logs/laravel.log | grep -i raspberry
```

### Eventos recientes vía API
```bash
curl -H "Authorization: Bearer tu_token" \
  "http://localhost:8000/api/raspberry/eventos?limit=10"
```

### Estadísticas de base de datos
```sql
SELECT 
    status,
    COUNT(*) as total,
    DATE(created_at) as fecha
FROM raspberry_events 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY status, DATE(created_at)
ORDER BY fecha DESC;
```

## 🔄 Mantenimiento

### Limpieza de eventos antiguos
```sql
-- Eliminar eventos de más de 30 días
DELETE FROM raspberry_events 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Backup de eventos
```bash
mysqldump -u user -p database raspberry_events > raspberry_events_backup.sql
```

## 🎯 Próximos Pasos

1. **Integrar con hardware real** en la Raspberry Pi
2. **Configurar notificaciones** para fallos críticos
3. **Implementar WebSockets** para actualizaciones en tiempo real
4. **Agregar métricas** de rendimiento y uso
5. **Configurar alertas** por email/Slack para errores

---

¿Necesitas ayuda con algún aspecto específico de la integración? ¡Consulta la documentación o revisa los logs para más detalles!
