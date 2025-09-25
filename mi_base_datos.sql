CREATE DATABASE IF NOT EXISTS gestion_postulantes;
USE gestion_postulantes;

CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cedula_identidad VARCHAR(20) UNIQUE NOT NULL,
    correo_electronico VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('postulante', 'personal_admision', 'admin') NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE postulantes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(50) NOT NULL,
    apellido_materno VARCHAR(50) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    telefono_contacto VARCHAR(15),
    direccion_residencia TEXT,
    nacionalidad VARCHAR(50) DEFAULT 'Boliviana',
    foto_perfil_url VARCHAR(255),
    estado_postulacion ENUM('pendiente', 'documentos_aprobados', 'documentos_rechazados', 'admitido', 'no_admitido') DEFAULT 'pendiente',
    fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE carreras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo_carrera VARCHAR(20) UNIQUE NOT NULL,
    nombre_carrera VARCHAR(100) NOT NULL,
    descripcion_carrera TEXT,
    numero_vacantes INT NOT NULL,
    estado ENUM('activa', 'inactiva') DEFAULT 'activa'
);

CREATE TABLE periodos_academicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_periodo VARCHAR(50) NOT NULL,
    fecha_inicio_inscripciones DATE NOT NULL,
    fecha_fin_inscripciones DATE NOT NULL,
    fecha_examen_admision DATE NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    administrador_id INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE inscripciones_carreras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    postulante_id INT NOT NULL,
    carrera_id INT NOT NULL,
    periodo_id INT NOT NULL,
    opcion_carrera ENUM('primera', 'segunda') NOT NULL,
    numero_folio VARCHAR(50) UNIQUE NOT NULL,
    puntaje_examen DECIMAL(5,2),
    estado_inscripcion ENUM('inscrito', 'presento_examen', 'admitido', 'no_admitido', 'acepto_vacante') DEFAULT 'inscrito',
    fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE documentos_requeridos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_documento VARCHAR(100) NOT NULL,
    descripcion_documento TEXT,
    obligatorio BOOLEAN DEFAULT true,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

CREATE TABLE documentos_postulantes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    postulante_id INT NOT NULL,
    documento_id INT NOT NULL,
    archivo_url VARCHAR(255) NOT NULL,
    estado_validacion ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    observaciones TEXT,
    personal_validador_id INT,
    fecha_carga DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_validacion DATETIME
);

CREATE TABLE temarios_examen (
    id INT PRIMARY KEY AUTO_INCREMENT,
    carrera_id INT NOT NULL,
    nombre_temario VARCHAR(100) NOT NULL,
    descripcion_temario TEXT,
    archivo_url VARCHAR(255),
    fecha_inicio_vigencia DATE NOT NULL,
    fecha_fin_vigencia DATE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

CREATE TABLE temas_estudio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    temario_id INT NOT NULL,
    nombre_asignatura VARCHAR(100) NOT NULL,
    nombre_tema VARCHAR(200) NOT NULL,
    orden_tema INT NOT NULL
);

CREATE TABLE materiales_estudio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo_material VARCHAR(200) NOT NULL,
    autor_material VARCHAR(100),
    descripcion_material TEXT,
    archivo_url VARCHAR(255) NOT NULL,
    tipo_material ENUM('libro', 'guia', 'archivo', 'video') NOT NULL,
    carrera_id INT,
    permitir_descarga BOOLEAN DEFAULT true,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_publicacion DATE NOT NULL
);

CREATE TABLE consultas_materiales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    postulante_id INT NOT NULL,
    material_id INT NOT NULL,
    fecha_consulta DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo_consulta ENUM('visualizacion', 'descarga') NOT NULL
);

CREATE TABLE estadisticas_admision (
    id INT PRIMARY KEY AUTO_INCREMENT,
    carrera_id INT NOT NULL,
    periodo_id INT NOT NULL,
    total_postulantes INT NOT NULL DEFAULT 0,
    postulantes_admitidos INT NOT NULL DEFAULT 0,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE postulantes ADD FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;
ALTER TABLE periodos_academicos ADD FOREIGN KEY (administrador_id) REFERENCES usuarios(id);
ALTER TABLE inscripciones_carreras ADD FOREIGN KEY (postulante_id) REFERENCES postulantes(id);
ALTER TABLE inscripciones_carreras ADD FOREIGN KEY (carrera_id) REFERENCES carreras(id);
ALTER TABLE inscripciones_carreras ADD FOREIGN KEY (periodo_id) REFERENCES periodos_academicos(id);
ALTER TABLE documentos_postulantes ADD FOREIGN KEY (postulante_id) REFERENCES postulantes(id);
ALTER TABLE documentos_postulantes ADD FOREIGN KEY (documento_id) REFERENCES documentos_requeridos(id);
ALTER TABLE documentos_postulantes ADD FOREIGN KEY (personal_validador_id) REFERENCES usuarios(id);
ALTER TABLE temarios_examen ADD FOREIGN KEY (carrera_id) REFERENCES carreras(id);
ALTER TABLE temas_estudio ADD FOREIGN KEY (temario_id) REFERENCES temarios_examen(id) ON DELETE CASCADE;
ALTER TABLE materiales_estudio ADD FOREIGN KEY (carrera_id) REFERENCES carreras(id);
ALTER TABLE consultas_materiales ADD FOREIGN KEY (postulante_id) REFERENCES postulantes(id);
ALTER TABLE consultas_materiales ADD FOREIGN KEY (material_id) REFERENCES materiales_estudio(id);
ALTER TABLE estadisticas_admision ADD FOREIGN KEY (carrera_id) REFERENCES carreras(id);
ALTER TABLE estadisticas_admision ADD FOREIGN KEY (periodo_id) REFERENCES periodos_academicos(id);

CREATE INDEX idx_usuarios_cedula ON usuarios(cedula_identidad);
CREATE INDEX idx_usuarios_rol ON usuarios(rol);
CREATE INDEX idx_postulantes_estado ON postulantes(estado_postulacion);
CREATE INDEX idx_carreras_estado ON carreras(estado);
CREATE INDEX idx_periodos_fechas ON periodos_academicos(fecha_inicio_inscripciones, fecha_fin_inscripciones);
CREATE INDEX idx_inscripciones_estado ON inscripciones_carreras(estado_inscripcion);
CREATE INDEX idx_documentos_estado ON documentos_postulantes(estado_validacion);
CREATE INDEX idx_materiales_tipo ON materiales_estudio(tipo_material);
CREATE INDEX idx_consultas_fecha ON consultas_materiales(fecha_consulta);

ALTER TABLE inscripciones_carreras ADD UNIQUE KEY inscripcion_unica (postulante_id, periodo_id, opcion_carrera);
ALTER TABLE estadisticas_admision ADD UNIQUE KEY estadistica_unica (carrera_id, periodo_id);

-- Insertar usuarios con contraseñas en texto plano
INSERT INTO usuarios (cedula_identidad, correo_electronico, contrasena, rol, estado) VALUES 
('1234567', 'admin@university.edu', 'admin123', 'admin', 'activo'),
('7654321', 'admision@university.edu', 'admision2024', 'personal_admision', 'activo'),
('8912345', 'juan.perez@email.com', 'miPassword123', 'postulante', 'activo');

INSERT INTO carreras (codigo_carrera, nombre_carrera, descripcion_carrera, numero_vacantes, estado) VALUES 
('ING-SIS', 'Ingeniería de Sistemas', 'Carrera de ingeniería en sistemas computacionales', 100, 'activa'),
('ING-CIV', 'Ingeniería Civil', 'Carrera de ingeniería civil y construcción', 80, 'activa'),
('MED-GEN', 'Medicina General', 'Carrera de medicina humana', 120, 'activa'),
('DER-PEN', 'Derecho', 'Carrera de derecho y ciencias jurídicas', 90, 'activa');

INSERT INTO documentos_requeridos (nombre_documento, descripcion_documento, obligatorio, estado) VALUES 
('Fotografía 4x4', 'Fotografía tamaño carnet fondo azul', true, 'activo'),
('Certificado de Nacimiento', 'Partida de nacimiento original', true, 'activo'),
('Título de Bachiller', 'Título de bachiller legalizado', true, 'activo'),
('Certificado de Notas', 'Certificado de calificaciones de colegio', true, 'activo'),
('Cédula de Identidad', 'Fotocopia de cédula de identidad', true, 'activo'),
('Certificado Médico', 'Certificado de salud general', false, 'activo');

INSERT INTO periodos_academicos (nombre_periodo, fecha_inicio_inscripciones, fecha_fin_inscripciones, fecha_examen_admision, estado, administrador_id) VALUES 
('2024-I', '2024-01-15', '2024-03-15', '2024-03-30', 'activo', 1),
('2024-II', '2024-06-01', '2024-07-31', '2024-08-15', 'activo', 1);

INSERT INTO materiales_estudio (titulo_material, autor_material, descripcion_material, archivo_url, tipo_material, carrera_id, permitir_descarga, estado, fecha_publicacion) VALUES 
('Guía de Matemáticas Básicas', 'Departamento de Matemáticas', 'Guía completa de matemáticas para examen de admisión', 'guias/matematicas.pdf', 'guia', 1, true, 'activo', '2024-01-01'),
('Física General', 'Dr. Carlos Méndez', 'Libro de física general para ingenierías', 'libros/fisica.pdf', 'libro', 1, true, 'activo', '2024-01-01'),
('Química Orgánica', 'Dra. Ana López', 'Material de química para carreras de salud', 'libros/quimica.pdf', 'libro', 3, true, 'activo', '2024-01-01'),
('Introducción al Derecho', 'Dr. Roberto Silva', 'Conceptos básicos de derecho', 'guias/derecho.pdf', 'guia', 4, true, 'activo', '2024-01-01');