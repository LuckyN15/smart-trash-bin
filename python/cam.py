from ultralytics import YOLO
import cv2
import requests
import numpy as np
import threading
import time

# =====================
# CONFIG
# =====================

model = YOLO("best.pt")

# --- Kamera TP-Link Tapo TC06 (RTSP) ---
CAM_IP = ""
CAM_USERNAME = ""
CAM_PASSWORD = ""


CAM_STREAM_PATH = "stream1"

STREAM_URL = f"rtsp://{CAM_USERNAME}:{CAM_PASSWORD}@{CAM_IP}:554/{CAM_STREAM_PATH}"

WEBSITE_API_URL = "http://127.0.0.1:8000/api"
DEVICE_ID = "BIN-03"

CONFIDENCE_THRESHOLD = 0.70
STABLE_FRAMES = 5

TARGET_FPS = 15
FRAME_INTERVAL = 1.0 / TARGET_FPS

COUNT_INTERVAL = 0.4
REPORT_COOLDOWN = 5

# Resolusi inference disamakan dengan resolusi training (imgsz=640)
INFERENCE_IMGSZ = 640

# =====================
# CLASS MAP -> KATEGORI SAMPAH
# =====================
# Key HARUS sama persis dengan nama class asli dari model (cek dengan print(model.names))

WASTE_MAP = {
    "leaf": "Organik",
    "banana - v1 2023-02-21 10:25pm": "Organik",
    "egg_shell": "Organik",

    "plastic-bottle": "Anorganik",
    "trash_plastic": "Anorganik",
    "cable": "Anorganik",

    "Battery - v2 Battery": "B3",
    "LED": "B3",
    "MARKER": "B3",
    "Pijar": "B3",
    "TL": "B3",
    "WHITEBOARD ERASER": "B3",
    "mask": "B3",
}

# =====================
# NAMA TAMPILAN (BAGUS) UNTUK DIKIRIM KE WEB / DITAMPILKAN DI LAYAR
# =====================
# Key = nama class asli dari model, Value = nama yang enak dibaca

DISPLAY_NAME_MAP = {
    "Battery - v2 Battery": "Baterai",
    "LED": "Lampu LED",
    "MARKER": "Spidol",
    "Pijar": "Lampu Pijar",
    "TL": "Lampu TL",
    "WHITEBOARD ERASER": "Penghapus Whiteboard",
    "banana - v1 2023-02-21 10:25pm": "Kulit Pisang",
    "cable": "Kabel",
    "egg_shell": "Cangkang Telur",
    "leaf": "Daun",
    "mask": "Masker",
    "plastic-bottle": "Botol Plastik",
    "trash_plastic": "Sampah Plastik",
}

COLORS = {
    "Organik": (0, 255, 0),
    "Anorganik": (0, 165, 255),
    "B3": (0, 0, 255),
}

# =====================
# GLOBAL
# =====================

detection_locked = False
detect_count = 0
last_class = None

latest_frame = None
frame_lock = threading.Lock()
stream_running = True

last_count_time = 0

# =====================
# WEBSITE / LARAVEL
# =====================

def kirim_ke_website(display_name, jenis, confidence):
    """Laptop hanya melapor ke Laravel. Servo diputuskan oleh Laravel."""
    try:
        response = requests.post(
            f"{WEBSITE_API_URL}/bins/{DEVICE_ID}/detection",
            json={
                "nama_sampah": display_name,
                "jenis_sampah": jenis.lower(),
                "confidence": round(confidence, 2),
                "model_version": "v1",
            },
            timeout=3
        )

        if response.status_code == 200:
            data = response.json()
            accepted = data.get("accepted", False)
            trigger_servo = data.get("trigger_servo", 0)
            capacity = data.get("capacity_percent")
            status = data.get("compartment_status")
            message = data.get("message")

            if accepted:
                print(
                    f"[OK] Laravel menerima laporan: {display_name} ({jenis}) | "
                    f"capacity={capacity}% | status={status} | queued_servo={trigger_servo}"
                )
            else:
                print(f"[INFO] Laravel menolak laporan: {display_name} ({jenis}) | {message}")
        else:
            print(f"[WARN] Website respon {response.status_code}: {response.text}")

    except Exception as e:
        print("[ERROR] Gagal kirim ke website:", e)

# =====================
# DETECTION COOLDOWN
# =====================

def unlock_detection():
    global detection_locked
    time.sleep(REPORT_COOLDOWN)
    detection_locked = False
    print("[OK] Deteksi aktif kembali")

# =====================
# THREAD: BACA STREAM KAMERA TAPO TC06 (RTSP)
# =====================

def stream_reader():
    global latest_frame, stream_running

    print("[OK] Connecting ke kamera Tapo TC06 (RTSP)...")

    while stream_running:
        cap = None
        try:
            cap = cv2.VideoCapture(STREAM_URL, cv2.CAP_FFMPEG)

            # Kurangi buffer supaya frame yang diambil selalu yang terbaru (mengurangi delay)
            cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

            if not cap.isOpened():
                print("[ERROR] Gagal membuka RTSP stream, coba lagi...")
                time.sleep(2)
                continue

            print("[OK] Stream Tapo TC06 berhasil terbuka!")

            fail_count = 0

            while stream_running:
                ret, frame = cap.read()

                if not ret or frame is None:
                    fail_count += 1
                    if fail_count > 20:
                        print("[ERROR] Terlalu banyak frame gagal, reconnect RTSP...")
                        break
                    time.sleep(0.05)
                    continue

                fail_count = 0

                # Catatan: resize di sini hanya untuk tampilan/window,
                # inference tetap pakai imgsz=640 lewat parameter model.predict()
                frame = cv2.resize(frame, (640, 480))

                with frame_lock:
                    latest_frame = frame

        except Exception as e:
            print(f"[ERROR] Error stream RTSP: {e}")
            time.sleep(2)
        finally:
            if cap is not None:
                cap.release()

        if stream_running:
            time.sleep(2)

# =====================
# LOOP UTAMA: YOLO + TAMPILAN
# =====================

def jalankan_deteksi():
    global detection_locked, detect_count, last_class, last_count_time

    print(f"[INFO] FPS deteksi dibatasi ke {TARGET_FPS} fps")
    print(f"[INFO] Resolusi inference: {INFERENCE_IMGSZ}px (menyesuaikan resolusi training)")
    print("Menunggu frame pertama dari kamera Tapo TC06...")

    last_process_time = time.time()

    while True:
        now = time.time()
        elapsed = now - last_process_time

        if elapsed < FRAME_INTERVAL:
            time.sleep(0.01)
            continue

        last_process_time = now

        with frame_lock:
            if latest_frame is None:
                time.sleep(0.05)
                continue
            frame = latest_frame.copy()

        if detection_locked:
            cv2.putText(
                frame,
                "LOCKED - LAPORAN SUDAH DIKIRIM",
                (10, 40),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.8,
                (0, 0, 255),
                2
            )
            cv2.imshow("Smart Bin - Tapo TC06", frame)

            if cv2.waitKey(1) & 0xFF == ord("q"):
                break

            continue

        # imgsz disamakan dengan resolusi saat training (640) agar akurasi optimal
        results = model.predict(source=frame, imgsz=INFERENCE_IMGSZ, conf=0.5, verbose=False)

        object_found = False

        for r in results:
            for box in r.boxes:
                object_found = True

                x1, y1, x2, y2 = map(int, box.xyxy[0])
                class_name = model.names[int(box.cls)]          # nama asli (untuk lookup)
                display_name = DISPLAY_NAME_MAP.get(class_name, class_name)  # nama bagus (untuk tampilan/kirim)
                confidence = float(box.conf)

                jenis = WASTE_MAP.get(class_name, "Unknown")
                color = COLORS.get(jenis, (255, 255, 255))

                cv2.rectangle(frame, (x1, y1), (x2, y2), color, 2)

                label = f"{display_name} | {jenis} {confidence:.0%}"
                (tw, th), _ = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 1)

                cv2.rectangle(frame, (x1, y1 - 20), (x1 + tw, y1), color, -1)
                cv2.putText(
                    frame,
                    label,
                    (x1, y1 - 5),
                    cv2.FONT_HERSHEY_SIMPLEX,
                    0.5,
                    (255, 255, 255),
                    1
                )

                if jenis != "Unknown" and confidence >= CONFIDENCE_THRESHOLD:
                    now_count = time.time()

                    if class_name != last_class:
                        detect_count = 1
                        last_class = class_name
                        last_count_time = now_count
                    elif now_count - last_count_time >= COUNT_INTERVAL:
                        detect_count += 1
                        last_count_time = now_count

                    cv2.putText(
                        frame,
                        f"COUNT: {detect_count}/{STABLE_FRAMES}",
                        (10, 40),
                        cv2.FONT_HERSHEY_SIMPLEX,
                        0.8,
                        (0, 255, 255),
                        2
                    )

                    if detect_count >= STABLE_FRAMES:
                        print(f"[DETECTED] {display_name} -> {jenis}. Kirim laporan ke Laravel.")

                        detection_locked = True

                        threading.Thread(
                            target=kirim_ke_website,
                            args=(display_name, jenis, confidence),
                            daemon=True
                        ).start()

                        threading.Thread(
                            target=unlock_detection,
                            daemon=True
                        ).start()

                        detect_count = 0
                        last_class = None

        if not object_found:
            detect_count = 0
            last_class = None

        for i, (jenis, color) in enumerate(COLORS.items()):
            cv2.putText(
                frame,
                f"- {jenis}",
                (10, frame.shape[0] - 20 - i * 20),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.5,
                color,
                1
            )

        cv2.putText(
            frame,
            f"Tapo TC06 | FPS target: {TARGET_FPS} | imgsz: {INFERENCE_IMGSZ} | Q = keluar",
            (10, 25),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.6,
            (255, 255, 255),
            2
        )

        cv2.imshow("Smart Bin - Tapo TC06", frame)

        if cv2.waitKey(1) & 0xFF == ord("q"):
            break


if __name__ == "__main__":
    # Cek dulu nama class asli model, cocokkan dengan WASTE_MAP & DISPLAY_NAME_MAP di atas
    print("[INFO] Daftar class model:", model.names)

    t = threading.Thread(target=stream_reader, daemon=True)
    t.start()

    try:
        jalankan_deteksi()
    finally:
        stream_running = False
        cv2.destroyAllWindows()
        print("[OK] Selesai!")