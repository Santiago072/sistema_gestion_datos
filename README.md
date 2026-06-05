# 🎓 Sistema de Gestión de Datos SENA — Juicios Evaluativos

Sistema web para la gestión integral de aprendices, programas de formación, fases del proyecto formativo y juicios evaluativos del SENA. Permite la importación de datos desde archivos Excel y PDF, seguimiento académico en tiempo real y visualización de indicadores clave a través de un Dashboard.

---

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Tecnologías](#-tecnologías)
- [Arquitectura](#-arquitectura)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Requisitos Previos](#-requisitos-previos)
- [Instalación y Configuración](#-instalación-y-configuración)
- [Base de Datos](#-base-de-datos)
- [Módulos del Sistema](#-módulos-del-sistema)
- [Flujo de Negocio](#-flujo-de-negocio)
- [Reglas de Negocio](#-reglas-de-negocio)
- [Documentación](#-documentación)
- [Contribuir](#-contribuir)

---

## ✨ Características

- 📥 **Importación de datos** desde archivos Excel (.xls / .xlsx)
- 📄 **Lectura de PDF** con extracción de fases y competencias del proyecto formativo
- 👩‍🎓 **Gestión completa de aprendices**: registro, consulta, actualización y eliminación
- 📊 **Dashboard con KPIs**: aprendices activos, retirados, trasladados, juicios por estado
- 🔍 **Filtros avanzados** de búsqueda de aprendices por múltiples criterios
- 📈 **Seguimiento académico**: avance por competencia, cumplimiento de fases y comparativa de juicios
- 🗂️ **Gestión de fases** del proyecto formativo con actividades, competencias y resultados de aprendizaje
- 🔒 Arquitectura MVC en PHP con PDO y consultas preparadas (protección contra SQL Injection)

---

## 🛠️ Tecnologías

| Capa | Tecnología |
|---|---|
| Backend | PHP 8+ (sin framework) |
| Base de Datos | MySQL 5.7+ / MariaDB 10.5+ |
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Servidor Local | XAMPP (Apache) |
| Importación Excel | [SimpleXLS / SimpleXLSX](https://github.com/shuchkin/simplexls) |

---

## 🏗️ Arquitectura

El sistema sigue el patrón **MVC (Modelo-Vista-Controlador)**:

```
Request (HTTP)
      │
      ▼
 Controller          ← Lógica de aplicación, orquesta la operación
      │
      ├──► Model     ← Acceso a datos via PDO/MySQL
      │
      └──► View      ← Renderizado HTML de la interfaz
```

**Componentes clave:**
- **Motor de Importación:** Recibe, lee y extrae datos de archivos Excel y PDF.
- **Motor de Validación:** Aplica reglas de negocio antes de persistir datos.
- **Motor de Reportes:** Alimenta el Dashboard con indicadores y métricas.

---

## 📁 Estructura del Proyecto

```
sistema_gestion_datos/
├── assets/                  # CSS, JS e imágenes estáticas
├── config/
│   ├── database.example.php # Plantilla de configuración (COPIAR Y RENOMBRAR)
│   └── database.php         # ⚠️ NO incluido en el repo (contiene credenciales)
├── controllers/             # Endpoints PHP (lógica de aplicación)
│   ├── upload_aprendices.php
│   ├── fases_crud.php
│   ├── import_pdf_fases.php
│   ├── dashboard_kpis.php
│   └── ...
├── docs/                    # Documentación técnica del sistema
│   ├── arquitectura.md
│   ├── flujo_negocio.md
│   ├── modulos.md
│   └── reglas_negocio.md
├── libs/                    # Librerías de terceros (SimpleXLS, SimpleXLSX)
├── models/                  # Modelos de datos (clases PHP)
│   ├── AprendizModel.php
│   ├── DashboardModel.php
│   ├── FasesModel.php
│   ├── JuiciosModel.php
│   ├── ProgramaModel.php
│   └── RetiradosModel.php
├── sql/
│   └── migrations.sql       # Script de creación y migración de la BD
├── views/                   # Plantillas HTML
│   ├── layouts/             # Header y footer comunes
│   └── pages/               # Páginas de cada módulo
└── index.php                # Punto de entrada de la aplicación
```

---

## ✅ Requisitos Previos

- [XAMPP](https://www.apachefriends.org/) (Apache + PHP 8+ + MySQL) o equivalente
- Git

---

## ⚙️ Instalación y Configuración

### 1. Clonar el repositorio

```bash
git clone https://github.com/Santiago072/sistema_gestion_datos.git
```

Coloca la carpeta resultante dentro de `C:\xampp\htdocs\` (en Windows) o de tu directorio web raíz.

### 2. Configurar la base de datos

Copia el archivo de ejemplo de configuración y edítalo con tus credenciales:

```bash
# En Windows (PowerShell)
Copy-Item config\database.example.php config\database.php

# En Linux/macOS
cp config/database.example.php config/database.php
```

Edita `config/database.php` con tus datos:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_NAME', 'sena_juicios');
```

> ⚠️ **Importante:** El archivo `config/database.php` está en `.gitignore` y **nunca debe subirse al repositorio**.

### 3. Importar la base de datos

1. Abre [phpMyAdmin](http://localhost/phpmyadmin) o tu cliente MySQL.
2. Crea una base de datos llamada `sena_juicios`.
3. Importa el script:

```bash
mysql -u root -p sena_juicios < sql/migrations.sql
```

O desde phpMyAdmin: selecciona la base de datos → **Importar** → elige `sql/migrations.sql`.

### 4. Iniciar el servidor

Arranca Apache y MySQL desde el panel de control de XAMPP y accede en tu navegador a:

```
http://localhost/sistema_gestion_datos/
```

---

## 🗄️ Base de Datos

La base de datos `sena_juicios` contiene las siguientes entidades principales:

| Tabla | Descripción |
|---|---|
| `aprendices` | Información de cada aprendiz (documento, nombre, programa, estado) |
| `programas` | Catálogo de programas de formación SENA |
| `fichas` | Fichas de matrícula que agrupan aprendices por programa |
| `juicios` | Juicios evaluativos (Aprobado / No aprobado / Por evaluar) |
| `fases_proyecto` | Fases del proyecto formativo (Análisis, Planeación, Ejecución, Evaluación) |
| `actividades_fase` | Actividades detalladas por fase |
| `competencias` | Competencias de aprendizaje |
| `resultados` | Resultados de aprendizaje asociados a competencias |
| `funcionarios` | Instructores y funcionarios del centro |

La vista `v_dashboard_indicadores` consolida los KPIs del Dashboard.

---

## 📦 Módulos del Sistema

### 1. 👩‍🎓 Aprendices
Gestión CRUD completa de aprendices. Permite buscar por nombre, documento, programa o estado. Soporta importación masiva desde Excel.

### 2. 📚 Programas
Administración del catálogo de programas de formación SENA, con soporte para datos enriquecidos del PDF (código SOFIA, centro, regional).

### 3. 🔄 Fases
Gestión de las fases del proyecto formativo y sus actividades. Soporta importación desde PDFs de planes de formación SENA.

### 4. ⚖️ Juicios
Registro y consulta de los juicios evaluativos asignados a cada aprendiz por competencia y fase.

### 5. 📊 Dashboard
Tablero de indicadores con KPIs en tiempo real: aprendices activos, juicios por estado, avance por competencia, cumplimiento de fases y más.

---

## 🔄 Flujo de Negocio

```
1. INGESTA
   └── El usuario carga archivos Excel (aprendices/juicios) o PDF (plan de formación)

2. PROCESAMIENTO
   └── El sistema lee los datos estructuradamente

3. VALIDACIÓN
   └── Se verifican reglas de negocio: programas existentes, fases válidas, duplicados

4. PERSISTENCIA
   └── Los datos validados se insertan o actualizan en MySQL

5. CONSULTA
   └── Los usuarios consultan aprendices, juicios y aplican filtros avanzados

6. REPORTE
   └── El Dashboard genera KPIs e indicadores de gestión
```

---

## 📏 Reglas de Negocio

| Regla | Descripción |
|---|---|
| **Asignación de programa** | Todo aprendiz debe pertenecer a un programa de formación específico. |
| **Fichas** | Un programa agrupa aprendices mediante fichas. Un programa puede tener múltiples fichas. |
| **Multiplicidad de juicios** | Un aprendiz puede tener múltiples juicios a lo largo de su proceso formativo. |
| **Asociación de fases** | Todo juicio debe estar asociado a una fase del proyecto formativo. |
| **Validación en importación** | Los programas y fases del Excel deben coincidir con los catálogos registrados. |
| **Duplicados** | Los registros duplicados o inconsistentes se reportan y no se almacenan. |

---

## 📖 Documentación

La documentación técnica del sistema se encuentra en la carpeta [`docs/`](docs/):

| Documento | Contenido |
|---|---|
| [arquitectura.md](docs/arquitectura.md) | Arquitectura MVC, estructura de carpetas y componentes clave |
| [flujo_negocio.md](docs/flujo_negocio.md) | Ciclo de vida de los datos: ingesta, validación, persistencia y reporte |
| [modulos.md](docs/modulos.md) | Descripción detallada de cada módulo del sistema |
| [reglas_negocio.md](docs/reglas_negocio.md) | Reglas, validaciones y restricciones del sistema |

---

## 🤝 Contribuir

1. Haz un fork del proyecto.
2. Crea una rama para tu funcionalidad: `git checkout -b feature/nueva-funcionalidad`
3. Realiza tus cambios y haz commit: `git commit -m "feat: descripción del cambio"`
4. Sube tu rama: `git push origin feature/nueva-funcionalidad`
5. Abre un Pull Request.

> ⚠️ **Recuerda:** Nunca incluyas credenciales, contraseñas o datos reales de aprendices en tus commits.

---

## ⚠️ Seguridad

Este repositorio **no incluye** por defecto:
- `config/database.php` (credenciales de base de datos)
- `graphify-out/` (metadatos del grafo de conocimiento)
- `scratch/` (scripts de depuración locales)
- `tmp_pdf/` (archivos PDF temporales cargados por usuarios)
- `.agents/` y `.claude/` (configuración de agentes de IA locales)

Toda esta información está gestionada por [`.gitignore`](.gitignore).
