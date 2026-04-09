#include <Servo.h>
#include <OneWire.h>
#include <DallasTemperature.h>

//leds
const int ledVerte = 12;
const int ledRouge = 13;

//Buzzer
const int buzzerPin = 8;
//config du capteur temp
const int oneWireBus = 2;
OneWire oneWire(oneWireBus);
DallasTemperature sensors(&oneWire);



Servo monServo;

const int startButton = 10;
const int mode2Button = 11;


float temperature = -127;
unsigned long lastTempRead = 0;
const unsigned long tempInterval = 10000;

const int trigPin = 3;
const int echoPin = 4;

int mode = 1;

int position = 0;
int direction = 1;
const int stepSize = 2;

bool lastStartState = HIGH;
bool lastMode2State = HIGH;
unsigned long lastStartTime = 0;
unsigned long lastMode2Time = 0;
const unsigned long debounceDelay = 100;

bool objetDetecte = false;

float lireDistanceCM() {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);

  long duree = pulseIn(echoPin, HIGH, 20000);

  if (duree == 0) return -1;

  float distance = duree * 0.0343 / 2.0;

  if (distance < 2 || distance > 400) return -1;

  return distance;
}

//func de temp
float lireTemperatureC() {
  sensors.requestTemperatures();
  float tempC = sensors.getTempCByIndex(0);

  if (tempC == DEVICE_DISCONNECTED_C) {
    return -127;
  }

  return tempC;
}




void setup() {


  pinMode(ledVerte, OUTPUT);
  pinMode(ledRouge, OUTPUT);

  digitalWrite(ledVerte, LOW);
  digitalWrite(ledRouge, LOW);
  pinMode(startButton, INPUT_PULLUP);
  pinMode(mode2Button, INPUT_PULLUP);
  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);

  pinMode(buzzerPin, OUTPUT);
  digitalWrite(buzzerPin, LOW);

  sensors.begin();
  monServo.attach(5);
  monServo.write(0);
  temperature = lireTemperatureC();
  Serial.begin(9600);
  Serial.println("Systeme demarre en MODE 1 : SCAN NORMAL");
  Serial.println("BTN Red = MODE SCAN NORMAL");
  Serial.println("BTN Bleu = MODE SCAN INTELLIGENT");
  
}

void loop() {
  bool startState = digitalRead(startButton);
  bool mode2State = digitalRead(mode2Button);
  unsigned long now = millis();

  if (startState == LOW && lastStartState == HIGH && (now - lastStartTime) > debounceDelay) {
    digitalWrite(buzzerPin, LOW);
    lastStartTime = now;
    mode = 1;
    position = 0;
    direction = 1;
    objetDetecte = false;
    Serial.println("MODE 1 : SCAN NORMAL");
  }

  if (mode2State == LOW && lastMode2State == HIGH && (now - lastMode2Time) > debounceDelay) {
    digitalWrite(buzzerPin, LOW);
    lastMode2Time = now;
    mode = 2;
    objetDetecte = false;
    position = 0;
    direction = 1;
    Serial.println("MODE 2 : SCAN INTELLIGENT");
  }

  lastStartState = startState;
  lastMode2State = mode2State;


  if (millis() - lastTempRead >= tempInterval) {
    lastTempRead = millis();
    temperature = lireTemperatureC();
  }

  float distance = lireDistanceCM();




  if (mode == 1) {
    monServo.write(position);
    //verification obstacle 
    if (distance > 0 && distance <= 20) {
    digitalWrite(ledRouge, HIGH);
    digitalWrite(ledVerte, LOW);
    } 
    else {
        digitalWrite(ledRouge, LOW);
        digitalWrite(ledVerte, HIGH);
    }

    Serial.print("SCAN | Position: ");
    Serial.print(position);
    Serial.print(" deg | Distance: ");
    if (distance < 0) Serial.println("Hors portee");
    else {
      Serial.print(distance);
      Serial.println(" cm");
    }

    position += direction * stepSize;

    if (position >= 180) {
      position = 180;
      direction = -1;
    }
    if (position <= 0) {
      position = 0;
      direction = 1;
    }

    delay(20);
  }

  else if (mode == 2) {
    if (!objetDetecte) {
      monServo.write(position);

      Serial.print("SCAN INTELLIGENT | Position: ");
      Serial.print(position);
      Serial.print(" deg | Distance: ");
      if (distance < 0) Serial.println("Hors portee");
      else {
        Serial.print(distance);
        Serial.println(" cm");
      }

      if (distance > 0 && distance <= 20) {
        digitalWrite(ledRouge, HIGH);
        digitalWrite(ledVerte, LOW);
        digitalWrite(buzzerPin, HIGH);
        objetDetecte = true;
        Serial.print("CIBLE VERROUILLEE | Angle: ");
        Serial.print(position);
        Serial.print(" deg | Distance: ");
        Serial.print(distance);
        Serial.println(" cm");
      } else {
        digitalWrite(ledRouge, LOW);
        digitalWrite(ledVerte, HIGH);
        position += direction * stepSize;

        if (position >= 180) {
          position = 180;
          direction = -1;
        }
        if (position <= 0) {
          position = 0;
          direction = 1;
        }

        delay(20);
      }
    } else {
      Serial.print("CIBLE VERROUILLEE | Angle: ");
      Serial.print(position);
      Serial.print(" deg | Distance: ");
      if (distance < 0) Serial.println("Hors portee");
      else {
        Serial.print(distance);
        Serial.println(" cm");
      }

      if (distance < 0 || distance > 20) {

        digitalWrite(ledRouge, LOW);
        digitalWrite(ledVerte, HIGH);
        digitalWrite(buzzerPin, LOW);
        objetDetecte = false;
        Serial.println("Cible perdue -> reprise scan");
      }


      delay(100);
    }

    
  }

  Serial.print(" | Temperature: ");
  if (temperature == -127) {
    Serial.println("Erreur");
  } else {
    Serial.print(temperature);
    Serial.println(" C");
  }
}