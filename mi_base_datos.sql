
DROP DATABASE IF EXISTS admisiones_unificadas;
CREATE DATABASE admisiones_unificadas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE admisiones_unificadas;

CREATE TABLE usuarios (
  id_usuario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(200) NOT NULL,
  cedula_identidad VARCHAR(50) DEFAULT NULL,
  correo_electronico VARCHAR(255) DEFAULT NULL,
  contrasena VARCHAR(255) NOT NULL,
  rol ENUM('postulante','personal_admision','admin') NOT NULL DEFAULT 'postulante',
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY ux_usuarios_cedula (cedula_identidad),
  UNIQUE KEY ux_usuarios_email (correo_electronico)
) ENGINE=InnoDB;


CREATE TABLE carreras (
  id_carrera INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(50) NOT NULL UNIQUE,
  nombre VARCHAR(255) NOT NULL,
  descripcion TEXT,
  cupos INT UNSIGNED DEFAULT 0,
  estado ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
  postulantes_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_carrera)
) ENGINE=InnoDB;


CREATE TABLE periodos_academicos (
  id_periodo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre_periodo VARCHAR(100) NOT NULL,
  fecha_inicio_inscripciones DATE NOT NULL,
  fecha_fin_inscripciones DATE NOT NULL,
  fecha_examen_admision DATE NOT NULL,
  estado ENUM('activo','inactivo') DEFAULT 'activo',
  administrador_id BIGINT UNSIGNED NOT NULL,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_periodo),
  INDEX (fecha_inicio_inscripciones, fecha_fin_inscripciones),
  CONSTRAINT fk_periodo_admin FOREIGN KEY (administrador_id) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;


CREATE TABLE postulantes (
  id_postulante BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NULL,
  nombres VARCHAR(100) NOT NULL,
  apellido_paterno VARCHAR(100) DEFAULT '',
  apellido_materno VARCHAR(100) DEFAULT '',
  ci VARCHAR(50) DEFAULT NULL,
  fecha_nacimiento DATE NULL,
  telefono VARCHAR(50) DEFAULT NULL,
  direccion_residencia TEXT,
  nacionalidad VARCHAR(100) DEFAULT 'Boliviana',
  foto_perfil_url VARCHAR(255) DEFAULT NULL,
  estado_postulacion ENUM('pendiente','documentos_aprobados','documentos_rechazados','admitido','no_admitido') DEFAULT 'pendiente',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_postulante),
  UNIQUE KEY ux_postulante_ci (ci),
  CONSTRAINT fk_postulante_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;


CREATE TABLE inscripciones (
  id_inscripcion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_postulante BIGINT UNSIGNED NOT NULL,
  id_carrera INT UNSIGNED NOT NULL,
  periodo_id BIGINT UNSIGNED NOT NULL,
  opcion_carrera ENUM('primera','segunda') DEFAULT 'primera',
  numero_folio VARCHAR(100) NOT NULL UNIQUE,
  fecha_inscripcion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  puntaje_examen DECIMAL(6,2) NULL,
  estado_inscripcion ENUM('inscrito','presento_examen','admitido','no_admitido','acepto_vacante','pendiente','confirmada','rechazada') DEFAULT 'inscrito',
  datos_form JSON NULL,
  PRIMARY KEY (id_inscripcion),
  CONSTRAINT fk_insc_postulante FOREIGN KEY (id_postulante) REFERENCES postulantes(id_postulante),
  CONSTRAINT fk_insc_carrera FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera),
  CONSTRAINT fk_insc_periodo FOREIGN KEY (periodo_id) REFERENCES periodos_academicos(id_periodo)
) ENGINE=InnoDB;


CREATE TABLE documentos_requeridos (
  id_documento_req BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre_documento VARCHAR(150) NOT NULL,
  descripcion_documento TEXT,
  obligatorio TINYINT(1) DEFAULT 1,
  estado ENUM('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (id_documento_req)
) ENGINE=InnoDB;

CREATE TABLE documentos_postulantes (
  id_doc BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  postulante_id BIGINT UNSIGNED NOT NULL,
  documento_req_id BIGINT UNSIGNED NULL,
  tipo_documento VARCHAR(100) DEFAULT NULL,
  archivo_url VARCHAR(1000) NOT NULL,
  estado_validacion ENUM('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  personal_validador_id BIGINT UNSIGNED NULL,
  fecha_carga TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_validacion TIMESTAMP NULL,
  comentario TEXT NULL,
  PRIMARY KEY (id_doc),
  CONSTRAINT fk_doc_postulante FOREIGN KEY (postulante_id) REFERENCES postulantes(id_postulante),
  CONSTRAINT fk_doc_req FOREIGN KEY (documento_req_id) REFERENCES documentos_requeridos(id_documento_req),
  CONSTRAINT fk_doc_validador FOREIGN KEY (personal_validador_id) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;


CREATE TABLE temarios (
  id_temario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_carrera INT UNSIGNED NOT NULL,
  version VARCHAR(80) DEFAULT 'v1',
  nombre_temario VARCHAR(200) DEFAULT NULL,
  publicado_en DATE NULL,
  archivo_url VARCHAR(1000) NULL,
  descripcion TEXT,
  estado ENUM('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (id_temario),
  CONSTRAINT fk_temario_carrera FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera)
) ENGINE=InnoDB;

CREATE TABLE temas (
  id_tema BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_temario BIGINT UNSIGNED NOT NULL,
  id_asignatura INT UNSIGNED NULL,
  titulo VARCHAR(500) NOT NULL,
  contenido TEXT,
  orden INT UNSIGNED DEFAULT 0,
  PRIMARY KEY (id_tema),
  CONSTRAINT fk_tema_temario FOREIGN KEY (id_temario) REFERENCES temarios(id_temario)
) ENGINE=InnoDB;


CREATE TABLE asignaturas (
  id_asignatura INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(255) NOT NULL,
  codigo VARCHAR(80) NOT NULL,
  PRIMARY KEY (id_asignatura)
) ENGINE=InnoDB;


CREATE TABLE recursos (
  id_recurso BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo VARCHAR(300) NOT NULL,
  descripcion TEXT,
  archivo_url VARCHAR(1000),
  tipo ENUM('libro','guia','archivo','video','otro') DEFAULT 'archivo',
  permitir_descarga TINYINT(1) DEFAULT 1,
  publico TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  carrera_id INT UNSIGNED NULL,
  estado ENUM('activo','inactivo') DEFAULT 'activo',
  fecha_publicacion DATE NULL,
  PRIMARY KEY (id_recurso),
  CONSTRAINT fk_recurso_carrera FOREIGN KEY (carrera_id) REFERENCES carreras(id_carrera)
) ENGINE=InnoDB;

CREATE TABLE consultas_materiales (
  id_consulta BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  postulante_id BIGINT UNSIGNED NOT NULL,
  material_id BIGINT UNSIGNED NOT NULL,
  fecha_consulta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  tipo_consulta ENUM('visualizacion','descarga') NOT NULL,
  PRIMARY KEY (id_consulta),
  CONSTRAINT fk_consulta_postulante FOREIGN KEY (postulante_id) REFERENCES postulantes(id_postulante),
  CONSTRAINT fk_consulta_material FOREIGN KEY (material_id) REFERENCES recursos(id_recurso)
) ENGINE=InnoDB;


CREATE TABLE resultados (
  id_resultado BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_postulante BIGINT UNSIGNED NOT NULL,
  id_carrera INT UNSIGNED NOT NULL,
  folio_consulta VARCHAR(150) NOT NULL UNIQUE,
  puntaje DECIMAL(6,2) NULL,
  aprobado TINYINT(1) NULL,
  fecha_resultado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  carta_url VARCHAR(1000) NULL,
  PRIMARY KEY (id_resultado),
  CONSTRAINT fk_resultado_postulante FOREIGN KEY (id_postulante) REFERENCES postulantes(id_postulante),
  CONSTRAINT fk_resultado_carrera FOREIGN KEY (id_carrera) REFERENCES carreras(id_carrera)
) ENGINE=InnoDB;

CREATE TABLE pagos (
  id_pago BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_postulante BIGINT UNSIGNED NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  concepto VARCHAR(200) NOT NULL,
  metodo ENUM('efectivo','tarjeta','transferencia') DEFAULT 'efectivo',
  fecha_pago TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  referencia VARCHAR(255) NULL,
  estado ENUM('pendiente','completado','fallido') DEFAULT 'completado',
  PRIMARY KEY (id_pago),
  CONSTRAINT fk_pago_postulante FOREIGN KEY (id_postulante) REFERENCES postulantes(id_postulante)
) ENGINE=InnoDB;


CREATE TABLE notificaciones (
  id_notificacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id BIGINT UNSIGNED NOT NULL,
  mensaje TEXT NOT NULL,
  leido TINYINT(1) DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_notificacion),
  CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;


CREATE TABLE bitacora (
  id_bitacora BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entidad VARCHAR(100) NOT NULL,
  id_entidad VARCHAR(100) NULL,
  accion VARCHAR(50) NOT NULL,
  usuario VARCHAR(200) NULL,
  detalles JSON NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_bitacora)
) ENGINE=InnoDB;


INSERT INTO usuarios (nombre, cedula_identidad, correo_electronico, contrasena, rol, estado)
VALUES 
('Administrador General', '1000001', 'admin@umss.bo', 'admin123', 'admin', 'activo'),
('Juan Pérez', '1234567', 'juanperez@mail.com', 'post123', 'postulante', 'activo'),
('María López', '2345678', 'marialopez@mail.com', 'post456', 'postulante', 'activo'),
('Carlos Vargas', '3456789', 'carlosv@mail.com', 'admision123', 'personal_admision', 'activo');


INSERT INTO carreras (codigo, nombre, descripcion, cupos)
VALUES
('INF01', 'Ingeniería de Sistemas', 'Carrera orientada al desarrollo de software y sistemas.', 120),
('CIV01', 'Ingeniería Civil', 'Carrera enfocada en construcción y estructuras.', 80),
('MED01', 'Medicina', 'Carrera en ciencias de la salud.', 100),
('DER01', 'Derecho', 'Carrera en ciencias jurídicas.', 90);


INSERT INTO periodos_academicos (nombre_periodo, fecha_inicio_inscripciones, fecha_fin_inscripciones, fecha_examen_admision, administrador_id)
VALUES
('Gestión 2025-I', '2025-01-15', '2025-03-01', '2025-03-15', 1);


INSERT INTO postulantes (usuario_id, nombres, apellido_paterno, apellido_materno, ci, fecha_nacimiento, telefono, direccion_residencia, nacionalidad)
VALUES
(2, 'Juan', 'Pérez', 'Gutiérrez', '1234567', '2005-04-12', '77445566', 'Zona Norte, Cochabamba', 'Boliviana'),
(3, 'María', 'López', 'Fernández', '2345678', '2004-09-22', '76543210', 'Zona Central, Cochabamba', 'Boliviana');


INSERT INTO inscripciones (id_postulante, id_carrera, periodo_id, opcion_carrera, numero_folio, estado_inscripcion)
VALUES
(1, 1, 1, 'primera', 'FOLIO-2025-001', 'inscrito'),
(2, 3, 1, 'primera', 'FOLIO-2025-002', 'inscrito');


INSERT INTO documentos_requeridos (nombre_documento, descripcion_documento)
VALUES
('Cédula de Identidad', 'Fotocopia simple de la cédula de identidad'),
('Certificado de Nacimiento', 'Certificado original o fotocopia legalizada'),
('Título de Bachiller', 'Título de bachiller en formato físico o digitalizado');


INSERT INTO documentos_postulantes (postulante_id, documento_req_id, archivo_url, estado_validacion)
VALUES
(1, 1, 'docs/juan_ci.pdf', 'aprobado'),
(1, 2, 'docs/juan_certnac.pdf', 'pendiente'),
(2, 1, 'docs/maria_ci.pdf', 'aprobado'),
(2, 3, 'docs/maria_bachiller.pdf', 'rechazado');

INSERT INTO temarios (id_carrera, version, nombre_temario, publicado_en, descripcion)
VALUES
(1, 'v1', 'Temario de Matemáticas', '2025-01-10', 'Contiene álgebra, cálculo y geometría'),
(3, 'v1', 'Temario de Ciencias Biológicas', '2025-01-10', 'Contiene biología, química y anatomía');

INSERT INTO temas (id_temario, titulo, contenido, orden)
VALUES
(1, 'Álgebra', 'Ecuaciones lineales y cuadráticas', 1),
(1, 'Cálculo', 'Límites y derivadas', 2),
(2, 'Biología Celular', 'Estructura y funciones de la célula', 1);


INSERT INTO asignaturas (nombre, codigo)
VALUES
('Matemáticas', 'MAT101'),
('Física', 'FIS101'),
('Biología', 'BIO101');


INSERT INTO recursos (titulo, descripcion, archivo_url, tipo, carrera_id, fecha_publicacion)
VALUES
('Guía de Examen de Matemáticas', 'Material de práctica para postulantes', 'recursos/matematicas.pdf', 'guia', 1, '2025-01-20'),
('Manual de Biología', 'Texto de referencia para Medicina', 'recursos/biologia.pdf', 'libro', 3, '2025-01-20');


INSERT INTO resultados (id_postulante, id_carrera, folio_consulta, puntaje, aprobado)
VALUES
(1, 1, 'RES-2025-001', 78.50, 1),
(2, 3, 'RES-2025-002', 52.00, 0);


INSERT INTO pagos (id_postulante, monto, concepto, metodo, referencia)
VALUES
(1, 200.00, 'Inscripción examen admisión', 'efectivo', 'REC-001'),
(2, 200.00, 'Inscripción examen admisión', 'tarjeta', 'TRX-12345');


INSERT INTO notificaciones (usuario_id, mensaje, leido)
VALUES
(2, 'Tu inscripción fue registrada correctamente', 0),
(3, 'Debes volver a subir tu Título de Bachiller', 0);

INSERT INTO bitacora (entidad, id_entidad, accion, usuario, detalles)
VALUES
('inscripciones', '1', 'INSERT', 'Juan Pérez', JSON_OBJECT('carrera','Ingeniería de Sistemas','periodo','2025-I')),
('documentos_postulantes', '4', 'VALIDACIÓN', 'Carlos Vargas', JSON_OBJECT('estado','rechazado','documento','Título de Bachiller'));