// --- sistema_reciclaje_v2.ino ---
// Sistema mejorado con control de compuerta y estados

#include <Servo.h>
#include <ArduinoJson.h>

// ============================================
// CONFIGURACIÓN DE PINES
// ============================================

// Servos
Servo servoCompuerta;  // Compuerta principal de entrada
Servo servoCamara;     // Cámara de retención
Servo servoMetal;      // Compartimento metal
Servo servoPapel;      // Compartimento papel
Servo servoPlastico;   // Compartimento plástico
Servo servoBio;        // Compartimento biodegradable

// Pines de servos
const int PIN_SERVO_COMPUERTA = 13;
const int PIN_SERVO_CAMARA = 11;
const int PIN_SERVO_METAL = 12;
const int PIN_SERVO_PAPEL = 5;
const int PIN_SERVO_PLASTICO = 6;
const int PIN_SERVO_BIO = 10;

// Pines sensores
const int PIN_SENSOR_CAPACITIVO = 7;
const int PIN_SENSOR_INDUCTIVO = 8;
const int PIN_SENSOR_PRESENCIA = 2;  // KY-032
const int PIN_SENSOR_REFLEXION = A0; // TCRT5000
const int PIN_SENSOR_ULTRASONICO_TRIG = A1;
const int PIN_SENSOR_ULTRASONICO_ECHO = A2;

// Pines LEDs indicadores
const int LED_VERDE = 3;    // Sistema listo
const int LED_AMARILLO = 4; // Procesando
const int LED_ROJO = 9;     // Error/Cerrado
const int LED_AZUL = A3;    // Depósito exitoso

// Pin buzzer
const int PIN_BUZZER = A4;

// ============================================
// CONFIGURACIÓN DE PARÁMETROS
// ============================================

// Ángulos de servos
const int ANGULO_CERRADO = 0;
const int ANGULO_ABIERTO = 90;
const int ANGULO_MEDIO = 45;

// Umbrales de sensores
const int UMBRAL_TCRT = 300;
const int DISTANCIA_DETECCION = 10; // cm

// Tiempos (ms)
const unsigned long TIMEOUT_DEPOSITO = 30000;    // 30 segundos
const unsigned long TIMEOUT_DETECCION = 10000;  // 10 segundos
const unsigned long DELAY_LECTURA = 100;         // 100ms entre lecturas
const unsigned long DELAY_SERVO = 1000;          // 1 segundo para movimiento de servo

// ============================================
// ESTADOS DEL SISTEMA
// ============================================

enum EstadoSistema {
  ESTADO_ESPERANDO,      // Esperando comando de Raspberry
  ESTADO_COMPUERTA_ABIERTA,  // Compuerta abierta, esperando depósito
  ESTADO_DETECTANDO,     // Detectando tipo de basura
  ESTADO_CLASIFICANDO,   // Moviendo basura al compartimento
  ESTADO_COMPLETADO,     // Proceso completado
  ESTADO_ERROR,          // Error en el sistema
  ESTADO_TIMEOUT         // Timeout
};

// ============================================
// VARIABLES GLOBALES
// ============================================

EstadoSistema estadoActual = ESTADO_ESPERANDO;
EstadoSistema estadoAnterior = ESTADO_ESPERANDO;

unsigned long tiempoInicioEstado = 0;
unsigned long tiempoUltimaLectura = 0;
unsigned long tiempoTimeout = 0;

bool sensoresActivos = false;
bool compuertaAbierta = false;
bool objetoDetectado = false;

String tipoBasuraDetectado = "";
float confianzaDeteccion = 0.0;

// Buffer para comunicación JSON
StaticJsonDocument<200> docEntrada;
StaticJsonDocument<200> docSalida;

// Contadores para mejorar detección
int contadorMetal = 0;
int contadorPapel = 0;
int contadorPlastico = 0;
int contadorBio = 0;
const int LECTURAS_CONFIRMACION = 3;

// ============================================
// FUNCIONES DE INICIALIZACIÓN
// ============================================

void setup() {
  Serial.begin(9600);
  
  // Inicializar servos
  servoCompuerta.attach(PIN_SERVO_COMPUERTA);
  servoCamara.attach(PIN_SERVO_CAMARA);
  servoMetal.attach(PIN_SERVO_METAL);
  servoPapel.attach(PIN_SERVO_PAPEL);
  servoPlastico.attach(PIN_SERVO_PLASTICO);
  servoBio.attach(PIN_SERVO_BIO);
  
  // Inicializar sensores
  pinMode(PIN_SENSOR_CAPACITIVO, INPUT);
  pinMode(PIN_SENSOR_INDUCTIVO, INPUT);
  pinMode(PIN_SENSOR_PRESENCIA, INPUT);
  pinMode(PIN_SENSOR_ULTRASONICO_TRIG, OUTPUT);
  pinMode(PIN_SENSOR_ULTRASONICO_ECHO, INPUT);
  
  // Inicializar LEDs
  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_AMARILLO, OUTPUT);
  pinMode(LED_ROJO, OUTPUT);
  pinMode(LED_AZUL, OUTPUT);
  
  // Inicializar buzzer
  pinMode(PIN_BUZZER, OUTPUT);
  
  // Estado inicial
  resetearSistema();
  
  enviarLog("Sistema iniciado correctamente");
}

// ============================================
// LOOP PRINCIPAL
// ============================================

void loop() {
  // Procesar comandos de Raspberry Pi
  if (Serial.available()) {
    procesarComando();
  }
  
  // Actualizar estado del sistema
  actualizarEstado();
  
  // Verificar timeouts
  verificarTimeout();
  
  // Pequeña pausa para no saturar
  delay(10);
}

// ============================================
// PROCESAMIENTO DE COMANDOS
// ============================================

void procesarComando() {
  String entrada = Serial.readStringUntil('\n');
  entrada.trim();
  
  // Intentar parsear JSON
  DeserializationError error = deserializeJson(docEntrada, entrada);
  
  if (error) {
    enviarError("Error parseando JSON: " + String(error.c_str()));
    return;
  }
  
  String comando = docEntrada["comando"];
  
  if (comando == "ABRIR_COMPUERTA") {
    comandoAbrirCompuerta();
  }
  else if (comando == "CERRAR_COMPUERTA") {
    comandoCerrarCompuerta();
  }
  else if (comando == "ACTIVAR_SENSORES") {
    comandoActivarSensores();
  }
  else if (comando == "DESACTIVAR_SENSORES") {
    comandoDesactivarSensores();
  }
  else if (comando == "RESET") {
    comandoReset();
  }
  else if (comando == "STATUS") {
    comandoStatus();
  }
  else if (comando == "LED_ON") {
    String led = docEntrada["led"];
    comandoLed(led, true);
  }
  else if (comando == "LED_OFF") {
    comandoLed("", false);
  }
  else if (comando == "BEEP") {
    int duracion = docEntrada["duracion"] | 200;
    comandoBeep(duracion);
  }
  else {
    enviarError("Comando desconocido: " + comando);
  }
}

// ============================================
// COMANDOS ESPECÍFICOS
// ============================================

void comandoAbrirCompuerta() {
  if (estadoActual != ESTADO_ESPERANDO) {
    enviarError("No se puede abrir compuerta en estado actual");
    return;
  }
  
  enviarLog("Abriendo compuerta principal");
  
  // Abrir compuerta principal
  servoCompuerta.write(ANGULO_ABIERTO);
  compuertaAbierta = true;
  
  // Feedback visual y sonoro
  digitalWrite(LED_VERDE, HIGH);
  digitalWrite(LED_ROJO, LOW);
  beep(200);
  
  // Cambiar estado
  cambiarEstado(ESTADO_COMPUERTA_ABIERTA);
  
  // Iniciar timeout
  tiempoTimeout = millis() + TIMEOUT_DEPOSITO;
  
  enviarEstado();
}

void comandoCerrarCompuerta() {
  enviarLog("Cerrando compuerta principal");
  
  // Cerrar compuerta
  servoCompuerta.write(ANGULO_CERRADO);
  compuertaAbierta = false;
  
  // Actualizar LEDs
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_ROJO, HIGH);
  
  // Si estábamos esperando depósito, volver a esperando
  if (estadoActual == ESTADO_COMPUERTA_ABIERTA) {
    cambiarEstado(ESTADO_ESPERANDO);
  }
  
  enviarEstado();
}

void comandoActivarSensores() {
  sensoresActivos = true;
  enviarLog("Sensores activados");
  digitalWrite(LED_AMARILLO, HIGH);
  
  // Si la compuerta está abierta, empezar a detectar
  if (compuertaAbierta) {
    cambiarEstado(ESTADO_DETECTANDO);
    tiempoTimeout = millis() + TIMEOUT_DETECCION;
  }
}

void comandoDesactivarSensores() {
  sensoresActivos = false;
  enviarLog("Sensores desactivados");
  digitalWrite(LED_AMARILLO, LOW);
}

void comandoReset() {
  resetearSistema();
  enviarLog("Sistema reseteado");
  enviarEstado();
}

void comandoStatus() {
  enviarEstado();
}

void comandoLed(String led, bool encender) {
  int pinLed = -1;
  
  if (led == "verde") pinLed = LED_VERDE;
  else if (led == "amarillo") pinLed = LED_AMARILLO;
  else if (led == "rojo") pinLed = LED_ROJO;
  else if (led == "azul") pinLed = LED_AZUL;
  
  if (pinLed != -1) {
    digitalWrite(pinLed, encender ? HIGH : LOW);
  } else if (!encender) {
    // Apagar todos
    digitalWrite(LED_VERDE, LOW);
    digitalWrite(LED_AMARILLO, LOW);
    digitalWrite(LED_ROJO, LOW);
    digitalWrite(LED_AZUL, LOW);
  }
}

void comandoBeep(int duracion) {
  beep(duracion);
}

// ============================================
// ACTUALIZACIÓN DE ESTADOS
// ============================================

void actualizarEstado() {
  unsigned long ahora = millis();
  
  switch (estadoActual) {
    case ESTADO_ESPERANDO:
      // Esperando comando de Raspberry
      digitalWrite(LED_ROJO, HIGH);
      digitalWrite(LED_VERDE, LOW);
      break;
      
    case ESTADO_COMPUERTA_ABIERTA:
      // Compuerta abierta, verificar si hay objeto
      if (detectarObjeto()) {
        if (sensoresActivos) {
          cambiarEstado(ESTADO_DETECTANDO);
          tiempoTimeout = millis() + TIMEOUT_DETECCION;
        }
      }
      break;
      
    case ESTADO_DETECTANDO:
      // Detectar tipo de basura
      if (ahora - tiempoUltimaLectura >= DELAY_LECTURA) {
        detectarTipoBasura();
        tiempoUltimaLectura = ahora;
        
        // Si tenemos detección confiable
        if (tipoBasuraDetectado != "" && confianzaDeteccion >= 0.7) {
          cambiarEstado(ESTADO_CLASIFICANDO);
        }
      }
      break;
      
    case ESTADO_CLASIFICANDO:
      // Clasificar basura
      clasificarBasura();
      cambiarEstado(ESTADO_COMPLETADO);
      break;
      
    case ESTADO_COMPLETADO:
      // Proceso completado, cerrar compuerta después de un delay
      delay(2000);
      comandoCerrarCompuerta();
      resetearDeteccion();
      cambiarEstado(ESTADO_ESPERANDO);
      break;
      
    case ESTADO_ERROR:
      // Estado de error, parpadear LED rojo
      digitalWrite(LED_ROJO, (millis() / 500) % 2);
      break;
      
    case ESTADO_TIMEOUT:
      // Timeout, cerrar todo y volver a esperando
      comandoCerrarCompuerta();
      resetearDeteccion();
      cambiarEstado(ESTADO_ESPERANDO);
      break;
  }
}

// ============================================
// DETECCIÓN DE BASURA MEJORADA
// ============================================

bool detectarObjeto() {
  // Usar sensor ultrasónico para detectar presencia
  long distancia = medirDistancia();
  return (distancia > 0 && distancia < DISTANCIA_DETECCION);
}

long medirDistancia() {
  digitalWrite(PIN_SENSOR_ULTRASONICO_TRIG, LOW);
  delayMicroseconds(2);
  digitalWrite(PIN_SENSOR_ULTRASONICO_TRIG, HIGH);
  delayMicroseconds(10);
  digitalWrite(PIN_SENSOR_ULTRASONICO_TRIG, LOW);
  
  long duracion = pulseIn(PIN_SENSOR_ULTRASONICO_ECHO, HIGH, 30000);
  long distancia = duracion * 0.034 / 2;
  
  return distancia;
}

void detectarTipoBasura() {
  // Leer todos los sensores
  bool capacitivo = digitalRead(PIN_SENSOR_CAPACITIVO);
  bool inductivo = digitalRead(PIN_SENSOR_INDUCTIVO);
  int presencia = digitalRead(PIN_SENSOR_PRESENCIA);
  int reflexion = analogRead(PIN_SENSOR_REFLEXION);
  
  // Resetear contadores si cambian las lecturas
  static bool ultimoCapacitivo = false;
  static bool ultimoInductivo = false;
  
  if (capacitivo != ultimoCapacitivo || inductivo != ultimoInductivo) {
    contadorMetal = 0;
    contadorPapel = 0;
    contadorPlastico = 0;
    contadorBio = 0;
  }
  
  ultimoCapacitivo = capacitivo;
  ultimoInductivo = inductivo;
  
  // Lógica mejorada de detección con contadores
  if (inductivo) {
    // Metal detectado por sensor inductivo
    contadorMetal++;
    if (contadorMetal >= LECTURAS_CONFIRMACION) {
      tipoBasuraDetectado = "metal";
      confianzaDeteccion = 0.95;
      enviarDeteccion();
    }
  }
  else if (capacitivo && !inductivo) {
    // Orgánico/Biodegradable - detectado por capacitivo pero no inductivo
    contadorBio++;
    if (contadorBio >= LECTURAS_CONFIRMACION) {
      tipoBasuraDetectado = "biodegradable";
      confianzaDeteccion = 0.85;
      enviarDeteccion();
    }
  }
  else if (!capacitivo && !inductivo) {
    // Papel o plástico - no conductivo
    if (reflexion > UMBRAL_TCRT && presencia == HIGH) {
      // Papel - alta reflexión
      contadorPapel++;
      if (contadorPapel >= LECTURAS_CONFIRMACION) {
        tipoBasuraDetectado = "papel";
        confianzaDeteccion = 0.80;
        enviarDeteccion();
      }
    }
    else if (reflexion <= UMBRAL_TCRT || presencia == LOW) {
      // Plástico - baja reflexión o transparente
      contadorPlastico++;
      if (contadorPlastico >= LECTURAS_CONFIRMACION) {
        tipoBasuraDetectado = "plastico";
        confianzaDeteccion = 0.75;
        enviarDeteccion();
      }
    }
  }
  
  // Enviar estado de sensores para debug
  if (millis() % 1000 == 0) {  // Cada segundo
    docSalida.clear();
    docSalida["sensores"]["capacitivo"] = capacitivo;
    docSalida["sensores"]["inductivo"] = inductivo;
    docSalida["sensores"]["presencia"] = presencia;
    docSalida["sensores"]["reflexion"] = reflexion;
    docSalida["contadores"]["metal"] = contadorMetal;
    docSalida["contadores"]["bio"] = contadorBio;
    docSalida["contadores"]["papel"] = contadorPapel;
    docSalida["contadores"]["plastico"] = contadorPlastico;
    serializeJson(docSalida, Serial);
    Serial.println();
  }
}

void clasificarBasura() {
  enviarLog("Clasificando: " + tipoBasuraDetectado);
  
  // Cerrar compuerta principal primero
  servoCompuerta.write(ANGULO_CERRADO);
  delay(500);
  
  // Abrir cámara de retención
  servoCamara.write(ANGULO_ABIERTO);
  delay(1000);
  
  // Abrir compartimento correspondiente
  Servo* servoDestino = nullptr;
  int ledDestino = LED_AZUL;
  
  if (tipoBasuraDetectado == "metal") {
    servoDestino = &servoMetal;
  }
  else if (tipoBasuraDetectado == "papel") {
    servoDestino = &servoPapel;
  }
  else if (tipoBasuraDetectado == "plastico") {
    servoDestino = &servoPlastico;
  }
  else if (tipoBasuraDetectado == "biodegradable") {
    servoDestino = &servoBio;
  }
  
  if (servoDestino != nullptr) {
    // Abrir compartimento destino
    servoDestino->write(ANGULO_ABIERTO);
    digitalWrite(ledDestino, HIGH);
    beep(500);
    delay(3000);  // Tiempo para que caiga la basura
    
    // Cerrar todo
    servoDestino->write(ANGULO_CERRADO);
    servoCamara.write(ANGULO_CERRADO);
    digitalWrite(ledDestino, LOW);
    
    // Notificar éxito
    for (int i = 0; i < 3; i++) {
      digitalWrite(LED_AZUL, HIGH);
      beep(100);
      delay(100);
      digitalWrite(LED_AZUL, LOW);
      delay(100);
    }
  }
}

// ============================================
// FUNCIONES DE UTILIDAD
// ============================================

void cambiarEstado(EstadoSistema nuevoEstado) {
  estadoAnterior = estadoActual;
  estadoActual = nuevoEstado;
  tiempoInicioEstado = millis();
  
  enviarLog("Estado: " + obtenerNombreEstado(estadoAnterior) + " -> " + obtenerNombreEstado(nuevoEstado));
}

String obtenerNombreEstado(EstadoSistema estado) {
  switch (estado) {
    case ESTADO_ESPERANDO: return "ESPERANDO";
    case ESTADO_COMPUERTA_ABIERTA: return "COMPUERTA_ABIERTA";
    case ESTADO_DETECTANDO: return "DETECTANDO";
    case ESTADO_CLASIFICANDO: return "CLASIFICANDO";
    case ESTADO_COMPLETADO: return "COMPLETADO";
    case ESTADO_ERROR: return "ERROR";
    case ESTADO_TIMEOUT: return "TIMEOUT";
    default: return "DESCONOCIDO";
  }
}

void verificarTimeout() {
  if (tiempoTimeout > 0 && millis() > tiempoTimeout) {
    switch (estadoActual) {
      case ESTADO_COMPUERTA_ABIERTA:
        enviarLog("Timeout: No se depositó basura");
        cambiarEstado(ESTADO_TIMEOUT);
        break;
        
      case ESTADO_DETECTANDO:
        enviarLog("Timeout: No se pudo detectar tipo de basura");
        cambiarEstado(ESTADO_TIMEOUT);
        break;
    }
    tiempoTimeout = 0;
  }
}

void resetearSistema() {
  // Cerrar todos los servos
  servoCompuerta.write(ANGULO_CERRADO);
  servoCamara.write(ANGULO_CERRADO);
  servoMetal.write(ANGULO_CERRADO);
  servoPapel.write(ANGULO_CERRADO);
  servoPlastico.write(ANGULO_CERRADO);
  servoBio.write(ANGULO_CERRADO);
  
  // Apagar LEDs
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_AMARILLO, LOW);
  digitalWrite(LED_ROJO, HIGH);
  digitalWrite(LED_AZUL, LOW);
  
  // Resetear variables
  estadoActual = ESTADO_ESPERANDO;
  estadoAnterior = ESTADO_ESPERANDO;
  compuertaAbierta = false;
  sensoresActivos = false;
  objetoDetectado = false;
  tiempoTimeout = 0;
  
  resetearDeteccion();
}

void resetearDeteccion() {
  tipoBasuraDetectado = "";
  confianzaDeteccion = 0.0;
  contadorMetal = 0;
  contadorPapel = 0;
  contadorPlastico = 0;
  contadorBio = 0;
}

void beep(int duracion) {
  digitalWrite(PIN_BUZZER, HIGH);
  delay(duracion);
  digitalWrite(PIN_BUZZER, LOW);
}

// ============================================
// COMUNICACIÓN JSON
// ============================================

void enviarDeteccion() {
  docSalida.clear();
  docSalida["tipo"] = tipoBasuraDetectado;
  docSalida["confianza"] = confianzaDeteccion;
  docSalida["timestamp"] = millis();
  serializeJson(docSalida, Serial);
  Serial.println();
}

void enviarEstado() {
  docSalida.clear();
  docSalida["estado"]["actual"] = obtenerNombreEstado(estadoActual);
  docSalida["estado"]["compuerta"] = compuertaAbierta ? "ABIERTA" : "CERRADA";
  docSalida["estado"]["sensores"] = sensoresActivos ? "ACTIVOS" : "INACTIVOS";
  docSalida["estado"]["tiempo_en_estado"] = millis() - tiempoInicioEstado;
  serializeJson(docSalida, Serial);
  Serial.println();
}

void enviarLog(String mensaje) {
  docSalida.clear();
  docSalida["log"] = mensaje;
  docSalida["timestamp"] = millis();
  serializeJson(docSalida, Serial);
  Serial.println();
}

void enviarError(String error) {
  docSalida.clear();
  docSalida["error"] = error;
  docSalida["timestamp"] = millis();
  serializeJson(docSalida, Serial);
  Serial.println();
}
