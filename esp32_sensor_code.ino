// ============================================================
// SMART BIN IoT - ESP32 Sensor Code
// ============================================================
// Contoh code untuk mengirim data kapasitas 3 kompartemen ke Laravel Backend

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ========== CONFIG ==========
const char* SSID = "your_wifi_ssid";
const char* PASSWORD = "your_wifi_password";
const char* DEVICE_ID = "BIN-03";
const char* SERVER_URL = "http://192.168.1.100:8000/api/bins";

// Simulated sensor pins (ganti dengan pin yang sebenarnya)
const int SENSOR_ORGANIK = 34;
const int SENSOR_ANORGANIK = 35;
const int SENSOR_B3 = 32;
const int BATTERY_PIN = 33;

HTTPClient http;
WiFiClient client;

// ========== SETUP ==========
void setup() {
    Serial.begin(115200);
    delay(1000);
    
    // Connect to WiFi
    connectToWiFi();
    
    // Initialize sensor pins
    pinMode(SENSOR_ORGANIK, INPUT);
    pinMode(SENSOR_ANORGANIK, INPUT);
    pinMode(SENSOR_B3, INPUT);
    pinMode(BATTERY_PIN, INPUT);
}

// ========== MAIN LOOP ==========
void loop() {
    if (WiFi.status() == WL_CONNECTED) {
        // Read sensor values
        int organik_capacity = readSensor(SENSOR_ORGANIK);
        int anorganik_capacity = readSensor(SENSOR_ANORGANIK);
        int b3_capacity = readSensor(SENSOR_B3);
        int battery_level = readBatteryLevel(BATTERY_PIN);
        
        Serial.println("\n=== Sensor Data ===");
        Serial.print("Organik: ");
        Serial.print(organik_capacity);
        Serial.println("%");
        Serial.print("Anorganik: ");
        Serial.print(anorganik_capacity);
        Serial.println("%");
        Serial.print("B3: ");
        Serial.print(b3_capacity);
        Serial.println("%");
        Serial.print("Battery: ");
        Serial.print(battery_level);
        Serial.println("%");
        
        // Send individual request for each compartment
        sendSensorData(organik_capacity, anorganik_capacity, b3_capacity, battery_level);
        
        // Check status and receive alerts
        checkStatus();
    } else {
        Serial.println("WiFi not connected. Reconnecting...");
        connectToWiFi();
    }
    
    delay(10000); // Send data every 10 seconds
}

// ========== FUNCTIONS ==========

void connectToWiFi() {
    Serial.print("Connecting to WiFi: ");
    Serial.println(SSID);
    WiFi.mode(WIFI_STA);
    WiFi.begin(SSID, PASSWORD);
    
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500);
        Serial.print(".");
        attempts++;
    }
    
    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\nWiFi connected!");
        Serial.print("IP: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println("\nFailed to connect WiFi");
    }
}

int readSensor(int pin) {
    // Baca analog value dan convert ke percentage (0-100)
    int raw = analogRead(pin);
    int percentage = map(raw, 0, 4095, 0, 100);
    return constrain(percentage, 0, 100);
}

int readBatteryLevel(int pin) {
    // Baca battery voltage dan convert ke percentage
    int raw = analogRead(pin);
    // Adjust multiplier based on your battery voltage divider circuit
    float voltage = raw * (3.3 / 4095.0) * 2; // Assuming voltage divider
    int percentage = map(voltage * 100, 300, 420, 0, 100); // 3.0V - 4.2V untuk Li-ion
    return constrain(percentage, 0, 100);
}

void sendSensorData(int organik, int anorganik, int b3, int battery) {
    // ============ METHOD 1: Individual Update ============
    String url = String(SERVER_URL) + "/" + String(DEVICE_ID) + "/update";
    
    http.begin(client, url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    
    // Create JSON payload
    DynamicJsonDocument doc(256);
    doc["organik"] = organik;
    doc["anorganik"] = anorganik;
    doc["b3"] = b3;
    doc["battery"] = battery;
    
    String payload;
    serializeJson(doc, payload);
    
    Serial.println("\n=== Sending Request ===");
    Serial.print("URL: ");
    Serial.println(url);
    Serial.print("Payload: ");
    Serial.println(payload);
    
    int httpCode = http.POST(payload);
    
    Serial.print("Response Code: ");
    Serial.println(httpCode);
    
    if (httpCode == HTTP_CODE_OK || httpCode == HTTP_CODE_CREATED) {
        String response = http.getString();
        Serial.println("Response:");
        Serial.println(response);
        
        // Parse response
        DynamicJsonDocument responseDoc(512);
        deserializeJson(responseDoc, response);
        
        if (responseDoc["success"]) {
            Serial.println("✓ Data sent successfully!");
            
            // Check for alerts
            JsonArray alerts = responseDoc["alerts"];
            if (alerts.size() > 0) {
                Serial.println("⚠️ ALERTS:");
                for (JsonObject alert : alerts) {
                    String category = alert["category"];
                    String alertType = alert["alert_type"];
                    Serial.print("  - ");
                    Serial.print(category);
                    Serial.print(" is ");
                    Serial.println(alertType);
                    
                    // Send signal to compartment door
                    if (alertType == "full") {
                        closeCompartment(category);
                    }
                }
            }
        }
    } else {
        Serial.print("✗ Request failed. HTTP Code: ");
        Serial.println(httpCode);
    }
    
    http.end();
}

void checkStatus() {
    // Get current status dari server
    String url = String(SERVER_URL) + "/" + String(DEVICE_ID) + "/status";
    
    http.begin(client, url);
    http.addHeader("Accept", "application/json");
    
    int httpCode = http.GET();
    
    if (httpCode == HTTP_CODE_OK) {
        String response = http.getString();
        
        DynamicJsonDocument doc(512);
        deserializeJson(doc, response);
        
        if (doc["success"]) {
            Serial.println("\n=== Current Status ===");
            
            JsonObject compartments = doc["compartments"];
            
            // Check each compartment
            if (compartments.containsKey("organik")) {
                JsonObject org = compartments["organik"];
                Serial.print("Organik: ");
                Serial.print(org["capacity_percent"].as<int>());
                Serial.print("% - ");
                Serial.println(org["status"].as<const char*>());
                
                // Control door based on status
                if (org["can_accept"] == false) {
                    closeCompartment("organik");
                } else {
                    openCompartment("organik");
                }
            }
            
            if (compartments.containsKey("anorganik")) {
                JsonObject anorg = compartments["anorganik"];
                Serial.print("Anorganik: ");
                Serial.print(anorg["capacity_percent"].as<int>());
                Serial.print("% - ");
                Serial.println(anorg["status"].as<const char*>());
                
                if (anorg["can_accept"] == false) {
                    closeCompartment("anorganik");
                } else {
                    openCompartment("anorganik");
                }
            }
            
            if (compartments.containsKey("b3")) {
                JsonObject b3obj = compartments["b3"];
                Serial.print("B3: ");
                Serial.print(b3obj["capacity_percent"].as<int>());
                Serial.print("% - ");
                Serial.println(b3obj["status"].as<const char*>());
                
                if (b3obj["can_accept"] == false) {
                    closeCompartment("b3");
                } else {
                    openCompartment("b3");
                }
            }
        }
    }
    
    http.end();
}

void openCompartment(String category) {
    // Control relay/motor untuk buka kompartemen
    Serial.print("Opening compartment: ");
    Serial.println(category);
    // Example: digitalWrite(RELAY_PIN, HIGH);
}

void closeCompartment(String category) {
    // Control relay/motor untuk tutup kompartemen
    Serial.print("Closing compartment: ");
    Serial.println(category);
    // Example: digitalWrite(RELAY_PIN, LOW);
}

// ============ BONUS: Batch Update Method ============
/*
void sendBatchUpdate(int organik, int anorganik, int b3) {
    String url = String(SERVER_URL) + "/" + String(DEVICE_ID) + "/batch-update";
    
    http.begin(client, url);
    http.addHeader("Content-Type", "application/json");
    
    DynamicJsonDocument doc(512);
    JsonArray data = doc.createNestedArray("data");
    
    JsonObject org = data.createNestedObject();
    org["category"] = "organik";
    org["capacity"] = organik;
    
    JsonObject anorg = data.createNestedObject();
    anorg["category"] = "anorganik";
    anorg["capacity"] = anorganik;
    
    JsonObject b3obj = data.createNestedObject();
    b3obj["category"] = "b3";
    b3obj["capacity"] = b3;
    
    String payload;
    serializeJson(doc, payload);
    
    int httpCode = http.POST(payload);
    if (httpCode == HTTP_CODE_OK) {
        Serial.println("Batch update successful!");
    }
    
    http.end();
}
*/
