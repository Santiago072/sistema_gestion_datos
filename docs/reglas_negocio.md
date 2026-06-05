# Reglas de Negocio

Este documento contiene las reglas de negocio, validaciones y restricciones que rigen el comportamiento del sistema.

## Relaciones de Entidades (Modelo de Datos)

### Aprendices y Programas
- **Asignación Única:** Todo aprendiz debe pertenecer a un programa de formación específico.
- **Fichas:** Un programa de formación agrupa a los aprendices mediante "fichas". Un programa puede contener varias fichas, y una ficha agrupa a un conjunto de aprendices.

### Evaluación (Juicios y Fases)
- **Multiplicidad de Juicios:** Un aprendiz puede tener múltiples juicios evaluativos a lo largo de su proceso formativo.
- **Asociación de Fases:** Todo juicio evaluativo debe estar obligatoriamente asociado a una fase del proceso formativo (por ejemplo, Análisis, Planeación, Ejecución, Evaluación).

## Reglas de Procesamiento de Excel (Proyectadas)
- Durante la carga de datos, el sistema debe validar que los programas y las fases especificadas en el archivo coincidan con los catálogos existentes.
- Los registros duplicados o inconsistentes deben ser reportados y no almacenados hasta que se subsane el error.
