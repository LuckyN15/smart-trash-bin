# SMART BIN IoT - API Documentation

## API Endpoints

### Base URL
```
http://localhost:8000/api/bins
```

---

## 1. Update Compartment Kapasitas (Single Request)
**Endpoint:** `POST /api/bins/{device_id}/update`

### Request Format
```json
{
  "organik": 85,
  "anorganik": 45,
  "b3": 30,
  "battery": 78,
  "temperature": 31,
  "humidity": 68
}
```

### cURL Example
```bash
curl -X POST http://localhost:8000/api/bins/BIN-03/update \
  -H "Content-Type: application/json" \
  -d '{
    "organik": 85,
    "anorganik": 45,
    "b3": 30,
    "battery": 78
  }'
```

### Response (Success)
```json
{
  "success": true,
  "message": "Data berhasil diupdate",
  "device_id": "BIN-03",
  "battery_level": 78,
  "alerts": [
    {
      "category": "organik",
      "capacity": 85,
      "alert_type": "near"
    }
  ],
  "compartments": {
    "organik": 85,
    "anorganik": 45,
    "b3": 30
  }
}
```

---

## 2. Get Bin Status (Check Current State)
**Endpoint:** `GET /api/bins/{device_id}/status`

### cURL Example
```bash
curl -X GET http://localhost:8000/api/bins/BIN-03/status \
  -H "Accept: application/json"
```

### Response
```json
{
  "success": true,
  "device_id": "BIN-03",
  "name": "Pasar Baru Lt.1",
  "location": "Pasar Baru Lt.1",
  "battery_level": 78,
  "online_status": "online",
  "compartments": {
    "organik": {
      "capacity_percent": 85,
      "status": "near",
      "can_accept": true
    },
    "anorganik": {
      "capacity_percent": 45,
      "status": "ok",
      "can_accept": true
    },
    "b3": {
      "capacity_percent": 30,
      "status": "ok",
      "can_accept": true
    }
  },
  "last_reported_at": "2026-06-19T14:30:00Z"
}
```

**Gunakan respons ini untuk:**
- ESP32 tahu apakah harus membuka/tutup setiap kompartemen
- `can_accept: false` = Tutup kompartemen (sudah penuh)
- `can_accept: true` = Buka kompartemen (masih bisa menerima sampah)

---

## 3. Batch Update (Multiple Compartments Sekaligus)
**Endpoint:** `POST /api/bins/{device_id}/batch-update`

### Request Format
```json
{
  "data": [
    {
      "category": "organik",
      "capacity": 85
    },
    {
      "category": "anorganik",
      "capacity": 45
    },
    {
      "category": "b3",
      "capacity": 30
    }
  ]
}
```

### cURL Example
```bash
curl -X POST http://localhost:8000/api/bins/BIN-03/batch-update \
  -H "Content-Type: application/json" \
  -d '{
    "data": [
      {"category": "organik", "capacity": 85},
      {"category": "anorganik", "capacity": 45},
      {"category": "b3", "capacity": 30}
    ]
  }'
```

### Response
```json
{
  "success": true,
  "message": "Batch update berhasil",
  "alerts_count": 1,
  "alerts": [
    {
      "category": "organik",
      "status": "near"
    }
  ]
}
```

---

## Kapasitas Status Mapping

| Kapasitas (%) | Status | Aksi ESP32 |
|---|---|---|
| 0-30 | empty | Buka kompartemen |
| 31-69 | ok | Buka kompartemen |
| 70-89 | near | Buka (warning) |
| 90-100 | full | **TUTUP kompartemen** |

---

## Example Workflow

### Skenario: Organik sudah penuh (92%)

**1. ESP32 kirim data:**
```json
{
  "organik": 92,
  "anorganik": 45,
  "b3": 20,
  "battery": 85
}
```

**2. Server merespon:**
```json
{
  "success": true,
  "alerts": [
    {
      "category": "organik",
      "capacity": 92,
      "alert_type": "full"
    }
  ]
}
```

**3. ESP32 menerima alert:**
- Organik status = "full"
- Tutup motor/relay kompartemen organik
- Tetap buka kompartemen anorganik & B3

**4. Dashboard Laravel menampilkan:**
- Kartu "Sampah Organik": 92% 🔴 PENUH
- Kartu "Sampah Anorganik": 45% 🟢 OK
- Kartu "Sampah B3": 20% 🟢 OK
- Notifikasi: "BIN-03 Kompartemen Organik Penuh"

---

## Device ID List (Contoh)

```
BIN-01 - Taman Monumen
BIN-03 - Pasar Baru Lt.1
BIN-07 - Taman Monumen
BIN-09 - Taman Kota Barat
BIN-12 - Jl. Sudirman 45
BIN-18 - Kantor Walikota
BIN-22 - Mall Raya Gatot
```

---

## Error Responses

### Device tidak ditemukan
```json
{
  "success": false,
  "message": "Device tidak ditemukan"
}
```

### Invalid request
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "organik": ["organik harus integer min 0 max 100"]
  }
}
```

---

## Headers yang Dibutuhkan

```
Content-Type: application/json
Accept: application/json
```

---

## Testing dengan Postman

1. Import sebagai Raw JSON body
2. Set method: POST
3. URL: `http://localhost:8000/api/bins/BIN-03/update`
4. Headers:
   - `Content-Type: application/json`
   - `Accept: application/json`
5. Body (raw):
```json
{
  "organik": 85,
  "anorganik": 45,
  "b3": 30,
  "battery": 78
}
```

---

## Catatan Penting

- **Interval pengiriman:** Setiap 10-30 detik untuk data sensor
- **Timeout:** Server tunggu response max 5 detik
- **Retry:** Jika gagal, esp32 retry 3x dengan delay 2 detik
- **Battery Critical:** Jika battery < 20%, prioritaskan alert ke server
- **Offline handling:** Jika WiFi putus, buffer data ke SPIFFS/LittleFS

