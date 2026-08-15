# 📋 Plan de Implementación y Evolución Arquitectónica

Este documento describe las fases estratégicas, el stack tecnológico y los hitos de evolución del **Sistema de Gestión de Datos**.

---

## 🎯 Objetivos Estratégicos

1. **Centralización y Calidad del Dato:** Unificar los reportes heterogéneos de Sofia Plus y los proyectos formativos en una única base de datos relacional consistente.
2. **Monitoreo y Prevención de Deserción:** Proveer herramientas visuales analíticas (curva de supervivencia) para detectar en qué competencias y fechas se concentran las salidas.
3. **Auditoría Transparente:** Visibilizar los tiempos de respuesta y volumen evaluativo de los instructores.
4. **Despliegue Continuo Zero-Downtime:** Automatizar la infraestructura con Docker y scripts de despliegue en VPS.

---

## 🗓️ Fases de Desarrollo del Proyecto

```mermaid
gantt
    title Cronograma de Fases de Implementación
    dateFormat  YYYY-MM-DD
    section Fase 1: Arquitectura Base
    Estructuración MVC & Front Controller       :done, 2026-08-01, 2026-08-05
    Contenerización Docker & VPS deploy.sh     :done, 2026-08-04, 2026-08-08
    section Fase 2: Importación & Dashboard
    Parser por lotes Sofia Plus (500 filas)     :done, 2026-08-08, 2026-08-11
    Dashboard KPIs & Gráficos Chart.js         :done, 2026-08-10, 2026-08-13
    section Fase 3: Fases Curriculares & PDF
    Extractor Python GFPI-F-016 (pdfplumber)   :done, 2026-08-12, 2026-08-14
    Línea de tiempo y avance de cumplimiento   :done, 2026-08-13, 2026-08-15
    section Fase 4: Analítica de Retiros & Fixes
    Cruce jerárquico de fases por resultado     :done, 2026-08-15, 2026-08-15
    Curva de supervivencia con fechas 2025     :done, 2026-08-15, 2026-08-15
    Auditoría cronológica de instructores      :done, 2026-08-15, 2026-08-15
```

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología | Justificación / Rol |
| :--- | :--- | :--- |
| **Backend** | PHP 8.2+ | Rendimiento, tipado estricto y arquitectura MVC limpia. |
| **Base de Datos** | MariaDB 10.11 / MySQL | Persistencia relacional segura con sentencias preparadas PDO. |
| **Procesamiento PDF** | Python 3.11 + pdfplumber | Extracción precisa de tablas y cajas curriculares del formato GFPI-F-016. |
| **Frontend** | Vanilla CSS Modular + JS | Rendimiento óptimo, sin sobrecarga de frameworks externos. |
| **Gráficos** | Chart.js 4.x | Renderizado responsivo para curvas de supervivencia y barras. |
| **Infraestructura** | Docker + Docker Compose + Nginx | Portabilidad idéntica entre desarrollo local y servidor VPS en producción. |
