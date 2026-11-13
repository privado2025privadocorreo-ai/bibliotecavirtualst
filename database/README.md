# Base de datos — Biblioteca Virtual ST

La aplicación de escritorio usa SQLite mediante PDO. En el primer acceso, crea
el archivo indicado por `DB_PATH` (por defecto
`%LOCALAPPDATA%\BibliotecaVirtualST\biblioteca.sqlite`) y aplica
automáticamente `01_schema.sql` si la base está vacía.

## Archivos

| Archivo | Contenido |
|---|---|
| `01_schema.sql` | Único script SQL de instalación: crea las 17 tablas SQLite y sus relaciones. Se aplica automáticamente en una base vacía. |

No se necesitan scripts de seed ni migraciones para una instalación nueva. El
asistente local crea la institución, el primer administrador, los roles,
grados, catálogos y parámetros iniciales. El acceso al asistente se bloquea
después de completar la configuración.

Las antiguas migraciones MariaDB y el seed de demostración se retiraron. La
aplicación no convierte automáticamente una base MySQL/MariaDB existente. El
archivo SQLite contiene todos los datos locales; debe conservarse al actualizar
el programa y formar parte de los respaldos.
