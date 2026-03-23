#include <Servo.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <SoftwareSerial.h>

#define SERVO_PIN 5
#define TRIG_PIN 3
#define ECHO_PIN 4
#define BUZZER_PIN 8
#define ONE_WIRE_BUS 2

#define BT_RX 7
#define BT_TX 6

Servo s;
SoftwareSerial BT(BT_RX, BT_TX);

OneWire oneWire(ONE_WIRE_BUS);
DallasTemperature sensors(&oneWire);

int angle = 20;
int stepAngle = 10;

float tempC = 0;

// timer température
unsigned long lastTempTime = 0;
unsigned long tempInterval = 2000;

// timer envoi Bluetooth
unsigned long lastSendTime = 0;
unsigned long sendInterval = 180;

// timer clignotement LED
unsigned long lastBlinkTime = 0;
bool ledState = false;
int blinkInterval = 100;

float readDistanceCM() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);

  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  long duration = pulseIn(ECHO_PIN, HIGH, 15000);

  if (duration == 0) return 999;

  float distance = duration * 0.034 / 2.0;

  if (distance < 2 || distance > 400) return 999;

  return distance;
}

void setup() {
  Serial.begin(9600);
  BT.begin(9600);

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(LED_BUILTIN, OUTPUT);

  digitalWrite(BUZZER_PIN, LOW);
  digitalWrite(LED_BUILTIN, LOW);

  sensors.begin();

  s.attach(SERVO_PIN);
  s.write(angle);

  delay(500);
}

void loop() {
  // Rotation servo
  s.write(angle);
  delay(150);

  // Lecture distance
  float distance = readDistanceCM();
  bool isNearby = (distance < 50.0);
  int alerte = isNearby ? 1 : 0;

  // LED + buzzer
  if (isNearby) {
    if (millis() - lastBlinkTime > blinkInterval) {
      ledState = !ledState;
      digitalWrite(LED_BUILTIN, ledState);
      lastBlinkTime = millis();
    }
    tone(BUZZER_PIN, 1200);
  } else {
    digitalWrite(LED_BUILTIN, LOW);
    ledState = false;
    noTone(BUZZER_PIN);
  }

  // Température toutes les 2 secondes
  if (millis() - lastTempTime > tempInterval) {
    sensors.requestTemperatures();
    tempC = sensors.getTempCByIndex(0);
    lastTempTime = millis();
  }

  // Envoi Bluetooth + Serial
  if (millis() - lastSendTime > sendInterval) {
    // format simple CSV
    BT.print(angle);
    BT.print(",");
    BT.print(distance);
    BT.print(",");
    BT.print(tempC);
    BT.print(",");
    BT.println(alerte);

    Serial.print(angle);
    Serial.print(",");
    Serial.print(distance);
    Serial.print(",");
    Serial.print(tempC);
    Serial.print(",");
    Serial.println(alerte);

    lastSendTime = millis();
  }

  //sevo move
  angle += stepAngle;

  if (angle >= 180) {
    angle = 180;
    stepAngle = -10;
  }

  if (angle <= 0) {
    angle = 0;
    stepAngle = 10;
  }
}
