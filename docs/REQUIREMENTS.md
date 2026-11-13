# Requisitos del sistema

## 1. Requerimientos funcionales

| Código | Nombre | Requerimiento |
|---|---|---|
| RF01 | Autenticación | El sistema debe permitir el inicio de sesión diferenciado por roles (Administrador y Bibliotecario). |
| RF02 | Gestión de libros | El sistema debe permitir registrar, consultar, modificar y dar de baja ejemplares físicos indicando código de barras, título, autor, editorial, tema y formato. |
| RF03 | Gestión de usuarios | El sistema debe permitir registrar estudiantes o usuarios institucionales con documento de identidad, nombres, apellidos, correo, teléfono, dirección y grado escolar. |
| RF04 | Control de préstamos | El sistema debe registrar en una sola operación varios ejemplares de títulos distintos para un usuario responsable, respetando el límite institucional y conservando una nota opcional. |
| RF05 | Control de devoluciones | El sistema debe registrar la devolución de ejemplares y actualizar automáticamente su disponibilidad en el inventario. |
| RF06 | Configuración de tiempos | El sistema debe permitir editar el lapso de tiempo para la devolución de libros. |
| RF07 | Inventario por condición | Al registrar un título, el sistema debe permitir indicar cantidades de ejemplares buenos, intermedios y que necesitan arreglo; cada ejemplar conserva su condición individual. |
| RF08 | Búsqueda en préstamos | Al registrar un préstamo, el sistema debe mostrar coincidencias de usuarios activos por número de identidad o nombre completo. |
| RF09 | Exportación del inventario | El sistema debe exportar todos los títulos y ejemplares a Excel (`.xlsx`), indicando disponibilidad, préstamos, condición física y necesidades de arreglo. |
| RF10 | Eliminación de cuentas de personal | El administrador debe poder eliminar de la base de datos a otro bibliotecario o administrador, sin eliminar los préstamos históricos registrados por esa cuenta. |
| RF11 | Eliminación de institución | El administrador debe poder eliminar permanentemente su institución y los datos que le pertenecen, con confirmación explícita. |
| RF12 | Eliminación de ejemplares | El sistema debe permitir eliminar ejemplares sin historial de préstamos; los ejemplares con historial se conservan mediante baja lógica. |
| RF13 | Información del software | La vista Acerca de debe mostrar descripción, enlaces oficiales, términos y condiciones, e información de soporte. |
| RF14 | Cambio de contraseña | Cada usuario del sistema debe poder cambiar su propia contraseña tras verificar la contraseña actual; la nueva contraseña debe guardarse con hash seguro. |

## 2. Requerimientos no funcionales

| Código del documento | Nombre | Requerimiento |
|---|---|---|
| RNF01 | Seguridad | Las contraseñas deben almacenarse mediante un hash seguro (por ejemplo, `password_hash()` y `password_verify()` en PHP), no con cifrado reversible. |
| RNF03 | Rendimiento | Las consultas al catálogo y el registro de transacciones deben completarse en menos de 2 segundos. |
| RNF04 | Usabilidad | La interfaz desarrollada con HTML, CSS y JavaScript debe ser intuitiva y de fácil navegación. |
| RNF05 | Configurabilidad | El administrador debe poder parametrizar el plazo de devolución sin modificar el código fuente. |

## 3. Observaciones pendientes

- El documento original no contiene RNF02. No se debe inventar ni renumerar sin confirmación.
- Se deben precisar los criterios para medir el rendimiento de menos de 2 segundos.
- Se deben definir renovaciones, tratamiento de retrasos y restricciones adicionales por usuario.
- Se deben definir los campos obligatorios y las reglas de validación de cada formulario.
- Una institución eliminada no puede recuperarse desde la aplicación; se debe crear un respaldo antes de confirmar la eliminación si se requiere conservar sus datos.
