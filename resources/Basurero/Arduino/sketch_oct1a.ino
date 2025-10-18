#include <Servo.h>

// ---------------- PINES ----------------
// Servos
Servo servoCamara;    // retención
Servo servoMetal;     // metal
Servo servoPapel;     // papel
Servo servoPlastico;  // plástico

// Pines sensores
const int pinSensorCapacitivo = 7;
const int pinSensorInductivo = 8;
const int pinKY032 = 2;
const int pinTCRT = A0;

// Umbral para TCRT5000
const int umbralTCRT = 300;

// Pines LEDs indicadores
const int ledBio = 3;
const int ledMetal = 4;
const int ledPapel = 9;
const int ledPlastico = 10;

// ---------------- CONFIG ----------------
const int anguloAbierto = 90;       // Ángulo de apertura de servos
const int duracionAbierto = 7000;   // Tiempo que se mantiene abierto (ms)

// ---------------- VARIABLES ----------------
bool habilitado = false;  // Solo permite recibir basura cuando se active desde QR

// ---------------- FUNCIONES ----------------
void cicloServo(Servo &servo, int ledPin, int angulo = 90, int duracion = 5000) {
  digitalWrite(ledPin, HIGH);      // Enciende LED indicador
  servoCamara.write(angulo);       // Abre cámara
  servo.write(angulo);             // Abre compuerta

  delay(duracion);                  // Tiempo para depositar basura

  servoCamara.write(0);             // Cierra cámara
  servo.write(0);                   // Cierra compuerta
  digitalWrite(ledPin, LOW);        // Apaga LED
}

void cerrarServos() {
  servoCamara.write(0);
  servoMetal.write(0);
  servoPapel.write(0);
  servoPlastico.write(0);
}

void apagarLeds() {
  digitalWrite(ledBio, LOW);
  digitalWrite(ledMetal, LOW);
  digitalWrite(ledPapel, LOW);
  digitalWrite(ledPlastico, LOW);
}

// ---------------- SETUP ----------------
void setup() {
  Serial.begin(9600);

  // Inicializar servos
  servoCamara.attach(11);
  servoMetal.attach(12);
  servoPapel.attach(5);
  servoPlastico.attach(6);

  // Inicializar sensores
  pinMode(pinSensorCapacitivo, INPUT);
  pinMode(pinSensorInductivo, INPUT);
  pinMode(pinKY032, INPUT);

  // Inicializar LEDs
  pinMode(ledBio, OUTPUT);
  pinMode(ledMetal, OUTPUT);
  pinMode(ledPapel, OUTPUT);
  pinMode(ledPlastico, OUTPUT);

  // Posiciones iniciales
  cerrarServos();
  apagarLeds();
}

// ---------------- LOOP ----------------
void loop() {
  // Revisar si llegó un comando Serial desde la Raspberry
  if (Serial.available()) {
    String comando = Serial.readStringUntil('\n');
    comando.trim(); // eliminar espacios y saltos de línea
    if (comando == "ACTIVAR") {
      habilitado = true;
      Serial.println("✅ Arduino habilitado para recibir basura");
      Serial.println("{\"status\":\"activado\"}");
    } else if (comando == "DESACTIVAR") {
      habilitado = false;
      Serial.println("🔴 Arduino deshabilitado");
      Serial.println("{\"status\":\"desactivado\"}");
    } else if (comando == "STATUS") {
      // Comando para verificar estado del Arduino
      Serial.println(habilitado ? "{\"status\":\"habilitado\"}" : "{\"status\":\"deshabilitado\"}");
    }
  }

  // Si no está habilitado, no hacer nada
  if (!habilitado) {
    return;
  }

  // Leer sensores
  bool capacitivo = digitalRead(pinSensorCapacitivo);
  bool inductivo = digitalRead(pinSensorInductivo);
  int presencia = digitalRead(pinKY032); // LOW = objeto detectado
  int reflexion = analogRead(pinTCRT);

  // Retener basura en la cámara
  servoCamara.write(0);
  delay(500);

  if (capacitivo && inductivo) {
    // Biodegradable
    Serial.println("🍃 BIODEGRADABLE detectado");
    Serial.println("{\"tipo\":\"biodegradable\"}");
    cicloServo(servoCamara, ledBio, anguloAbierto, 3000);
    habilitado = false; // desactivar hasta próximo QR
    Serial.println("{\"status\":\"completado\",\"tipo\":\"biodegradable\"}");
  } 
  else if (capacitivo) {
    // Metal
    Serial.println("🔩 METAL detectado");
    Serial.println("{\"tipo\":\"metal\"}");
    cicloServo(servoMetal, ledMetal, anguloAbierto, duracionAbierto);
    habilitado = false;
    Serial.println("{\"status\":\"completado\",\"tipo\":\"metal\"}");
  } 
  else {
    // Papel o plástico
    if (presencia == HIGH && reflexion > umbralTCRT) {
      Serial.println("📝 PAPEL detectado");
      Serial.println("{\"tipo\":\"papel\"}");
      cicloServo(servoPapel, ledPapel, anguloAbierto, duracionAbierto);
      habilitado = false;
      Serial.println("{\"status\":\"completado\",\"tipo\":\"papel\"}");
    } 
    else if (presencia == LOW && reflexion > umbralTCRT) {
      Serial.println("🧴 PLÁSTICO detectado");
      Serial.println("{\"tipo\":\"plastico\"}");
      cicloServo(servoPlastico, ledPlastico, anguloAbierto, duracionAbierto);
      habilitado = false;
      Serial.println("{\"status\":\"completado\",\"tipo\":\"plastico\"}");
    } 
    else {
      Serial.println("Esperando objeto...");
      cerrarServos();
      apagarLeds();
    }
  }

  delay(500); // retardo entre lecturas
}
