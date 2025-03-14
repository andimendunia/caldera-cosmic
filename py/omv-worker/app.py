import cv2
import io
import json
import logging
import os
import requests
import serial
import shutil
import sqlite3
import sys
import threading
import time
import win32api

from datetime import datetime, timedelta, timezone
from flask import Flask, jsonify, request, send_file
from flask_cors import CORS
from logging.handlers import RotatingFileHandler
from urllib.parse import urlparse

# Set up flask server
app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "*"}})


# Set up logging
log_file = 'debug.log'
log_handler = RotatingFileHandler(log_file, maxBytes=100000, backupCount=0)
log_handler.setFormatter(logging.Formatter('%(asctime)s - %(levelname)s - %(message)s'))
logger = logging.getLogger()
logger.addHandler(log_handler)
logger.setLevel(logging.INFO)

# set up logging handler
console_handler = logging.StreamHandler(sys.stdout)
console_handler.setLevel(logging.INFO)
formatter = logging.Formatter('%(levelname)s - %(message)s')
console_handler.setFormatter(formatter)
logger.addHandler(console_handler)
print()
print("Aplikasi omv-worker v.1.1")
print("-------------------------")
print()
logger.info("Mejalankan aplikasi...")

# Set up config
config_file_path = 'config.json'
config_example_path = 'config.json.example'

def load_configuration():
    if os.path.exists(config_file_path):
        # config.json exists, load it
        with open(config_file_path, 'r') as config_file:
            logger.info(f"Konfigurasi berhasil dimuat.")
            return json.load(config_file)
    elif os.path.exists(config_example_path):
        # config.json doesn't exist, but config.json.example does
        try:
            # Copy config.json.example to config.json
            shutil.copy2(config_example_path, config_file_path)
            logger.warning(f"Konfigurasi hilang, membuat baru dari config.json.example")
            
            # Load the newly created config.json
            with open(config_file_path, 'r') as config_file:
                return json.load(config_file)
        except Exception as e:
            logger.error(f"Tidak dapat membuat konfigurasi dari config.json.example: {str(e)}")
            raise
    else:
        # Neither config.json nor config.json.example exists
        error_message = f"Berkas konfigurasi {config_file_path} hilang dan {config_example_path} tidak tersedia."
        logger.error(error_message)
        raise FileNotFoundError(error_message)

# Load configuration
try:
    logger.info("Memuat konfigurasi...")
    config = load_configuration()
except Exception as e:
    logger.error(f"Gagal memuat konfigurasi: {str(e)}")
    quit()

# Database setup
logger.info(f"Mempersiapkan database...")
try:
    conn = sqlite3.connect(config['app']['database_file'], check_same_thread=False)
    cursor = conn.cursor()
    cursor.execute('''CREATE TABLE IF NOT EXISTS queue
                    (id INTEGER PRIMARY KEY AUTOINCREMENT,
                    data TEXT,
                    attempts INTEGER,
                    created_at TIMESTAMP,
                    last_tried_at TIMESTAMP,
                    last_tried_msg TEXT)''')
    conn.commit()
    logger.info(f"Database berhasil dimuat.")
except Exception as e:
    logger.error(f"Gagal memuat database: {str(e)}")
    quit()

# Global variables
start_time = time.time()
request_count = 0
error_count = 0

def update_config(new_ip):
    if config['remote_server']['ip'] != new_ip:
        config['remote_server']['ip'] = new_ip
        with open(config_file_path, 'w') as config_file:
            json.dump(config, config_file, indent=4)
        logger.info(f"IP remote server diperbarui ke: {new_ip}")

def get_server_time(url):
    try:
        logger.info(f"Mengambil waktu server...")
        response = requests.get(url)
        response.raise_for_status()
        # Parse the ISO 8601 string and ensure it's UTC
        return datetime.fromisoformat(response.json()['formatted']).replace(tzinfo=timezone.utc)
    except requests.RequestException as e:
        logger.error(f"Waktu tidak dapat diambil karena tidak ada respon dari server")
        return None

def set_system_time(server_time):
    try:
        # Convert UTC time to local time for Windows
        local_time = server_time
        win32api.SetSystemTime(local_time.year, local_time.month,
                               local_time.weekday(), local_time.day,
                               local_time.hour, local_time.minute,
                               local_time.second, local_time.microsecond // 1000)
        logger.info(f"Waktu perangkat diperbarui ke {local_time}")
    except Exception as e:
        logger.error(f"Gagal menyetel waktu perangkat: {e}")

def sync_time():
    server_url = f"{config['remote_server']['protocol']}://{config['remote_server']['ip']}/api/time"
    server_time = get_server_time(server_url)
    
    if server_time is None:
        return
    
    # Ensure local_time is also UTC aware
    local_time = datetime.now(timezone.utc)
    time_difference = abs((server_time - local_time).total_seconds())
    
    logger.info(f"Waktu server    : {server_time}")
    logger.info(f"Waktu perangkat : {local_time}")
    logger.info(f"Perbedaan       : {time_difference} detik")
    
    if time_difference > 60:  # 1 minute
        set_system_time(server_time)
    else:
        logger.info("Perbedaan waktu dalam batas wajar (di bawah 60 detik). Waktu perangkat tidak diperbarui.")

sync_time()

@app.route('/get-line')
def get_line():
    line = config['app'].get('line')
    if line is not None:
        return str(line), 200, {'Content-Type': 'text/plain'}
    else:
        return str(99), 404, {'Content-Type': 'text/plain'}

@app.route('/send-data', methods=['POST'])
def send_data():
    data = request.json
    if 'server_ip' not in data:
        return jsonify({"status": "error", "message": "Parameter server_ip hilang"}), 400
    
    server_ip = data['server_ip']    
    update_config(server_ip)
    
    success, server_message = send_to_server(data)
    if success:
        return jsonify({"status": "success", "message": server_message}), 200
    else:
        queue_data(data, server_message)
        error_message = f"Data queued. Server message: {server_message}"
        logger.error(error_message)
        return jsonify({"status": "queued", "message": error_message}), 202

def send_to_server(data):
    try:
        url = f"{config['remote_server']['protocol']}://{config['remote_server']['ip']}/api/omv-metric"
        response = requests.post(url, json=data, timeout=5)
        server_message = response.text
        if response.status_code != 200:
            error_message = f"Remove server memberi kode non-200: {response.status_code}."
            logger.error(error_message)
            return False, error_message
        return True, server_message
    except requests.RequestException as e:
        error_message = f"Galat saat mengirimkan data ke remote server: {str(e)}"
        logger.error(error_message)
        return False, error_message

def queue_data(data, server_message):
    try:
        current_time = datetime.now()
        cursor.execute("""
            INSERT INTO queue (data, attempts, created_at, last_tried_at, last_tried_msg)
            VALUES (?, ?, ?, ?, ?)
        """, (json.dumps(data), 0, current_time, current_time, server_message))
        conn.commit()
    except sqlite3.Error as e:
        error_message = f"Galat saat mengantrikan data: {str(e)}"
        logger.error(error_message)

def retry_queued_data():
    while True:
        try:
            cursor.execute("""
                SELECT id, data, attempts FROM queue
                WHERE attempts < ? AND created_at > ?
            """, (config['data_handling']['max_attempts'], 
                  datetime.now() - timedelta(days=config['data_handling']['max_age_days'])))
            rows = cursor.fetchall()
            for row in rows:
                id, data, attempts = row
                success, server_message = send_to_server(json.loads(data))
                if success:
                    cursor.execute("DELETE FROM queue WHERE id = ?", (id,))
                else:
                    new_attempts = attempts + 1
                    cursor.execute("""
                        UPDATE queue
                        SET attempts = ?, last_tried_at = ?, last_tried_msg = ?
                        WHERE id = ?
                    """, (new_attempts, datetime.now(), server_message, id))
                conn.commit()
                time.sleep(calculate_backoff(attempts))
            
            if not rows:
                # If no rows were processed, sleep for a while before checking again
                time.sleep(config['data_handling']['initial_backoff'])
        except Exception as e:
            error_message = f"Error in retry_queued_data: {str(e)}"
            logger.error(error_message)
            time.sleep(config['data_handling']['initial_backoff'])

def calculate_backoff(attempts):
    return min(config['data_handling']['max_backoff'], 
               config['data_handling']['initial_backoff'] * (2 ** attempts))

def read_serial_data():
    global error_count
    ser = None
    try:
        ser = serial.Serial(config['serial']['port'], config['serial']['baud_rate'], timeout=1)
        ser.reset_input_buffer()
        for _ in range(5):
            line = ser.readline()
            if line:
                decoded_line = line.decode('utf-8', errors='replace').strip()
                if decoded_line.isdigit():
                    return int(decoded_line)
            time.sleep(0.2)
        return None
    except serial.SerialException as e:
        error_message = f"Galat saat membaca port serial: {e}"
        logger.error(error_message)
        error_count += 1
        return None
    finally:
        if ser is not None and ser.is_open:
            ser.close()

@app.route('/get-data')
def get_data():
    global request_count
    request_count += 1
    raw_data = read_serial_data()
    
    if raw_data is not None:
        eval_result = raw_data >= config['serial']['threshold']
        return jsonify({"eval": eval_result, "raw": raw_data})
    else:
        error_message = "Tak ada data yang sah dari Arduino"
        logger.error(error_message)
        return jsonify({"error": error_message, "eval": False, "raw": None}), 500

def crop_and_resize(image, target_width, target_height):
    height, width = image.shape[:2]
    aspect_ratio = width / height

    if aspect_ratio > target_width / target_height:
        new_width = int(height * target_width / target_height)
        start_x = (width - new_width) // 2
        cropped = image[:, start_x:start_x+new_width]
    else:
        new_height = int(width * target_height / target_width)
        start_y = (height - new_height) // 2
        cropped = image[start_y:start_y+new_height, :]

    resized = cv2.resize(cropped, (target_width, target_height), interpolation=cv2.INTER_AREA)
    return resized

@app.route('/get-photo')
def get_photo():
    try:
        cap = cv2.VideoCapture(config['capture']['camera_index'], cv2.CAP_DSHOW)
        if not cap.isOpened():
            raise IOError("Tak dapat mengakses kamera")
        
        for i in range(config['capture']['frame']):
            ret, frame = cap.read()

        cap.release()
        if not ret:
            raise IOError("Cannot capture image")
        processed_frame = crop_and_resize(frame, config['capture']['width'], config['capture']['height'])
        _, buffer = cv2.imencode('.jpg', processed_frame)
        image_bytes = io.BytesIO(buffer)
        return send_file(image_bytes, mimetype='image/jpeg')
    except Exception as e:
        error_message = f"Galat saat mengambil foto: {str(e)}"
        logger.error(error_message)
        return jsonify({"error": error_message}), 500

@app.route('/debug-info')
def debug_info():
    uptime = time.time() - start_time
    return jsonify({
        "configuration": config,
        "statistics": {
            "uptime_seconds": round(uptime, 2),
            "total_requests": request_count,
            "error_count": error_count
        }
    })

if __name__ == '__main__':
    threading.Thread(target=retry_queued_data, daemon=True).start()
    app.run(host=config['local_server']['host'], port=config['local_server']['port'], debug=False)