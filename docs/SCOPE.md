# Alcance del proyecto

## 1. Alcance funcional

- **Gestión de catálogo:** registrar, consultar y actualizar títulos, autores, editoriales y categorías; administrar ejemplares por condición física, dar de baja copias con historial y eliminar copias sin historial.
- **Gestión de usuarios:** registrar y administrar usuarios autorizados de la institución, como estudiantes, docentes o personal administrativo.
- **Control de préstamos y devoluciones:** registrar la salida y entrada de material bibliográfico, calcular fechas de entrega y hacer seguimiento al estado del recurso (disponible, prestado o en mora).
- **Seguridad y control de acceso:** autenticación de usuarios mediante roles y permisos según el tipo de cuenta institucional.
- **Inventario:** exportar el catálogo y el estado de cada ejemplar a Excel (`.xlsx`); buscar usuarios por documento al registrar préstamos.
- **Administración institucional:** eliminar cuentas de personal de forma permanente y, con confirmación, eliminar una institución con todos sus datos.

## 2. Delimitaciones (fuera del alcance)

- No incluye pasarelas de pago ni cobro de multas en línea.
- No contempla la importación de archivos PDF o e-books; está enfocado en la gestión del inventario físico.
- No abarca la integración con servicios o catálogos externos fuera de la institución.

## 3. Perfiles del sistema

El documento define dos roles principales:

- **Administrador:** acceso a la configuración y administración avanzada.
- **Bibliotecario:** perfil operativo para la gestión cotidiana de libros, usuarios, préstamos y devoluciones.

Los permisos exactos de cada operación deben detallarse antes de implementar la autorización.

## 4. Módulos previstos

1. Inicio / Dashboard operativo.
2. En préstamo.
3. Libros / Catálogo.
4. Registro o creación de la cuenta institucional inicial.
5. Configuración (exclusiva del Administrador).
6. Acerca de con información, enlaces de contacto y soporte.
