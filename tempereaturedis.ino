#include <SoftwareSerial.h>
#include <OneWire.h>
#include <DallasTemperature.h>

#define ONE_WIRE_BUS 2
#define ECHO_PIN 4
#define TRIG_PIN 3
#define BUZZER_PIN 8

#define BT_RX 7
#define BT_TX 6

int buzzerState = 0;

SoftwareSerial BT(BT_RX, BT_TX);

OneWire oneWire(ONE_WIRE_BUS);
DallasTemperature sensors(&oneWire);
bool lastBuzzerState = false;




void setup() {
  Serial.begin(9600);
  BT.begin(9600);

  pinMode(LED_BUILTIN, OUTPUT);
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(BUZZER_PIN, OUTPUT);

  digitalWrite(BUZZER_PIN, LOW);

  sensors.begin();
}

float readDistanceCM() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);

  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);

  digitalWrite(TRIG_PIN, LOW);

  long duration = pulseIn(ECHO_PIN, HIGH, 30000);

  if (duration == 0) {
    return 999;
  }

  return duration * 0.034 / 2.0;
}

void loop() {

  float distance = readDistanceCM();
  bool isNearby = distance < 5.0;

  digitalWrite(LED_BUILTIN, isNearby);

  int buzzerTrigger = 0;

  if (isNearby) {

    tone(BUZZER_PIN, 1500);
    delay(80);
    tone(BUZZER_PIN, 500);
    delay(80);

    // Détection OFF → ON
    if (!lastBuzzerState) {
      buzzerTrigger = 1;
    }

    lastBuzzerState = true;

  } 
  else {

    noTone(BUZZER_PIN);
    digitalWrite(BUZZER_PIN, LOW);

    lastBuzzerState = false;
  }

  sensors.requestTemperatures();
  float tempC = sensors.getTempCByIndex(0);

  Serial.print(tempC);
  Serial.print(",");
  Serial.print(distance);
  Serial.print(",");
  Serial.println(buzzerTrigger);

  BT.print(tempC);
  BT.print(",");
  BT.print(distance);
  BT.print(",");
  BT.println(buzzerTrigger);

  delay(300);
}
