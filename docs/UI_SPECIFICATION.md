# Especificación de interfaz y vistas

## 1. Navegación lateral

El menú lateral incluye:

- Inicio.
- En préstamo.
- Libros.
- Acerca de.
- Configuración, visible únicamente para el Administrador.

## 2. Inicio — Dashboard operativo

Vista orientada al trabajo diario. Debe incorporar:

- Acciones rápidas: Nuevo préstamo y Devolución.
- Tabla resumen de préstamos próximos a vencer o vencidos.

## 3. En préstamo

Tabla general de circulación con filtros por:

- Fecha de préstamo.
- Fecha de devolución.
- Grado escolar.

Al crear un préstamo, la búsqueda por número de identidad o nombre completo
muestra coincidencias seleccionables. Los títulos aparecen una sola vez con
su cantidad de ejemplares disponibles; se puede seleccionar una copia por
título hasta completar el límite configurado para la persona. El historial
agrupa los títulos registrados en una misma operación y permite devolver cada
ejemplar por separado. Los ejemplares que necesitan arreglo no se ofrecen.

Indicadores visuales:

- **Rojo:** préstamos vencidos.
- **Amarillo:** préstamos próximos a vencer, dentro de los tres días anteriores al vencimiento.
- **Verde:** préstamos que se encuentran a tiempo.

## 4. Libros — Catálogo

Módulo de gestión del catálogo con:

- Filtros por editorial, autor, género y categoría.
- Búsqueda por ISBN/código de barras o título.
- Registro de cantidades de ejemplares por condición: bueno, intermedio y arreglar; los códigos de barras de las copias iniciales se generan automáticamente.
- Resumen de ejemplares disponibles, en préstamo y por condición; exportación de todos los títulos y ejemplares a Excel (`.xlsx`).
- Edición de la condición física, baja lógica y eliminación permanente de ejemplares sin historial de préstamos.

## 5. Configuración inicial local

En una instalación sin configurar, el asistente local solicita el nombre y
correo de la institución, además del nombre, apellidos, correo de acceso y
contraseña del administrador. Solo acepta conexiones locales y se bloquea
después de completarse. La operación inicializa los catálogos y parámetros
requeridos de forma transaccional. No existe registro público.

## 6. Configuración — exclusiva del Administrador

### 6.1. Parámetros de préstamo

- Configurar los días predeterminados para la devolución.
- Configurar el límite máximo de ejemplares permitidos por usuario.

### 6.2. Gestión de personal

- Crear y editar cuentas.
- Asignar o cambiar roles.
- Eliminar permanentemente otras cuentas de personal. No se puede eliminar la cuenta activa ni la última cuenta administradora; los préstamos históricos se conservan.

### 6.3. Datos de la institución

- Nombre de la entidad.
- Correo de contacto.
- Dirección física.
- Logotipo.
- Eliminación permanente de la institución y de todos sus datos, protegida por confirmación escrita.

### 6.4. Seguridad y respaldos

- Cada usuario del sistema puede cambiar su contraseña desde la navegación principal, verificando la contraseña actual y confirmando una nueva de al menos 8 caracteres.
- Restablecimiento de contraseñas del personal.
- Generación o exportación de copias de seguridad de la base de datos.

## 7. Acerca de

Incluye la descripción del software, enlaces a Instagram y al sitio oficial,
un enlace para explorar más proyectos, la disponibilidad de los términos y
condiciones y un enlace «Reportar problema» a Instagram sin mostrar la URL.
