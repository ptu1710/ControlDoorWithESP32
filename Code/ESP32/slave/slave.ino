#include <SPI.h>
#include <WiFi.h>
#include <MFRC522.h>
#include <esp_now.h>
#include <esp_wifi.h>

#define SS_PIN 4
#define MISO_PIN 12
#define MOSI_PIN 13
#define SCK_PIN 14
#define RST_PIN 15
MFRC522 mfrc522(SS_PIN, RST_PIN);

char* masterAddress = "EC:62:60:A1:B5:30";
esp_now_peer_info_t master;

unsigned long lastRead = 0; 
unsigned long lastOpen = 0;  
unsigned long timeRead = 2500;
unsigned long timeOpen = 8000;
const int DoorNum = 5;
int DoorStatus = 0;
int channel = 1;
bool isPause = false;

typedef struct struct_message {
    int doorNum;
    int doorStatus;
} struct_message;
struct_message outMsg;
struct_message inMsg;

void OnDataSent(const uint8_t *mac_addr, esp_now_send_status_t status) {
  Serial.println(status == ESP_NOW_SEND_SUCCESS ? "Send Success" : "Send Fail");
  delay(200);
}

void OnDataRecv(const uint8_t *mac_addr, const uint8_t *data, int data_len) {
  if (isPause) {
    return;
  }
  memcpy(&inMsg, data, data_len);
  if (inMsg.doorNum == DoorNum) {
    digitalWrite(LED_BUILTIN, inMsg.doorStatus);
    digitalWrite(inMsg.doorNum, inMsg.doorStatus);
    Serial.print("SET: "); Serial.println(inMsg.doorStatus);
  }
  delay(200);
}

void InitESPNow() {
  WiFi.disconnect();
  if (esp_now_init() == ESP_OK) { Serial.println("ESPNow Init Success"); }
}

void pairMaster() {
  int mac[6];
  if (6 == sscanf(masterAddress, "%x:%x:%x:%x:%x:%x", &mac[0], &mac[1], &mac[2], &mac[3], &mac[4], &mac[5])) {
    for (int ii = 0; ii < 6; ++ii ) {
      master.peer_addr[ii] = (uint8_t) mac[ii];
    }
  }
  
  esp_err_t addStatus = esp_now_add_peer(&master);
  if (addStatus == ESP_OK) { Serial.println("Pair success"); } 
  else { Serial.println("Pair fail"); }
}

void BlinkLED() {
  digitalWrite(LED_BUILTIN, 1);
  delay(1000);
  digitalWrite(LED_BUILTIN, 0);
  delay(1000);
}

void sendMsg() {
  const uint8_t *peer_addr = master.peer_addr;
  outMsg.doorNum = DoorNum;
  outMsg.doorStatus = DoorStatus;
  esp_now_send(peer_addr, (uint8_t *) &outMsg, sizeof(outMsg));
  delay(100);
}

void Authorized() {
  isPause = true;
  Serial.println("Authorized");
  DoorStatus = 1;
  digitalWrite(LED_BUILTIN, DoorStatus); 
  digitalWrite(DoorNum, DoorStatus);
  lastOpen = millis();
  sendMsg();
  delay(100);
}

void UnAuthorized() {
  Serial.println("Unauthorized");
  for (int i = 0; i < 3; i++) {
    digitalWrite(LED_BUILTIN, HIGH);
    delay(50);
    digitalWrite(LED_BUILTIN, LOW);
    delay(50);
  }
}

void configAP() {
  const char *SSID = "*";
  bool result = WiFi.softAP(SSID, "Slave_1_Password", channel, 0);
  if (!result) {
    Serial.println("AP Config failed.");
  }
}

void setup() {
  Serial.begin(115200);
  pinMode(DoorNum, OUTPUT);
  pinMode(LED_BUILTIN, OUTPUT);
  
  SPI.begin(SCK_PIN, MISO_PIN, MOSI_PIN, SS_PIN);
  mfrc522.PCD_Init();
  Serial.println("RFID OK!");
  
  WiFi.mode(WIFI_AP_STA);
  configAP();
  
  InitESPNow();
  esp_now_register_send_cb(OnDataSent);
  esp_now_register_recv_cb(OnDataRecv);
  
  pairMaster();
}

void loop() {
  unsigned long currMillis = millis();
  if (DoorStatus == 1 && (currMillis - lastOpen) > timeOpen) {
    isPause = false;
    DoorStatus = 0;
    digitalWrite(LED_BUILTIN, DoorStatus);
    digitalWrite(DoorNum, DoorStatus);
    sendMsg();
  }
  else if ((currMillis - lastRead) > timeRead) {
    if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) 
    {
      String content= "";
      for (byte i = 0; i < mfrc522.uid.size; i++) 
      {
         content.concat(String(mfrc522.uid.uidByte[i] < 0x10 ? " 0" : " "));
         content.concat(String(mfrc522.uid.uidByte[i], HEX));
      }
      if (content.substring(1) == "bd 94 fc 30") Authorized();
      else UnAuthorized(); 
      lastRead = currMillis;
    }
  }
  delay(500);
}
