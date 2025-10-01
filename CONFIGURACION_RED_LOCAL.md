# 🌐 Configuración Raspberry Pi por Red Local (Sin Dominio)

Esta guía te explica cómo conectar tu Raspberry Pi al sistema usando tu red local WiFi/Ethernet, **sin necesidad de dominio o hosting**.

---

## 📋 **Requisitos**

- ✅ PC con Laravel (servidor) y Raspberry Pi en la **misma red WiFi/Ethernet**
- ✅ Conocer la IP local de tu PC
- ✅ Puerto 8000 disponible

---

## 🔧 **Paso 1: Obtener la IP de tu PC (Servidor Laravel)**

### En Windows:

Abre **CMD** o **PowerShell** y ejecuta:

```bash
ipconfig
```

Busca la sección de tu adaptador de red activo (WiFi o Ethernet) y anota la **IPv4 Address**:

```
Adaptador de LAN inalámbrica Wi-Fi:
   Dirección IPv4. . . . . . . . . : 192.168.1.100  ← ESTA ES TU IP
```

Ejemplos comunes de IPs locales:
- `192.168.1.xxx`
- `192.168.0.xxx`
- `10.0.0.xxx`

---

## 🚀 **Paso 2: Ejecutar Laravel Accesible desde la Red**

En lugar de usar `php artisan serve` normal, ejecuta:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**¿Qué hace esto?**
- `--host=0.0.0.0`: Permite conexiones desde cualquier dispositivo en tu red
- `--port=8000`: Usa el puerto 8000 (puedes cambiarlo si está ocupado)

Deberías ver:
```
INFO  Server running on [http://0.0.0.0:8000].
```

**⚠️ Importante:** Mantén esta terminal abierta mientras uses el sistema.

---

## 🔑 **Paso 3: Configurar API Key en .env**

Edita tu archivo `.env` y agrega:

```env
# API Key para Raspberry Pi (genera una clave segura)
RASPBERRY_API_KEY=RaspberryPi2024_MiClaveSecreta_ABC123XYZ

# URL de tu aplicación (opcional pero recomendado)
APP_URL=http://192.168.1.100:8000
```

**Reemplaza `192.168.1.100` con TU IP local del Paso 1.**

---

## 🐍 **Paso 4: Configurar Cliente Python en Raspberry Pi**

### A. Transferir el archivo a la Raspberry

Copia `raspberry_client.py` a tu Raspberry Pi usando:

**Opción 1 - USB:**
- Copia el archivo a una memoria USB
- Conéctala a la Raspberry y copia el archivo

**Opción 2 - SCP (si tienes SSH):**
```bash
scp raspberry_client.py pi@192.168.1.200:/home/pi/
```

**Opción 3 - Crear directamente:**
```bash
# En la Raspberry, crea el archivo
nano raspberry_client.py
# Pega el contenido y guarda con Ctrl+X, Y, Enter
```

### B. Editar la configuración

En la Raspberry Pi, edita el archivo:

```bash
nano raspberry_client.py
```

Cambia estas líneas al inicio del archivo:

```python
# Configuración
API_URL = "http://192.168.1.100:8000/api/raspberry/deposito"  # ← TU IP AQUÍ
API_KEY = "RaspberryPi2024_MiClaveSecreta_ABC123XYZ"           # ← MISMA CLAVE DEL .ENV
```

**⚠️ Importante:** 
- Reemplaza `192.168.1.100` con la IP de tu PC
- Usa la **misma API_KEY** que pusiste en el `.env`

### C. Instalar dependencias

```bash
pip install requests
```

---

## 🧪 **Paso 5: Probar la Conexión**

### Desde la Raspberry Pi:

#### 1. Verificar conectividad de red:
```bash
ping 192.168.1.100
```

Deberías ver:
```
64 bytes from 192.168.1.100: icmp_seq=1 ttl=64 time=2.5 ms
```

Si no funciona, verifica que ambos dispositivos estén en la misma red.

#### 2. Probar con curl:
```bash
curl -X POST "http://192.168.1.100:8000/api/raspberry/deposito" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: RaspberryPi2024_MiClaveSecreta_ABC123XYZ" \
  -d '{"qr_codigo": "EST001", "tipo_basura": "plastico"}'
```

**Respuesta esperada (si el estudiante existe):**
```json
{
  "success": true,
  "message": "¡Depósito registrado correctamente!",
  "estudiante": {
    "id": 1,
    "nombre": "Juan",
    "apellidos": "Pérez García",
    "puntos_actuales": 150
  }
}
```

#### 3. Ejecutar el cliente Python:
```bash
python raspberry_client.py
```

---

## 🔥 **Paso 6: Configurar Firewall de Windows (si no conecta)**

Si la Raspberry no puede conectarse, probablemente el firewall de Windows está bloqueando el puerto.

### Permitir el puerto 8000:

1. Presiona `Win + R` y escribe: `wf.msc`
2. Click en **"Reglas de entrada"** (lado izquierdo)
3. Click en **"Nueva regla..."** (lado derecho)
4. Selecciona **"Puerto"** → Siguiente
5. Selecciona **"TCP"** y escribe `8000` → Siguiente
6. Selecciona **"Permitir la conexión"** → Siguiente
7. Marca todas las opciones (Dominio, Privado, Público) → Siguiente
8. Nombre: `Laravel Puerto 8000` → Finalizar

### Alternativa rápida (desactiva temporalmente el firewall):
```powershell
# ⚠️ Solo para pruebas, NO recomendado en producción
netsh advfirewall set allprofiles state off
```

Para reactivarlo:
```powershell
netsh advfirewall set allprofiles state on
```

---

## 📊 **Paso 7: Acceder al Panel de Administración**

Desde cualquier dispositivo en tu red (PC, tablet, celular):

```
http://192.168.1.100:8000/admin/raspberry/eventos
```

Inicia sesión como administrador y verás:
- ✅ Todos los eventos de la Raspberry en tiempo real
- 📊 Estadísticas de éxito/fallo
- 🔄 Auto-actualización cada 5 segundos
- 🔍 Filtros por estado

---

## 🐛 **Solución de Problemas**

### ❌ Error: "Connection refused"

**Causa:** El servidor Laravel no está ejecutándose o no es accesible.

**Solución:**
1. Verifica que Laravel esté corriendo: `php artisan serve --host=0.0.0.0 --port=8000`
2. Verifica tu IP: `ipconfig`
3. Verifica el firewall (Paso 6)

---

### ❌ Error: "Unauthorized - Invalid API Key"

**Causa:** La API Key no coincide.

**Solución:**
1. Verifica el `.env`: `RASPBERRY_API_KEY=...`
2. Verifica `raspberry_client.py`: `API_KEY = "..."`
3. Deben ser **exactamente iguales**
4. Reinicia Laravel después de cambiar `.env`

---

### ❌ Error: "Usuario no encontrado"

**Causa:** El código QR no existe en la base de datos.

**Solución:**
1. Verifica que el estudiante exista en la tabla `usuario`
2. Verifica que tenga el campo `qr_codigo` poblado
3. Usa un código QR válido de tu sistema

---

### ❌ Error: "Tipo de basura no válido"

**Causa:** El tipo de basura no existe o está inactivo.

**Solución:**
1. Verifica que el tipo exista en la tabla `tipoBasura`
2. Verifica que esté activo (`estado = 1`)
3. Usa nombres exactos: "plastico", "papel", "vidrio", etc.

---

### ❌ No puedo hacer ping a la IP

**Causa:** Dispositivos en redes diferentes o firewall bloqueando ICMP.

**Solución:**
1. Verifica que ambos estén en la misma red WiFi
2. Verifica que no haya aislamiento de clientes en el router
3. Intenta con la IP directamente en el navegador: `http://192.168.1.100:8000`

---

## 🔒 **Seguridad en Red Local**

### ✅ Buenas prácticas:

1. **API Key fuerte:** Mínimo 32 caracteres aleatorios
2. **Red privada:** Solo dispositivos de confianza
3. **Firewall activo:** Solo abre el puerto 8000
4. **Logs:** Revisa `storage/logs/laravel.log` regularmente

### ⚠️ NO hacer en red local:

- ❌ No uses contraseñas débiles
- ❌ No expongas el puerto 8000 a Internet sin HTTPS
- ❌ No compartas la API Key públicamente

---

## 🚀 **Comandos Rápidos de Referencia**

### En tu PC (Servidor):
```bash
# Obtener IP
ipconfig

# Ejecutar Laravel
php artisan serve --host=0.0.0.0 --port=8000

# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

### En la Raspberry Pi:
```bash
# Probar conectividad
ping 192.168.1.100

# Ejecutar cliente Python (modo interactivo)
python raspberry_client.py

# Ejecutar cliente Python (modo automático)
python raspberry_client.py --auto

# Ver logs del cliente
python raspberry_client.py 2>&1 | tee raspberry.log
```

---

## 📱 **Ejemplo Completo de Uso**

### Escenario: Estudiante deposita plástico

1. **Raspberry lee QR:** `EST001`
2. **Sensor detecta tipo:** `plastico`
3. **Raspberry envía a API:**
   ```json
   {
     "qr_codigo": "EST001",
     "tipo_basura": "plastico",
     "peso": 0.25
   }
   ```
4. **Laravel procesa:**
   - ✅ Busca estudiante
   - ✅ Valida tipo de basura
   - ✅ Crea depósito
   - ✅ Actualiza puntos
   - ✅ Registra evento

5. **Laravel responde:**
   ```json
   {
     "success": true,
     "estudiante": {
       "nombre": "Juan Pérez",
       "puntos_actuales": 160
     },
     "deposito": {
       "puntos_ganados": 10
     }
   }
   ```

6. **Raspberry muestra en pantalla:**
   ```
   ✅ ¡Depósito registrado!
   Estudiante: Juan Pérez
   Puntos ganados: +10
   Total de puntos: 160
   ```

---

## 🎯 **Próximos Pasos**

Una vez que funcione en red local:

1. **Integrar hardware real:**
   - Lector QR/código de barras
   - Sensores de clasificación
   - Báscula digital
   - Pantalla LCD para feedback

2. **Mejorar el sistema:**
   - Agregar sonidos de confirmación
   - LEDs indicadores (verde=éxito, rojo=error)
   - Pantalla táctil para interfaz
   - Cámara para verificación visual

3. **Escalar (opcional):**
   - Múltiples Raspberry Pi
   - Base de datos en servidor dedicado
   - Dominio y HTTPS para acceso remoto

---

## 📞 **Soporte**

Si tienes problemas:

1. **Revisa los logs:**
   ```bash
   # En el servidor
   tail -f storage/logs/laravel.log
   
   # Eventos de Raspberry en BD
   php artisan tinker
   >>> DB::table('raspberry_events')->latest()->limit(5)->get()
   ```

2. **Verifica la configuración:**
   - IP correcta en ambos lados
   - API Key idéntica
   - Firewall permite puerto 8000
   - Ambos dispositivos en misma red

3. **Consulta la documentación:**
   - `RASPBERRY_PI_INTEGRATION.md` - Guía técnica completa
   - `raspberry_client.py` - Código comentado

---

¡Tu sistema está listo para funcionar en red local! 🎉
