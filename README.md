## Status Proyek
🚧 Work in progress

### ✅ Sudah berfungsi
- Login dan register
- Update kapasitas tempat sampah (`POST /api/bins/{device_id}/update`)
- Mendeteksi Sampah dan Mengirim Informasi Ke ESP32 Untuk Menggerakan Servo Sesuai Dengan Jenis Sampahnya
- Dashboard monitoring

### ❌ Belum berfungsi / dalam pengerjaan
- Forgot password
- Klasifikasi Realtime(Saat ini Visual masih berasal dari python)
- Dan beberapa fitur yang belum berfungsi

### 📝 Rencana
- Reset password lewat email
- Grafik suhu dan kelembapan
- Fitur2 Lainnya
  
## Cara Kerja
1. Kamera Tapo TC06 (RTSP) mengirim video ke `python/cam.py`.
2. Model YOLO (`best.pt`) mendeteksi jenis sampah: Organik, Anorganik, B3.
3. Hasil deteksi dikirim ke Laravel lewat `POST /api/bins/{device_id}/detection`.
4. Laravel menentukan kompartemen dan memerintahkan servo.
5. ESP32 (`firmware/smart_bin/smart_bin.ino`) membaca status dari Laravel
   dan menampilkannya di LCD.

## Menjalankan Deteksi
cd python
pip install -r requirements.txt
python cam.py
