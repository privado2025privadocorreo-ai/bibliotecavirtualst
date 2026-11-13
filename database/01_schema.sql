-- Biblioteca Virtual ST - SQLite schema
-- The application creates this schema automatically in an empty DB_PATH.
-- This file can also be executed manually with the SQLite CLI.

PRAGMA foreign_keys = ON;

CREATE TABLE instituciones (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre         TEXT NOT NULL,
    correo         TEXT NOT NULL UNIQUE,
    direccion      TEXT,
    logotipo       TEXT,
    creado_en      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE roles (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre      TEXT NOT NULL UNIQUE,
    descripcion TEXT
);

CREATE TABLE usuarios_sistema (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id  INTEGER NOT NULL,
    rol_id          INTEGER NOT NULL,
    nombre          TEXT NOT NULL,
    apellidos       TEXT NOT NULL,
    correo          TEXT NOT NULL UNIQUE,
    contrasena_hash TEXT NOT NULL,
    activo          INTEGER NOT NULL DEFAULT 1 CHECK (activo IN (0, 1)),
    ultimo_acceso   TEXT,
    creado_en       TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (institucion_id, id),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (rol_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE INDEX ix_usuarios_sistema_institucion ON usuarios_sistema (institucion_id);
CREATE INDEX ix_usuarios_sistema_rol ON usuarios_sistema (rol_id);

CREATE TABLE grados (
    id     INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL UNIQUE,
    orden  INTEGER
);

CREATE TABLE usuarios (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id INTEGER NOT NULL,
    grado_id       INTEGER,
    tipo_documento TEXT,
    documento      TEXT NOT NULL,
    nombres        TEXT NOT NULL,
    apellidos      TEXT NOT NULL,
    correo         TEXT,
    telefono       TEXT,
    direccion      TEXT,
    tipo_usuario   TEXT,
    activo         INTEGER NOT NULL DEFAULT 1 CHECK (activo IN (0, 1)),
    creado_en      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (institucion_id, id),
    UNIQUE (institucion_id, documento),
    UNIQUE (institucion_id, correo),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (grado_id) REFERENCES grados (id)
        ON UPDATE CASCADE ON DELETE SET NULL
);
CREATE INDEX ix_usuarios_institucion ON usuarios (institucion_id);
CREATE INDEX ix_usuarios_grado ON usuarios (grado_id);

CREATE TABLE autores (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id INTEGER NOT NULL,
    nombres        TEXT NOT NULL,
    apellidos      TEXT,
    UNIQUE (institucion_id, id),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE INDEX ix_autores_apellidos ON autores (institucion_id, apellidos);

CREATE TABLE editoriales (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id INTEGER NOT NULL,
    nombre         TEXT NOT NULL,
    UNIQUE (institucion_id, id),
    UNIQUE (institucion_id, nombre),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE formatos (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id INTEGER NOT NULL,
    nombre         TEXT NOT NULL,
    UNIQUE (institucion_id, id),
    UNIQUE (institucion_id, nombre),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE categorias (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id INTEGER NOT NULL,
    padre_id       INTEGER,
    tipo           TEXT NOT NULL CHECK (tipo IN ('genero', 'categoria', 'tema')),
    nombre         TEXT NOT NULL,
    descripcion    TEXT,
    UNIQUE (institucion_id, id),
    UNIQUE (institucion_id, tipo, nombre),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (institucion_id, padre_id) REFERENCES categorias (institucion_id, id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE INDEX ix_categorias_padre ON categorias (institucion_id, padre_id);

CREATE TABLE libros (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id   INTEGER NOT NULL,
    editorial_id     INTEGER,
    titulo           TEXT NOT NULL,
    subtitulo        TEXT,
    isbn             TEXT,
    anio_publicacion INTEGER CHECK (anio_publicacion IS NULL OR anio_publicacion BETWEEN 0 AND 2100),
    idioma           TEXT,
    descripcion      TEXT,
    activo           INTEGER NOT NULL DEFAULT 1 CHECK (activo IN (0, 1)),
    creado_en        TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (institucion_id, id),
    UNIQUE (institucion_id, isbn),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (institucion_id, editorial_id) REFERENCES editoriales (institucion_id, id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE INDEX ix_libros_titulo ON libros (institucion_id, titulo);
CREATE INDEX ix_libros_editorial ON libros (institucion_id, editorial_id);

CREATE TABLE libro_autores (
    institucion_id INTEGER NOT NULL,
    libro_id       INTEGER NOT NULL,
    autor_id       INTEGER NOT NULL,
    orden          INTEGER,
    PRIMARY KEY (libro_id, autor_id),
    FOREIGN KEY (institucion_id, libro_id) REFERENCES libros (institucion_id, id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (institucion_id, autor_id) REFERENCES autores (institucion_id, id)
        ON UPDATE CASCADE ON DELETE CASCADE
);
CREATE INDEX ix_libro_autores_autor ON libro_autores (institucion_id, autor_id);

CREATE TABLE libro_categorias (
    institucion_id INTEGER NOT NULL,
    libro_id       INTEGER NOT NULL,
    categoria_id   INTEGER NOT NULL,
    PRIMARY KEY (libro_id, categoria_id),
    FOREIGN KEY (institucion_id, libro_id) REFERENCES libros (institucion_id, id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (institucion_id, categoria_id) REFERENCES categorias (institucion_id, id)
        ON UPDATE CASCADE ON DELETE CASCADE
);
CREATE INDEX ix_libro_categorias_categoria ON libro_categorias (institucion_id, categoria_id);

CREATE TABLE ejemplares (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id INTEGER NOT NULL,
    libro_id       INTEGER NOT NULL,
    formato_id     INTEGER,
    codigo_barras  TEXT NOT NULL,
    ubicacion      TEXT,
    estado         TEXT NOT NULL DEFAULT 'disponible'
                   CHECK (estado IN ('disponible', 'prestado', 'baja')),
    condicion      TEXT NOT NULL DEFAULT 'bueno'
                   CHECK (condicion IN ('bueno', 'intermedio', 'arreglar')),
    fecha_alta     TEXT NOT NULL,
    fecha_baja     TEXT,
    observaciones  TEXT,
    creado_en      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (institucion_id, id),
    UNIQUE (institucion_id, codigo_barras),
    CHECK (fecha_baja IS NULL OR fecha_baja >= fecha_alta),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (institucion_id, libro_id) REFERENCES libros (institucion_id, id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (institucion_id, formato_id) REFERENCES formatos (institucion_id, id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE INDEX ix_ejemplares_libro ON ejemplares (institucion_id, libro_id);
CREATE INDEX ix_ejemplares_estado ON ejemplares (institucion_id, estado);
CREATE INDEX ix_ejemplares_formato ON ejemplares (institucion_id, formato_id);

CREATE TABLE lotes_prestamos (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id INTEGER NOT NULL,
    creado_en      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (institucion_id, id),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE prestamos (
    id                        INTEGER PRIMARY KEY AUTOINCREMENT,
    lote_id                   INTEGER,
    institucion_id            INTEGER NOT NULL,
    ejemplar_id               INTEGER NOT NULL,
    usuario_id                INTEGER NOT NULL,
    registrado_por            INTEGER,
    fecha_prestamo            TEXT NOT NULL,
    fecha_devolucion_prevista TEXT NOT NULL,
    fecha_devolucion_real     TEXT,
    estado                    TEXT NOT NULL DEFAULT 'activo'
                              CHECK (estado IN ('activo', 'devuelto', 'perdido')),
    nota                      TEXT,
    nota_devolucion           TEXT,
    creado_en                 TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en            TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (institucion_id, ejemplar_id, id, estado),
    CHECK (fecha_devolucion_prevista >= fecha_prestamo),
    CHECK (fecha_devolucion_real IS NULL OR fecha_devolucion_real >= fecha_prestamo),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (institucion_id, lote_id) REFERENCES lotes_prestamos (institucion_id, id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (institucion_id, ejemplar_id) REFERENCES ejemplares (institucion_id, id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (institucion_id, usuario_id) REFERENCES usuarios (institucion_id, id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (institucion_id, registrado_por) REFERENCES usuarios_sistema (institucion_id, id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE INDEX ix_prestamos_lote ON prestamos (institucion_id, lote_id);
CREATE INDEX ix_prestamos_ejemplar ON prestamos (institucion_id, ejemplar_id);
CREATE INDEX ix_prestamos_usuario ON prestamos (institucion_id, usuario_id);
CREATE INDEX ix_prestamos_registrado_por ON prestamos (institucion_id, registrado_por);
CREATE INDEX ix_prestamos_estado_fecha ON prestamos (institucion_id, estado, fecha_devolucion_prevista);

CREATE TABLE prestamos_activos (
    institucion_id INTEGER NOT NULL,
    ejemplar_id    INTEGER NOT NULL,
    prestamo_id    INTEGER NOT NULL,
    estado         TEXT NOT NULL DEFAULT 'activo'
                   CHECK (estado IN ('activo', 'devuelto', 'perdido')),
    PRIMARY KEY (institucion_id, ejemplar_id),
    UNIQUE (institucion_id, prestamo_id),
    CHECK (estado = 'activo'),
    FOREIGN KEY (institucion_id, ejemplar_id, prestamo_id, estado)
        REFERENCES prestamos (institucion_id, ejemplar_id, id, estado)
        ON UPDATE RESTRICT ON DELETE CASCADE
);
CREATE INDEX ix_prestamos_activos_ref
    ON prestamos_activos (institucion_id, ejemplar_id, prestamo_id, estado);

CREATE TABLE configuraciones (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    institucion_id INTEGER NOT NULL,
    clave          TEXT NOT NULL,
    valor          TEXT NOT NULL,
    tipo           TEXT NOT NULL DEFAULT 'texto' CHECK (tipo IN ('entero', 'texto', 'booleano')),
    descripcion    TEXT,
    actualizado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (institucion_id, clave),
    FOREIGN KEY (institucion_id) REFERENCES instituciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TRIGGER instituciones_actualizado_en
AFTER UPDATE ON instituciones
FOR EACH ROW WHEN NEW.actualizado_en = OLD.actualizado_en
BEGIN
    UPDATE instituciones SET actualizado_en = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER usuarios_sistema_actualizado_en
AFTER UPDATE ON usuarios_sistema
FOR EACH ROW WHEN NEW.actualizado_en = OLD.actualizado_en
BEGIN
    UPDATE usuarios_sistema SET actualizado_en = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER usuarios_actualizado_en
AFTER UPDATE ON usuarios
FOR EACH ROW WHEN NEW.actualizado_en = OLD.actualizado_en
BEGIN
    UPDATE usuarios SET actualizado_en = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER libros_actualizado_en
AFTER UPDATE ON libros
FOR EACH ROW WHEN NEW.actualizado_en = OLD.actualizado_en
BEGIN
    UPDATE libros SET actualizado_en = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER ejemplares_actualizado_en
AFTER UPDATE ON ejemplares
FOR EACH ROW WHEN NEW.actualizado_en = OLD.actualizado_en
BEGIN
    UPDATE ejemplares SET actualizado_en = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER prestamos_actualizado_en
AFTER UPDATE ON prestamos
FOR EACH ROW WHEN NEW.actualizado_en = OLD.actualizado_en
BEGIN
    UPDATE prestamos SET actualizado_en = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER configuraciones_actualizado_en
AFTER UPDATE ON configuraciones
FOR EACH ROW WHEN NEW.actualizado_en = OLD.actualizado_en
BEGIN
    UPDATE configuraciones SET actualizado_en = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;
