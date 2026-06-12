# Arquitectura del Sistema

Este documento describe la arquitectura general del sistema de gestión de datos SENA, enfocado en el procesamiento de información de aprendices, programas y juicios evaluativos.

## Estructura del Proyecto

El sistema sigue el patrón arquitectónico **MVC (Modelo-Vista-Controlador)**, extendido con una capa de Servicios para lógica de negocio compleja.

```
sistema_gestion_datos/
├── assets/
│   ├── css/
│   │   └── styles.css          ← Design System centralizado (tokens, componentes, responsive)
│   └── js/
│       ├── fases.js            ← Lógica JS del módulo Fases (búsqueda, CRUD, filtros)
│       └── pdf_upload.js       ← Lógica JS para subida y previsualización de PDF
├── config/
│   └── database.php            ← Conexión PDO a MySQL (sena_juicios)
├── controllers/
│   ├── scripts/
│   │   ├── extract_pdf.py      ← Script Python: extrae datos de PDF GFPI-F-016 (pdfplumber)
│   │   └── app.py              ← Micro-API Flask (endpoint HTTP para extract_pdf.py)
│   ├── upload_pdf_fases.php    ← Recibe PDF → llama Flask API → devuelve JSON al frontend
│   ├── fases_crud.php          ← API REST para CRUD de Fases y Actividades
│   ├── dashboard_kpis.php      ← KPIs del dashboard principal
│   ├── filtro_avanzado.php     ← Búsqueda avanzada paginada de juicios
│   ├── aprendices_formacion.php
│   ├── comparativa_juicios.php
│   ├── retirados_competencia.php
│   └── auditoria_funcionarios.php
├── models/
│   ├── BaseModel.php           ← Clase base PDO
│   ├── FasesModel.php          ← CRUD de Fases, Actividades y Relaciones
│   ├── DashboardModel.php      ← KPIs del dashboard principal
│   ├── DashboardFasesRepository.php ← Consultas pesadas del Dashboard de Fases
│   ├── DashboardModel.php
│   ├── JuiciosModel.php
│   └── AprendizModel.php
├── services/
│   └── Import/
│       └── FasesImportService.php ← Bulk Insert de Fases/Actividades desde PDF
├── views/
│   ├── layouts/
│   │   ├── header.php          ← Navbar + sidebar + meta SEO
│   │   └── footer.php          ← Scripts globales (Chart.js, badgeEstado, badgeJuicio)
│   └── pages/
│       ├── dashboard.php       ← Dashboard principal con KPIs y filtros avanzados
│       ├── dashboard_fases.php ← Dashboard de cumplimiento de fases
│       ├── fases_proyecto.php  ← Gestión de Fases Formativas (CRUD + carga de PDF)
│       ├── carga_masiva.php    ← Carga masiva de aprendices/juicios desde Excel
│       ├── consulta_aprendiz.php
│       ├── eliminacion_masiva.php
│       └── proyectos_formativos.php
└── docs/                       ← Documentación del sistema
```

## Capas de la Arquitectura

### 1. Presentación (Frontend)
- **Un único `styles.css`** como Design System centralizado con tokens CSS (colores, radios, sombras), componentes semánticos (`.kpi-card`, `.fase-item`, `.card`, `.badge`) y **media queries completas** (xs/sm/md/lg/xl/2xl + landscape + print) para responsividad total.
- **JavaScript externo** en `/assets/js/`: la lógica compleja se separa de las vistas PHP. `fases.js` maneja búsqueda en tiempo real con resaltado (`<mark>`), cache local de arrays para filtros instantáneos sin peticiones extra, y estados de carga en botones.
- **Chart.js** para gráficas dinámicas (barras, líneas de supervivencia, donut).

### 2. Controladores
- Reciben peticiones HTTP, delegan al modelo/servicio y devuelven JSON o HTML.
- `upload_pdf_fases.php`: ya NO usa `shell_exec`. En su lugar **auto-inicia la micro-API Flask** via `popen()` si no está corriendo, y luego se comunica con ella mediante `curl`.

### 3. Servicios
- `FasesImportService.php`: Centraliza la inserción masiva de fases/actividades/relaciones usando **Bulk Inserts** (lotes de 500 registros), evitando N+1 queries durante la importación de PDFs.

### 4. Modelos
- `DashboardFasesRepository.php`: Separa las consultas complejas de estadísticas del modelo transaccional `FasesModel`.

### 5. Microservicio Python (Flask)
- `controllers/scripts/app.py`: Servidor Flask que expone `POST /extract-pdf` y `GET /health`.
- `extract_pdf.py`: Lógica de extracción de tablas del PDF GFPI-F-016 usando `pdfplumber`. Detecta fases canónicas SENA, llena celdas combinadas y deduplica actividades.
- **Auto-inicio transparente**: PHP verifica `/health` antes de cada subida de PDF. Si el servicio está apagado, lo inicia automáticamente en segundo plano (`start /B`). El usuario no necesita hacer nada manual.

## Tecnologías Utilizadas

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8+ (MVC + Servicios) |
| Microservicio PDF | Python 3.13 + Flask + pdfplumber |
| Procesamiento Asíncrono (Excel) | Sistema de Colas MySQL + Worker PHP CLI |
| Frontend | HTML5 + Vanilla CSS (Design System) + JavaScript |
| Gráficas | Chart.js |
| Base de Datos | MySQL (`sena_juicios`) vía PDO |
| Servidor Local | XAMPP (Apache + MySQL) |
