# SISTEMA DE PUNTOS PARA RECICLAJE INTELIGENTE
## Documentación Técnica y Funcional

---

## ÍNDICE

1. [Introducción](#introducción)
2. [Objetivos del Sistema](#objetivos-del-sistema)
3. [Tecnologías Utilizadas](#tecnologías-utilizadas)
4. [Arquitectura del Sistema](#arquitectura-del-sistema)
5. [Módulos y Funcionalidades](#módulos-y-funcionalidades)
6. [Roles de Usuario](#roles-de-usuario)
7. [Sistema de Basureros Inteligentes](#sistema-de-basureros-inteligentes)
8. [Base de Datos](#base-de-datos)
9. [APIs y Endpoints](#apis-y-endpoints)
10. [Instalación y Configuración](#instalación-y-configuración)

---

## INTRODUCCIÓN

El **Sistema de Puntos para Reciclaje Inteligente** es una aplicación web integral que promueve la cultura del reciclaje en instituciones educativas mediante un sistema de gamificación. La plataforma integra tecnología IoT (Internet de las Cosas) con basureros inteligentes equipados con Raspberry Pi y Arduino para automatizar el proceso de clasificación de residuos y asignación de puntos.

### Propósito
Fomentar la conciencia ambiental y el reciclaje responsable entre estudiantes, docentes y personal administrativo mediante un sistema de recompensas por puntos que reconoce y premia las acciones de reciclaje.

---

## OBJETIVOS DEL SISTEMA

### Objetivo General
Desarrollar un sistema integral de gestión de reciclaje que combine tecnología web moderna con dispositivos IoT para automatizar y gamificar el proceso de clasificación de residuos en instituciones educativas.

### Objetivos Específicos

#### 🎯 Educativos
- Promover la educación ambiental y conciencia ecológica
- Incentivar hábitos de reciclaje responsable
- Crear competencias saludables entre estudiantes y cursos
- Generar reportes de impacto ambiental

#### 🎯 Tecnológicos
- Automatizar la clasificación de residuos mediante sensores IoT
- Implementar un sistema de puntos justo y transparente
- Proporcionar monitoreo en tiempo real de los basureros
- Integrar hardware (Arduino/Raspberry Pi) con software web

#### 🎯 Administrativos
- Facilitar la gestión de estudiantes y cursos
- Generar estadísticas y reportes detallados
- Optimizar la gestión de residuos institucionales
- Reducir la carga administrativa manual

---

## TECNOLOGÍAS UTILIZADAS

### 🖥️ Backend (Servidor)
- **Laravel 11**: Framework PHP para desarrollo web robusto y escalable
- **PHP 8.2+**: Lenguaje de programación del lado del servidor
- **MySQL 8.0**: Sistema de gestión de base de datos relacional
- **Eloquent ORM**: Mapeo objeto-relacional para interacción con la base de datos
- **Laravel Sanctum**: Autenticación API para dispositivos IoT
- **Laravel Migrations**: Control de versiones de base de datos

### 🎨 Frontend (Cliente)
- **Vue.js 3**: Framework JavaScript progresivo para interfaces de usuario
- **TypeScript**: Superset de JavaScript con tipado estático
- **Vite**: Herramienta de construcción rápida para desarrollo
- **Tailwind CSS**: Framework de CSS utilitario para diseño responsivo
- **Lucide Icons**: Biblioteca de iconos SVG moderna
- **Axios**: Cliente HTTP para comunicación con APIs

### 🤖 Hardware IoT
- **Raspberry Pi 4**: Computadora de placa única para procesamiento principal
- **Arduino Uno**: Microcontrolador para sensores y actuadores
- **Python 3.9+**: Lenguaje para programación del Raspberry Pi
- **OpenCV**: Biblioteca de visión por computadora para detección QR
- **PyZBar**: Decodificador de códigos QR y códigos de barras

### 📡 Comunicación y Protocolos
- **HTTP/HTTPS**: Protocolo de comunicación web
- **REST API**: Arquitectura para servicios web
- **JSON**: Formato de intercambio de datos
- **Serial Communication**: Comunicación entre Raspberry Pi y Arduino
- **WebSocket**: Para actualizaciones en tiempo real (futuro)

### 🛠️ Herramientas de Desarrollo
- **Composer**: Gestor de dependencias PHP
- **NPM**: Gestor de paquetes Node.js
- **Git**: Control de versiones
- **VS Code**: Editor de código
- **Postman**: Pruebas de API

---

## ARQUITECTURA DEL SISTEMA

### Arquitectura General
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   FRONTEND      │    │    BACKEND      │    │   HARDWARE      │
│   (Vue.js)      │◄──►│   (Laravel)     │◄──►│ (Raspberry Pi)  │
│                 │    │                 │    │                 │
│ - Dashboard     │    │ - API REST      │    │ - Cámara QR     │
│ - Gestión       │    │ - Autenticación │    │ - Arduino       │
│ - Reportes      │    │ - Base de Datos │    │ - Sensores      │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Flujo de Datos
1. **Estudiante** presenta código QR al basurero inteligente
2. **Raspberry Pi** captura y decodifica el código QR
3. **Sistema** verifica la identidad del estudiante via API
4. **Arduino** detecta el tipo de residuo mediante sensores
5. **Sistema** registra el depósito y asigna puntos
6. **Base de datos** almacena la transacción
7. **Frontend** muestra actualizaciones en tiempo real

---

## MÓDULOS Y FUNCIONALIDADES

### 📊 Dashboard Principal
- **Estadísticas generales**: Total de estudiantes, depósitos, puntos
- **Gráficos interactivos**: Tendencias de reciclaje por período
- **Top rankings**: Mejores estudiantes y cursos
- **Actividad reciente**: Últimos depósitos realizados

### 👥 Gestión de Estudiantes
- **Registro masivo**: Importación desde archivos Excel/CSV
- **Perfiles individuales**: Información personal y académica
- **Historial de puntos**: Detalle de todos los depósitos
- **Códigos QR**: Generación automática de identificadores únicos

### 🏫 Gestión de Cursos y Paralelos
- **Estructura académica**: Organización por niveles y paralelos
- **Asignación de estudiantes**: Vinculación automática
- **Estadísticas por curso**: Rendimiento grupal en reciclaje
- **Rankings comparativos**: Competencias entre cursos

### 👨‍🏫 Gestión de Docentes
- **Perfiles de docentes**: Información personal y profesional
- **Asignación de materias**: Vinculación con cursos
- **Asignación de puntos**: Capacidad de otorgar puntos adicionales
- **Reportes de clase**: Seguimiento del progreso estudiantil

### 🗑️ Gestión de Tipos de Basura
- **Categorías de residuos**: Papel, plástico, metal, biodegradable
- **Configuración de puntos**: Valor por tipo de residuo
- **Parámetros de sensores**: Calibración para detección automática
- **Estadísticas de clasificación**: Efectividad del sistema

### 📈 Sistema de Reportes
- **Reportes por período**: Diario, semanal, mensual, anual
- **Reportes por estudiante**: Historial individual detallado
- **Reportes por curso**: Rendimiento grupal
- **Reportes ambientales**: Impacto ecológico calculado

### 🔧 Panel de Administración
- **Gestión de usuarios**: Creación y modificación de cuentas
- **Configuración del sistema**: Parámetros generales
- **Monitoreo de basureros**: Estado en tiempo real
- **Logs del sistema**: Registro de eventos y errores

---

## ROLES DE USUARIO

### 👨‍💼 ADMINISTRADOR
**Responsabilidades:**
- Gestión completa del sistema
- Configuración de parámetros generales
- Supervisión de todos los módulos
- Generación de reportes ejecutivos

**Funcionalidades específicas:**
- ✅ Acceso total a todos los módulos
- ✅ Gestión de usuarios y roles
- ✅ Configuración de tipos de basura y puntos
- ✅ Monitoreo de basureros inteligentes
- ✅ Exportación de datos y reportes
- ✅ Gestión de períodos académicos
- ✅ Configuración de APIs y seguridad

**Dashboard del Administrador:**
- Estadísticas globales del sistema
- Monitoreo en tiempo real de basureros
- Alertas y notificaciones del sistema
- Acceso rápido a todas las funciones

### 👨‍🏫 DOCENTE
**Responsabilidades:**
- Supervisión del progreso de sus estudiantes
- Asignación de puntos adicionales por actividades
- Generación de reportes de clase
- Promoción del reciclaje en el aula

**Funcionalidades específicas:**
- ✅ Vista de estudiantes asignados
- ✅ Historial de puntos por estudiante
- ✅ Asignación manual de puntos (actividades especiales)
- ✅ Reportes de rendimiento por curso
- ✅ Estadísticas de sus materias
- ✅ Comunicación con administradores

**Dashboard del Docente:**
- Resumen de sus cursos y estudiantes
- Top 10 estudiantes de sus clases
- Gráficos de progreso por materia
- Herramientas de asignación de puntos

### 🎓 ESTUDIANTE
**Responsabilidades:**
- Participación activa en el reciclaje
- Uso correcto de los basureros inteligentes
- Seguimiento de su progreso personal
- Competencia saludable con compañeros

**Funcionalidades específicas:**
- ✅ Dashboard personal con estadísticas
- ✅ Historial completo de depósitos
- ✅ Ranking personal y por curso
- ✅ Visualización de puntos acumulados
- ✅ Progreso por tipo de residuo
- ✅ Comparación con compañeros

**Dashboard del Estudiante:**
- Puntos totales y por período
- Gráfico de progreso personal
- Ranking en su curso
- Historial de reciclaje
- Metas y logros

---

## SISTEMA DE BASUREROS INTELIGENTES

### 🤖 Componentes Hardware

#### Raspberry Pi 4
**Función:** Cerebro del sistema de basurero inteligente
**Responsabilidades:**
- Captura y procesamiento de códigos QR
- Comunicación con la API web
- Control del Arduino
- Procesamiento de imágenes
- Gestión de audio y LCD

**Especificaciones técnicas:**
- Procesador: ARM Cortex-A72 quad-core 1.5GHz
- RAM: 4GB LPDDR4
- Conectividad: WiFi 802.11ac, Bluetooth 5.0
- Puertos: USB 3.0, HDMI, GPIO

#### Arduino Uno
**Función:** Control de sensores y actuadores
**Responsabilidades:**
- Detección de tipos de residuo
- Control de servomotores para clasificación
- Gestión de LEDs indicadores
- Comunicación serial con Raspberry Pi

**Sensores integrados:**
- **Sensor capacitivo:** Detección de materiales orgánicos
- **Sensor inductivo:** Detección de metales
- **Sensor de presencia KY-032:** Detección de objetos
- **Sensor de reflexión TCRT:** Análisis de superficie

### 🔧 Software del Sistema IoT

#### Raspberry Pi (Python)
```python
# Funcionalidades principales:
- Captura de video en tiempo real
- Decodificación de códigos QR (PyZBar)
- Comunicación HTTP con API Laravel
- Control de pantalla LCD 20x4
- Síntesis de voz (gTTS)
- Gestión de cache para optimización
```

#### Arduino (C++)
```cpp
// Funcionalidades principales:
- Lectura de sensores analógicos y digitales
- Control de servomotores para clasificación
- Gestión de LEDs indicadores por tipo
- Comunicación serial con Raspberry Pi
- Estados de activación/desactivación
```

### 📡 Protocolo de Comunicación

#### Flujo de Operación:
1. **Detección QR:** Raspberry Pi detecta código QR del estudiante
2. **Verificación:** Consulta API para validar estudiante
3. **Activación:** Envía comando "ACTIVAR" al Arduino
4. **Detección:** Arduino analiza el residuo con sensores
5. **Clasificación:** Servomotores dirigen residuo al compartimento correcto
6. **Registro:** Raspberry Pi registra depósito via API
7. **Desactivación:** Sistema se resetea para próximo usuario

#### Comandos Serial (Raspberry ↔ Arduino):
- `ACTIVAR`: Habilita sensores para detección
- `DESACTIVAR`: Deshabilita sistema hasta próximo QR
- `STATUS`: Consulta estado actual del Arduino

#### Respuestas Arduino:
- `{"tipo":"papel"}`: Papel detectado
- `{"tipo":"plastico"}`: Plástico detectado
- `{"tipo":"metal"}`: Metal detectado
- `{"tipo":"biodegradable"}`: Material orgánico detectado
- `{"status":"completado"}`: Clasificación finalizada

### 🔒 Seguridad IoT
- **Autenticación API:** Header X-API-KEY para todas las peticiones
- **Validación de datos:** Verificación de integridad en comunicaciones
- **Logs detallados:** Registro completo de eventos para auditoría
- **Reconexión automática:** Manejo de fallos de conectividad

---

## BASE DE DATOS

### 📊 Estructura Principal

#### Tabla: `users`
```sql
- id (PK): Identificador único
- nombres: Nombres del usuario
- primerApellido: Primer apellido
- segundoApellido: Segundo apellido
- email: Correo electrónico
- password: Contraseña encriptada
- role: Rol (admin, docente, estudiante)
- qr_code: Código QR único
- puntos_totales: Puntos acumulados
- created_at, updated_at: Timestamps
```

#### Tabla: `cursos`
```sql
- idCurso (PK): Identificador único
- nombre: Nombre del curso
- nivel: Nivel académico
- created_at, updated_at: Timestamps
```

#### Tabla: `paralelos`
```sql
- idParalelo (PK): Identificador único
- nombre: Nombre del paralelo (A, B, C)
- idCurso (FK): Referencia al curso
- created_at, updated_at: Timestamps
```

#### Tabla: `tipos_basura`
```sql
- idTipoBasura (PK): Identificador único
- nombre: Tipo de residuo
- puntos: Puntos asignados por depósito
- descripcion: Descripción del tipo
- created_at, updated_at: Timestamps
```

#### Tabla: `depositos`
```sql
- idDeposito (PK): Identificador único
- idUsuario (FK): Usuario que realizó el depósito
- idTipoBasura (FK): Tipo de residuo depositado
- fechaHora: Timestamp del depósito
- puntos_asignados: Puntos otorgados
- created_at, updated_at: Timestamps
```

#### Tabla: `raspberry_events`
```sql
- id (PK): Identificador único
- qr_codigo: Código QR escaneado
- user_id (FK): Usuario asociado
- tipo_basura_id (FK): Tipo de basura detectado
- deposito_id (FK): Depósito generado
- status: Estado (pending, success, failed)
- message: Mensaje del evento
- ip: Dirección IP del dispositivo
- meta: Datos adicionales (JSON)
- created_at, processed_at: Timestamps
```

### 🔗 Relaciones
- **Usuario → Depósitos:** Un usuario puede tener múltiples depósitos
- **Curso → Paralelos:** Un curso puede tener múltiples paralelos
- **Usuario → Curso/Paralelo:** Relación many-to-many para asignaciones
- **TipoBasura → Depósitos:** Un tipo puede tener múltiples depósitos
- **RaspberryEvent → Usuario/Depósito:** Trazabilidad completa

---

## APIS Y ENDPOINTS

### 🔐 Autenticación
```
POST /api/login
POST /api/logout
POST /api/register
```

### 👥 Gestión de Usuarios
```
GET    /api/users              # Listar usuarios
POST   /api/users              # Crear usuario
GET    /api/users/{id}         # Obtener usuario
PUT    /api/users/{id}         # Actualizar usuario
DELETE /api/users/{id}         # Eliminar usuario
POST   /api/users/import       # Importar usuarios masivamente
```

### 🎓 Gestión Académica
```
GET    /api/cursos             # Listar cursos
POST   /api/cursos             # Crear curso
GET    /api/paralelos          # Listar paralelos
POST   /api/paralelos          # Crear paralelo
```

### 🗑️ Gestión de Residuos
```
GET    /api/tipos-basura       # Listar tipos de basura
POST   /api/tipos-basura       # Crear tipo de basura
PUT    /api/tipos-basura/{id}  # Actualizar tipo
```

### 📊 Depósitos y Puntos
```
GET    /api/depositos          # Listar depósitos
POST   /api/depositos          # Registrar depósito
GET    /api/depositos/usuario/{id} # Depósitos por usuario
GET    /api/puntos/ranking     # Ranking de puntos
```

### 🤖 APIs para Raspberry Pi
```
POST   /api/raspberry/deposito           # Registrar depósito desde IoT
GET    /api/raspberry/verificar/{qr}     # Verificar estudiante
GET    /api/raspberry/eventos            # Obtener eventos del sistema
```

### 📈 Reportes
```
GET    /api/reportes/estudiante/{id}     # Reporte individual
GET    /api/reportes/curso/{id}          # Reporte por curso
GET    /api/reportes/periodo             # Reporte por período
GET    /api/reportes/export              # Exportar datos
```

---

## INSTALACIÓN Y CONFIGURACIÓN

### 🖥️ Requisitos del Sistema

#### Servidor Web
- **PHP:** 8.2 o superior
- **Composer:** Gestor de dependencias PHP
- **Node.js:** 18.0 o superior
- **NPM:** Gestor de paquetes Node.js
- **MySQL:** 8.0 o superior
- **Apache/Nginx:** Servidor web

#### Hardware IoT
- **Raspberry Pi 4:** Con Raspbian OS
- **Arduino Uno:** Con IDE Arduino
- **Cámara USB:** Compatible con OpenCV
- **Pantalla LCD 20x4:** Con interfaz I2C
- **Sensores:** Capacitivo, inductivo, presencia, reflexión
- **Servomotores:** Para clasificación de residuos

### ⚙️ Configuración del Backend

#### 1. Clonar repositorio
```bash
git clone [repositorio]
cd SistemaDePuntosDarioMontano
```

#### 2. Instalar dependencias PHP
```bash
composer install
```

#### 3. Configurar entorno
```bash
cp .env.example .env
php artisan key:generate
```

#### 4. Configurar base de datos
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_puntos
DB_USERNAME=root
DB_PASSWORD=
```

#### 5. Ejecutar migraciones
```bash
php artisan migrate
php artisan db:seed
```

### 🎨 Configuración del Frontend

#### 1. Instalar dependencias Node.js
```bash
npm install
```

#### 2. Compilar assets
```bash
npm run dev          # Desarrollo
npm run build        # Producción
```

### 🤖 Configuración Raspberry Pi

#### 1. Instalar dependencias Python
```bash
pip3 install opencv-python pyzbar requests gtts pygame
```

#### 2. Configurar API
```python
API_CONFIG = {
    'base_url': 'http://192.168.1.100:8000',
    'api_key': 'tu_api_key_aqui',
    'timeout': 10,
}
```

#### 3. Ejecutar sistema
```bash
python3 raspberry_sistema_reciclaje.py
```

### 🔧 Configuración Arduino

#### 1. Cargar sketch
```cpp
// Abrir sketch_oct1a.ino en Arduino IDE
// Verificar y cargar al Arduino Uno
```

#### 2. Conectar sensores según esquema
- Pin A0: Sensor TCRT
- Pin 2: Sensor capacitivo
- Pin 3: Sensor inductivo
- Pin 4: Sensor KY-032
- Pines 5-8: Servomotores
- Pines 9-12: LEDs indicadores

---

## CONCLUSIONES

El **Sistema de Puntos para Reciclaje Inteligente** representa una solución integral que combina tecnología web moderna con dispositivos IoT para crear un ecosistema completo de gestión de reciclaje. La aplicación no solo automatiza el proceso de clasificación de residuos, sino que también gamifica la experiencia para motivar la participación activa de la comunidad educativa.

### Beneficios Principales:
- **Automatización completa** del proceso de reciclaje
- **Gamificación efectiva** que motiva la participación
- **Monitoreo en tiempo real** de todas las actividades
- **Reportes detallados** para toma de decisiones
- **Escalabilidad** para múltiples instituciones
- **Impacto ambiental positivo** medible y cuantificable

### Tecnologías Clave:
La combinación de **Laravel + Vue.js** para la aplicación web, junto con **Raspberry Pi + Arduino** para el hardware IoT, proporciona una base sólida, escalable y mantenible para el sistema completo.

---

**Desarrollado por:** Dario Montano  
**Versión:** 1.0  
**Fecha:** Octubre 2025  
**Tecnologías:** Laravel 11, Vue.js 3, Raspberry Pi 4, Arduino Uno
