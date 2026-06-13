# 🎓 Sistema de Gestión de Juicios Evaluativos SENA

Sistema web para la gestión integral de aprendices, programas de formación, fases del Proyecto Formativo y juicios evaluativos del SENA. Centraliza el seguimiento académico, automatiza la importación de datos desde Excel y PDF, y entrega indicadores en tiempo real a través de un Dashboard analítico.

---

## 📚 Documentación y Manuales

| Documento | Descripción |
|---|---|
| [📋 Especificación de Requisitos](docs/Especificacion_Requisitos.md) | Requisitos funcionales (RF) y no funcionales (RNF), modelo de datos y restricciones |
| [📘 Manual de Usuario](docs/Manual_de_Usuario.md) | Guía paso a paso para usar cada módulo del sistema |
| [🏗 Arquitectura](docs/arquitectura.md) | Estructura de carpetas, capas del sistema y tecnologías |
| [🔄 Flujo de Negocio](docs/flujo_negocio.md) | Ciclo de vida de los datos: ingesta, procesamiento, persistencia y consulta |
| [📦 Módulos](docs/modulos.md) | Descripción detallada de cada módulo y sus componentes |
| [📏 Reglas de Negocio](docs/reglas_negocio.md) | Validaciones, restricciones y reglas que rigen el sistema |
| [📝 Changelog](docs/changelog.md) | Historial de mejoras y refactorizaciones aplicadas |

---

## ✨ Características

- 📥 **Importación asíncrona desde Excel** — Los aprendices y juicios se procesan en segundo plano (cola MySQL + Worker PHP) sin bloquear la interfaz
- 📄 **Extracción de PDF automática** — El Proyecto Formativo SENA (GFPI-F-016) se procesa mediante un microservicio Python (Flask + pdfplumber) que se inicia solo, sin intervención manual
- 👩‍🎓 **Gestión completa de aprendices** — CRUD, importación masiva, búsqueda con filtros múltiples
- 📊 **Dashboard analítico con 6 KPIs** — Aprendices activos, juicios por estado, curva de supervivencia por competencia, comparativa de instructores
- 🔍 **Filtro avanzado de juicios** — Búsqueda combinada por documento, competencia, resultado, estado y tipo de juicio con exportación CSV
- 🗂️ **Gestión de Fases Formativas** — CRUD de fases y actividades con búsqueda en tiempo real y resaltado de términos
- 🎨 **Design System único** — Un solo archivo CSS con tokens, componentes semánticos y media queries completas (xs a 2xl + print)
- 🔒 **Consultas preparadas (PDO)** — Protección contra SQL Injection en todas las operaciones

---

## 🛠️ Tecnologías

| Capa | Tecnología |
|---|---|
| Backend | PHP 8+ (Patrón MVC + Capa de Servicios) |
| Microservicio PDF | Python 3.10+ · Flask · pdfplumber |
| Procesamiento Asíncrono | Cola MySQL + Worker PHP CLI |
| Frontend | HTML5 · CSS3 (Design System) · JavaScript Vanilla |
| Gráficas | Chart.js |
| Base de Datos | MySQL (`sena_juicios`) vía PDO |
| Servidor Local | XAMPP (Apache + MySQL) |

---

## 🏗️ Arquitectura

El sistema sigue el patrón **MVC extendido con Servicios y Repositorios**:

```
Request (HTTP)
      │
      ▼
 Controller          ← Orquesta la operación, delega al Servicio o Modelo
      │
      ├──► Service   ← Lógica de negocio compleja (Bulk Insert, importaciones)
      │
      ├──► Model     ← Acceso a datos via PDO/MySQL
      │
      └──► View      ← HTML + CSS (Design System) + JavaScript externo
                               │
                               └──► Python Micro-API (Flask)
                                    └── pdfplumber (extracción de PDF GFPI-F-016)
```

Ver detalles completos en [docs/arquitectura.md](docs/arquitectura.md).

---

## 📁 Estructura del Proyecto

```
sistema_gestion_datos/
├── assets/
│   ├── css/
│   │   └── styles.css              ← Design System centralizado (tokens + responsive)
│   └── js/
│       ├── fases.js                ← Lógica JS del módulo Fases (filtros, CRUD, modales)
│       └── pdf_upload.js           ← Lógica JS para subida y previsualización de PDF
├── config/
│   ├── database.example.php        ← Plantilla de configuración (COPIAR Y RENOMBRAR)
│   └── database.php                ← ⚠️ NO incluido en el repo (contiene credenciales)
├── controllers/
│   ├── scripts/
│   │   ├── extract_pdf.py          ← Extractor de datos de PDF GFPI-F-016 (pdfplumber)
│   │   └── app.py                  ← Micro-API Flask (HTTP endpoint para el extractor)
│   ├── upload_pdf_fases.php        ← Recibe PDF → auto-inicia Flask → devuelve JSON
│   ├── fases_crud.php              ← API REST para CRUD de Fases y Actividades
│   ├── dashboard_kpis.php          ← KPIs del Dashboard principal
│   ├── filtro_avanzado.php         ← Búsqueda paginada de juicios
│   └── ...
├── docs/                           ← Documentación técnica y manuales
│   ├── Especificacion_Requisitos.md
│   ├── Manual_de_Usuario.md
│   ├── arquitectura.md
│   ├── flujo_negocio.md
│   ├── modulos.md
│   ├── reglas_negocio.md
│   └── changelog.md
├── models/
│   ├── BaseModel.php
│   ├── AprendizModel.php
│   ├── DashboardModel.php
│   ├── DashboardFasesRepository.php ← Consultas estadísticas separadas del modelo transaccional
│   ├── FasesModel.php
│   └── JuiciosModel.php
├── services/
│   └── Import/
│       └── FasesImportService.php   ← Bulk Insert de fases/actividades desde PDF
├── sql/
│   └── migrations.sql               ← Script de creación y migración de la BD
├── views/
│   ├── layouts/
│   │   ├── header.php              ← Sidebar + navbar + meta SEO
│   │   └── footer.php              ← Scripts globales (Chart.js, badges)
│   └── pages/
│       ├── dashboard.php
│       ├── dashboard_fases.php
│       ├── fases_proyecto.php
│       ├── carga_masiva.php
│       ├── consulta_aprendiz.php
│       ├── eliminacion_masiva.php
│       └── proyectos_formativos.php
└── index.php                        ← Punto de entrada de la aplicación
```

---

## ✅ Requisitos Previos

- [XAMPP](https://www.apachefriends.org/) (Apache + PHP 8+ + MySQL)
- [Python 3.10+](https://www.python.org/) con `flask` y `pdfplumber`
- Git

---

## ⚙️ Instalación y Configuración

### 1. Clonar el repositorio

```bash
git clone https://github.com/Santiago072/sistema_gestion_datos.git
```

Coloca la carpeta en `C:\xampp\htdocs\` (Windows) o en tu directorio web raíz.

### 2. Instalar dependencias Python

```bash
pip install flask pdfplumber
```

> Solo es necesario para el módulo de importación de PDF. El sistema lo inicia automáticamente cuando se necesita.

### 3. Configurar la base de datos

Copia el archivo de configuración de ejemplo:

```powershell
# Windows (PowerShell)
Copy-Item config\database.example.php config\database.php
```

Edita `config/database.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // tu contraseña de MySQL
define('DB_NAME', 'sena_juicios');
```

> ⚠️ El archivo `config/database.php` está en `.gitignore` y **nunca debe subirse al repositorio**.

### 4. Importar la base de datos

1. Inicia Apache y MySQL desde el Panel de Control de XAMPP.
2. Abre [phpMyAdmin](http://localhost/phpmyadmin).
3. Crea una base de datos llamada `sena_juicios`.
4. Importa el script SQL:

```bash
mysql -u root sena_juicios < sql/migrations.sql
```

O desde phpMyAdmin: selecciona la base de datos → **Importar** → elige `sql/migrations.sql`.

### 5. Acceder al sistema

```
http://localhost/sistema_gestion_datos/
```

---

## 🗄️ Base de Datos

La base de datos `sena_juicios` contiene las siguientes tablas principales:

| Tabla | Descripción |
|---|---|
| `aprendices` | Información de cada aprendiz (documento, nombre, programa, estado) |
| `programas` | Catálogo de programas de formación SENA |
| `juicios` | Juicios evaluativos (Aprobado / No aprobado / Por evaluar) |
| `fases_proyecto` | Fases del proyecto formativo (Análisis, Planeación, Ejecución, Evaluación) |
| `actividades_fase` | Actividades detalladas por fase |
| `competencias` | Competencias de aprendizaje |
| `resultados` | Resultados de aprendizaje asociados a competencias |
| `funcionarios` | Instructores y funcionarios del centro |
| `trabajos_importacion` | Cola de trabajos asíncronos de importación |
| `logs_importacion` | Auditoría de errores por fila durante importaciones |

---

## 📏 Reglas de Negocio Principales

| Regla | Descripción |
|---|---|
| **Asignación de programa** | Todo aprendiz debe pertenecer a un programa/ficha específico |
| **Multiplicidad de juicios** | Un aprendiz puede tener múltiples juicios a lo largo de su proceso formativo |
| **Fases canónicas SENA** | Las 4 fases válidas del GFPI-F-016 son: Análisis, Planeación, Ejecución, Evaluación |
| **Sin duplicados** | Los registros duplicados en Excel se detectan y se reportan sin insertarse |
| **Transacciones** | Las inserciones masivas usan `BEGIN`/`COMMIT`/`ROLLBACK` para garantizar integridad |
| **Bulk Insert** | Las importaciones agrupan registros en lotes de 500 para máxima eficiencia |
| **PDF temporal** | El PDF se elimina del servidor inmediatamente tras ser procesado |

Ver el detalle completo en [docs/reglas_negocio.md](docs/reglas_negocio.md).

---

## ⚠️ Seguridad

Este repositorio **no incluye** por defecto:
- `config/database.php` — credenciales de base de datos
- `graphify-out/` — metadatos del grafo de conocimiento local
- `tmp_pdf/` y `tmp_uploads/` — archivos temporales cargados por usuarios
- `.agents/` y `.claude/` — configuración de agentes de IA locales

Toda esta información está gestionada por [`.gitignore`](.gitignore).

---

## 🤝 Contribuir

1. Haz un fork del proyecto.
2. Crea una rama para tu funcionalidad: `git checkout -b feature/nueva-funcionalidad`
3. Realiza tus cambios y haz commit siguiendo el formato convencional:
   - `feat:` nueva funcionalidad
   - `fix:` corrección de bug
   - `perf:` mejora de rendimiento
   - `UI:` cambio de interfaz
   - `docs:` actualización de documentación
4. Sube tu rama: `git push origin feature/nueva-funcionalidad`
5. Abre un Pull Request.

> ⚠️ **Nunca incluyas credenciales, contraseñas ni datos reales de aprendices en tus commits.**
