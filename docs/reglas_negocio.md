# Reglas de Negocio

Este documento define las reglas, validaciones y restricciones que rigen el comportamiento del sistema.

---

## 1. Modelo de Datos — Relaciones de Entidades

### Aprendices y Programas
- Todo aprendiz debe pertenecer a **exactamente un programa de formación** (a través de una ficha).
- Un programa puede contener **varias fichas**; una ficha agrupa a un conjunto de aprendices.
- Estados válidos de un aprendiz: `En formación`, `Retirado`, `Trasladado`, `Egresado`.

### Evaluación (Juicios)
- Un aprendiz puede tener **múltiples juicios evaluativos** a lo largo de su proceso formativo.
- Cada juicio se asocia obligatoriamente a: **Aprendiz → Competencia → Resultado de Aprendizaje → Funcionario**.
- Tipos de juicio válidos: `Aprobado`, `Por evaluar`, `No aprobado`.

### Fases Formativas
- Las fases canónicas del SENA (GFPI-F-016) son exactamente 4, en orden:
  1. `ANÁLISIS` (orden 1)
  2. `PLANEACIÓN` (orden 2)
  3. `EJECUCIÓN` (orden 3)
  4. `EVALUACIÓN` (orden 4)
- Cada fase puede tener **múltiples actividades** asociadas.
- Si el PDF no contiene fases explícitas, se insertan las 4 fases canónicas por defecto.

---

## 2. Reglas de Importación desde Excel

- **Sin duplicados:** No se inserta un aprendiz si su documento ya existe en la ficha.
- **Campos obligatorios:** Documento, Nombre, Programa/Ficha deben estar presentes; las filas incompletas se rechazan.
- **Auditoría de errores:** Cada fila rechazada genera un registro en `logs_importacion` con el motivo exacto del fallo.
- **Transacciones:** La inserción usa `BEGIN TRANSACTION` / `COMMIT`. Si un lote falla, se hace `ROLLBACK` parcial para evitar datos a medias.
- **Bulk Insert:** Los registros se agrupan en lotes de 500 antes de cada `INSERT`, evitando N+1 queries.

---

## 3. Reglas de Importación desde PDF (Fases)

- Solo se procesan tablas que contengan al menos una de las 4 fases canónicas SENA en su primera columna no vacía.
- Las celdas combinadas (merged cells) se reconstruyen aplicando **fill-down** en las columnas de Fase y Actividad.
- El identificador de una actividad se extrae del número inicial (ej: `4.` → `NUM_4`) para desduplicar nombres que varían levemente entre filas.
- Solo se conserva la versión **más larga/completa** del nombre de una actividad.
- Filas con Fase + Actividad + Resultado + Competencia todos vacíos son descartadas.
- Si el código de una competencia o resultado no tiene el formato SENA (`NNN... - Nombre`), se guarda el texto completo como nombre sin código.

---

## 4. Reglas del Dashboard

- Los KPIs del dashboard principal se filtran por ficha/programa si se selecciona uno en el selector global; de lo contrario muestran totales globales.
- La curva de supervivencia solo grafica programas que tengan al menos un aprendiz retirado o trasladado registrado en una competencia.
- El filtro avanzado no ejecuta ninguna query si **todos los campos** están vacíos (para evitar traer toda la tabla).

---

## 5. Reglas del Microservicio Python

- El microservicio Flask escucha exclusivamente en `127.0.0.1:5000` (solo tráfico local, no expuesto a la red).
- PHP auto-inicia el servicio si el endpoint `/health` no responde, esperando hasta **5 segundos** (10 intentos × 500ms).
- Si tras 5 segundos el servicio sigue inactivo, el controlador PHP devuelve un error `500` descriptivo al usuario.
- El PDF temporal se elimina del servidor inmediatamente después de que Flask responde (éxito o error).
- El tamaño máximo de PDF aceptado es **10 MB**.
