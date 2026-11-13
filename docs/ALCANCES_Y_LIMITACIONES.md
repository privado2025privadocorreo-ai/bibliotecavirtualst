# Alcances y limitaciones

Este documento describe lo que ofrece actualmente Biblioteca Virtual ST y lo
que queda fuera de su alcance. No sustituye una copia de seguridad ni garantiza
que todas las funciones estén disponibles en cualquier alojamiento: la
instalación debe cumplir los requisitos técnicos del proyecto.

## Alcance actual

### Instituciones y acceso

- En el primer inicio local se configura una institución y su primera cuenta
  administradora. No hay registro público ni creación de cuentas en línea.
- El Administrador puede actualizar los datos de contacto y el logotipo,
  administrar cuentas del personal y cambiar parámetros de circulación.
- El personal inicia sesión con correo y contraseña. Los roles disponibles son
  Administrador y Bibliotecario; la configuración está reservada al
  Administrador.
- Los datos operativos se separan por institución.

### Catálogo e inventario físico

- Registro y edición de títulos, autores, editoriales, categorías, géneros,
  temas y formatos físicos.
- Administración de ejemplares físicos con código de barras, ubicación,
  condición y estado.
- Búsqueda y filtros del catálogo y exportación del inventario a Excel (`.xlsx`).

### Usuarios y circulación

- Registro y edición de usuarios institucionales, con documento, datos de
  contacto y grado. Estos usuarios no tienen una cuenta para iniciar sesión.
- Préstamo de una copia de cada título seleccionado, agrupando varios títulos
  en una misma operación.
- El plazo y el máximo de ejemplares activos por usuario se configuran por
  institución. Los ejemplares que necesitan arreglo no se ofrecen para
  préstamo.
- Registro de devoluciones por ejemplar, filtros del historial e indicadores
  de préstamos activos, próximos a vencer y vencidos.
- Cada empleado puede cambiar su propia contraseña; el Administrador puede
  establecer una contraseña nueva para otra cuenta del personal.

## Fuera del alcance actual

- Catálogo público para consultar la colección sin iniciar sesión, cuentas o
  autoservicio para estudiantes, docentes u otros usuarios institucionales.
- Libros electrónicos, lectura digital, archivos PDF o gestión de contenido
  digital.
- Reservas, renovaciones, multas, pagos, recibos o procesamiento financiero.
- Envío automático de avisos por correo, SMS o mensajería; recuperación de
  contraseña por correo y verificación de correo institucional.
- Integración con sistemas académicos, directorios, proveedores de libros,
  plataformas externas, aplicaciones móviles o servicios de terceros.
- Informes estadísticos avanzados, exportación completa de la base de datos,
  importación masiva del inventario o copias de seguridad automatizadas. La
  exportación Excel disponible contiene inventario; no es un respaldo.
- Sincronización sin conexión ni aplicación de escritorio o móvil nativa.

## Límites operativos importantes

- La instalación local requiere PHP 8.0 o superior, un servidor web compatible
  y la extensión `pdo_sqlite`. La base de datos es un archivo SQLite local.
- La disponibilidad, el espacio, el tamaño máximo de archivos y las conexiones
  permitidas dependen del proveedor de alojamiento. Deben verificarse antes de
  publicar la aplicación; el código no puede ampliar los límites del plan.
- La búsqueda del catálogo y la lista de usuarios muestran como máximo 200
  resultados por consulta. El historial limita la consulta a 250 filas de
  ejemplares prestados y no tiene paginación; en historiales extensos, un grupo
  puede quedar incompleto si supera ese límite o coincide con su borde.
- El logotipo acepta imágenes PNG, JPG o WebP de hasta 2 MB.
- El registro de cada título admite hasta 999 ejemplares en total. Los campos
  también tienen longitudes máximas validadas por la aplicación.
- La aplicación no reemplaza los procedimientos de respaldo y recuperación del
  proveedor. Antes de migraciones, borrados o cambios de alojamiento, el
  administrador debe respaldar y comprobar la base de datos.

## Responsabilidades del administrador

- Mantener protegidos `.env`, credenciales y accesos al panel de alojamiento;
  usar HTTPS y credenciales de base de datos con permisos mínimos.
- Configurar respaldos externos y probar periódicamente su restauración.
- Aplicar las migraciones de base de datos que correspondan a la versión
  instalada, después de respaldar y confirmar la base objetivo.
- Revisar las políticas de privacidad, conservación y eliminación de datos
  aplicables a su institución.
- Verificar el funcionamiento en el alojamiento elegido antes de incorporar
  datos reales.
