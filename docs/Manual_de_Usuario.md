# 📘 Manual de Usuario — Sistema de Gestión de Juicios Evaluativos SENA

> **Institución:** Centro de Formación SENA · **Plataforma:** Web (PHP + MySQL)

Este documento describe paso a paso cómo utilizar el Sistema de Gestión de Juicios Evaluativos del SENA. Está dirigido a coordinadores académicos e instructores del centro de formación.

---

## Índice

1. [Acceso al Sistema](#1-acceso-al-sistema)
2. [Navegación General](#2-navegación-general)
3. [Dashboard Principal](#3-dashboard-principal)
   - [3.1 Tarjetas KPI](#31-tarjetas-kpi)
   - [3.2 Gráficas](#32-gráficas)
   - [3.3 Filtro Avanzado de Juicios](#33-filtro-avanzado-de-juicios)
   - [3.4 Auditoría por Funcionario](#34-auditoría-por-funcionario)
4. [Módulo de Fases Formativas](#4-módulo-de-fases-formativas)
   - [4.1 Selector de Programa](#41-selector-de-programa)
   - [4.2 Gestión de Fases (CRUD)](#42-gestión-de-fases-crud)
   - [4.3 Gestión de Actividades](#43-gestión-de-actividades)
   - [4.4 Cargar Fases desde PDF](#44-cargar-fases-desde-pdf)
5. [Módulo de Carga Masiva](#5-módulo-de-carga-masiva)
   - [5.1 Importar Aprendices desde Excel](#51-importar-aprendices-desde-excel)
   - [5.2 Importar Juicios desde Excel](#52-importar-juicios-desde-excel)
6. [Módulo de Consulta de Aprendiz](#6-módulo-de-consulta-de-aprendiz)
7. [Módulo de Eliminación Masiva](#7-módulo-de-eliminación-masiva)
8. [Dashboard de Fases](#8-dashboard-de-fases)
9. [Cambio de Tema Visual](#9-cambio-de-tema-visual)
10. [Preguntas Frecuentes y Solución de Problemas](#10-preguntas-frecuentes-y-solución-de-problemas)

---

## 1. Acceso al Sistema

1. Abre tu navegador web (Chrome, Edge o Firefox recomendado).
2. Navega a la URL del sistema:
   ```
   http://localhost/sistema_gestion_datos/
   ```
3. El sistema carga directamente al **Dashboard Principal**.

> **Nota:** El sistema opera en red local (XAMPP). Asegúrate de que Apache y MySQL estén iniciados en el Panel de Control de XAMPP antes de acceder.

---

## 2. Navegación General

La interfaz tiene dos áreas principales:

| Área | Descripción |
|---|---|
| **Barra lateral izquierda (Sidebar)** | Menú de navegación entre módulos. En pantallas pequeñas se oculta y se abre con el botón ☰ |
| **Área de contenido** | El módulo activo se muestra aquí |

### Menú lateral

| Opción | Módulo |
|---|---|
| 📊 Dashboard | Indicadores generales y filtro avanzado de juicios |
| 🔄 Fases Formativas | Gestión de fases y carga de PDF del Proyecto Formativo |
| 📥 Carga Masiva | Importación de aprendices y juicios desde Excel |
| 🔍 Consulta Aprendiz | Búsqueda individual de aprendiz y su historial |
| 🗑 Eliminación Masiva | Eliminación controlada de registros |
| 📈 Dashboard Fases | Cumplimiento y avance por fase del proyecto formativo |

---

## 3. Dashboard Principal

El Dashboard es la página de inicio. Muestra indicadores en tiempo real que se actualizan automáticamente al seleccionar un programa distinto.

### 3.1 Tarjetas KPI

En la parte superior encontrarás **6 tarjetas de indicadores**:

| Tarjeta | Qué muestra |
|---|---|
| 🟢 **Aprendices Activos** | Total de aprendices con estado "En formación" |
| 🟢 **Juicios Aprobados** | Total de juicios con resultado "Aprobado" |
| 🟠 **Por Evaluar** | Juicios pendientes de resultado |
| 🟣 **Programas** | Total de programas de formación registrados |
| 🟡 **Retirados** | Aprendices con estado "Retirado" |
| 🟡 **Trasladados** | Aprendices con estado "Trasladado" |

Los números aparecen con una animación de contador al cargar. Si seleccionas un programa en el **selector global** (esquina superior del dashboard), los KPIs se actualizarán para ese programa específico.

### 3.2 Gráficas

**Gráfica 1 — Aprendices por Formación:**
- Muestra una barra apilada por programa.
- Colores: verde (En formación), rojo (Retirado), azul (Trasladado).
- Debajo de la gráfica aparece una tabla con los mismos datos.

**Gráfica 2 — Comparativa de Juicios por Instructor:**
- Barras horizontales, una por instructor/funcionario.
- Compara el volumen de Aprobados, Por Evaluar y No Aprobados por persona.

**Gráfica 3 — Curva de Supervivencia:**
- Línea por programa que muestra cuántos aprendices permanecen activos competencia a competencia.
- Útil para identificar en qué punto del proceso formativo se producen los retiros.
- Al pasar el cursor sobre un punto de la línea, aparece un tooltip con los nombres de los aprendices que salieron en esa competencia y el funcionario que los tenía asignados.

### 3.3 Filtro Avanzado de Juicios

Esta sección permite buscar juicios específicos con múltiples criterios combinados:

| Campo | Cómo usarlo |
|---|---|
| **Documento** | Escribe el número de documento del aprendiz (búsqueda automática al escribir) |
| **Competencia** | Escribe parte del código o nombre de la competencia |
| **Resultado de Aprendizaje** | Escribe parte del código o nombre del resultado |
| **Estado** | Selecciona el estado del aprendiz en el desplegable |
| **Tipo de Juicio** | Selecciona entre Aprobado, Por evaluar o No aprobado |

**Funcionalidades adicionales:**
- Los resultados se resaltan en **amarillo** el término que escribiste.
- Aparecen **chips** en la parte superior indicando los filtros activos; puedes eliminar uno individualmente haciendo clic en su ✕.
- El botón **Limpiar Todo** resetea todos los filtros.
- El botón **⬇ Exportar CSV** descarga todos los resultados del filtro actual en formato CSV.
- Los resultados se paginan con navegación de páginas en la parte inferior.

### 3.4 Auditoría por Funcionario

Al final del Dashboard hay una tabla que muestra, por cada instructor registrado:
- Total de juicios que ha registrado.
- Desglose: Aprobados / Por Evaluar / No Aprobados.
- Fecha de su primer y último registro en el sistema.

---

## 4. Módulo de Fases Formativas

Accede desde **Sidebar → Fases Formativas**.

### 4.1 Selector de Programa

En la parte superior encontrarás un selector de programa/ficha. **Selecciona siempre el programa con el que quieres trabajar** antes de crear o modificar fases. Si no seleccionas ninguno, el sistema mostrará fases globales y deshabilitará los botones de creación.

### 4.2 Gestión de Fases (CRUD)

La columna izquierda muestra la lista de fases del programa seleccionado.

**Buscar una fase:**
1. Escribe en el campo de búsqueda (🔍) en la parte superior de la lista.
2. Las fases se filtran en tiempo real y el término buscado se resalta en amarillo.
3. El contador junto al título muestra cuántos resultados están visibles.
4. Haz clic en ✕ para limpiar la búsqueda.

**Crear una fase:**
1. Haz clic en el botón **+ Nueva Fase**.
2. Completa el formulario:
   - **Nombre:** Nombre de la fase (ej: ANÁLISIS, PLANEACIÓN).
   - **Orden:** Número de secuencia (se sugiere automáticamente).
   - **Descripción:** Descripción breve opcional.
3. Haz clic en **Guardar**.

**Editar una fase:**
- Haz clic en el ícono ✏️ en la fase que deseas modificar.
- El formulario se abre precargado con los datos actuales.
- Modifica y guarda.

**Eliminar una fase:**
- Haz clic en el ícono 🗑 de la fase.
- Se pedirá confirmación. Al aceptar, se eliminan también todas sus actividades.

### 4.3 Gestión de Actividades

Al hacer clic sobre una fase en la columna izquierda, la columna derecha muestra sus actividades.

**Buscar una actividad:**
- Usa el campo de búsqueda sobre la lista de actividades (funciona igual que en fases).

**Agregar una actividad:**
1. Selecciona una fase (debe estar seleccionada — aparecerá resaltada).
2. Haz clic en **+ Nueva Actividad**.
3. Ingresa el nombre y descripción opcional.
4. Haz clic en **Guardar**.

**Eliminar una actividad:**
- Haz clic en 🗑 junto a la actividad y confirma.

### 4.4 Cargar Fases desde PDF

Esta funcionalidad extrae automáticamente las fases y actividades desde el PDF oficial del Proyecto Formativo SENA (GFPI-F-016).

1. Selecciona el programa en el selector superior.
2. Ve a la pestaña **📄 Cargar PDF** (o busca la zona de carga de archivos).
3. Arrastra el archivo PDF a la zona marcada, o haz clic en ella para buscarlo.
4. El sistema procesará el archivo automáticamente. Verás un indicador de carga.
5. Cuando termine, aparecerá una **previsualización** con:
   - Información básica extraída (código SOFIA, programa, regional, etc.)
   - Lista de fases detectadas.
   - Lista de actividades por fase.
   - Resumen de competencias y resultados de aprendizaje.
6. Revisa los datos y haz clic en **✔ Confirmar e Importar** para guardar.

> **Notas importantes:**
> - El PDF debe ser el GFPI-F-016 generado desde SOFIA Plus.
> - El tamaño máximo es **10 MB**.
> - Si el botón de carga tarda más de lo habitual la primera vez, es porque el sistema está iniciando automáticamente el procesador de PDF en segundo plano. Esto es normal y solo ocurre la primera vez.

---

## 5. Módulo de Carga Masiva

Accede desde **Sidebar → Carga Masiva**. Permite importar grandes volúmenes de datos desde archivos Excel.

### 5.1 Importar Aprendices desde Excel

1. Selecciona la pestaña o sección **Aprendices**.
2. Descarga la plantilla de ejemplo si la necesitas.
3. Arrastra o selecciona tu archivo `.xlsx` / `.xls`.
4. Haz clic en **Subir e Importar**.
5. El sistema procesará el archivo en **segundo plano** (no bloquea la pantalla).
6. Verás una barra de progreso o indicador mientras el worker trabaja.
7. Al finalizar, se mostrará un resumen: registros insertados, duplicados detectados y errores.

**Si hay errores:** El sistema registra exactamente qué filas fallaron y por qué. Revisa el reporte de errores para corregir el archivo y volver a intentarlo.

### 5.2 Importar Juicios desde Excel

El proceso es idéntico al de aprendices, pero seleccionando la sección **Juicios**.

> **Estructura requerida del Excel:**
> El archivo debe seguir la plantilla oficial del centro. Las columnas mínimas requeridas son: Documento del aprendiz, Competencia, Resultado de Aprendizaje, Tipo de Juicio, Fecha y Funcionario evaluador.

---

## 6. Módulo de Consulta de Aprendiz

Accede desde **Sidebar → Consulta Aprendiz**.

1. Escribe el nombre o documento del aprendiz en el campo de búsqueda.
2. El sistema mostrará el perfil del aprendiz con:
   - Datos básicos (nombre, documento, programa, estado).
   - Historial completo de juicios evaluativos por competencia.
   - Progreso por fase del proyecto formativo.

---

## 7. Módulo de Eliminación Masiva

Accede desde **Sidebar → Eliminación Masiva**.

> ⚠️ **Advertencia:** Esta acción es irreversible. Úsala únicamente cuando sea estrictamente necesario.

1. Selecciona el programa/ficha del que deseas eliminar registros.
2. Aplica los filtros necesarios para acotar los registros a eliminar.
3. Revisa la lista de registros que se eliminarán.
4. Confirma la acción escribiendo la frase de confirmación solicitada.

---

## 8. Dashboard de Fases

Accede desde **Sidebar → Dashboard Fases**.

Muestra el nivel de cumplimiento de cada fase del Proyecto Formativo por programa:

- **Barra de progreso** por fase: indica el porcentaje de aprendices que han completado cada fase.
- **Tabla de detalles:** desglose por competencia y resultado de aprendizaje.

Usa el selector de programa en la parte superior para filtrar por ficha.

---

## 9. Cambio de Tema Visual

El sistema dispone de dos modos de visualización:

- 🌙 **Modo Oscuro** (predeterminado): fondo oscuro con acentos en verde SENA.
- ☀️ **Modo Claro**: fondo claro con los mismos colores de acento.

Para cambiar de tema, haz clic en el **botón de sol/luna** ubicado en la esquina superior derecha del sistema. La preferencia se guarda automáticamente en tu navegador.

---

## 10. Preguntas Frecuentes y Solución de Problemas

### ¿Por qué la carga del PDF tarda mucho la primera vez?
El sistema inicia automáticamente el procesador Python en segundo plano. La primera vez puede tardar entre 3 y 8 segundos en arrancar. Las cargas siguientes serán más rápidas porque el procesador ya está activo.

### ¿Por qué algunos aprendices del Excel no se importaron?
Revisa el **reporte de errores** que aparece al finalizar la importación. Los motivos más comunes son:
- El programa/ficha no existe en el sistema.
- El documento del aprendiz ya está registrado (duplicado).
- Faltan campos obligatorios en la fila.

### ¿Qué hago si el dashboard no carga los datos?
1. Verifica que MySQL esté iniciado en XAMPP.
2. Recarga la página (F5).
3. Si el problema persiste, verifica que la base de datos `sena_juicios` exista y tenga datos.

### ¿Por qué el PDF genera un error "servicio no disponible"?
Python no pudo iniciarse automáticamente. Verifica:
1. Que Python 3 esté instalado en el sistema.
2. Que `flask` y `pdfplumber` estén instalados (`pip install flask pdfplumber`).
3. Que el antivirus no esté bloqueando la ejecución de Python.

### ¿Cómo exporto los datos del filtro avanzado?
En la sección **Filtro Avanzado de Juicios** del Dashboard, aplica los filtros deseados y haz clic en el botón **⬇ Exportar CSV**. Se descargará automáticamente un archivo con los resultados visibles.

### El contenido se sale de la pantalla en mi computador
El sistema es totalmente responsive. Si ves contenido desbordado:
1. Verifica que tu zoom del navegador esté al 100% (Ctrl + 0).
2. Usa la barra de desplazamiento horizontal que aparece en las tablas.
3. En pantallas pequeñas, el sidebar se oculta automáticamente; usa el botón ☰ para abrirlo.
