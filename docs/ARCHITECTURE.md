# Arquitectura y tecnologías

## 1. Arquitectura definida

- **Modelo arquitectónico:** MVC (Modelo-Vista-Controlador).
- **Frontend:** HTML5, CSS3 y JavaScript.
- **Backend:** PHP.
- **Base de datos:** SQLite local mediante PDO SQLite.
- **Entorno de ejecución:** PHP Desktop en Windows; no requiere Apache ni MySQL.
- **Instalación prevista:** empaquetado con Inno Setup.

## 2. Organización MVC esperada

La aplicación debe separar responsabilidades:

- **Modelos:** acceso y operaciones sobre los datos.
- **Vistas:** presentación de la interfaz.
- **Controladores:** recepción de solicitudes, validación del flujo y coordinación entre modelos y vistas.

La estructura concreta de carpetas y el enrutamiento deben proponerse antes de generar código.

## 3. Criterios técnicos recomendados

Estas son recomendaciones para evaluar, no decisiones originales ya aprobadas:

- Usar PDO y consultas preparadas para el acceso a SQL.
- Mantener credenciales fuera del control de versiones.
- Validar los datos en el servidor, además de las validaciones de interfaz.
- Proteger formularios contra CSRF y prevenir XSS mediante escape de salida.
- Usar `password_hash()` y `password_verify()` para contraseñas.
- Aplicar control de acceso en el servidor para cada rol.
- No permitir que una vista ejecute directamente consultas SQL.

## 4. Requisitos de ejecución

- PHP 8.0 o superior con la extensión `pdo_sqlite`.
- Mantener el archivo SQLite en `%LOCALAPPDATA%` para conservar los datos entre actualizaciones del instalador.
- Copiar el runtime de PHP Desktop junto con la aplicación; no se necesita acceso a Internet para las funciones de la biblioteca.
