# Documentación — Biblioteca Virtual ST

Este directorio contiene la especificación funcional y técnica inicial del proyecto.

## Archivos

- [PROJECT.md](PROJECT.md): descripción y justificación.
- [SCOPE.md](SCOPE.md): alcance, delimitaciones y módulos.
- [REQUIREMENTS.md](REQUIREMENTS.md): requisitos funcionales y no funcionales.
- [UI_SPECIFICATION.md](UI_SPECIFICATION.md): roles, navegación y vistas.
- [ARCHITECTURE.md](ARCHITECTURE.md): arquitectura y tecnologías definidas.
- [DATABASE_DESIGN_BRIEF.md](DATABASE_DESIGN_BRIEF.md): instrucciones para que la IA diseñe el modelo de datos desde los requisitos, sin imponer un esquema previo.
- [DATABASE_DESIGN.md](DATABASE_DESIGN.md): diseño aprobado de entidades, relaciones y reglas de integridad.

## Criterio de trabajo

La documentación conserva las decisiones funcionales y técnicas aprobadas.
El único script SQL de instalación es `database/01_schema.sql`; la
configuración inicial local prepara la institución, el primer administrador y
los catálogos requeridos. No se incluyen seeds de demostración ni migraciones
para MySQL/MariaDB.
