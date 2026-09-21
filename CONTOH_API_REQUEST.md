# Contoh API Request dari ESP32

## Ringkasan Cepat

Setiap Bin punya 3 kompartemen:
- **Organik** (sampah organik: sisa makanan, dedaunan)
- **Anorganik** (sampah anorganik: plastik, kertas, logam, kaca)
- **B3** (sampah berbahaya: baterai, kimia, elektronik)

---

## Contoh 1: Request Organik Penuh

**Saat organik sudah 92%, anorganik 45%, B3 20%**

### Request
```bash
curl -X POST http://192.168.1.100:8000/api/bins/BIN-03/update \
  -H "Content-Type: application/json" \
  -d '{
    "organik": 92,
    "anorganik": 45,
    "b3": 20,
    "battery": 85
  }'
```

### JSON Payload
```json
{
  "organik": 92,
  "anorganik": 45,
  "b3": 20,
  "battery": 85
}
```

### Response
```json
{
  "success": true,
  "message": "Data berhasil diupdate",
  "device_id": "BIN-03",
  "battery_level": 85,
  "alerts": [
    {
      "category": "organik",
      "capacity": 92,
      "alert_type": "full"
    }
  ],
  "compartments": {
    "organik": 92,
    "anorganik": 45,
    "b3": 20
  }
}
```

**Aksi ESP32:**
- ✅ Organik **TUTUP** (full)
- ✅ Anorganik **BUKA** (ok)
- ✅ B3 **BUKA** (empty/ok)
- 📱 Dashboard notifikasi: "BIN-03 Kompartemen Organik Penuh - Pasar Baru Lt.1"

---

## Contoh 2: Request Anorganik Hampir Penuh

**Saat organik 55%, anorganik 78%, B3 35%**

### Request
```bash
curl -X POST http://192.168.1.100:8000/api/bins/BIN-07/update \
  -H "Content-Type: application/json" \
  -d '{
    "organik": 55,
    "anorganik": 78,
    "b3": 35,
    "battery": 92,
    "temperature": 29,
    "humidity": 72
  }'
```

### Response
```json
{
  "success": true,
  "message": "Data berhasil diupdate",
  "device_id": "BIN-07",
  "battery_level": 92,
  "alerts": [
    {
      "category": "anorganik",
      "capacity": 78,
      "alert_type": "near"
    }
  ],
  "compartments": {
    "organik": 55,
    "anorganik": 78,
    "b3": 35
  }
}
```

**Aksi ESP32:**
- ✅ Organik **BUKA**
- ⚠️ Anorganik **BUKA** (tapi warning - monitor dalam 2 jam)
- ✅ B3 **BUKA**
- 📱 Dashboard warning: "BIN-07 Kompartemen Anorganik Hampir Penuh (78%)"

---

## Contoh 3: Semua Kompartemen Status Bagus

**Saat organik 35%, anorganik 42%, B3 28%**

### Request
```bash
curl -X POST http://192.168.1.100:8000/api/bins/BIN-12/update \
  -H "Content-Type: application/json" \
  -d '{
    "organik": 35,
    "anorganik": 42,
    "b3": 28,
    "battery": 88
  }'
```

### Response
```json
{
  "success": true,
  "message": "Data berhasil diupdate",
  "device_id": "BIN-12",
  "battery_level": 88,
  "alerts": [],
  "compartments": {
    "organik": 35,
    "anorganik": 42,
    "b3": 28
  }
}
```

**Aksi ESP32:**
- ✅ Semua kompartemen **BUKA** (normal)
- ✅ Tidak ada alert
- 📱 Dashboard: Status normal (OK)

---

## Contoh 4: B3 Penuh

**Saat organik 45%, anorganik 50%, B3 95%**

### Request
```bash
curl -X POST http://192.168.1.100:8000/api/bins/BIN-22/update \
  -H "Content-Type: application/json" \
  -d '{
    "organik": 45,
    "anorganik": 50,
    "b3": 95,
    "battery": 72
  }'
```

### Response
```json
{
  "success": true,
  "message": "Data berhasil diupdate",
  "device_id": "BIN-22",
  "battery_level": 72,
  "alerts": [
    {
      "category": "b3",
      "capacity": 95,
      "alert_type": "full"
    }
  ],
  "compartments": {
    "organik": 45,
    "anorganik": 50,
    "b3": 95
  }
}
```

**Aksi ESP32:**
- ✅ Organik **BUKA**
- ✅ Anorganik **BUKA**
- ✅ B3 **TUTUP** (PENUH!)
- 🔴 Dashboard alert: "BIN-22 Kompartemen B3 Penuh - Perlu penanganan khusus - Mall Raya Gatot"

---

## Contoh 5: Query Status Kompartemen

**ESP32 ingin tahu status setiap kompartemen sebelum menerima sampah**

### Request
```bash
curl -X GET http://192.168.1.100:8000/api/bins/BIN-03/status \
  -H "Accept: application/json"
```

### Response
```json
{
  "success": true,
  "device_id": "BIN-03",
  "name": "Pasar Baru",
  "location": "Pasar Baru Lt.1",
  "battery_level": 85,
  "online_status": "online",
  "compartments": {
    "organik": {
      "capacity_percent": 92,
      "status": "full",
      "can_accept": false
    },
    "anorganik": {
      "capacity_percent": 45,
      "status": "ok",
      "can_accept": true
    },
    "b3": {
      "capacity_percent": 20,
      "status": "ok",
      "can_accept": true
    }
  },
  "last_reported_at": "2026-06-19T14:35:22Z"
}
```

**Interpretasi untuk ESP32:**
```c
// Dari response, ESP32 tahu:
if (compartments.organik.can_accept == false) {
    digitalWrite(ORGANIK_DOOR, LOW);  // TUTUP organik
}
if (compartments.anorganik.can_accept == true) {
    digitalWrite(ANORGANIK_DOOR, HIGH);  // BUKA anorganik
}
if (compartments.b3.can_accept == true) {
    digitalWrite(B3_DOOR, HIGH);  // BUKA B3
}
```

---

## Contoh 6: Batch Update (Lebih Efisien)

**Update semua 3 kompartemen dalam 1 request**

### Request
```bash
curl -X POST http://192.168.1.100:8000/api/bins/BIN-09/batch-update \
  -H "Content-Type: application/json" \
  -d '{
    "data": [
      {"category": "organik", "capacity": 65},
      {"category": "anorganik", "capacity": 55},
      {"category": "b3", "capacity": 40}
    ]
  }'
```

### Response
```json
{
  "success": true,
  "message": "Batch update berhasil",
  "alerts_count": 0,
  "alerts": []
}
```

---

## Status Code Chart

| Kapasitas | Status | Arti | Relay |
|---|---|---|---|
| 0-30% | `empty` | Kosong/Aman | 🟢 BUKA |
| 31-69% | `ok` | Normal | 🟢 BUKA |
| 70-89% | `near` | Hampir Penuh ⚠️ | 🟢 BUKA (monitor) |
| 90-100% | `full` | PENUH 🔴 | 🔴 TUTUP |

---

## Response Status untuk Android/Web

Dashboard akan menampilkan:

### Saat Organik Penuh
```
🗑️ ORGANIK
92% 🔴 PENUH
Sisa makanan & bahan alami

⚡ Notifikasi:
BIN-03 Kompartemen Organik Penuh
Kapasitas mencapai 92%. Segera jadwalkan pengangkutan.
```

### Saat Anorganik Hampir Penuh
```
♻️ ANORGANIK
78% 🟡 HAMPIR PENUH
Plastik, kertas, logam, kaca

⚡ Notifikasi:
BIN-07 Kompartemen Anorganik Hampir Penuh (78%)
Kapasitas Anorganik mencapai 78%. Monitor dalam 2 jam ke depan.
```

### Saat B3 Penuh
```
☣️ B3
95% 🔴 PENUH
Perlu penanganan khusus

🚨 CRITICAL Notifikasi:
BIN-22 Kompartemen B3 Penuh
Kapasitas mencapai 95%. Segera hubungi tim penanganan B3.
```

---

## Testing Tips

1. **Gunakan Postman** untuk test request format
2. **Monitor logs** di `storage/logs/laravel.log`
3. **Cek database** dengan: `php artisan tinker`
   ```php
   > Bin::with('compartments')->find(1)->toArray()
   ```
4. **Simulasi kapasitas** dengan request berbeda setiap 5 detik

