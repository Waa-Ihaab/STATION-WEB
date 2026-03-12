#include <OneWire.h>
#include <DallasTemperature.h>

#define ONE_WIRE_BUS 2
#define ECHO_PIN 4
#define TRIG_PIN 3

OneWire oneWire(ONE_WIRE_BUS);
DallasTemperature sensors(&oneWire);

void setup() {
  Serial.begin(115200);
  pinMode(LED_BUILTIN, OUTPUT);
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  sensors.begin();
}

float readDistanceCM() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);
  int duration = pulseIn(ECHO_PIN, HIGH);
  return duration * 0.034 / 2;
}


int i = 0;



void loop() {
  float distance = readDistanceCM();

  bool isNearby = distance < 20;
  digitalWrite(LED_BUILTIN, isNearby);

  Serial.print("Measured distance: ");
  Serial.println(readDistanceCM());
  delay(100);


  i++;
  sensors.requestTemperatures();
  float tempC = sensors.getTempCByIndex(0);
  Serial.print(i);
  Serial.print(")=>");
  Serial.print("Temperature: ");
  Serial.print(tempC);
  Serial.println(" °C");
  delay(1000);


}
