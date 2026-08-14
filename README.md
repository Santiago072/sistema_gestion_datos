# 🎓 Sistema de Gestión de Juicios Evaluativos SENA

Sistema web para la gestión, procesamiento y visualización analítica de aprendices, programas de formación, fases del Proyecto Formativo (GFPI-F-016) y juicios evaluativos del SENA. Centraliza el seguimiento académico, automatiza la importación de datos masivos desde Excel/CSV y PDF, y entrega indicadores en tiempo real mediante Dashboards interactivos.

---

## 📚 Documentación Técnica

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

## ✨ Características Principales

- 📥 **Carga Masiva desde Excel / CSV** — Ingesta optimizada de aprendices y juicios evaluativos mediante procesamiento en lote (*Bulk Insert*) con transacciones PDO seguras.
- 📄 **Extracción Inteligente de PDF (GFPI-F-016)** — Extracción automatizada de la Sección 3 (Planeación) de proyectos formativos mediante Python y `pdfplumber`, preservando celdas combinadas, fases, actividades, competencias y resultados de aprendizaje.
- 👩‍🎓 **Consulta Integral por Aprendiz** — Búsqueda instantánea con autocompletado, visualización de estado, ficha y cumplimiento de competencias.
- 📊 **Dashboard Analítico** — KPIs en tiempo real con Chart.js: aprendices en formación vs. retirados, juicios evaluados por resultado, curva de avance y auditoría de funcionarios.
- 🗂️ **Gestión de Fases y Actividades** — Visualización interactiva y CRUD completo de fases, actividades y proyectos asociados por ficha.
- 🗑️ **Módulo de Depuración Segura** — Eliminación controlada de programas completos o aprendices individuales con validación estricta.
- 🛡️ **Seguridad Multi-Capa Integrada** — Rate limiting anti-DoS por IP, protección de directorios sensibles (`.htaccess`, `web.config`, `index.php 403`), escape XSS y cabeceras HTTP restrictivas.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología | Propósito |
|---|---|---|
| **Backend** | PHP 8.2+ | Lógica de negocio, servicios de importación y APIs REST |
| **Extractor PDF** | Python 3.10+ / `pdfplumber` | Parseo estructural de tablas y celdas del GFPI-F-016 |
| **Frontend** | Vanilla HTML5 / CSS3 / JavaScript | Interfaz rápida y responsiva sin dependencias pesadas |
| **Gráficas** | Chart.js 4.4 | Gráficos de barras, dona y métricas de rendimiento |
| **Base de Datos** | MariaDB 10.11 / MySQL | Persistencia relacional (13 tablas estructuradas) |
| **Servidor Web / Proxy** | Caddy (Docker) + Nginx (VPS) | Servidor interno ultrarrápido y proxy inverso con SSL |

---

## 🏗️ Arquitectura del Sistema

El sistema opera bajo el patrón **MVC extendido con Servicios de Ingesta**:

```
[ Cliente Web / Navegador ]
            │
            ▼ (HTTP / JSON)
[ Capa de Controladores (PHP) ] ──► Rate Limiting por IP + Sanitización
      │                 │
      ▼                 ▼
[ Servicios Import ]  [ Modelos PDO ] ──► Consultas preparadas (SQL Injection Free)
      │                       │
      │ (CLI síncrono UTF-8)  ▼
      ├──► Python Extractor  [ Base de Datos: sena_juicios ]
      │    (pdfplumber)
```

---

## 📁 Estructura de Directorios

```
sistema_gestion_datos/
├── assets/
│   ├── css/
│   │   └── styles.css              ← Design System centralizado (tokens + responsive)
│   └── js/
│       ├── fases.js                ← Lógica de interacción de proyectos y fases
│       └── pdf_upload.js           ← Drag & Drop, previsualización y confirmación de PDF
├── config/
│   ├── .htaccess                   ← Bloqueo web directo a archivos de configuración
│   ├── index.php                   ← Respuesta 403 Forbidden
│   ├── web.config                  ← Regla de denegación para IIS
│   ├── database.php                ← Conexión PDO multi-entorno (Docker / XAMPP)
│   ├── seguridad.php               ← Rate limiting IP, escape XSS y cabeceras HTTP
│   └── url_config.php              ← Detección dinámica de BASE_URL
├── controllers/
│   ├── scripts/
│   │   └── extract_pdf.py          ← Motor Python para extracción de PDF GFPI-F-016
│   ├── upload_aprendices.php       ← Ingesta masiva de Excel/CSV
│   ├── upload_pdf_fases.php        ← Extracción de PDF con respuesta JSON
│   ├── import_pdf_fases.php        ← Persistencia de fases en la BD
│   ├── fases_crud.php              ← Endpoints CRUD para fases y actividades
│   ├── eliminar_programa.php       ← Depuración de programas
│   ├── eliminar_aprendiz.php       ← Depuración de aprendices
│   └── dashboard_kpis.php          ← Métricas para el Dashboard
├── models/                         ← Modelos de datos PDO (Aprendiz, Fases, Dashboard, etc.)
├── services/                       ← Servicios de negocio y adaptadores de importación
├── sql/
│   ├── init.sql                    ← Estructura completa de las 13 tablas (MariaDB 10.11)
│   └── migrations.sql              ← Scripts de migración y ajustes de columnas
├── views/
│   ├── layouts/
│   │   ├── header.php              ← Cabecera, navegación lateral y tokens dinámicos
│   │   └── footer.php              ← Cierre de estructura y scripts
│   └── pages/                      ← Vistas principales del sistema
├── deploy.sh                       ← Script de despliegue automático en VPS
├── docker-compose.yml              ← Orquestación de contenedores (App + MariaDB)
└── Dockerfile                      ← Imagen base PHP 8.2-FPM + Caddy + Python3/pdfplumber
```

---

## 🚀 Despliegue en VPS (Docker + Nginx)

### 1. Variables de Entorno (`.env`)
```env
DB_HOST=gestion_datos_db
DB_NAME=sena_juicios
DB_USER=sena_user
DB_PASS=TuPasswordSeguro123*
MYSQL_ROOT_PASSWORD=TuRootPassword123*
APP_BASE=/
PORT=80
SESSION_LIFETIME=3600
COOKIE_SECURE=1
UPLOAD_MAX_SIZE=10485760
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,webp,pdf,xlsx
```

### 2. Despliegue Automático
```bash
cd /var/www/sistema_gestion_datos
bash deploy.sh
```

### 3. Proxy Inverso Nginx en VPS
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name gestiondatos.slscode.online;

    location / {
        proxy_pass http://127.0.0.1:8898;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 120s;
        client_max_body_size 20M;
    }
}
```

### 4. Certificado SSL con Certbot
```bash
sudo certbot --nginx -d gestiondatos.slscode.online
```
