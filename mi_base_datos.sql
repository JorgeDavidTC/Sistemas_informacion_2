CREATE DATABASE IF NOT EXISTS gestion_postulantes;
USE gestion_postulantes;

CREATE TABLE carreras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(10) UNIQUE NOT NULL,
    duracion_semestres INT NOT NULL,
    modalidad ENUM('presencial', 'virtual', 'mixta') DEFAULT 'presencial',
    cupos_disponibles INT DEFAULT 0,
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE postulantes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    -- Información personal básica
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    documento_tipo ENUM('CI', 'Pasaporte', 'Cédula', 'Otro') DEFAULT 'CI',
    documento_numero VARCHAR(20) UNIQUE NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro') DEFAULT 'Otro',
    nacionalidad VARCHAR(50) DEFAULT 'Perú',
    email VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    celular VARCHAR(20),
    direccion TEXT,
    ciudad VARCHAR(100),
    departamento VARCHAR(100),
    colegio_procedencia VARCHAR(200),
    tipo_colegio ENUM('Público', 'Privado', 'Parroquial') DEFAULT 'Público',
    año_egreso INT,
    promedio_secundaria DECIMAL(4,2),
    carrera_id INT NOT NULL,
    periodo_postulacion VARCHAR(20) NOT NULL,
    fecha_postulacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'aceptado', 'rechazado', 'habilitado', 'matriculado') DEFAULT 'pendiente',
    fecha_examen DATETIME NULL,
    aula_examen VARCHAR(50) NULL,
    nota_examen DECIMAL(4,2) NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (carrera_id) REFERENCES carreras(id),
    INDEX idx_documento (documento_numero),
    INDEX idx_estado (estado),
    INDEX idx_carrera (carrera_id)
);

CREATE TABLE documentos_postulante (
    id INT PRIMARY KEY AUTO_INCREMENT,
    postulante_id INT NOT NULL,
    tipo_documento ENUM('CI', 'PartidaNacimiento', 'CertificadoEstudios', 'Foto', 'Otro') NOT NULL,
    nombre_archivo VARCHAR(255),
    ruta_archivo VARCHAR(500),
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    observaciones TEXT,

    FOREIGN KEY (postulante_id) REFERENCES postulantes(id) ON DELETE CASCADE,
    INDEX idx_postulante (postulante_id)
);

INSERT INTO carreras (nombre, codigo, duracion_semestres, modalidad, cupos_disponibles) VALUES 
('Ingeniería de Sistemas', 'IS', 10, 'presencial', 50),('Medicina Humana', 'MED', 12, 'presencial', 30),('Derecho y Ciencias Políticas', 'DER', 10, 'mixta', 40),
('Administración de Empresas', 'ADM', 8, 'virtual', 60),('Psicología', 'PSI', 10, 'presencial', 35),('Arquitectura y Urbanismo', 'ARQ', 10, 'presencial', 25),
('Ingeniería Civil', 'IC', 10, 'presencial', 40),('Contabilidad', 'CONT', 8, 'mixta', 55);

INSERT INTO postulantes ( nombre, apellido, documento_tipo, documento_numero, fecha_nacimiento, genero,email, telefono, celular, direccion, ciudad, departamento,
colegio_procedencia, tipo_colegio, año_egreso, promedio_secundaria,carrera_id, periodo_postulacion, estado, fecha_examen, aula_examen, nota_examen) 

VALUES 

('María', 'González', 'DNI', '12345678', '2000-05-15', 'Femenino','maria.gonzalez@email.com', '014567890', '987654321', 'Av. Principal 123', 'Lima', 'Lima',
'Colegio Nacional María Parado de Bellido', 'Público', 2022, 16.5,1, '2024-I', 'aceptado', '2024-01-20 09:00:00', 'Aula 101', 85.5),

('Carlos', 'López', 'DNI', '87654321', '1999-08-22', 'Masculino','carlos.lopez@email.com', '012345678', '912345678', 'Jr. Los Olivos 456', 'Arequipa', 'Arequipa',
'Colegio Particular San Agustín', 'Privado', 2021, 17.2,2, '2024-I', 'rechazado', '2024-01-20 09:00:00', 'Aula 102', 72.0),

('Ana', 'Martínez', 'DNI', '11223344', '2001-03-10', 'Femenino','ana.martinez@email.com', NULL, '933221144', 'Calle Las Magnolias 789', 'Trujillo', 'La Libertad',
 'Colegio Nacional Daniel Alcides Carrión', 'Público', 2023, 15.8,3, '2024-I', 'aceptado', '2024-01-21 09:00:00', 'Aula 201', 88.0),

('Javier', 'Rodríguez', 'DNI', '44332211', '2002-11-30', 'Masculino','javier.rodriguez@email.com', '017896543', '944332211', 'Av. Universitaria 321', 'Lima', 'Lima',
 'Colegio Parroquial Santa Rosa', 'Parroquial', 2023, 16.9,
 4, '2024-I', 'pendiente', NULL, NULL, NULL),

('Laura', 'Sánchez', 'DNI', '55667788', '2000-07-18', 'Femenino','laura.sanchez@email.com', '015678912', '955667788', 'Psje. Los Pinos 654', 'Cusco', 'Cusco',
'Colegio Particular La Salle', 'Privado', 2022, 18.0,5, '2024-I', 'habilitado', '2024-01-22 09:00:00', 'Aula 301', 92.5);