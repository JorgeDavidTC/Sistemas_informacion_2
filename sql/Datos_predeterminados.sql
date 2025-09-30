
INSERT INTO usuarios (nombre, cedula_identidad, correo_electronico, contrasena, rol, estado) VALUES 
('Administrador General', '1000001', 'admin@umss.edu.bo', '123456', 'admin', 'activo'),
('María Elena García', '6543210', 'maria.garcia@email.com', '123456', 'postulante', 'activo'),
('Carlos Andrés Mendoza', '7123456', 'carlos.mendoza@email.com', '123456', 'postulante', 'activo'),
('Ana Patricia Vargas', '8234567', 'ana.vargas@email.com', '123456', 'postulante', 'activo'),
('Luis Fernando Rojas', '9345678', 'luis.rojas@email.com', '123456', 'postulante', 'activo'),
('Sofia Camacho', '1045678', 'sofia.camacho@email.com', '123456', 'postulante', 'activo'),
('Diego Antonio Pérez', '1156789', 'diego.perez@email.com', '123456', 'postulante', 'activo'),
('Valeria Morales', '1267890', 'valeria.morales@email.com', '123456', 'postulante', 'activo'),
('Jorge Luis Fernández', '1378901', 'jorge.fernandez@email.com', '123456', 'postulante', 'activo'),
('Gabriela Torrez', '1489012', 'gabriela.torrez@email.com', '123456', 'postulante', 'activo'),
('Roberto Carlos Silva', '1590123', 'roberto.silva@email.com', '123456', 'personal_admision', 'activo'),
('Laura Patricia Arce', '1601234', 'laura.arce@email.com', '123456', 'personal_admision', 'activo');

INSERT INTO facultades (codigo, nombre, descripcion, estado) VALUES
('FING', 'Facultad de Ingeniería', 'Facultad que agrupa carreras de ingeniería y tecnología', 'activa'),
('FMED', 'Facultad de Medicina', 'Facultad especializada en ciencias de la salud', 'activa'),
('FCJE', 'Facultad de Ciencias Jurídicas y Empresariales', 'Facultad de derecho y administración de empresas', 'activa'),
('FCEyT', 'Facultad de Ciencias Económicas y Tecnológicas', 'Facultad de economía y tecnologías aplicadas', 'activa'),
('FHyA', 'Facultad de Humanidades y Artes', 'Facultad de humanidades, letras y artes', 'activa'),
('FCNyA', 'Facultad de Ciencias Naturales y Agropecuarias', 'Facultad de ciencias naturales y agropecuarias', 'activa'),
('FADyP', 'Facultad de Arquitectura y Diseño', 'Facultad de arquitectura y diseño urbano', 'activa'),
('FCSyP', 'Facultad de Ciencias Sociales y Políticas', 'Facultad de ciencias sociales y políticas', 'activa');

INSERT INTO carreras (facultad_id, codigo, nombre, descripcion, cupos, estado, postulantes_count) VALUES
(1, 'IS-001', 'Ingeniería de Sistemas', 'Carrera orientada al desarrollo de software y sistemas computacionales', 120, 'activa', 45),
(1, 'IC-002', 'Ingeniería Civil', 'Carrera enfocada en construcción, estructuras y obras civiles', 80, 'activa', 38),
(1, 'IEL-003', 'Ingeniería Electrónica', 'Carrera especializada en sistemas electrónicos y telecomunicaciones', 60, 'activa', 25),
(2, 'MED-004', 'Medicina', 'Carrera en ciencias de la salud y medicina humana', 100, 'activa', 72),
(2, 'ENF-005', 'Enfermería', 'Carrera en cuidados de enfermería y atención primaria', 70, 'activa', 40),
(3, 'DER-006', 'Derecho', 'Carrera en ciencias jurídicas y ejercicio legal', 90, 'activa', 55),
(3, 'ADE-007', 'Administración de Empresas', 'Carrera en gestión empresarial y administración', 110, 'activa', 65),
(4, 'ECO-008', 'Economía', 'Carrera en ciencias económicas y finanzas', 75, 'activa', 30),
(5, 'PSI-009', 'Psicología', 'Carrera en ciencias del comportamiento y psicología clínica', 85, 'activa', 48),
(6, 'BIO-010', 'Biología', 'Carrera en ciencias biológicas y investigación ambiental', 50, 'activa', 22);

INSERT INTO periodos_academicos (nombre_periodo, fecha_inicio_inscripciones, fecha_fin_inscripciones, fecha_examen_admision, estado, administrador_id) VALUES
('2025-I', '2025-01-15', '2025-03-15', '2025-03-30', 'activo', 1),
('2025-II', '2025-06-01', '2025-07-31', '2025-08-15', 'activo', 1),
('2024-II', '2024-06-01', '2024-07-31', '2024-08-15', 'inactivo', 1),
('2026-I', '2026-01-15', '2026-03-15', '2026-03-30', 'activo', 1);

INSERT INTO postulantes (usuario_id, nombres, apellido_paterno, apellido_materno, ci, fecha_nacimiento, telefono, direccion_residencia, nacionalidad, estado_postulacion) VALUES
(2, 'María Elena', 'García', 'Méndez', '6543210', '2004-05-15', '77412345', 'Av. Blanco Galindo Km 5, Cochabamba', 'Boliviana', 'documentos_aprobados'),
(3, 'Carlos Andrés', 'Mendoza', 'López', '7123456', '2003-08-22', '77423456', 'Calle Jordán E-456, Cochabamba', 'Boliviana', 'pendiente'),
(4, 'Ana Patricia', 'Vargas', 'Silva', '8234567', '2004-12-10', '77434567', 'Zona Sur, Calle México 123, Cochabamba', 'Boliviana', 'documentos_aprobados'),
(5, 'Luis Fernando', 'Rojas', 'Pérez', '9345678', '2003-03-30', '77445678', 'Av. América O-789, Cochabamba', 'Boliviana', 'admitido'),
(6, 'Sofia', 'Camacho', 'Ríos', '1045678', '2004-07-18', '77456789', 'Calle Buenos Aires 234, Cochabamba', 'Boliviana', 'no_admitido'),
(7, 'Diego Antonio', 'Pérez', 'Gutiérrez', '1156789', '2003-11-25', '77467890', 'Zona Queru Queru, Calle Sucre 567', 'Boliviana', 'pendiente'),
(8, 'Valeria', 'Morales', 'Castro', '1267890', '2004-02-14', '77478901', 'Av. Pando 890, Cochabamba', 'Boliviana', 'documentos_rechazados'),
(9, 'Jorge Luis', 'Fernández', 'Duran', '1378901', '2003-09-08', '77489012', 'Calle Tarata 345, Cochabamba', 'Boliviana', 'documentos_aprobados'),
(10, 'Gabriela', 'Torrez', 'Arce', '1489012', '2004-04-03', '77490123', 'Zona Adela Zamudio, Calle Antofagasta 678', 'Boliviana', 'pendiente');

INSERT INTO inscripciones (id_postulante, id_carrera, periodo_id, opcion_carrera, numero_folio, puntaje_examen, estado_inscripcion, datos_form) VALUES
(1, 1, 1, 'primera', 'FOLIO-2025-001', 85.50, 'admitido', '{"colegio": "San Agustín", "tipo_colegio": "Privado", "año_egreso": 2024}'),
(2, 1, 1, 'primera', 'FOLIO-2025-002', 78.25, 'presento_examen', '{"colegio": "Juan Misael Saracho", "tipo_colegio": "Fiscal", "año_egreso": 2023}'),
(3, 4, 1, 'primera', 'FOLIO-2025-003', 92.75, 'admitido', '{"colegio": "Sagrado Corazón", "tipo_colegio": "Privado", "año_egreso": 2024}'),
(4, 2, 1, 'primera', 'FOLIO-2025-004', 88.00, 'acepto_vacante', '{"colegio": "Bolivia", "tipo_colegio": "Fiscal", "año_egreso": 2024}'),
(5, 6, 1, 'primera', 'FOLIO-2025-005', 65.50, 'no_admitido', '{"colegio": "Mariscal Sucre", "tipo_colegio": "Fiscal", "año_egreso": 2023}'),
(6, 4, 1, 'primera', 'FOLIO-2025-006', 74.25, 'presento_examen', '{"colegio": "Santa María", "tipo_colegio": "Privado", "año_egreso": 2024}'),
(7, 3, 1, 'primera', 'FOLIO-2025-007', 81.75, 'confirmada', '{"colegio": "Don Bosco", "tipo_colegio": "Privado", "año_egreso": 2024}'),
(8, 7, 1, 'primera', 'FOLIO-2025-008', 79.50, 'inscrito', '{"colegio": "La Salle", "tipo_colegio": "Privado", "año_egreso": 2023}'),
(9, 1, 1, 'segunda', 'FOLIO-2025-009', 83.25, 'admitido', '{"colegio": "San Simón", "tipo_colegio": "Fiscal", "año_egreso": 2024}'),
(1, 4, 1, 'segunda', 'FOLIO-2025-010', 87.00, 'pendiente', '{"colegio": "San Agustín", "tipo_colegio": "Privado", "año_egreso": 2024}');

INSERT INTO documentos_requeridos (nombre_documento, descripcion_documento, obligatorio, estado) VALUES
('Fotocopia de Cédula de Identidad', 'Fotocopia legalizada de CI vigente', 1, 'activo'),
('Certificado de Nacimiento', 'Certificado original de nacimiento', 1, 'activo'),
('Título de Bachiller', 'Fotocopia legalizada del título de bachiller', 1, 'activo'),
('Certificado de Notas', 'Certificado de calificaciones de secundaria', 1, 'activo'),
('Fotografías 4x4', '4 fotografías fondo rojo', 1, 'activo'),
('Formulario de Inscripción', 'Formulario completo y firmado', 1, 'activo'),
('Certificado Médico', 'Certificado de salud general', 1, 'activo'),
('Comprobante de Pago', 'Recibo de pago por derechos de inscripción', 1, 'activo'),
('Certificado de Conducta', 'Certificado de buena conducta (opcional)', 0, 'activo'),
('Carnet de Vacunas', 'Carnet de vacunación COVID-19', 1, 'activo');

INSERT INTO documentos_postulantes (postulante_id, documento_req_id, tipo_documento, archivo_url, estado_validacion, personal_validador_id, comentario) VALUES
(1, 1, 'Fotocopia CI', '/docs/6543210_ci.pdf', 'aprobado', 11, 'Documento claro y legible'),
(1, 2, 'Certificado Nacimiento', '/docs/6543210_nacimiento.pdf', 'aprobado', 11, 'Certificado original'),
(1, 3, 'Título Bachiller', '/docs/6543210_bachiller.pdf', 'aprobado', 11, 'Legalizado correctamente'),
(2, 1, 'Fotocopia CI', '/docs/7123456_ci.pdf', 'pendiente', NULL, NULL),
(2, 2, 'Certificado Nacimiento', '/docs/7123456_nacimiento.pdf', 'pendiente', NULL, NULL),
(3, 1, 'Fotocopia CI', '/docs/8234567_ci.pdf', 'aprobado', 12, 'Documento en buen estado'),
(3, 3, 'Título Bachiller', '/docs/8234567_bachiller.pdf', 'rechazado', 12, 'Falta legalización'),
(4, 1, 'Fotocopia CI', '/docs/9345678_ci.pdf', 'aprobado', 11, 'OK'),
(4, 4, 'Certificado Notas', '/docs/9345678_notas.pdf', 'aprobado', 11, 'Calificaciones completas'),
(5, 1, 'Fotocopia CI', '/docs/1045678_ci.pdf', 'aprobado', 12, 'Documento válido');

INSERT INTO temarios (id_carrera, version, nombre_temario, publicado_en, descripcion, estado) VALUES
(1, 'v2.1', 'Temario Ingeniería de Sistemas 2025', '2024-11-15', 'Temario actualizado para examen de admisión 2025', 'activo'),
(4, 'v1.8', 'Temario Medicina 2025', '2024-11-20', 'Temario para carrera de medicina ciclo 2025', 'activo'),
(6, 'v2.0', 'Temario Derecho 2025', '2024-11-10', 'Temario actualizado derecho constitucional y civil', 'activo'),
(2, 'v1.5', 'Temario Ingeniería Civil 2025', '2024-11-18', 'Temario para ingeniería civil', 'activo');

INSERT INTO asignaturas (nombre, codigo) VALUES
('Matemáticas', 'MAT-001'),
('Física', 'FIS-001'),
('Química', 'QUI-001'),
('Biología', 'BIO-001'),
('Lenguaje', 'LEN-001'),
('Historia', 'HIS-001'),
('Geografía', 'GEO-001'),
('Inglés', 'ING-001'),
('Razonamiento Lógico', 'RL-001'),
('Conocimientos Específicos', 'CE-001');

INSERT INTO temas (id_temario, id_asignatura, titulo, contenido, orden, fecha_inicio, fecha_fin) VALUES
(1, 1, 'Álgebra Lineal', 'Matrices, determinantes, sistemas de ecuaciones', 1, '2025-01-15', '2025-02-15'),
(1, 1, 'Cálculo Diferencial', 'Límites, derivadas y aplicaciones', 2, '2025-02-16', '2025-03-15'),
(1, 2, 'Mecánica Clásica', 'Cinemática, dinámica, leyes de Newton', 3, '2025-01-20', '2025-02-20'),
(1, 9, 'Lógica Proposicional', 'Proposiciones, conectivos lógicos, tablas de verdad', 4, '2025-03-01', '2025-03-20'),
(2, 4, 'Biología Celular', 'Estructura celular, mitosis, meiosis', 1, '2025-01-10', '2025-02-10'),
(2, 4, 'Anatomía Humana', 'Sistemas del cuerpo humano', 2, '2025-02-11', '2025-03-10'),
(2, 3, 'Química Orgánica', 'Compuestos orgánicos, hidrocarburos', 3, '2025-01-25', '2025-02-25'),
(3, 6, 'Derecho Constitucional', 'Constitución Política del Estado', 1, '2025-01-12', '2025-02-12'),
(3, 6, 'Derecho Civil', 'Personas, familia, sucesiones', 2, '2025-02-13', '2025-03-12');


INSERT INTO notas_temas (id_postulante, id_tema, nota, fecha_realizacion) VALUES
(1, 1, 85.00, '2025-03-01 09:00:00'),
(1, 2, 92.50, '2025-03-08 09:00:00'),
(1, 3, 78.75, '2025-03-15 09:00:00'),
(2, 1, 72.25, '2025-03-01 09:00:00'),
(2, 2, 68.50, '2025-03-08 09:00:00'),
(3, 5, 88.00, '2025-03-02 10:00:00'),
(3, 6, 94.25, '2025-03-09 10:00:00'),
(4, 1, 81.75, '2025-03-01 09:00:00'),
(4, 3, 76.50, '2025-03-15 09:00:00'),
(5, 8, 65.25, '2025-03-03 11:00:00');

INSERT INTO recursos (titulo, descripcion, archivo_url, tipo, permitir_descarga, publico, carrera_id, estado, fecha_publicacion) VALUES
('Guía Matemáticas Admisión', 'Guía completa de matemáticas para examen', '/recursos/matematicas_2025.pdf', 'guia', 1, 1, 1, 'activo', '2024-12-01'),
('Video Clases Física', 'Video clases de física básica', 'https://youtube.com/playlist/fisica', 'video', 0, 1, 1, 'activo', '2024-12-05'),
('Manual Biología Celular', 'Manual completo de biología celular', '/recursos/biologia_celular.pdf', 'libro', 1, 1, 4, 'activo', '2024-12-10'),
('Simulacro Examen Medicina', 'Examen simulado para medicina', '/recursos/simulacro_medicina.pdf', 'archivo', 1, 1, 4, 'activo', '2024-12-15'),
('Guía Derecho Constitucional', 'Guía de derecho constitucional boliviano', '/recursos/derecho_constitucional.pdf', 'guia', 1, 1, 6, 'activo', '2024-12-08'),
('Guía Oficial de Ingeniería', NULL, 'guias/guia_ingenieria.jpg', 'guia', 1, 1, 1, 'activo', '2025-09-29'),
('Guía Oficial de Medicina', NULL, 'guias/guia_medicina.jpg', 'guia', 1, 1, 1, 'activo', '2025-09-29'),
('Guía Oficial de Derecho', NULL, 'guias/guia_derecho.jpg', 'guia', 1, 1, 1, 'activo', '2025-09-29'),
('Primer Examen de Ingreso FCyT 1-2005', NULL, 'examenes/050_ExamenAdmissionPrimerOpcion1-2005.pdf', 'archivo', 1, 1, 1, 'activo', '2005-01-15'),
('Segundo Examen de Ingreso FCyT 1-2005', NULL, 'examenes/051_ExamenAdmissionSegundaOpcion1-2005.pdf', 'archivo', 1, 1, 1, 'activo', '2005-07-20'),
('Primer Examen de Ingreso FCyT 2-2005', NULL, 'examenes/052_ExamenAdmissionPrimeraOpcion2-2005.pdf', 'archivo', 1, 1, 1, 'activo', '2005-08-10');

INSERT INTO consultas_materiales (postulante_id, material_id, fecha_consulta, tipo_consulta) VALUES
(1, 1, '2025-01-10 14:30:00', 'descarga'),
(1, 2, '2025-01-11 10:15:00', 'visualizacion'),
(2, 1, '2025-01-12 16:45:00', 'descarga'),
(3, 3, '2025-01-13 11:20:00', 'descarga'),
(3, 4, '2025-01-14 15:30:00', 'visualizacion'),
(4, 1, '2025-01-15 09:45:00', 'descarga'),
(5, 5, '2025-01-16 14:15:00', 'visualizacion');

INSERT INTO resultados (id_postulante, id_carrera, folio_consulta, puntaje, aprobado, carta_url) VALUES
(1, 1, 'RES-2025-001', 85.50, 1, '/cartas/carta_6543210.pdf'),
(3, 4, 'RES-2025-002', 92.75, 1, '/cartas/carta_8234567.pdf'),
(4, 2, 'RES-2025-003', 88.00, 1, '/cartas/carta_9345678.pdf'),
(5, 6, 'RES-2025-004', 65.50, 0, NULL),
(9, 1, 'RES-2025-005', 83.25, 1, '/cartas/carta_1378901.pdf');

INSERT INTO pagos (id_postulante, monto, concepto, metodo, referencia, estado) VALUES
(1, 150.00, 'Derechos de Inscripción', 'transferencia', 'TRF-001234', 'completado'),
(2, 150.00, 'Derechos de Inscripción', 'efectivo', 'REC-001235', 'completado'),
(3, 150.00, 'Derechos de Inscripción', 'tarjeta', 'TARJ-001236', 'completado'),
(4, 150.00, 'Derechos de Inscripción', 'transferencia', 'TRF-001237', 'completado'),
(5, 150.00, 'Derechos de Inscripción', 'efectivo', 'REC-001238', 'completado'),
(6, 150.00, 'Derechos de Inscripción', 'transferencia', 'TRF-001239', 'pendiente'),
(7, 150.00, 'Derechos de Inscripción', 'tarjeta', 'TARJ-001240', 'completado');

INSERT INTO notificaciones (usuario_id, mensaje, leido) VALUES
(2, 'Su documentación ha sido aprobada exitosamente', 1),
(2, 'Su examen de admisión está programado para el 30 de marzo', 0),
(3, 'Falta subir el certificado de nacimiento', 0),
(4, '¡Felicidades! Ha sido admitido en Ingeniería Civil', 1),
(5, 'Lamentamos informarle que no alcanzó el puntaje mínimo', 1),
(6, 'Recuerde completar su formulario de inscripción', 0),
(7, 'Su pago ha sido procesado exitosamente', 1);

INSERT INTO bitacora (entidad, id_entidad, accion, usuario, detalles) VALUES
('usuarios', '2', 'login', 'maria.garcia@email.com', '{"ip": "192.168.1.100", "navegador": "Chrome"}'),
('inscripciones', '1', 'create', 'maria.garcia@email.com', '{"carrera": "Ingeniería de Sistemas", "periodo": "2025-I"}'),
('documentos', '1', 'validate', 'roberto.silva@email.com', '{"estado": "aprobado", "comentario": "Documento claro"}'),
('pagos', '1', 'confirm', 'sistema', '{"monto": 150.00, "metodo": "transferencia"}'),
('notas', '1', 'register', 'sistema', '{"tema": "Álgebra Lineal", "nota": 85.00}');


