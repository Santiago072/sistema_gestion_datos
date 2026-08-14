# -*- coding: utf-8 -*-
"""
extract_pdf.py — Extractor de Proyecto Formativo SENA (GFPI-F-016)
Uso: python -X utf8 extract_pdf.py <ruta_del_pdf>
"""
import sys
import json
import re
import logging
import warnings
import os

warnings.filterwarnings("ignore")
logging.basicConfig(level=logging.ERROR)
for logger_name in ["pdfminer", "pypdf", "pdfplumber"]:
    logging.getLogger(logger_name).setLevel(logging.CRITICAL)

try:
    import pdfplumber
except ImportError:
    print(json.dumps({"ok": False, "error": "Librería pdfplumber no está instalada. Ejecuta: pip install pdfplumber"}))
    sys.exit(1)


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
    if not text:
        return ""
    return re.sub(r"\s*\|\s*", " ", text).strip()


def strip_stop_sections(text: str) -> str:
    if not text:
        return ""
    stop_patterns = [
        r"\s*3\.5(?:\.\d+)?\s+.*$",
        r"\s*3\.6(?:\.\d+)?\s+.*$",
        r"\s*4\.\s+RECURSOS.*$",
        r"\s*5\.\s+RUBROS.*$",
        r"\s*6\.\s+CONTROL.*$",
        r"\s*GFPI-F-016.*$",
        r"\s*P[aá]gina\s+\d+\s+de\s+\d+.*$",
        r"\s*SERVICIO NACIONAL DE APRENDIZAJE.*$",
    ]
    for pat in stop_patterns:
        text = re.sub(pat, "", text, flags=re.IGNORECASE | re.DOTALL)
    return text.strip()


def clean(text) -> str:
    if text is None:
        return ""
    text = strip_pipe(str(text))
    text = strip_stop_sections(text)
    return re.sub(r"\s+", " ", text).strip()


def get_activity_identity(text: str) -> str:
    if not text:
        return ""
    text = str(text).strip()
    m = re.match(r"^(\d+(?:\.\d+)*)", text)
    if m:
        return f"NUM_{m.group(1)}"
    norm = re.sub(r"^[\d\.\s-]+", "", text)
    norm = re.sub(r"[\.\s]+$", "", norm)
    norm = re.sub(r"\s+", " ", norm).strip().lower()
    return norm[:40]


def detect_fase(text: str) -> str | None:
    REMOVE_ACCENTS = str.maketrans("ÁÉÍÓÚÀÈÌÒÙ", "AEIOUAEIOU")
    upper = text.upper().translate(REMOVE_ACCENTS).strip()
    upper = re.sub(r"^[\d\.\s-]+", "", upper)
    for key, canonical in FASES_CANONICAL.items():
        key_clean = key.translate(REMOVE_ACCENTS)
        if upper == key_clean or upper.startswith(key_clean):
            return canonical
    return None


def fill_down(column: list) -> list:
    last = None
    result = []
    for val in column:
        v = clean(val)
        if v:
            last = v
        result.append(last or "")
    return result


def parse_resultado(text: str) -> tuple[str, str]:
    text = clean(text)
    if not text:
        return ("", "")
    
    codigo = ""
    nombre = text

    m = re.match(r"^(\d{5,9})\s*[-–:]\s*(?:\d{1,3}[-–:\.\s]+)?(.+)$", text, re.DOTALL)
    if m:
        codigo = m.group(1).strip()
        nombre = m.group(2).strip()
    else:
        m2 = re.match(r"^(\d{5,9})\s+(.+)$", text, re.DOTALL)
        if m2:
            codigo = m2.group(1).strip()
            nombre = m2.group(2).strip()

    nombre = re.sub(r"^[-–:\.\s]+", "", nombre).strip()
    nombre = re.sub(r"^\d{1,2}\s+[-–]?\s*", "", nombre).strip()
    return (codigo, nombre)


def parse_competencia(text: str) -> tuple[str, str]:
    text = clean(text)
    if not text:
        return ("", "")
    m = re.match(r"^(\d{7,9})\s*[-–]\s*(.+)$", text, re.DOTALL)
    if m:
        return (m.group(1).strip(), m.group(2).strip())
    return ("", text)


def is_planeacion_table(table: list) -> bool:
    if not table or len(table) < 1:
        return False
    for row in table:
        if not row:
            continue
        non_empty = [c for c in row if clean(c)]
        if non_empty and detect_fase(non_empty[0]):
            return True
    return False


def extract_info_basica(pages_text: str) -> dict:
    info = {}
    m = re.search(r"C[oó]digo\s+Proyecto\s+SOFIA[:\s]+(\d+)", pages_text, re.IGNORECASE)
    if m: info["codigo_proyecto_sofia"] = m.group(1).strip()

    m = re.search(r"C[oó]digo\s+del\s+Programa\s+SOFIA[:\s]+(\d+)", pages_text, re.IGNORECASE)
    if m: info["codigo_programa_sofia"] = m.group(1).strip()

    m = re.search(r"Fichas\s+asociadas[:\s]+(\d+)", pages_text, re.IGNORECASE)
    if m: info["fichas_asociadas"] = m.group(1).strip()

    m = re.search(r"1\.1\s+Centro\s+de\s+Formaci[oó]n[:\s]+(.+?)(?:1\.2|$)", pages_text, re.IGNORECASE | re.DOTALL)
    if m: info["centro_formacion"] = m.group(1).strip()[:120]

    m = re.search(r"1\.2\s+Regional[:\s]+(.+?)(?:1\.3|$)", pages_text, re.IGNORECASE | re.DOTALL)
    extra_project_name = ""
    if m:
        raw = m.group(1).strip()
        lines = [l.strip() for l in raw.splitlines() if l.strip()]
        if lines:
            info["regional"] = lines[0][:80]
            if len(lines) > 1:
                extra_project_name = " ".join(lines[1:]) + " "

    m = re.search(r"1\.3\s+Nombre\s+del\s+proyecto[:\s]+(.+?)(?:1\.4|$)", pages_text, re.IGNORECASE | re.DOTALL)
    if m:
        project_name = extra_project_name + m.group(1)
        info["nombre_proyecto"] = re.sub(r"\s+", " ", project_name).strip()[:300]

    m = re.search(r"1\.4\s+Programa\s+de\s+Formaci[oó]n\s+al?\s+(.+?)(?:que\s+da\s+respuesta|1\.5|$)", pages_text, re.IGNORECASE | re.DOTALL)
    if m:
        info["programa_formacion"] = re.sub(r"\s+", " ", m.group(1)).strip()[:200]

    m = re.search(r"1\.5\s+Tiempo\s+estimado.+?(\d+)\s*(?:meses)?", pages_text, re.IGNORECASE | re.DOTALL)
    if m: info["tiempo_estimado_meses"] = m.group(1).strip()

    m = re.search(r"N[uú]mero\s+total\s+de\s+resultados.+?(\d+)", pages_text, re.IGNORECASE | re.DOTALL)
    if m: info["total_resultados_programa"] = m.group(1).strip()

    m = re.search(r"1\.9\.1\s+N[uú]mero\s+de\s+resultados.+?espec[ií]ficos.+?(\d+)", pages_text, re.IGNORECASE | re.DOTALL)
    if m: info["resultados_especificos"] = m.group(1).strip()

    return info


def extract_table_from_pdf(pdf_path: str) -> dict:
    registros = []
    pages_text = ""

    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages[:4]:
            t = page.extract_text()
            if t:
                pages_text += t + "\n"

        info_basica = extract_info_basica(pages_text)

        for page_num, page in enumerate(pdf.pages):
            tables = page.extract_tables()
            if not tables:
                continue

            for table in tables:
                if not is_planeacion_table(table):
                    continue

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

                    upper_f = fase_val.upper()
                    upper_a = act_val.upper()
                    if any(kw in upper_f for kw in ["3.1", "FASES DEL PROYECTO", "PLANEACI"]):
                        if not detect_fase(fase_val):
                            continue
                    if any(kw in upper_a for kw in ["3.2", "ACTIVIDADES DEL PROYECTO"]):
                        continue

                    if not fase_val and not act_val and not res_text and not comp_text:
                        continue

                    # Ignorar filas de stop (3.5, 4. RECURSOS)
                    if any(re.search(r"^(?:3\.5|4\.|5\.|6\.|GFPI)", x, re.I) for x in [res_text, comp_text] if x):
                        continue

                    fase_canonical = detect_fase(fase_val) or fase_val
                    if fase_canonical not in FASES_ORDEN:
                        continue

                    has_res_code = bool(re.match(r"^\d{5,9}", res_text))
                    has_comp_code = bool(re.match(r"^\d{7,9}", comp_text))

                    # Si es una línea de continuación sin código, unir a la entrada anterior
                    if registros and not has_res_code and not has_comp_code and (res_text or comp_text):
                        if res_text:
                            registros[-1]["resultado_nombre"] = (registros[-1]["resultado_nombre"] + " " + res_text).strip()
                        if comp_text:
                            registros[-1]["competencia"] = (registros[-1]["competencia"] + " " + comp_text).strip()
                        continue

                    res_codigo, res_nombre = parse_resultado(res_text)
                    comp_codigo, comp_nombre = parse_competencia(comp_text)

                    registros.append({
                        "fase":              fase_canonical,
                        "actividad":         act_val,
                        "resultado_codigo":  res_codigo,
                        "resultado_nombre":  res_nombre,
                        "competencia_codigo": comp_codigo,
                        "competencia":       comp_nombre,
                    })

    # Determinar el nombre más largo para cada actividad
    best_act_names = {}
    for reg in registros:
        act_id = get_activity_identity(reg["actividad"])
        if not act_id: continue
        current_best = best_act_names.get(act_id, "")
        if len(str(reg["actividad"])) > len(current_best):
            best_act_names[act_id] = reg["actividad"]

    # Deduplicar
    seen = set()
    clean_registros = []
    for reg in registros:
        act_id = get_activity_identity(reg["actividad"])
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

    # Fases únicas
    fases_vistas = {}
    for reg in clean_registros:
        nombre = reg["fase"]
        if nombre and nombre not in fases_vistas:
            fases_vistas[nombre] = FASES_ORDEN.get(nombre, 99)

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

    # Actividades únicas
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


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"ok": False, "error": "Uso: python extract_pdf.py <ruta_del_pdf>"}))
        sys.exit(1)

    pdf_path = sys.argv[1]
    try:
        result = extract_table_from_pdf(pdf_path)
        print(json.dumps(result, ensure_ascii=False))
    except Exception as e:
        import traceback
        print(json.dumps({"ok": False, "error": str(e), "traceback": traceback.format_exc()}))
        sys.exit(1)
