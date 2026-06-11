# Flujo de Negocio

Este documento detalla el ciclo de vida de los datos dentro del sistema, desde su ingreso hasta su visualización.

## 1. Ingesta de Datos
- **Carga de Archivos:** Los usuarios cargan la información de aprendices, programas y juicios a través de archivos Excel.

## 2. Procesamiento y Validación (Asíncrono)
- El usuario sube un archivo Excel o CSV (Aprendices).
- El sistema recibe el archivo y lo envía al Gestor de Colas (MySQL).
- El sistema invoca automáticamente un `Auto-Worker` en segundo plano.
- El Frontend (Vista) se mantiene actualizando el estado de la tarea (Polling a la BD).
- El Auto-Worker utiliza el Motor de Importación (Adapters) para leer y procesar el archivo por lotes.
- Se aplica el Motor de Validación (no duplicar, campos obligatorios).
- Los datos se guardan en la DB.

### Subida de Plantillas de Fase (PDF)
- El usuario sube el reporte en PDF desde SOFIA Plus.
- El sistema lo recibe y lo guarda temporalmente.
- El sistema invoca síncronamente al script `extract_pdf.py` usando `shell_exec`.

## 3. Persistencia
- **Almacenamiento:** Una vez validados, los registros se insertan o actualizan en la base de datos relacional.

## 4. Consulta y Explotación
- **Consulta de Usuarios:** Los usuarios pueden consultar la información consolidada de los aprendices, sus programas y sus respectivos juicios.
- **Dashboard y Reportes:** El sistema procesa la información para alimentar un Dashboard, generando indicadores, métricas de rendimiento y estadísticas generales.