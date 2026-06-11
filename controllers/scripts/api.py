import os
import tempfile
from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.responses import JSONResponse

# Importamos la lógica existente del extractor (asegurarse de que extract_pdf.py esté en el mismo directorio)
import extract_pdf

app = FastAPI(title="SENA PDF Extractor API", description="Microservicio para extracción de datos GFPI-F-016")

@app.post("/extract")
async def extract(file: UploadFile = File(...)):
    if not file.filename.lower().endswith(".pdf"):
        raise HTTPException(status_code=400, detail="El archivo debe ser PDF")
    
    tmp_path = None
    try:
        # Guardar en archivo temporal para que pdfplumber pueda leerlo
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await file.read()
            tmp.write(content)
            tmp_path = tmp.name

        # Extraer usando la lógica existente
        result = extract_pdf.extract_table_from_pdf(tmp_path)
        
        return JSONResponse(content=result)
        
    except Exception as e:
        import traceback
        return JSONResponse(
            status_code=500, 
            content={"ok": False, "error": str(e), "traceback": traceback.format_exc()}
        )
    finally:
        # Limpiar temp
        if tmp_path and os.path.exists(tmp_path):
            try:
                os.unlink(tmp_path)
            except:
                pass

if __name__ == "__main__":
    import uvicorn
    # Para arrancar el servidor: python api.py o uvicorn api:app --host 127.0.0.1 --port 8000
    uvicorn.run("api:app", host="127.0.0.1", port=8000, reload=True)
