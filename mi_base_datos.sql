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

CREATE TABLE facultades (
  id_facultad INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(50) NOT NULL UNIQUE,
  nombre VARCHAR(255) NOT NULL,
  descripcion TEXT,
  estado ENUM('activa','inactiva') DEFAULT 'activa',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_facultad)
) ENGINE=InnoDB;

CREATE TABLE carreras (
  id_carrera INT UNSIGNED NOT NULL AUTO_INCREMENT,
  facultad_id INT UNSIGNED NULL,
  codigo VARCHAR(50) NOT NULL UNIQUE,
  nombre VARCHAR(255) NOT NULL,
  descripcion TEXT,
  cupos INT UNSIGNED DEFAULT 0,
  estado ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
  postulantes_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_carrera),
  CONSTRAINT fk_carrera_facultad FOREIGN KEY (facultad_id) REFERENCES facultades(id_facultad)
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
  fecha_inicio DATE NULL,
  fecha_fin DATE NULL,
  PRIMARY KEY (id_tema),
  CONSTRAINT fk_tema_temario FOREIGN KEY (id_temario) REFERENCES temarios(id_temario)
) ENGINE=InnoDB;

CREATE TABLE notas_temas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_postulante BIGINT UNSIGNED NOT NULL,
  id_tema BIGINT UNSIGNED NOT NULL,
  nota DECIMAL(5,2) NULL,
  fecha_realizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE(id_postulante, id_tema),
  CONSTRAINT fk_notas_postulante FOREIGN KEY (id_postulante) REFERENCES postulantes(id_postulante),
  CONSTRAINT fk_notas_tema FOREIGN KEY (id_tema) REFERENCES temas(id_tema)
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