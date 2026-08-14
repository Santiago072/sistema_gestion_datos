"""
extract_pdf.py — Extractor de Proyecto Formativo SENA (GFPI-F-016)
Uso: python -X utf8 extract_pdf.py <ruta_del_pdf>

Devuelve JSON por stdout con la estructura:
{
  "ok": true,
  "informacion_basica": {...},
  "fases": [...],
  "actividades": [...],
  "registros": [...],
  "resumen": {...}
}
"""
import sys
import json
import re

try:
    import pdfplumber
except ImportError:
    print(json.dumps({"ok": False, "error": "Librería pdfplumber no está instalada. Ejecuta: pip install pdfplumber"}))
    sys.exit(1)


# ─────────────────────────────────────────────────────────
# Constantes SENA — Las 4 fases canónicas del GFPI-F-016
# ─────────────────────────────────────────────────────────
FASES_CANONICAL = {
    "ANÁLISIS":   "ANÁLISIS",
    "ANALISIS":   "ANÁLISIS",
    "PLANEACIÓN": "PLANEACIÓN",
    "PLANEACION": "PLANEACIÓN",
    "EJECUCIÓN":  "EJECUCIÓN",
    "EJECUCION":  "EJECUCIÓN",
    "EVALUACIÓN": "EVALUACIÓN",
    "EVALUACION": "EVALUACIÓN",
}

FASES_ORDEN = {
    "ANÁLISIS":   1,
    "PLANEACIÓN": 2,
    "EJECUCIÓN":  3,
    "EVALUACIÓN": 4,
}


def strip_pipe(text: str) -> str:
    """pdfplumber usa ' | ' para separar líneas dentro de una celda. Lo reemplazamos por espacio."""
    if not text:
        return ""
    return re.sub(r"\s*\|\s*", " ", text).strip()


def clean(text) -> str:
    """Normaliza el texto de una celda."""
    if text is None:
        return ""
    text = strip_pipe(str(text))
    text = re.sub(r"\s+", " ", text).strip()
    return text


def get_activity_identity(text: str) -> str:
    """
    Extrae un identificador único para la actividad.
    Si empieza con número (ej: '4.'), lo usa. Si no, usa los primeros 40 caracteres limpios.
    """
    if not text:
        return ""
    text = str(text).strip()
    m = re.match(r"^(\d+(?:\.\d+)*)", text)
    if m:
        return f"NUM_{m.group(1)}"
    
    # Fallback: normalizado corto
    norm = re.sub(r"^[\d\.\s-]+", "", text)
    norm = re.sub(r"[\.\s]+$", "", norm)
    norm = re.sub(r"\s+", " ", norm).strip().lower()
    return norm[:40]


def normalize_activity(text: str) -> str:
    """
    Normaliza el nombre de una actividad (ya no se usa como clave primaria, 
    pero sí para limpiar strings).
    """
    if not text:
        return ""
    norm = re.sub(r"^[\d\.\s-]+", "", text)
    norm = re.sub(r"[\.\s]+$", "", norm)
    return re.sub(r"\s+", " ", norm).strip().lower()


def detect_fase(text: str) -> str | None:
    """
    Devuelve el nombre canónico de la fase si el texto la contiene.
    Ignora números iniciales (ej: '1.', '2 ') y compara sin tildes.
    """
    REMOVE_ACCENTS = str.maketrans("ÁÉÍÓÚÀÈÌÒÙ", "AEIOUAEIOU")
    upper = text.upper().translate(REMOVE_ACCENTS).strip()
    
    # Limpiar números y puntuación al inicio (ej: "2.PLANEACION", "1. ANALISIS")
    upper = re.sub(r"^[\d\.\s-]+", "", upper)
    
    for key, canonical in FASES_CANONICAL.items():
        key_clean = key.translate(REMOVE_ACCENTS)
        if upper == key_clean or upper.startswith(key_clean):
            return canonical
    return None


def fill_down(column: list) -> list:
    """
    Rellena celdas vacías/None con el valor anterior.
    Esto reconstruye las celdas combinadas (merged cells) del PDF.
    """
    last = None
    result = []
    for val in column:
        v = clean(val)
        if v:
            last = v
        result.append(last or "")
    return result


def parse_resultado(text: str) -> tuple[str, str]:
    """
    Separa el código del nombre del resultado de aprendizaje.
    El código es SOLO el número antes del primer guión (ej: 593343).
    El número de resultado (01, 02…) NO forma parte del código.
    Retorna (codigo, nombre)
    """
    text = clean(text)
    if not text:
        return ("", "")
    
    codigo = ""
    nombre = text

    # Captura SOLO los dígitos antes del primer guión como código
    m = re.match(r"^(\d{5,7})\s*[-–]\s*\d{2}[-]?\s+(.+)$", text, re.DOTALL)
    if m:
        codigo, nombre = m.group(1).strip(), m.group(2).strip()
    else:
        # Fallback para formatos sin número de resultado (ej: "590803 - NOMBRE")
        m2 = re.match(r"^(\d{5,9})\s*[-–]\s*(.+)$", text, re.DOTALL)
        if m2:
            codigo, nombre = m2.group(1).strip(), m2.group(2).strip()

    # Quitar guiones iniciales del nombre (ej: "- 02 SOLUCIONAR...")
    nombre = re.sub(r"^[-–]\s*", "", nombre).strip()
    
    return (codigo, nombre)


def parse_competencia(text: str) -> tuple[str, str]:
    """
    Separa el código del nombre de la competencia.
    Formato del SENA: "240201530 - Nombre de la competencia"
    Retorna (codigo, nombre)
    """
    text = clean(text)
    if not text:
        return ("", "")
    m = re.match(r"^(\d{7,9})\s*[-–]\s*(.+)$", text, re.DOTALL)
    if m:
        return (m.group(1).strip(), m.group(2).strip())
    return ("", text)


def is_planeacion_table(table: list) -> bool:
    """
    Determina si una tabla contiene datos de planeación (sección 3).
    Buscamos si alguna fila tiene una fase SENA en su primera celda no vacía.
    """
    if not table or len(table) < 1:
        return False

    for row in table:
        if not row:
            continue
        non_empty = [c for c in row if clean(c)]
        if non_empty:
            if detect_fase(non_empty[0]):
                return True
    return False


def extract_info_basica(pages_text: str) -> dict:
    """
    Extrae la información del punto 1 del PDF.
    Usa el texto plano de las primeras páginas.
    """
    info = {}

    # Código SOFIA del proyecto (en el encabezado)
    m = re.search(r"C[oó]digo\s+Proyecto\s+SOFIA[:\s]+(\d+)", pages_text, re.IGNORECASE)
    if m:
        info["codigo_proyecto_sofia"] = m.group(1).strip()

    # Código del programa SOFIA
    m = re.search(r"C[oó]digo\s+del\s+Programa\s+SOFIA[:\s]+(\d+)", pages_text, re.IGNORECASE)
    if m:
        info["codigo_programa_sofia"] = m.group(1).strip()

    # Fichas asociadas
    m = re.search(r"Fichas\s+asociadas[:\s]+(\d+)", pages_text, re.IGNORECASE)
    if m:
        info["fichas_asociadas"] = m.group(1).strip()

    # Centro de Formación
    m = re.search(r"1\.1\s+Centro\s+de\s+Formaci[oó]n[:\s]+(.+?)(?:1\.2|$)", pages_text, re.IGNORECASE | re.DOTALL)
    if m:
        info["centro_formacion"] = m.group(1).strip()[:120]

    # Regional
    m = re.search(r"1\.2\s+Regional[:\s]+(.+?)(?:1\.3|$)", pages_text, re.IGNORECASE | re.DOTALL)
    extra_project_name = ""
    if m:
        # Solo la primera línea no vacía para evitar capturar texto del campo siguiente
        raw = m.group(1).strip()
        lines = [l.strip() for l in raw.splitlines() if l.strip()]
        if lines:
            info["regional"] = lines[0][:80]
            if len(lines) > 1:
                extra_project_name = " ".join(lines[1:]) + " "

    # Nombre del proyecto (1.3)
    m = re.search(r"1\.3\s+Nombre\s+del\s+proyecto[:\s]+(.+?)(?:1\.4|$)", pages_text, re.IGNORECASE | re.DOTALL)
    if m:
        project_name = extra_project_name + m.group(1)
        info["nombre_proyecto"] = re.sub(r"\s+", " ", project_name).strip()[:300]

    # Programa de formación (1.4) — puede estar en varias líneas
    m = re.search(r"1\.4\s+Programa\s+de\s+Formaci[oó]n\s+al?\s+(.+?)(?:que\s+da\s+respuesta|1\.5|$)", pages_text, re.IGNORECASE | re.DOTALL)
    if m:
        info["programa_formacion"] = re.sub(r"\s+", " ", m.group(1)).strip()[:200]

    # Tiempo estimado (1.5)
    m = re.search(r"1\.5\s+Tiempo\s+estimado.+?(\d+)\s*(?:meses)?", pages_text, re.IGNORECASE | re.DOTALL)
    if m:
        info["tiempo_estimado_meses"] = m.group(1).strip()

    # Número de resultados totales (1.8)
    m = re.search(r"N[uú]mero\s+total\s+de\s+resultados.+?(\d+)", pages_text, re.IGNORECASE | re.DOTALL)
    if m:
        info["total_resultados_programa"] = m.group(1).strip()

    # Resultados específicos (1.9.1)
    m = re.search(r"1\.9\.1\s+N[uú]mero\s+de\s+resultados.+?espec[ií]ficos.+?(\d+)", pages_text, re.IGNORECASE | re.DOTALL)
    if m:
        info["resultados_especificos"] = m.group(1).strip()

    return info

def extract_table_from_pdf(pdf_path: str) -> dict:
    """
    Función principal. Extrae información básica (punto 1) y
    la tabla de planeación del proyecto (punto 3) del PDF GFPI-F-016.
    """
    registros = []
    pages_text = ""

    with pdfplumber.open(pdf_path) as pdf:

        # ── Texto plano de las primeras páginas para sección 1 ──
        for page in pdf.pages[:4]:
            t = page.extract_text()
            if t:
                pages_text += t + "\n"

        info_basica = extract_info_basica(pages_text)

        # ── Tablas de planeación (sección 3.1-3.4) ──
        for page_num, page in enumerate(pdf.pages):
            tables = page.extract_tables()
            if not tables:
                continue

            for table in tables:
                if not is_planeacion_table(table):
                    continue

                # Detectar dinámicamente los índices de las 4 columnas de datos
                # Buscamos la primera fila que tenga al menos 4 celdas con texto y que empiece con una Fase
                col_fase = 0
                col_actividad = 1
                col_resultado = 2
                col_competencia = 3
                
                for row in table:
                    if not row: continue
                    non_empty_indices = [i for i, c in enumerate(row) if clean(c)]
                    if len(non_empty_indices) >= 4:
                        if detect_fase(clean(row[non_empty_indices[0]])):
                            col_fase = non_empty_indices[0]
                            col_actividad = non_empty_indices[1]
                            col_resultado = non_empty_indices[2]
                            col_competencia = non_empty_indices[3]
                            break

                # Extraer columnas y aplicar fill_down a Fase y Actividad
                def get_col(rows, idx):
                    return [row[idx] if row and len(row) > idx else None for row in rows]

                col_f_raw = fill_down(get_col(table, col_fase))
                col_a_raw = fill_down(get_col(table, col_actividad))
                col_r_raw = [clean(c) for c in get_col(table, col_resultado)]
                col_c_raw = [clean(c) for c in get_col(table, col_competencia)]

                for f, a, r, c in zip(col_f_raw, col_a_raw, col_r_raw, col_c_raw):
                    fase_val = f
                    act_val  = a
                    res_text = r
                    comp_text= c

                    # Saltar filas de encabezado (contienen "3.1", "Fases del Proyecto", etc.)
                    upper_f = fase_val.upper()
                    upper_a = act_val.upper()
                    if any(kw in upper_f for kw in ["3.1", "FASES DEL PROYECTO", "PLANEACI"]):
                        if not detect_fase(fase_val):
                            continue
                    if any(kw in upper_a for kw in ["3.2", "ACTIVIDADES DEL PROYECTO"]):
                        continue

                    # Saltar filas vacías
                    if not fase_val and not act_val and not res_text and not comp_text:
                        continue

                    # Normalizar fase al nombre canónico
                    fase_canonical = detect_fase(fase_val) or fase_val

                    # Saltar filas cuya fase no sea una de las 4 fases SENA válidas
                    if fase_canonical not in FASES_ORDEN:
                        continue

                    # Separar código y nombre del resultado
                    res_codigo, res_nombre = parse_resultado(res_text)

                    # Separar código y nombre de la competencia
                    comp_codigo, comp_nombre = parse_competencia(comp_text)

                    registros.append({
                        "fase":              fase_canonical,
                        "actividad":         act_val,
                        "resultado_codigo":  res_codigo,
                        "resultado_nombre":  res_nombre,
                        "competencia_codigo": comp_codigo,
                        "competencia":       comp_nombre,
                    })

    # ── Determinar el nombre más largo (más completo) para cada identidad de actividad ──
    best_act_names = {}
    for reg in registros:
        act_id = get_activity_identity(reg["actividad"])
        if not act_id: continue
        current_best = best_act_names.get(act_id, "")
        if len(str(reg["actividad"])) > len(current_best):
            best_act_names[act_id] = reg["actividad"]

    # ── Deduplicar ──
    seen = set()
    clean_registros = []
    for reg in registros:
        act_id = get_activity_identity(reg["actividad"])
        # Reemplazamos el nombre de la actividad por la versión más completa que encontramos
        if act_id in best_act_names:
            reg["actividad"] = best_act_names[act_id]
            
        key = (
            reg["fase"],
            act_id,
            reg["resultado_codigo"],
            reg["competencia_codigo"],
        )
        if key not in seen and (reg["actividad"] or reg["resultado_nombre"]):
            seen.add(key)
            clean_registros.append(reg)

    # ── Construir lista de fases únicas en orden ──
    fases_vistas = {}
    for reg in clean_registros:
        nombre = reg["fase"]
        if nombre and nombre not in fases_vistas:
            orden = FASES_ORDEN.get(nombre, 99)
            fases_vistas[nombre] = orden

    fases = sorted(
        [
            {
                "nombre_fase": nombre,
                "orden":       orden,
                "descripcion": f"Fase de {nombre.lower()} del proyecto formativo"
            }
            for nombre, orden in fases_vistas.items()
        ],
        key=lambda x: x["orden"]
    )

    if not fases:
        fases = [
            {"nombre_fase": "ANÁLISIS",   "orden": 1, "descripcion": "Fase de análisis del proyecto formativo"},
            {"nombre_fase": "PLANEACIÓN", "orden": 2, "descripcion": "Fase de planeación del proyecto formativo"},
            {"nombre_fase": "EJECUCIÓN",  "orden": 3, "descripcion": "Fase de ejecución del proyecto formativo"},
            {"nombre_fase": "EVALUACIÓN", "orden": 4, "descripcion": "Fase de evaluación del proyecto formativo"},
        ]

    # ── Construir actividades únicas ──
    act_seen = set()
    actividades = []
    for reg in clean_registros:
        act_id = get_activity_identity(reg["actividad"])
        key = (reg["fase"], act_id)
        if key not in act_seen and reg["actividad"]:
            act_seen.add(key)
            actividades.append({
                "nombre":      reg["actividad"][:255],
                "fase_nombre": reg["fase"],
                "descripcion": "",
            })

    resumen = {
        "total_fases":        len(fases),
        "total_actividades":  len(actividades),
        "total_competencias": len({r["competencia_codigo"] or r["competencia"] for r in clean_registros if r["competencia"]}),
        "total_resultados":   len({r["resultado_codigo"] or r["resultado_nombre"] for r in clean_registros if r["resultado_nombre"]}),
        "total_registros":    len(clean_registros),
    }

    return {
        "ok":               True,
        "informacion_basica": info_basica,
        "fases":            fases,
        "actividades":      actividades,
        "registros":        clean_registros,
        "resumen":          resumen,
    }


# ─────────────────────────────────────────────────────────
# Entry point
# ─────────────────────────────────────────────────────────
if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"ok": False, "error": "Uso: python extract_pdf.py <ruta_del_pdf>"}))
        sys.exit(1)

    pdf_path = sys.argv[1]

    try:
        result = extract_table_from_pdf(pdf_path)
        print(json.dumps(result, ensure_ascii=False))
    except FileNotFoundError:
        print(json.dumps({"ok": False, "error": f"Archivo no encontrado: {pdf_path}"}))
        sys.exit(1)
    except Exception as e:
        import traceback
        print(json.dumps({"ok": False, "error": str(e), "traceback": traceback.format_exc()}))
        sys.exit(1)
