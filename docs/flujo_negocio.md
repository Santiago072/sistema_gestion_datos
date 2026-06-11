# Flujo de Negocio

Este documento detalla el ciclo de vida de los datos dentro del sistema, desde su ingreso hasta su visualización.

## 1. Ingesta de Datos
- **Carga de Archivos:** Los usuarios cargan la información de aprendices, programas y juicios a través de archivos Excel.

## 2. Procesamiento y Validación (Asíncrono)
- **Encolamiento:** El archivo se guarda en el servidor y se registra una tarea en la base de datos (Sistema de Colas MySQL).
- **Procesamiento en Segundo Plano (Worker):** Un trabajador asíncrono lee la cola, extrae los datos (usando adaptadores PHP o el microservicio Python) y los estructura temporalmente.
- **Validación de Registros:** El trabajador aplica las reglas de negocio (ej. validación de formatos, comprobación de existencia de programas y fases) para garantizar la integridad de los datos.

## 3. Persistencia
- **Almacenamiento:** Una vez validados, los registros se insertan o actualizan en la base de datos relacional.

## 4. Consulta y Explotación
- **Consulta de Usuarios:** Los usuarios pueden consultar la información consolidada de los aprendices, sus programas y sus respectivos juicios.
- **Dashboard y Reportes:** El sistema procesa la información para alimentar un Dashboard, generando indicadores, métricas de rendimiento y estadísticas generales.