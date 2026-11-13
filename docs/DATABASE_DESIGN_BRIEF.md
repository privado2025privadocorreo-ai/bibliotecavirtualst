# Encargo para el diseño de la base de datos

> **Este archivo no contiene un modelo de datos ni un script SQL.** Su propósito es pedirle a la IA que diseñe la base de datos a partir de los requisitos aprobados del proyecto.

## Instrucciones para Codex

Analiza primero todos los documentos de esta carpeta (`PROJECT.md`, `SCOPE.md`, `REQUIREMENTS.md`, `UI_SPECIFICATION.md` y `ARCHITECTURE.md`).

Diseña una propuesta de modelo de datos para Biblioteca Virtual ST, una aplicación web institucional de gestión de inventario bibliográfico físico, usuarios, personal, préstamos y devoluciones.

### Reglas obligatorias

1. No inventes tablas, campos ni reglas de negocio sin justificar su necesidad en los requisitos.
2. Distingue claramente los datos explícitos del documento de las decisiones técnicas que propones.
3. Identifica ambigüedades y formula preguntas antes de dar por definitivas las reglas que afecten el modelo.
4. Considera que un título bibliográfico puede tener varios ejemplares físicos, cada uno con su propio código de barras y disponibilidad. Explica si propones separar `libros` y `ejemplares`.
5. Modela los roles Administrador y Bibliotecario y la cuenta institucional inicial, justificando las relaciones.
6. Analiza cómo representar autores, editoriales, categorías y temas; no asumas cardinalidades sin explicarlas.
7. Modela préstamos y devoluciones de manera que se conserve el historial y se evite prestar un ejemplar que ya esté prestado.
8. Considera la configuración del plazo de devolución y el límite máximo de ejemplares por usuario.
9. Propón claves primarias, claves foráneas, restricciones, índices y tipos de datos adecuados para el motor SQL que recomiendes.
10. No incluyas pagos ni multas en línea, archivos PDF/e-books ni catálogos externos: están fuera del alcance.

### Entregables, en este orden

1. Resumen de entidades y responsabilidades.
2. Diagrama entidad-relación en Mermaid.
3. Diccionario de datos: tabla, campo, tipo, nulabilidad, clave y descripción.
4. Relaciones y cardinalidades explicadas.
5. Reglas de integridad y restricciones.
6. Preguntas y decisiones pendientes para el responsable del proyecto.
7. Cuando las decisiones estén aprobadas, generar el script SQL de creación de la base de datos y datos iniciales mínimos.

### Forma de trabajo

Primero entrega el análisis y la propuesta. **No crees todavía el archivo SQL ni modifiques el código del proyecto.** Espera la aprobación del responsable antes de generar el script definitivo.
