#include "EEPROM.h"
#include <esp_now.h>
#include <esp_wifi.h>
#include <HTTPClient.h>
#include <WiFi.h>
#include <Arduino_JSON.h>

#define NUMSLAVES 20
#define LENGTH(x) (strlen(x) + 1)
#define EEPROM_SIZE 200
#define WiFi_RST 0
#define LEDPin 2

const long interval = 3000;
unsigned long RSTMillis = 0;
unsigned long lastRequest = 0;
unsigned long currMillis = 0;

String ssid = "";
String password = "";
const String actionGET = "door_status";
const String actionUPD = "esp_update";
const String host = "http://34.88.36.48//action.php?action=";

int SlaveCount = 0;
int channel = 1;
bool isPause = false;

esp_now_peer_info_t slaves[NUMSLAVES] = {};

typedef struct struct_message {
    int doorNum;
    int doorStatus;
} struct_message;

struct_message outMsg;
struct_message inMsg;

void InitESPNow() {
  WiFi.disconnect();
  if (esp_now_init() == ESP_OK) {
    Serial.println("ESPNow Init Success");
  }
}

void ScanForSlave() {
  int16_t scanResults = WiFi.scanNetworks(false, false, false, 300, channel);
  memset(&slaves, 0, sizeof(slaves));
  SlaveCount = 0;
  for (int i = 0; i < scanResults; ++i) {
    String SSID = WiFi.SSID(i);
    delay(10);
    if (SSID.indexOf("*") == 0 && SSID.length() == 1) {
      int mac[6];
      String BSSIDstr = WiFi.BSSIDstr(i);
      if ( 6 == sscanf(BSSIDstr.c_str(), "%x:%x:%x:%x:%x:%x",  &mac[0], &mac[1], &mac[2], &mac[3], &mac[4], &mac[5] ) ) {
        for (int ii = 0; ii < 6; ++ii ) {
          slaves[SlaveCount].peer_addr[ii] = (uint8_t) mac[ii];
        }
      }
      slaves[SlaveCount].channel = channel; 
      slaves[SlaveCount].encrypt = 0;
      SlaveCount++;
    }
  }
  if (SlaveCount == 0) {
    Serial.println("No slave found.");
  }
  WiFi.scanDelete();
}

void pairSlave() {
    for (int i = 0; i < SlaveCount; i++) {
      if (slaves[i].channel == channel) {
        esp_err_t addStatus = esp_now_add_peer(&slaves[i]);
        Serial.println(addStatus == ESP_OK ? "Pair success" : "Pair fail");
      }
    }
}

void sendMsg() {
  for (int i = 0; i < SlaveCount; i++) {
    esp_now_send(slaves[i].peer_addr, (uint8_t *) &outMsg, sizeof(outMsg));
  }
}

void OnDataSent(const uint8_t *mac_addr, esp_now_send_status_t status) {
  Serial.println(status == ESP_NOW_SEND_SUCCESS ? "Send Success" : "Send Fail");
}

void OnDataRecv(const uint8_t *mac_addr, const uint8_t *data, int data_len) {
  memcpy(&inMsg, data, data_len);
  Serial.print("Door: "); Serial.print(inMsg.doorNum);
  Serial.print(" Status: "); Serial.println(inMsg.doorStatus);
  isPause = true;
  delay(20);
}

String getRequest() {
  HTTPClient http;
  String fullHost = host + actionGET;
  http.begin(fullHost);
  int httpResponseCode = http.GET();
  String payload = "{}";
  if (httpResponseCode > 0) { payload = http.getString(); }
  http.end();
  return payload;
}

void updRequest() {
  WiFiClient client;
  HTTPClient http;
  String fullHost = host + actionUPD + "&door=" + inMsg.doorNum + "&status=" + inMsg.doorStatus;
  http.begin(client, fullHost);
  int httpResponseCode = http.GET();
  Serial.print("UPDATE Response: "); Serial.println(httpResponseCode);
  http.end();
  lastRequest = millis();
  isPause = false;
}

void writeStringToFlash(const char* toStore, int startAddr) {
  int i = 0;
  for (; i < LENGTH(toStore); i++) {
    EEPROM.write(startAddr + i, toStore[i]);
  }
  EEPROM.write(startAddr + i, '\0');
  EEPROM.commit();
}


String readStringFromFlash(int startAddr) {
  char in[128]; 
  int i = 0;
  for (; i < 128; i++) {
    in[i] = EEPROM.read(startAddr + i);
  }
  return String(in);
}

void BlinkLED() {
  digitalWrite(LEDPin, 1);
  delay(1500);
  digitalWrite(LEDPin, 0);
  delay(1500);
}

void configAP() {
  const char *SSID = "Smart Door";
  bool result = WiFi.softAP(SSID, "SmartDoor", channel, 1);
  if (!result) {
    Serial.println("AP Config failed.");
  }
}

void setup() {
  Serial.begin(115200);
  pinMode(WiFi_RST, INPUT);
  pinMode(LEDPin, OUTPUT);
  
  if (!EEPROM.begin(EEPROM_SIZE)) { 
    Serial.println("Failed to init EEPROM");
    delay(1000);
  }
  else
  {
    ssid = readStringFromFlash(0);
    password = readStringFromFlash(40);
    Serial.println(ssid);
    Serial.println(password);
  }
  
  WiFi.mode(WIFI_AP_STA);
  configAP();
  
  InitESPNow();
  esp_now_register_send_cb(OnDataSent);
  esp_now_register_recv_cb(OnDataRecv);
  
  WiFi.begin(ssid.c_str(), password.c_str());
  delay(5000);
  if (WiFi.status() != WL_CONNECTED)
  {
    WiFi.beginSmartConfig();
    Serial.println("Waiting for SmartConfig.");
    while (!WiFi.smartConfigDone()) {
      Serial.print(".");
      BlinkLED();
      delay(500);
    }
    
    while (WiFi.status() != WL_CONNECTED) {
      Serial.print("."); 
      BlinkLED();
      delay(500);
    }
    Serial.print("Connected, IP Address: ");
    Serial.println(WiFi.localIP());
    ssid = WiFi.SSID();
    password = WiFi.psk(); 

    writeStringToFlash(ssid.c_str(), 0);
    writeStringToFlash(password.c_str(), 40);
  }
  else { Serial.print("IP: "); Serial.println(WiFi.localIP()); } 
  
  ScanForSlave();
  pairSlave();
}

void loop() {
  if (!SlaveCount) {
    ScanForSlave();
    pairSlave();
    delay(5000);
    return;
  }
  
  currMillis = RSTMillis = millis();
  while (digitalRead(WiFi_RST) == LOW) { }

  if (millis() - RSTMillis >= interval) {
    Serial.println("Reseting the WiFi credentials");
    writeStringToFlash("", 0);  
    writeStringToFlash("", 40); 
    delay(500);
    ESP.restart();
  }
  else {
    if(!isPause && currMillis - lastRequest >= interval) {
      if(WiFi.status() == WL_CONNECTED){ 
        String outputsState = getRequest();
        JSONVar myObject = JSON.parse(outputsState);
        if (JSON.typeof(myObject) == "undefined") {
          return;
        }

        JSONVar keys = myObject.keys();
        for (int i = 0; i < keys.length(); i++) {
          JSONVar value = myObject[keys[i]];
          Serial.print("GPIO: "); Serial.print(keys[i]);
          Serial.print(" - SET: "); Serial.println(value);
          outMsg.doorNum = atoi(keys[i]);
          outMsg.doorStatus = atoi(value);
          sendMsg();
        }
        lastRequest = currMillis;
      }
      else { Serial.println("WiFi Disconnected"); BlinkLED(); }
    }
    else if (isPause) { updRequest(); }
  }
  delay(500);
}
