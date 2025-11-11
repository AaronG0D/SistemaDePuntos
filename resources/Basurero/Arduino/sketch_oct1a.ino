#include <Servo.h>

// ---------------- PINES ----------------
Servo servoCamara;    
Servo servoMetal;     
Servo servoPapel;     
Servo servoPlastico;  

// Pines sensores
const int pinSensorCapacitivo = 7;  // Biodegradable
const int pinSensorInductivo = 8;   // Metal
const int trigPin = 2;
const int echoPin = 13;
const int pinTCRT = A0;

// Umbral para TCRT5000
const int umbralTCRT = 300;

// Variables para detección de papel (cambio en ultrasónico)
long distanciaBase = 0;
const int toleranciaCambio = 3;  // cm de variación para detectar papel
int lecturasSinCambio = 0;
const int lecturasParaBase = 5;  // lecturas estables para establecer base

// Pines LEDs
const int ledBio = 3;
const int ledMetal = 10;
const int ledPapel = 9;
const int ledPlastico = 4;

// ---------------- CONFIG ----------------
const int anguloAbiertoCamara = 0;
const int anguloCerradoCamara = 98;

const int anguloAbiertoMetal = 0;
const int anguloCerradoMetal = 110;

const int anguloAbiertoPapel = 0;
const int anguloCerradoPapel = 90;

const int anguloAbiertoGeneral = 90;  
const int duracionAbierto = 3500;     

bool habilitado = false;  

// ---------------- FUNCIONES ----------------

// Medir distancia ultrasónica
long medirDistancia() {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);
  long duracion = pulseIn(echoPin, HIGH, 25000);
  return duracion * 0.034 / 2;
}

void cicloServo(Servo &servo, int ledPin, int anguloApertura, int duracion = 5000, int anguloCierre = 0) {
  digitalWrite(ledPin, HIGH);
  servo.write(anguloApertura);
  delay(500);

  servoCamara.write(anguloAbiertoCamara);
  delay(duracion);

  servoCamara.write(anguloCerradoCamara);
  delay(500);
  servo.write(anguloCierre);
  digitalWrite(ledPin, LOW);
}

void cerrarServos() {
  servoCamara.write(anguloCerradoCamara);
  servoMetal.write(anguloCerradoMetal);
  servoPapel.write(anguloCerradoPapel);
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

  servoCamara.attach(11);
  servoMetal.attach(6);
  servoPapel.attach(5);
  servoPlastico.attach(12);

  pinMode(pinSensorCapacitivo, INPUT);
  pinMode(pinSensorInductivo, INPUT);
  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);

  pinMode(ledBio, OUTPUT);
  pinMode(ledMetal, OUTPUT);
  pinMode(ledPapel, OUTPUT);
  pinMode(ledPlastico, OUTPUT);

  cerrarServos();
  apagarLeds();

  Serial.println("♻️  SISTEMA DE CLASIFICACIÓN DE BASURA INICIADO ♻️");
  Serial.println("------------------------------------------------------------");
  Serial.println("| CAPACITIVO | INDUCTIVO | DIST(cm) |  TCRT  |  DETECTADO  |");
  Serial.println("------------------------------------------------------------");
}

// ---------------- LOOP ----------------
void loop() {
  // Control remoto (Raspberry / QR)
  if (Serial.available()) {
    String comando = Serial.readStringUntil('\n');
    comando.trim();
    if (comando == "ACTIVAR") {
      habilitado = true;
      Serial.println("✅ Arduino habilitado para recibir basura");
    } else if (comando == "DESACTIVAR") {
      habilitado = false;
      Serial.println("🔴 Arduino deshabilitado");
    }
  }

  if (!habilitado) return;

  // Leer sensores (invirtiendo lógica NPN)
  bool capacitivo = !digitalRead(pinSensorCapacitivo); 
  bool inductivo = !digitalRead(pinSensorInductivo);   
  int reflexion = analogRead(pinTCRT);
  long distancia = medirDistancia();
  bool objetoPresente = (distancia > 0 && distancia < 15);

  String tipo = "NINGUNO";

  // Actualizar distancia base para detectar cambios (papel)
  if (distanciaBase == 0) {
    distanciaBase = distancia;
  } else {
    if (abs(distancia - distanciaBase) <= toleranciaCambio) {
      lecturasSinCambio++;
      if (lecturasSinCambio >= lecturasParaBase) {
        distanciaBase = distancia;  // Actualizar base con lecturas estables
      }
    } else {
      lecturasSinCambio = 0;
    }
  }
  
  bool cambioDeSonico = (distanciaBase > 0) && (abs(distancia - distanciaBase) > toleranciaCambio);

  // ---------------- DETECCIÓN CON JERARQUÍA ----------------
  // 1️⃣ METAL - Prioridad máxima (inductivo activo)
  if (inductivo) {
    tipo = "METAL";
    Serial.println("🔩 METAL detectado (inductivo ON)");
    cicloServo(servoMetal, ledMetal, anguloAbiertoMetal, duracionAbierto, anguloCerradoMetal);
    habilitado = false;
    lecturasSinCambio = 0;
    distanciaBase = 0;
  }
  
  // 2️⃣ BIODEGRADABLE - Capacitivo ON + NO voluminoso (ultrasónico lejos)
  else if (capacitivo && !objetoPresente) {
    tipo = "BIODEGRADABLE";
    Serial.println("🍃 BIODEGRADABLE detectado (capacitivo ON, no voluminoso)");
    cicloServo(servoCamara, ledBio, anguloAbiertoCamara, 3000, anguloCerradoCamara);
    habilitado = false;
    lecturasSinCambio = 0;
    distanciaBase = 0;
  }
  
  // 3️⃣ PLÁSTICO - Capacitivo ON + voluminoso (ultrasónico cerca)
  else if (capacitivo && objetoPresente) {
    tipo = "PLASTICO";
    Serial.println("🧴 PLÁSTICO detectado (capacitivo ON, voluminoso)");
    cicloServo(servoPlastico, ledPlastico, anguloAbiertoGeneral, duracionAbierto, 0);
    habilitado = false;
    lecturasSinCambio = 0;
    distanciaBase = 0;
  }
  
  // 4️⃣ PAPEL - Capacitivo OFF + cambio en ultrasónico (no constante)
  else if (!capacitivo && cambioDeSonico) {
    tipo = "PAPEL";
    Serial.print("📝 PAPEL detectado (capacitivo OFF, distancia cambió de ");
    Serial.print(distanciaBase);
    Serial.print("cm a ");
    Serial.print(distancia);
    Serial.println("cm)");
    cicloServo(servoPapel, ledPapel, anguloAbiertoPapel, duracionAbierto, anguloCerradoPapel);
    habilitado = false;
    lecturasSinCambio = 0;
    distanciaBase = 0;
  }


  // ---------------- TABLA SERIAL ----------------
  Serial.print("|     ");
  Serial.print(capacitivo ? "ON " : "OFF");
  Serial.print("     |     ");
  Serial.print(inductivo ? "ON " : "OFF");
  Serial.print("     |     ");
  Serial.print(distancia);
  Serial.print("cm  |  ");
  Serial.print(reflexion);
  Serial.print("  |  ");
  Serial.print(tipo);
  Serial.println("  |");

  delay(800);
}