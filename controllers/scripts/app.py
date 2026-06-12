from flask import Flask, request, jsonify
import os
import tempfile
import sys

# Agregar la ruta actual al path para importar extract_pdf sin problemas
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from extract_pdf import extract_table_from_pdf

app = Flask(__name__)

@app.route('/health', methods=['GET'])
def health():
    return jsonify({"status": "ok"})

@app.route('/extract-pdf', methods=['POST'])
def extract():
    if 'pdf' not in request.files:
        return jsonify({"ok": False, "error": "No file part"}), 400
    
    file = request.files['pdf']
    if file.filename == '':
        return jsonify({"ok": False, "error": "No selected file"}), 400

    if file:
        fd, temp_path = tempfile.mkstemp(suffix=".pdf")
        os.close(fd)
        try:
            file.save(temp_path)
            result = extract_table_from_pdf(temp_path)
            return jsonify(result)
        except Exception as e:
            import traceback
            return jsonify({
                "ok": False, 
                "error": f"Error interno en Python: {str(e)}", 
                "traceback": traceback.format_exc()
            }), 500
        finally:
            if os.path.exists(temp_path):
                os.remove(temp_path)

if __name__ == '__main__':
    # Usar puerto 5000 y escuchar solo en localhost por seguridad
    app.run(host='127.0.0.1', port=5000)
