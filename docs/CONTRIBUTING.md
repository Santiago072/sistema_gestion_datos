# 🤝 Guía para Colaboradores (CONTRIBUTING)

Gracias por tu interés en contribuir al **Sistema de Gestión de Datos**. Este documento describe las directrices y estándares para colaborar en el desarrollo del proyecto de forma ordenada y consistente.

---

## 📋 Código de Conducta

Nos comprometemos a brindar un entorno respetuoso, inclusivo y profesional para todos los colaboradores, independientemente de su nivel de experiencia.

---

## 🛠️ Flujo de Trabajo con Git

1. **Crear una rama para la característica o corrección:**
   ```bash
   git checkout -b feature/nombre-funcionalidad
   # o para correcciones
   git checkout -b fix/descripcion-del-bug
   ```

2. **Convención de Mensajes de Commit (Conventional Commits):**
   Usa prefijos claros y descriptivos en español o inglés:
   * `feat:` Nueva funcionalidad añadida al sistema.
   * `fix:` Corrección de un error o inconsistencia de datos.
   * `docs:` Cambios o adiciones en la documentación.
   * `style:` Ajustes visuales, CSS o formato sin alterar lógica de negocio.
   * `refactor:` Reestructuración de código sin cambiar su comportamiento externo.
   * `test:` Adición o modificación de pruebas.

   *Ejemplo:* `fix(retiros): correccion de cruce entre excel y pdf para fases formativas`

3. **Pruebas Locales antes de hacer Push:**
   * Verifica la sintaxis PHP sin errores (`php -l archivo.php`).
   * Asegúrate de que las consultas PDO utilicen sentencias preparadas.
   * Valida que la interfaz responda correctamente tanto en escritorio como en dispositivos móviles.

4. **Publicar y Crear Pull Request:**
   ```bash
   git push origin feature/nombre-funcionalidad
   ```
   Abre un Pull Request en GitHub detallando los cambios realizados, los archivos modificados y la evidencia de pruebas.

---

## 📐 Estándares de Código

* **PHP:**
  * PHP 8.2+ con tipado estricto donde sea posible (`declare(strict_types=1);`).
  * Estándares PSR-12 para formato de código.
  * Sanitización y escape de salidas HTML con `htmlspecialchars()` o utilidades de seguridad.
  * Prevención estricta de Inyecciones SQL mediante Sentencias Preparadas (`PDO::prepare()`).

* **Frontend y CSS:**
  * Uso de variables de diseño centralizadas (`var(--primary)`, `var(--bg)`, etc.).
  * CSS modularizado sin colisiones de selectores globales innecesarias.
  * Respeta el diseño oscuro/moderno con paleta corporativa SENA (`#39A900`).

---

## ⚖️ Licencia

Al contribuir a este repositorio, aceptas que tus aportes se distribuyan bajo los términos de la **Licencia MIT**.
