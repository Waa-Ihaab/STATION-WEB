#include <Servo.h>
#include <OneWire.h>
#include <DallasTemperature.h>

// ─── PINS ─────────────────────────────
#define TRIG_PIN 3
#define ECHO_PIN 4
#define SERVO_PIN 5
#define TEMP_PIN 2
#define BUTTON_START 10
#define BUTTON_MODE 11
#define BUZZER 8
#define LED_GREEN 12
#define LED_RED 13

// ─── OBJETS ───────────────────────────
Servo monServo;
OneWire oneWire(TEMP_PIN);
DallasTemperature sensors(&oneWire);

// ─── VARIABLES ────────────────────────
int angle = 0;
int stepSize = 2;
int mode = 1;
bool scanning = false;

unsigned long lastTempRead = 0;
const unsigned long tempInterval = 10000;
float temperature = 0;

// ─── SETUP ────────────────────────────
void setup() {
  Serial.begin(9600);

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(BUTTON_START, INPUT_PULLUP);
  pinMode(BUTTON_MODE, INPUT_PULLUP);
  pinMode(BUZZER, OUTPUT);
  pinMode(LED_GREEN, OUTPUT);
  pinMode(LED_RED, OUTPUT);

  monServo.attach(SERVO_PIN);
  sensors.begin();

  Serial.println("SYSTEM READY");
}

// ─── LIRE DISTANCE ────────────────────
String lireDistance() {
  long duree;
  float distance;

  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);

  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  duree = pulseIn(ECHO_PIN, HIGH, 20000);

  if (duree == 0) return "XXX";

  distance = duree * 0.0343 / 2.0;

  if (distance < 2 || distance > 400) return "XXX";

  return String(distance, 1);
}

// ─── LIRE TEMP ────────────────────────
void lireTemperature() {
  if (millis() - lastTempRead >= tempInterval) {
    sensors.requestTemperatures();
    temperature = sensors.getTempCByIndex(0);
    lastTempRead = millis();
  }
}

// ─── LOOP ─────────────────────────────
void loop() {

  // ─── BOUTON START ───
  if (digitalRead(BUTTON_START) == LOW) {
    scanning = !scanning;
    delay(300);
  }

  // ─── BOUTON MODE ───
  if (digitalRead(BUTTON_MODE) == LOW) {
    mode = (mode == 1) ? 2 : 1;
    delay(300);
  }

  lireTemperature();

  if (scanning) {

    monServo.write(angle);
    delay(20);

    String distance = lireDistance();

    // ─── MODE 1 : NORMAL ───
    if (mode == 1) {
      digitalWrite(BUZZER, LOW);

      if (distance != "XXX" && distance.toFloat() <= 20) {
        digitalWrite(LED_RED, HIGH);
        digitalWrite(LED_GREEN, LOW);
      } else {
        digitalWrite(LED_RED, LOW);
        digitalWrite(LED_GREEN, HIGH);
      }
    }

    // ─── MODE 2 : INTELLIGENT ───
    if (mode == 2) {
      if (distance != "XXX" && distance.toFloat() <= 20) {
        digitalWrite(BUZZER, HIGH);
        digitalWrite(LED_RED, HIGH);
        digitalWrite(LED_GREEN, LOW);
      } else {
        digitalWrite(BUZZER, LOW);
        digitalWrite(LED_RED, LOW);
        digitalWrite(LED_GREEN, HIGH);
      }
    }

    // ─── ENVOI SERIAL / BT ───
    Serial.print(mode);
    Serial.print(",");
    Serial.print(angle);
    Serial.print(",");
    Serial.print(distance);
    Serial.print(",");
    Serial.println(temperature);

    // ─── MOUVEMENT SERVO ───
    angle += stepSize;

    if (angle >= 180 || angle <= 0) {
      stepSize = -stepSize;
    }
  }
}
