# Arquitectura del Sistema

Este documento describe la arquitectura general del sistema de gestión de datos, enfocado en el procesamiento de información de aprendices, programas y juicios evaluativos.

## Estructura del Proyecto

El sistema sigue un patrón arquitectónico MVC (Modelo-Vista-Controlador).

- `models/`: Contiene la lógica de acceso a datos y las entidades principales (Aprendiz, Programa, Ficha, Juicio, Fase).
- `views/`: Contiene las interfaces de usuario, incluyendo las vistas de carga de archivos Excel y el Dashboard.
- `controllers/`: Contiene la lógica de aplicación que coordina entre modelos y vistas, incluyendo el procesamiento y validación de los datos importados.
- `config/`: Archivos de configuración de la base de datos y otras variables de entorno.
- `assets/`: Archivos estáticos como CSS, JS e imágenes para el diseño de la interfaz.
- `docs/`: Documentación del sistema.

## Componentes Clave

1. **Motor de Importación (Strategy/Adapter):** Un módulo estructurado (`services/import/`) encargado de recibir, leer y extraer datos desde archivos. Usa el patrón Adapter (`ImportAdapterInterface`) para desacoplar el sistema de librerías específicas (ej. `ExcelAdapter`, `CsvAdapter`).
2. **Sistema de Colas (Asíncrono):** Gestor de tareas en MySQL que encola los archivos subidos para que un proceso en segundo plano (Worker) los evalúe sin bloquear la interfaz web.
3. **Motor de Validación:** Verifica que los datos cumplan con las reglas de negocio antes de la persistencia.
4. **Motor de Reportes (Dashboard):** Genera estadísticas e indicadores a partir de los datos consolidados.

## Tecnologías Utilizadas
- **Backend PHP:** PHP (Motor web) con patrón MVC.
- **Microservicio Python:** FastAPI y pdfplumber (para la extracción avanzada de datos desde PDFs del SENA).
- **Procesamiento Asíncrono:** Sistema de Colas basado en MySQL y Worker (PHP CLI) para tareas pesadas.
- **Frontend:** HTML, CSS, JavaScript.
- **Base de Datos:** MySQL.
