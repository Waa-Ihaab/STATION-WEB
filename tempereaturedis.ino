#include <OneWire.h>
#include <DallasTemperature.h>

#define ONE_WIRE_BUS 2
#define ECHO_PIN 4
#define TRIG_PIN 3
#define BUZZER_PIN 8

OneWire oneWire(ONE_WIRE_BUS);
DallasTemperature sensors(&oneWire);

void setup() {
  Serial.begin(9600);

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

  if (isNearby) {
    tone(BUZZER_PIN, 1000);
  } else {
    noTone(BUZZER_PIN);
    digitalWrite(BUZZER_PIN, LOW);
  }

  sensors.requestTemperatures();
  float tempC = sensors.getTempCByIndex(0);

  // affichage simple
  Serial.print(tempC);
  Serial.print(",");
  Serial.println(distance);

  delay(300);
}
