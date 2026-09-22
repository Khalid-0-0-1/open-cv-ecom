from flask import Flask, render_template, Response, jsonify, request
import cv2
import numpy as np  # <-- TILFØJET
from pyzbar.pyzbar import decode  # type: ignore  # <-- type: ignore tilføjet
import threading
import time

app = Flask(__name__, template_folder='.')
# ... resten af koden

# --- SYSTEM DATA (Simulerer database) ---
system_data = {
    "mode": "vis", # Standard tilstand
    "ordrer": ["ORD-1001", "ORD-1002", "ORD-1003"],
    "klar_til_afsendelse": [],
    "afsendt": [],
    "lagerbeholdning": {"VAR-001": 10, "VAR-002": 25},
    "returer": [],
    "selected_item": None,
    "log": ["System startet. Klar til scanning."]
}

# Global variabel til at holde styr på det seneste scan
last_scanned_code = None
last_scan_time = 0

# Start webcam (0 er standard kamera)
camera = cv2.VideoCapture(0)

def generate_frames():
    """Læser kameraet og leder efter QR-koder (OpenCV + Pyzbar)"""
    global last_scanned_code, last_scan_time
    
    while True:
        success, frame = camera.read()
        if not success:
            break
        else:
            # Scan billedet for QR-koder
            decoded_objects = decode(frame)
            
            for obj in decoded_objects:
                # Tegn en firkant omkring QR-koden
                points = obj.polygon
                if len(points) == 4:
                    pts = [tuple(point) for point in points]
                    cv2.polylines(frame, [np.array(pts, dtype=np.int32)], True, (0, 255, 0), 3)
                
                # Hent data
                barcode_data = obj.data.decode("utf-8")
                
                # Undgå at scanne samme kode flere gange i træk (cooldown på 3 sekunder)
                current_time = time.time()
                if barcode_data != last_scanned_code or (current_time - last_scan_time) > 3:
                    last_scanned_code = barcode_data
                    last_scan_time = current_time
                    handle_scan_action(barcode_data)
            
            # Konverter billedet til JPEG format til browseren
            ret, buffer = cv2.imencode('.jpg', frame)
            frame = buffer.tobytes()
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + frame + b'\r\n')

def handle_scan_action(code):
    """Logikken der kører, når en QR-kode scannes (baseret på tilstand)"""
    global system_data
    mode = system_data["mode"]
    message = ""
    
    if mode == "vis":
        message = f"👁️ Info: Scannede {code}. (Vis tilstand - ingen handling)"
        
    elif mode == "hent":
        selected_item = system_data["selected_item"]
        if selected_item and code == selected_item["code"]:
            message = f"📦 {selected_item['title']} scannet og valgt til ordre {selected_item['order_id']}."
            system_data["selected_item"] = None
        elif code in system_data["ordrer"]:
            system_data["ordrer"].remove(code)
            system_data["klar_til_afsendelse"].append(code)
            message = f"📥 Ordrer {code} plukket og klar til afsendelse!"
        else:
            message = f"⚠️ Ordrer {code} findes ikke på pluklisten."
            
    elif mode == "send":
        if code in system_data["klar_til_afsendelse"]:
            system_data["klar_til_afsendelse"].remove(code)
            system_data["afsendt"].append(code)
            message = f"🚚 Pakke {code} er nu afsendt!"
        else:
            message = f"⚠️ Pakke {code} er ikke i listen over klar-til-afsendelse."
            
    elif mode == "modtag":
        if code in system_data["lagerbeholdning"]:
            system_data["lagerbeholdning"][code] += 1
            message = f"🏭 Vare {code} modtaget. Ny beholdning: {system_data['lagerbeholdning'][code]}"
        else:
            system_data["lagerbeholdning"][code] = 1
            message = f"🏭 Ny vare {code} oprettet på lager."
            
    elif mode == "refunder":
        system_data["returer"].append(code)
        message = f"🔄 Retur af {code} registreret. Kunden refunderes."

    # Tilføj til log
    system_data["log"].insert(0, message)
    if len(system_data["log"]) > 20: # Behold kun de 20 nyeste
        system_data["log"].pop()

# --- ROUTES (Hjemmesidens endpoints) ---

@app.route('/')
def index():
    """Viser hjemmesiden"""
    return render_template('index5.html')

@app.route('/video_feed')
def video_feed():
    """Sender live video stream til hjemmesiden"""
    return Response(generate_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/get_status')
def get_status():
    """Sender systemets nuværende status til hjemmesiden (AJAX)"""
    return jsonify(system_data)

@app.route('/set_mode', methods=['POST'])
def set_mode():
    """Skifter tilstand fra hjemmesiden"""
    new_mode = request.json.get('mode')
    system_data["mode"] = new_mode
    system_data["log"].insert(0, f"🔄 Skiftede til tilstand: {new_mode}")
    return jsonify({"success": True})

@app.route('/manual_scan', methods=['POST'])
def manual_scan():
    """Manuel scan knap"""
    # Simulerer et scan af en kode baseret på tilstand
    mode = system_data["mode"]
    code = "MANUEL-KODE"
    if mode == "hent" and system_data["selected_item"]:
        code = system_data["selected_item"]["code"]
    elif mode == "hent" and system_data["ordrer"]:
        code = system_data["ordrer"][0]
    elif mode == "send" and system_data["klar_til_afsendelse"]:
        code = system_data["klar_til_afsendelse"][0]
    elif mode == "modtag":
        code = "VAR-001"
    elif mode == "refunder":
        code = "RETUR-999"
        
    handle_scan_action(code)
    return jsonify({"success": True})

@app.route('/select_item', methods=['POST'])
def select_item():
    """Vælger en købt vare, som skal findes ved scanning."""
    item = request.get_json(silent=True) or {}
    order_id = str(item.get('order_id', '')).strip()
    product_id = str(item.get('product_id', '')).strip()
    title = str(item.get('title', '')).strip()

    if not order_id or not product_id or not title:
        return jsonify({"error": "Ugyldig vare"}), 400

    system_data["selected_item"] = {
        "order_id": order_id,
        "product_id": product_id,
        "title": title,
        "code": f"VAR-{product_id}",
    }
    system_data["mode"] = "hent"
    system_data["log"].insert(0, f"📌 Valgt: {title}. Scan VAR-{product_id}.")
    return jsonify({"success": True, "code": f"VAR-{product_id}"})

@app.route('/reset', methods=['POST'])
def reset():
    """Nulstil knap"""
    global last_scanned_code
    last_scanned_code = None
    system_data["log"] = ["System nulstillet. Klar til ny scanning."]
    return jsonify({"success": True})

if __name__ == '__main__':
    # Kør Flask appen
    app.run(host='0.0.0.0', port=5000, debug=True)