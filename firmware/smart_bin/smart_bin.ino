#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <ESP32Servo.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

const char* ssid = "";
const char* password = "";

// Ganti IP ini ke IP laptop yang menjalankan Laravel.
const char* LARAVEL_STATUS_URL = "http://10.45.61.188:8000/api/bins/BIN-03/status";

WebServer server(80);

// ===== Konfigurasi LCD I2C =====
// Ganti alamat 0x27 kalau LCD kamu pakai alamat lain (misal 0x3F)
// Cek dengan I2C scanner kalau LCD tidak menyala/tidak muncul teks
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Pin I2C default ESP32 (SDA, SCL). Dibuat eksplisit biar jelas & tidak
// bergantung default board.
#define SDA_PIN 21
#define SCL_PIN 22

Servo servos[3];
const int servoPins[3] = {13, 12, 14};

// Index 0 = Organik, 1 = Anorganik, 2 = B3
const char* categoryNames[3] = {"Organik", "Anorganik", "B3"};
const char* categoryKeys[3] = {"organik", "anorganik", "b3"};

bool isOpen[3] = {false, false, false};
unsigned long openStartTime[3] = {0, 0, 0};

const unsigned long OPEN_DURATION = 5000;
const unsigned long POLLING_INTERVAL = 1500;

unsigned long lastPollingTime = 0;
unsigned long lastLcdServoUpdate = 0;

// ===== Info kompartemen penuh (dari Laravel) =====
// Dipakai buat nampilin bergantian di LCD kalau lebih dari satu tong penuh
struct FullInfo
{
  const char* name;
  int percent;
};

FullInfo fullList[3];
int fullCount = 0;
int fullRotateIndex = 0;
unsigned long lastFullRotate = 0;
const unsigned long FULL_ROTATE_INTERVAL = 2000; // ganti tampilan tiap 2 detik

// ===== Helper buat tampilkan 2 baris ke LCD =====
void lcdShow(String line1, String line2 = "")
{
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(line1.substring(0, 16));
  lcd.setCursor(0, 1);
  lcd.print(line2.substring(0, 16));
}

void startOpenServo(int index)
{
  servos[index].write(90);
  isOpen[index] = true;
  openStartTime[index] = millis();
  lcdShow(String(categoryNames[index]) + " Dibuka", "Menunggu...");
}

bool anyServoOpen()
{
  for (int i = 0; i < 3; i++)
  {
    if (isOpen[i])
    {
      return true;
    }
  }

  return false;
}

// ===== Tampilkan status "tong penuh" secara bergantian di LCD =====
// Dipanggil dari pollLaravel() (tiap update data) dan dari loop() (buat rotasi
// otomatis walau tidak sedang polling), supaya kalau ada >1 kompartemen penuh
// semuanya kebagian tampil bergiliran.
void showFullRotation()
{
  if (fullCount <= 0)
  {
    return;
  }

  fullRotateIndex = fullRotateIndex % fullCount;

  String line1 = "TONG PENUH!";
  if (fullCount > 1)
  {
    line1 += " (" + String(fullRotateIndex + 1) + "/" + String(fullCount) + ")";
  }

  String line2 = String(fullList[fullRotateIndex].name) + " " + String(fullList[fullRotateIndex].percent) + "%";

  lcdShow(line1, line2);

  fullRotateIndex++;
  lastFullRotate = millis();
}

void handleControl()
{
  String servo_id = server.arg("servo");
  int index = -1;

  // Dukung format servo1/2/3 maupun nama kategori langsung
  if (servo_id == "servo1" || servo_id == "organik") index = 0;
  else if (servo_id == "servo2" || servo_id == "anorganik") index = 1;
  else if (servo_id == "servo3" || servo_id == "b3") index = 2;

  if (index == -1)
  {
    server.send(400, "text/plain", "servo id tidak valid");
    return;
  }

  if (isOpen[index])
  {
    server.send(200, "text/plain", "Servo masih terbuka, coba lagi nanti");
    return;
  }

  startOpenServo(index);
  server.send(200, "text/plain", "OK");
}

void handleStatus()
{
  String response = "Status Servo:\n";

  for (int i = 0; i < 3; i++)
  {
    response += String(categoryNames[i]) + ": ";

    if (isOpen[i])
    {
      unsigned long elapsed = millis() - openStartTime[i];
      response += "TERBUKA, elapsed=" + String(elapsed) + "ms\n";
    }
    else
    {
      response += "tertutup\n";
    }
  }

  response += "Kompartemen penuh: ";
  if (fullCount == 0)
  {
    response += "tidak ada\n";
  }
  else
  {
    for (int i = 0; i < fullCount; i++)
    {
      response += String(fullList[i].name) + "(" + String(fullList[i].percent) + "%) ";
    }
    response += "\n";
  }

  response += "Uptime: " + String(millis()) + "ms\n";
  server.send(200, "text/plain", response);
}

void pollLaravel()
{
  if (WiFi.status() != WL_CONNECTED)
  {
    lcdShow("WiFi Putus", "Reconnecting...");
    WiFi.reconnect();
    return;
  }

  if (anyServoOpen())
  {
    return;
  }

  HTTPClient http;
  http.begin(LARAVEL_STATUS_URL);
  http.addHeader("Accept", "application/json");

  int httpCode = http.GET();

  if (httpCode == 200)
  {
    String payload = http.getString();

    DynamicJsonDocument doc(2048);
    DeserializationError error = deserializeJson(doc, payload);

    if (error)
    {
      lcdShow("JSON Parse Gagal", String(error.c_str()));
      http.end();
      return;
    }

    // ===== Baca status tiap kompartemen (organik/anorganik/b3) =====
    // Endpoint /status Laravel mengembalikan objek "compartments" berisi
    // capacity_percent & status ("empty"/"ok"/"near"/"full") per kategori.
    fullCount = 0;
    JsonObject compartments = doc["compartments"];
    if (!compartments.isNull())
    {
      for (int i = 0; i < 3; i++)
      {
        JsonObject comp = compartments[categoryKeys[i]];
        if (!comp.isNull())
        {
          const char* status = comp["status"] | "";
          if (strcmp(status, "full") == 0)
          {
            fullList[fullCount].name = categoryNames[i];
            fullList[fullCount].percent = comp["capacity_percent"] | 0;
            fullCount++;
          }
        }
      }
    }

    int triggerServo = doc["trigger_servo"] | 0;

    if (triggerServo == 0)
    {
      // Tidak ada perintah buka servo. Kalau ada kompartemen penuh,
      // tampilkan itu di LCD (bergantian kalau lebih dari satu).
      // Kalau tidak ada yang penuh, tampilkan status normal.
      if (fullCount > 0)
      {
        showFullRotation();
      }
      else
      {
        lcdShow("Status: OK", "Tidak ada cmd");
      }
    }
    else if (triggerServo >= 1 && triggerServo <= 3)
    {
      int index = triggerServo - 1;

      if (!isOpen[index])
      {
        lcdShow("Perintah Masuk", "Buka " + String(categoryNames[index]));
        startOpenServo(index);
      }
    }
    else
    {
      lcdShow("Trigger Invalid", "Val: " + String(triggerServo));
    }
  }
  else
  {
    lcdShow("HTTP Error", "Code: " + String(httpCode));
  }

  http.end();
}

void setup()
{
  Serial.begin(115200);
  delay(1000);

  // Beri jeda kecil sebelum inisialisasi I2C/LCD supaya tegangan
  // & bus I2C sudah stabil saat boot (mengatasi LCD blank sesaat)
  delay(200);
  Wire.begin(SDA_PIN, SCL_PIN);
  lcd.init();
  lcd.backlight();
  lcdShow("Smart Bin BIN-03", "Booting...");

  ESP32PWM::allocateTimer(0);
  ESP32PWM::allocateTimer(1);
  ESP32PWM::allocateTimer(2);
  ESP32PWM::allocateTimer(3);

  for (int i = 0; i < 3; i++)
  {
    servos[i].setPeriodHertz(50);
    servos[i].attach(servoPins[i], 500, 2400);
    servos[i].write(0);
  }

  WiFi.begin(ssid, password);
  WiFi.setSleep(false);

  lcdShow("Connecting WiFi", ssid);

  while (WiFi.status() != WL_CONNECTED)
  {
    delay(500);
  }

  lcdShow("WiFi Connected", WiFi.localIP().toString());
  delay(2000);

  server.on("/control", handleControl);
  server.on("/status", handleStatus);
  server.begin();

  lcdShow("Server Siap!", "Mode: Polling");
  delay(1500);
}

void loop()
{
  server.handleClient();

  unsigned long now = millis();

  if (now - lastPollingTime >= POLLING_INTERVAL)
  {
    lastPollingTime = now;
    pollLaravel();
  }

  // Rotasi otomatis antar kompartemen yang penuh, supaya LCD tetap
  // bergantian menampilkan semuanya walau belum waktunya polling lagi.
  if (!anyServoOpen() && fullCount > 1 && (now - lastFullRotate >= FULL_ROTATE_INTERVAL))
  {
    showFullRotation();
  }

  for (int i = 0; i < 3; i++)
  {
    if (isOpen[i])
    {
      unsigned long elapsed = millis() - openStartTime[i];

      if (millis() - lastLcdServoUpdate >= 500)
      {
        unsigned long sisa = (elapsed < OPEN_DURATION) ? (OPEN_DURATION - elapsed) : 0;
        lcdShow(String(categoryNames[i]) + " Terbuka", "Tutup dlm " + String(sisa) + "ms");
        lastLcdServoUpdate = millis();
      }

      if (elapsed >= OPEN_DURATION)
      {
        servos[i].write(0);
        isOpen[i] = false;
        lcdShow(String(categoryNames[i]) + " Ditutup", "Selesai");
        delay(1000);
      }
    }
  }
}
