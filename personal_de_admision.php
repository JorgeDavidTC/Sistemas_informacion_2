<?php
session_start();
require_once 'conexion.php';

class DashboardManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getEstadisticasDashboard() {
        $estadisticas = [];
        
        $queries = [
            'total_postulantes' => "SELECT COUNT(*) as total FROM postulantes",
            'documentos_pendientes' => "SELECT COUNT(*) as total FROM documentos_postulantes WHERE estado_validacion = 'pendiente'",
            'inscripciones_activas' => "SELECT COUNT(*) as total FROM inscripciones WHERE estado_inscripcion IN ('inscrito', 'confirmada', 'presento_examen')",
            'total_carreras' => "SELECT COUNT(*) as total FROM carreras WHERE estado = 'activa'",
            'postulantes_aprobados' => "SELECT COUNT(*) as total FROM resultados WHERE aprobado = 1",
            'ingresos_totales' => "SELECT COALESCE(SUM(monto), 0) as total FROM pagos WHERE estado = 'completado'"
        ];
        
        foreach ($queries as $key => $query) {
            try {
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $estadisticas[$key] = $result ? $result['total'] : 0;
            } catch (PDOException $e) {
                $estadisticas[$key] = 0;
                error_log("Error en consulta $key: " . $e->getMessage());
            }
        }
        
        return $estadisticas;
    }
    
    public function getPostulantesPorCarrera() {
        try {
            $query = "SELECT c.nombre as carrera, COUNT(i.id_inscripcion) as cantidad 
                     FROM carreras c 
                     LEFT JOIN inscripciones i ON c.id_carrera = i.id_carrera 
                     WHERE c.estado = 'activa'
                     GROUP BY c.id_carrera, c.nombre
                     ORDER BY cantidad DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getPostulantesPorCarrera: " . $e->getMessage());
            return [];
        }
    }
    
    public function getEstadoDocumentos() {
        try {
            $query = "SELECT estado_validacion, COUNT(*) as cantidad 
                     FROM documentos_postulantes 
                     GROUP BY estado_validacion";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getEstadoDocumentos: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUltimasInscripciones() {
        try {
            $query = "SELECT i.*, p.nombres, p.apellido_paterno, p.apellido_materno, c.nombre as carrera_nombre
                     FROM inscripciones i
                     JOIN postulantes p ON i.id_postulante = p.id_postulante
                     JOIN carreras c ON i.id_carrera = c.id_carrera
                     ORDER BY i.fecha_inscripcion DESC
                     LIMIT 10";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getUltimasInscripciones: " . $e->getMessage());
            return [];
        }
    }
}

class PostulanteManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function buscarPostulantes($busqueda = null, $filtro_estado = 'todos') {
        try {
            $whereConditions = [];
            $params = [];
            
            if ($busqueda) {
                $whereConditions[] = "(p.nombres LIKE ? OR p.apellido_paterno LIKE ? OR p.apellido_materno LIKE ? OR p.ci LIKE ? OR i.numero_folio LIKE ?)";
                $param = "%$busqueda%";
                $params = array_merge($params, [$param, $param, $param, $param, $param]);
            }
            
            if ($filtro_estado != 'todos') {
                $whereConditions[] = "p.estado_postulacion = ?";
                $params[] = $filtro_estado;
            }
            
            $whereClause = "";
            if (!empty($whereConditions)) {
                $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            }
            
            $query = "SELECT p.*, i.numero_folio, c.nombre as carrera_nombre, i.estado_inscripcion,
                             (SELECT COUNT(*) FROM documentos_postulantes dp WHERE dp.postulante_id = p.id_postulante AND dp.estado_validacion = 'pendiente') as documentos_pendientes
                     FROM postulantes p 
                     LEFT JOIN inscripciones i ON p.id_postulante = i.id_postulante 
                     LEFT JOIN carreras c ON i.id_carrera = c.id_carrera 
                     $whereClause
                     ORDER BY p.creado_en DESC 
                     LIMIT 100";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en buscarPostulantes: " . $e->getMessage());
            return [];
        }
    }
    
    public function crearPostulante($datos) {
        try {
            $this->conn->beginTransaction();
            
            // Insertar postulante
            $query = "INSERT INTO postulantes (nombres, apellido_paterno, apellido_materno, ci, fecha_nacimiento, telefono, direccion_residencia, nacionalidad) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $datos['nombres'],
                $datos['apellido_paterno'],
                $datos['apellido_materno'],
                $datos['ci'],
                $datos['fecha_nacimiento'],
                $datos['telefono'],
                $datos['direccion'],
                $datos['nacionalidad']
            ]);
            
            $postulante_id = $this->conn->lastInsertId();
            
            // Registrar en bitácora
            $this->registrarBitacora('postulantes', $postulante_id, 'crear', $_SESSION['usuario']['nombre'], [
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellido_paterno'] . ' ' . $datos['apellido_materno'],
                'ci' => $datos['ci']
            ]);
            
            $this->conn->commit();
            return $postulante_id;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error en crearPostulante: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPostulante($id) {
        try {
            $query = "SELECT p.*, i.id_inscripcion, i.numero_folio, i.id_carrera, i.estado_inscripcion, i.periodo_id,
                             c.nombre as carrera_nombre, pa.nombre_periodo
                     FROM postulantes p 
                     LEFT JOIN inscripciones i ON p.id_postulante = i.id_postulante 
                     LEFT JOIN carreras c ON i.id_carrera = c.id_carrera
                     LEFT JOIN periodos_academicos pa ON i.periodo_id = pa.id_periodo
                     WHERE p.id_postulante = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPostulante: " . $e->getMessage());
            return null;
        }
    }
    
    public function actualizarPostulante($id, $datos) {
        try {
            $query = "UPDATE postulantes SET nombres = ?, apellido_paterno = ?, apellido_materno = ?, ci = ?, fecha_nacimiento = ?, telefono = ?, direccion_residencia = ?, nacionalidad = ?, estado_postulacion = ? WHERE id_postulante = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $datos['nombres'],
                $datos['apellido_paterno'],
                $datos['apellido_materno'],
                $datos['ci'],
                $datos['fecha_nacimiento'],
                $datos['telefono'],
                $datos['direccion'],
                $datos['nacionalidad'],
                $datos['estado_postulacion'],
                $id
            ]);
            
            if ($result) {
                $this->registrarBitacora('postulantes', $id, 'actualizar', $_SESSION['usuario']['nombre'], $datos);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error en actualizarPostulante: " . $e->getMessage());
            return false;
        }
    }
    
    public function eliminarPostulante($id) {
        try {
            $this->conn->beginTransaction();
            
            // Obtener datos del postulante para la bitácora
            $postulante = $this->obtenerPostulante($id);
            
            // Eliminar registros relacionados
            $queries = [
                "DELETE FROM documentos_postulantes WHERE postulante_id = ?",
                "DELETE FROM inscripciones WHERE id_postulante = ?",
                "DELETE FROM resultados WHERE id_postulante = ?",
                "DELETE FROM pagos WHERE id_postulante = ?",
                "DELETE FROM postulantes WHERE id_postulante = ?"
            ];
            
            foreach ($queries as $query) {
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$id]);
            }
            
            // Registrar en bitácora
            $this->registrarBitacora('postulantes', $id, 'eliminar', $_SESSION['usuario']['nombre'], [
                'nombres' => $postulante['nombres'],
                'apellidos' => $postulante['apellido_paterno'] . ' ' . $postulante['apellido_materno'],
                'ci' => $postulante['ci']
            ]);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error en eliminarPostulante: " . $e->getMessage());
            return false;
        }
    }
    
    private function registrarBitacora($entidad, $id_entidad, $accion, $usuario, $detalles) {
        try {
            $query = "INSERT INTO bitacora (entidad, id_entidad, accion, usuario, detalles) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$entidad, $id_entidad, $accion, $usuario, json_encode($detalles)]);
        } catch (PDOException $e) {
            error_log("Error al registrar en bitácora: " . $e->getMessage());
        }
    }
}

class DocumentoManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getDocumentos($filtro_estado = 'todos') {
        try {
            if ($filtro_estado == 'todos') {
                $query = "SELECT dp.*, p.nombres, p.apellido_paterno, p.apellido_materno, p.ci, 
                                 dr.nombre_documento, u.nombre as validador_nombre
                         FROM documentos_postulantes dp
                         JOIN postulantes p ON dp.postulante_id = p.id_postulante
                         LEFT JOIN documentos_requeridos dr ON dp.documento_req_id = dr.id_documento_req
                         LEFT JOIN usuarios u ON dp.personal_validador_id = u.id_usuario
                         ORDER BY dp.fecha_carga DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
            } else {
                $query = "SELECT dp.*, p.nombres, p.apellido_paterno, p.apellido_materno, p.ci,
                                 dr.nombre_documento, u.nombre as validador_nombre
                         FROM documentos_postulantes dp
                         JOIN postulantes p ON dp.postulante_id = p.id_postulante
                         LEFT JOIN documentos_requeridos dr ON dp.documento_req_id = dr.id_documento_req
                         LEFT JOIN usuarios u ON dp.personal_validador_id = u.id_usuario
                         WHERE dp.estado_validacion = ?
                         ORDER BY dp.fecha_carga DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$filtro_estado]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getDocumentos: " . $e->getMessage());
            return [];
        }
    }
    
    public function validarDocumento($id_doc, $estado, $comentario = '') {
        try {
            // Obtener información del usuario actual de la sesión
            $usuario_id = $_SESSION['usuario']['id_usuario'] ?? null;
            $usuario_nombre = $_SESSION['usuario']['nombre'] ?? 'Sistema';
            
            if (!$usuario_id) {
                throw new Exception("Usuario no autenticado correctamente");
            }
            
            $query = "UPDATE documentos_postulantes SET estado_validacion = ?, personal_validador_id = ?, fecha_validacion = NOW(), comentario = ? WHERE id_doc = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$estado, $usuario_id, $comentario, $id_doc]);
            
            if ($result) {
                // Obtener información del documento para la bitácora
                $docInfo = $this->getDocumentoInfo($id_doc);
                $this->registrarBitacora('documentos_postulantes', $id_doc, 'validar', $usuario_nombre, [
                    'postulante' => $docInfo['nombres'] . ' ' . $docInfo['apellido_paterno'],
                    'documento' => $docInfo['nombre_documento'] ?? $docInfo['tipo_documento'],
                    'estado' => $estado,
                    'comentario' => $comentario
                ]);
                
                // Actualizar estado del postulante si todos los documentos están aprobados
                $this->actualizarEstadoPostulante($docInfo['postulante_id']);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error en validarDocumento: " . $e->getMessage());
            return false;
        }
    }
    
    private function getDocumentoInfo($id_doc) {
        $query = "SELECT dp.*, p.nombres, p.apellido_paterno, p.id_postulante as postulante_id, dr.nombre_documento 
                 FROM documentos_postulantes dp
                 JOIN postulantes p ON dp.postulante_id = p.id_postulante
                 LEFT JOIN documentos_requeridos dr ON dp.documento_req_id = dr.id_documento_req
                 WHERE dp.id_doc = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_doc]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function actualizarEstadoPostulante($postulante_id) {
        try {
            // Contar documentos pendientes del postulante
            $query = "SELECT COUNT(*) as pendientes 
                     FROM documentos_postulantes 
                     WHERE postulante_id = ? AND estado_validacion = 'pendiente'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$postulante_id]);
            $pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['pendientes'];
            
            if ($pendientes == 0) {
                // Todos los documentos están revisados, verificar si hay rechazados
                $query = "SELECT COUNT(*) as rechazados 
                         FROM documentos_postulantes 
                         WHERE postulante_id = ? AND estado_validacion = 'rechazado'";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$postulante_id]);
                $rechazados = $stmt->fetch(PDO::FETCH_ASSOC)['rechazados'];
                
                $nuevo_estado = $rechazados > 0 ? 'documentos_rechazados' : 'documentos_aprobados';
                
                $query = "UPDATE postulantes SET estado_postulacion = ? WHERE id_postulante = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$nuevo_estado, $postulante_id]);
                
                // Registrar en bitácora
                $this->registrarBitacora('postulantes', $postulante_id, 'actualizar_estado', $_SESSION['usuario']['nombre'] ?? 'Sistema', [
                    'nuevo_estado' => $nuevo_estado,
                    'razon' => 'validacion_documentos'
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error en actualizarEstadoPostulante: " . $e->getMessage());
        }
    }
    
    private function registrarBitacora($entidad, $id_entidad, $accion, $usuario, $detalles) {
        try {
            $query = "INSERT INTO bitacora (entidad, id_entidad, accion, usuario, detalles) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$entidad, $id_entidad, $accion, $usuario, json_encode($detalles)]);
        } catch (PDOException $e) {
            error_log("Error al registrar en bitácora: " . $e->getMessage());
        }
    }
}

class InscripcionManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getInscripciones($filtro_estado = 'todas') {
        try {
            if ($filtro_estado == 'todas') {
                $query = "SELECT i.*, p.nombres, p.apellido_paterno, p.apellido_materno, p.ci, 
                                 c.nombre as carrera_nombre, pa.nombre_periodo,
                                 (SELECT COUNT(*) FROM documentos_postulantes dp WHERE dp.postulante_id = p.id_postulante AND dp.estado_validacion = 'pendiente') as documentos_pendientes
                         FROM inscripciones i
                         JOIN postulantes p ON i.id_postulante = p.id_postulante
                         JOIN carreras c ON i.id_carrera = c.id_carrera
                         JOIN periodos_academicos pa ON i.periodo_id = pa.id_periodo
                         ORDER BY i.fecha_inscripcion DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
            } else {
                $query = "SELECT i.*, p.nombres, p.apellido_paterno, p.apellido_materno, p.ci,
                                 c.nombre as carrera_nombre, pa.nombre_periodo,
                                 (SELECT COUNT(*) FROM documentos_postulantes dp WHERE dp.postulante_id = p.id_postulante AND dp.estado_validacion = 'pendiente') as documentos_pendientes
                         FROM inscripciones i
                         JOIN postulantes p ON i.id_postulante = p.id_postulante
                         JOIN carreras c ON i.id_carrera = c.id_carrera
                         JOIN periodos_academicos pa ON i.periodo_id = pa.id_periodo
                         WHERE i.estado_inscripcion = ?
                         ORDER BY i.fecha_inscripcion DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$filtro_estado]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getInscripciones: " . $e->getMessage());
            return [];
        }
    }
    
    public function crearInscripcion($datos) {
        try {
            $this->conn->beginTransaction();
            
            // Verificar si el postulante ya está inscrito en este periodo
            $query = "SELECT COUNT(*) as existe 
                     FROM inscripciones 
                     WHERE id_postulante = ? AND periodo_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$datos['id_postulante'], $datos['periodo_id']]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC)['existe'];
            
            if ($existe > 0) {
                throw new Exception("El postulante ya está inscrito en este periodo académico.");
            }
            
            // Generar número de folio único
            $folio = 'FOL-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $query = "INSERT INTO inscripciones (id_postulante, id_carrera, periodo_id, opcion_carrera, numero_folio, estado_inscripcion) 
                     VALUES (?, ?, ?, ?, ?, 'inscrito')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $datos['id_postulante'],
                $datos['id_carrera'],
                $datos['periodo_id'],
                $datos['opcion_carrera'],
                $folio
            ]);
            
            $inscripcion_id = $this->conn->lastInsertId();
            
            // Registrar pago de inscripción
            $query = "INSERT INTO pagos (id_postulante, monto, concepto, metodo, estado) 
                     VALUES (?, 50.00, 'Inscripción proceso de admisión', 'efectivo', 'completado')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$datos['id_postulante']]);
            
            // Registrar en bitácora
            $this->registrarBitacora('inscripciones', $inscripcion_id, 'crear', $_SESSION['usuario']['nombre'], [
                'postulante_id' => $datos['id_postulante'],
                'carrera_id' => $datos['id_carrera'],
                'periodo_id' => $datos['periodo_id'],
                'folio' => $folio
            ]);
            
            $this->conn->commit();
            return $inscripcion_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en crearInscripcion: " . $e->getMessage());
            return false;
        }
    }
    
    public function actualizarInscripcion($id, $datos) {
        try {
            $query = "UPDATE inscripciones SET id_carrera = ?, periodo_id = ?, opcion_carrera = ?, estado_inscripcion = ? WHERE id_inscripcion = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $datos['id_carrera'],
                $datos['periodo_id'],
                $datos['opcion_carrera'],
                $datos['estado_inscripcion'],
                $id
            ]);
            
            if ($result) {
                $this->registrarBitacora('inscripciones', $id, 'actualizar', $_SESSION['usuario']['nombre'], $datos);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error en actualizarInscripcion: " . $e->getMessage());
            return false;
        }
    }
    
    public function eliminarInscripcion($id) {
        try {
            $this->conn->beginTransaction();
            
            // Obtener datos de la inscripción para la bitácora
            $query = "SELECT i.*, p.nombres, p.apellido_paterno, c.nombre as carrera_nombre 
                     FROM inscripciones i
                     JOIN postulantes p ON i.id_postulante = p.id_postulante
                     JOIN carreras c ON i.id_carrera = c.id_carrera
                     WHERE i.id_inscripcion = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $inscripcion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Eliminar inscripción
            $query = "DELETE FROM inscripciones WHERE id_inscripcion = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            // Registrar en bitácora
            $this->registrarBitacora('inscripciones', $id, 'eliminar', $_SESSION['usuario']['nombre'], [
                'postulante' => $inscripcion['nombres'] . ' ' . $inscripcion['apellido_paterno'],
                'carrera' => $inscripcion['carrera_nombre'],
                'folio' => $inscripcion['numero_folio']
            ]);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error en eliminarInscripcion: " . $e->getMessage());
            return false;
        }
    }
    
    private function registrarBitacora($entidad, $id_entidad, $accion, $usuario, $detalles) {
        try {
            $query = "INSERT INTO bitacora (entidad, id_entidad, accion, usuario, detalles) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$entidad, $id_entidad, $accion, $usuario, json_encode($detalles)]);
        } catch (PDOException $e) {
            error_log("Error al registrar en bitácora: " . $e->getMessage());
        }
    }
}

class ResultadoManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getResultados($filtro_carrera = 'todas', $filtro_aprobado = 'todos') {
        try {
            $whereConditions = [];
            $params = [];
            
            if ($filtro_carrera != 'todas') {
                $whereConditions[] = "r.id_carrera = ?";
                $params[] = $filtro_carrera;
            }
            
            if ($filtro_aprobado != 'todos') {
                $whereConditions[] = "r.aprobado = ?";
                $params[] = ($filtro_aprobado == 'aprobados') ? 1 : 0;
            }
            
            $whereClause = "";
            if (!empty($whereConditions)) {
                $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            }
            
            $query = "SELECT r.*, p.nombres, p.apellido_paterno, p.apellido_materno, p.ci,
                             c.nombre as carrera_nombre, i.numero_folio, i.estado_inscripcion
                     FROM resultados r
                     JOIN postulantes p ON r.id_postulante = p.id_postulante
                     JOIN carreras c ON r.id_carrera = c.id_carrera
                     LEFT JOIN inscripciones i ON r.id_postulante = i.id_postulante AND r.id_carrera = i.id_carrera
                     $whereClause
                     ORDER BY r.fecha_resultado DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getResultados: " . $e->getMessage());
            return [];
        }
    }
    
    public function crearResultado($datos) {
        try {
            $this->conn->beginTransaction();
            
            // Verificar si ya existe resultado para este postulante y carrera
            $query = "SELECT COUNT(*) as existe 
                     FROM resultados 
                     WHERE id_postulante = ? AND id_carrera = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$datos['id_postulante'], $datos['id_carrera']]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC)['existe'];
            
            if ($existe > 0) {
                throw new Exception("Ya existe un resultado para este postulante en la carrera seleccionada.");
            }
            
            // Generar folio de consulta único
            $folio_consulta = 'RES-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $query = "INSERT INTO resultados (id_postulante, id_carrera, folio_consulta, puntaje, aprobado) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $datos['id_postulante'],
                $datos['id_carrera'],
                $folio_consulta,
                $datos['puntaje'],
                $datos['aprobado']
            ]);
            
            $resultado_id = $this->conn->lastInsertId();
            
            // Actualizar estado de la inscripción
            $nuevo_estado = $datos['aprobado'] ? 'admitido' : 'no_admitido';
            $query = "UPDATE inscripciones SET estado_inscripcion = ? 
                     WHERE id_postulante = ? AND id_carrera = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nuevo_estado, $datos['id_postulante'], $datos['id_carrera']]);
            
            // Actualizar estado del postulante
            $query = "UPDATE postulantes SET estado_postulacion = ? WHERE id_postulante = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nuevo_estado, $datos['id_postulante']]);
            
            // Registrar en bitácora
            $this->registrarBitacora('resultados', $resultado_id, 'crear', $_SESSION['usuario']['nombre'], [
                'postulante_id' => $datos['id_postulante'],
                'carrera_id' => $datos['id_carrera'],
                'puntaje' => $datos['puntaje'],
                'aprobado' => $datos['aprobado'] ? 'Sí' : 'No',
                'folio' => $folio_consulta
            ]);
            
            $this->conn->commit();
            return $resultado_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en crearResultado: " . $e->getMessage());
            return false;
        }
    }
    
    public function actualizarResultado($id, $datos) {
        try {
            $this->conn->beginTransaction();
            
            $query = "UPDATE resultados SET puntaje = ?, aprobado = ? WHERE id_resultado = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $datos['puntaje'],
                $datos['aprobado'],
                $id
            ]);
            
            // Obtener información del resultado
            $query = "SELECT * FROM resultados WHERE id_resultado = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Actualizar estado de la inscripción
            $nuevo_estado = $datos['aprobado'] ? 'admitido' : 'no_admitido';
            $query = "UPDATE inscripciones SET estado_inscripcion = ? 
                     WHERE id_postulante = ? AND id_carrera = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nuevo_estado, $resultado['id_postulante'], $resultado['id_carrera']]);
            
            // Actualizar estado del postulante
            $query = "UPDATE postulantes SET estado_postulacion = ? WHERE id_postulante = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nuevo_estado, $resultado['id_postulante']]);
            
            // Registrar en bitácora
            $this->registrarBitacora('resultados', $id, 'actualizar', $_SESSION['usuario']['nombre'], [
                'puntaje_anterior' => $resultado['puntaje'],
                'puntaje_nuevo' => $datos['puntaje'],
                'aprobado_anterior' => $resultado['aprobado'],
                'aprobado_nuevo' => $datos['aprobado']
            ]);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error en actualizarResultado: " . $e->getMessage());
            return false;
        }
    }
    
    public function eliminarResultado($id) {
        try {
            $this->conn->beginTransaction();
            
            // Obtener datos del resultado para la bitácora
            $query = "SELECT r.*, p.nombres, p.apellido_paterno, c.nombre as carrera_nombre 
                     FROM resultados r
                     JOIN postulantes p ON r.id_postulante = p.id_postulante
                     JOIN carreras c ON r.id_carrera = c.id_carrera
                     WHERE r.id_resultado = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Eliminar resultado
            $query = "DELETE FROM resultados WHERE id_resultado = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            // Revertir estado de la inscripción
            $query = "UPDATE inscripciones SET estado_inscripcion = 'presento_examen' 
                     WHERE id_postulante = ? AND id_carrera = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$resultado['id_postulante'], $resultado['id_carrera']]);
            
            // Revertir estado del postulante
            $query = "UPDATE postulantes SET estado_postulacion = 'documentos_aprobados' WHERE id_postulante = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$resultado['id_postulante']]);
            
            // Registrar en bitácora
            $this->registrarBitacora('resultados', $id, 'eliminar', $_SESSION['usuario']['nombre'], [
                'postulante' => $resultado['nombres'] . ' ' . $resultado['apellido_paterno'],
                'carrera' => $resultado['carrera_nombre'],
                'folio' => $resultado['folio_consulta'],
                'puntaje' => $resultado['puntaje']
            ]);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error en eliminarResultado: " . $e->getMessage());
            return false;
        }
    }
    
    private function registrarBitacora($entidad, $id_entidad, $accion, $usuario, $detalles) {
        try {
            $query = "INSERT INTO bitacora (entidad, id_entidad, accion, usuario, detalles) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$entidad, $id_entidad, $accion, $usuario, json_encode($detalles)]);
        } catch (PDOException $e) {
            error_log("Error al registrar en bitácora: " . $e->getMessage());
        }
    }
}

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'validar_documento':
                $documentoManager = new DocumentoManager($conn);
                $result = $documentoManager->validarDocumento(
                    $_POST['doc_id'],
                    $_POST['estado'],
                    $_POST['comentario'] ?? ''
                );
                $response = ['success' => $result, 'message' => $result ? 'Documento validado correctamente' : 'Error al validar documento'];
                break;
                
            case 'crear_postulante':
                $postulanteManager = new PostulanteManager($conn);
                $result = $postulanteManager->crearPostulante($_POST);
                if ($result) {
                    $response = ['success' => true, 'message' => 'Postulante creado correctamente', 'id' => $result];
                } else {
                    $response = ['success' => false, 'message' => 'Error al crear postulante'];
                }
                break;
                
            case 'actualizar_postulante':
                $postulanteManager = new PostulanteManager($conn);
                $result = $postulanteManager->actualizarPostulante($_POST['id'], $_POST);
                $response = ['success' => $result, 'message' => $result ? 'Postulante actualizado correctamente' : 'Error al actualizar postulante'];
                break;
                
            case 'eliminar_postulante':
                $postulanteManager = new PostulanteManager($conn);
                $result = $postulanteManager->eliminarPostulante($_POST['id']);
                $response = ['success' => $result, 'message' => $result ? 'Postulante eliminado correctamente' : 'Error al eliminar postulante'];
                break;
                
            case 'crear_inscripcion':
                $inscripcionManager = new InscripcionManager($conn);
                $result = $inscripcionManager->crearInscripcion($_POST);
                if ($result) {
                    $response = ['success' => true, 'message' => 'Inscripción creada correctamente', 'id' => $result];
                } else {
                    $response = ['success' => false, 'message' => 'Error al crear inscripción'];
                }
                break;
                
            case 'actualizar_inscripcion':
                $inscripcionManager = new InscripcionManager($conn);
                $result = $inscripcionManager->actualizarInscripcion($_POST['id'], $_POST);
                $response = ['success' => $result, 'message' => $result ? 'Inscripción actualizada correctamente' : 'Error al actualizar inscripción'];
                break;
                
            case 'eliminar_inscripcion':
                $inscripcionManager = new InscripcionManager($conn);
                $result = $inscripcionManager->eliminarInscripcion($_POST['id']);
                $response = ['success' => $result, 'message' => $result ? 'Inscripción eliminada correctamente' : 'Error al eliminar inscripción'];
                break;
                
            case 'crear_resultado':
                $resultadoManager = new ResultadoManager($conn);
                $result = $resultadoManager->crearResultado($_POST);
                if ($result) {
                    $response = ['success' => true, 'message' => 'Resultado creado correctamente', 'id' => $result];
                } else {
                    $response = ['success' => false, 'message' => 'Error al crear resultado'];
                }
                break;
                
            case 'actualizar_resultado':
                $resultadoManager = new ResultadoManager($conn);
                $result = $resultadoManager->actualizarResultado($_POST['id'], $_POST);
                $response = ['success' => $result, 'message' => $result ? 'Resultado actualizado correctamente' : 'Error al actualizar resultado'];
                break;
                
            case 'eliminar_resultado':
                $resultadoManager = new ResultadoManager($conn);
                $result = $resultadoManager->eliminarResultado($_POST['id']);
                $response = ['success' => $result, 'message' => $result ? 'Resultado eliminado correctamente' : 'Error al eliminar resultado'];
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Acción no válida'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Inicializar managers
$dashboardManager = new DashboardManager($conn);
$postulanteManager = new PostulanteManager($conn);
$documentoManager = new DocumentoManager($conn);
$inscripcionManager = new InscripcionManager($conn);
$resultadoManager = new ResultadoManager($conn);

// Obtener datos para el dashboard
$estadisticas = $dashboardManager->getEstadisticasDashboard();
$postulantesCarrera = $dashboardManager->getPostulantesPorCarrera();
$estadoDocumentos = $dashboardManager->getEstadoDocumentos();
$ultimasInscripciones = $dashboardManager->getUltimasInscripciones();

// Obtener datos con filtros
$busqueda = $_GET['buscar'] ?? null;
$filtro_estado_postulante = $_GET['estado_postulante'] ?? 'todos';
$postulantes = $postulanteManager->buscarPostulantes($busqueda, $filtro_estado_postulante);

$filtro_estado_documentos = $_GET['estado_documentos'] ?? 'todos';
$documentos = $documentoManager->getDocumentos($filtro_estado_documentos);

$filtro_estado_inscripciones = $_GET['estado_inscripciones'] ?? 'todas';
$inscripciones = $inscripcionManager->getInscripciones($filtro_estado_inscripciones);

$filtro_carrera_resultados = $_GET['carrera_resultados'] ?? 'todas';
$filtro_aprobado_resultados = $_GET['aprobado_resultados'] ?? 'todos';
$resultados = $resultadoManager->getResultados($filtro_carrera_resultados, $filtro_aprobado_resultados);

// Obtener datos para formularios
try {
    $query_carreras = "SELECT id_carrera, nombre FROM carreras WHERE estado = 'activa' ORDER BY nombre";
    $stmt_carreras = $conn->prepare($query_carreras);
    $stmt_carreras->execute();
    $carreras = $stmt_carreras->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $carreras = [];
    error_log("Error al obtener carreras: " . $e->getMessage());
}

try {
    $query_periodos = "SELECT id_periodo, nombre_periodo FROM periodos_academicos WHERE estado = 'activo' ORDER BY fecha_inicio_inscripciones DESC";
    $stmt_periodos = $conn->prepare($query_periodos);
    $stmt_periodos->execute();
    $periodos = $stmt_periodos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $periodos = [];
    error_log("Error al obtener periodos: " . $e->getMessage());
}

try {
    $query_documentos_req = "SELECT id_documento_req, nombre_documento FROM documentos_requeridos WHERE estado = 'activo' ORDER BY nombre_documento";
    $stmt_documentos_req = $conn->prepare($query_documentos_req);
    $stmt_documentos_req->execute();
    $documentos_requeridos = $stmt_documentos_req->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $documentos_requeridos = [];
    error_log("Error al obtener documentos requeridos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Personal de Admisión</title>
    <link rel="stylesheet" href="css/personal_de_admision.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="contenedor-principal">
        <nav class="barra-navegacion">
            <div class="logo">
                <h1><i class="fas fa-university"></i> Sistema de Admisiones</h1>
            </div>
            <div class="info-usuario">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Usuario'); ?></span>
                <button id="btn-cerrar-sesion"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</button>
            </div>
        </nav>
        
        <aside class="menu-lateral">
            <ul class="lista-menu">
                <li><a href="#" class="enlace-menu activo" data-seccion="dashboard"><i class="fas fa-tachometer-alt"></i> Panel de Inicio</a></li>
                <li><a href="#" class="enlace-menu" data-seccion="postulantes"><i class="fas fa-users"></i> Gestión de Postulantes</a></li>
                <li><a href="#" class="enlace-menu" data-seccion="documentos"><i class="fas fa-file-alt"></i> Validación de Documentos</a></li>
                <li><a href="#" class="enlace-menu" data-seccion="inscripciones"><i class="fas fa-clipboard-list"></i> Inscripciones</a></li>
                <li><a href="#" class="enlace-menu" data-seccion="resultados"><i class="fas fa-chart-bar"></i> Resultados</a></li>
            </ul>
        </aside>
        
        <main class="contenido-principal">
            <!-- Sección Dashboard -->
            <section id="dashboard" class="seccion-contenido activo">
                <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                
                <div class="tarjetas-resumen">
                    <div class="tarjeta tarjeta-primary">
                        <div class="tarjeta-icono">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="tarjeta-contenido">
                            <h3>Total Postulantes</h3>
                            <p id="total-postulantes"><?php echo $estadisticas['total_postulantes'] ?? 0; ?></p>
                        </div>
                    </div>
                    
                    <div class="tarjeta tarjeta-warning">
                        <div class="tarjeta-icono">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="tarjeta-contenido">
                            <h3>Documentos Pendientes</h3>
                            <p id="documentos-pendientes"><?php echo $estadisticas['documentos_pendientes'] ?? 0; ?></p>
                        </div>
                    </div>
                    
                    <div class="tarjeta tarjeta-success">
                        <div class="tarjeta-icono">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="tarjeta-contenido">
                            <h3>Inscripciones Activas</h3>
                            <p id="inscripciones-activas"><?php echo $estadisticas['inscripciones_activas'] ?? 0; ?></p>
                        </div>
                    </div>
                    
                    <div class="tarjeta tarjeta-info">
                        <div class="tarjeta-icono">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="tarjeta-contenido">
                            <h3>Carreras Activas</h3>
                            <p id="total-carreras"><?php echo $estadisticas['total_carreras'] ?? 0; ?></p>
                        </div>
                    </div>
                    
                    <div class="tarjeta tarjeta-danger">
                        <div class="tarjeta-icono">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="tarjeta-contenido">
                            <h3>Postulantes Aprobados</h3>
                            <p id="postulantes-aprobados"><?php echo $estadisticas['postulantes_aprobados'] ?? 0; ?></p>
                        </div>
                    </div>
                    
                    <div class="tarjeta tarjeta-secondary">
                        <div class="tarjeta-icono">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="tarjeta-contenido">
                            <h3>Ingresos Totales</h3>
                            <p id="ingresos-totales">Bs. <?php echo number_format($estadisticas['ingresos_totales'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="graficos-dashboard">
                    <div class="grafico">
                        <h3><i class="fas fa-chart-bar"></i> Postulantes por Carrera</h3>
                        <canvas id="grafico-carreras"></canvas>
                    </div>
                    <div class="grafico">
                        <h3><i class="fas fa-chart-pie"></i> Estado de Documentos</h3>
                        <canvas id="grafico-documentos"></canvas>
                    </div>
                </div>

                <div class="ultimas-inscripciones">
                    <h3><i class="fas fa-history"></i> Últimas Inscripciones</h3>
                    <div class="tabla-contenedor">
                        <table>
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Postulante</th>
                                    <th>Carrera</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ultimasInscripciones)): ?>
                                    <?php foreach ($ultimasInscripciones as $inscripcion): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($inscripcion['numero_folio']); ?></td>
                                        <td><?php echo htmlspecialchars($inscripcion['nombres'] . ' ' . $inscripcion['apellido_paterno'] . ' ' . $inscripcion['apellido_materno']); ?></td>
                                        <td><?php echo htmlspecialchars($inscripcion['carrera_nombre']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($inscripcion['fecha_inscripcion'])); ?></td>
                                        <td><span class="estado-<?php echo $inscripcion['estado_inscripcion']; ?>"><?php echo ucfirst($inscripcion['estado_inscripcion']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">No hay inscripciones recientes</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Sección Postulantes -->
            <section id="postulantes" class="seccion-contenido">
                <h2><i class="fas fa-users"></i> Gestión de Postulantes</h2>
                
                <div class="controles-busqueda">
                    <form method="GET" class="form-busqueda">
                        <input type="hidden" name="seccion" value="postulantes">
                        <div class="grupo-campos">
                            <input type="text" name="buscar" id="buscar-postulante" placeholder="Buscar por nombre, CI o folio..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                            <select name="estado_postulante" id="filtro-estado-postulante">
                                <option value="todos" <?php echo $filtro_estado_postulante == 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                                <option value="pendiente" <?php echo $filtro_estado_postulante == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="documentos_aprobados" <?php echo $filtro_estado_postulante == 'documentos_aprobados' ? 'selected' : ''; ?>>Documentos Aprobados</option>
                                <option value="documentos_rechazados" <?php echo $filtro_estado_postulante == 'documentos_rechazados' ? 'selected' : ''; ?>>Documentos Rechazados</option>
                                <option value="admitido" <?php echo $filtro_estado_postulante == 'admitido' ? 'selected' : ''; ?>>Admitido</option>
                                <option value="no_admitido" <?php echo $filtro_estado_postulante == 'no_admitido' ? 'selected' : ''; ?>>No Admitido</option>
                            </select>
                            <button type="submit" id="btn-buscar-postulante"><i class="fas fa-search"></i> Buscar</button>
                        </div>
                        <div class="grupo-botones">
                            <button type="button" id="btn-nuevo-postulante" class="btn-primary"><i class="fas fa-plus"></i> Nuevo Postulante</button>
                            <button type="button" id="btn-exportar-postulantes" class="btn-secondary"><i class="fas fa-file-export"></i> Exportar</button>
                        </div>
                    </form>
                </div>

                <div class="tabla-contenedor">
                    <table id="tabla-postulantes">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombres Completos</th>
                                <th>CI</th>
                                <th>Carrera</th>
                                <th>Folio</th>
                                <th>Estado Postulación</th>
                                <th>Doc. Pendientes</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla-postulantes">
                            <?php if (!empty($postulantes)): ?>
                                <?php foreach ($postulantes as $postulante): ?>
                                <tr>
                                    <td><?php echo $postulante['id_postulante']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($postulante['nombres'] . ' ' . $postulante['apellido_paterno'] . ' ' . $postulante['apellido_materno']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($postulante['telefono']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($postulante['ci']); ?></td>
                                    <td><?php echo htmlspecialchars($postulante['carrera_nombre'] ?? 'No asignada'); ?></td>
                                    <td><?php echo htmlspecialchars($postulante['numero_folio'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="estado-<?php echo $postulante['estado_postulacion']; ?>">
                                            <?php 
                                            $estados = [
                                                'pendiente' => 'Pendiente',
                                                'documentos_aprobados' => 'Docs. Aprobados',
                                                'documentos_rechazados' => 'Docs. Rechazados',
                                                'admitido' => 'Admitido',
                                                'no_admitido' => 'No Admitido'
                                            ];
                                            echo $estados[$postulante['estado_postulacion']] ?? $postulante['estado_postulacion'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($postulante['documentos_pendientes'] > 0): ?>
                                            <span class="badge badge-warning"><?php echo $postulante['documentos_pendientes']; ?> pendientes</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Completos</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($postulante['creado_en'])); ?></td>
                                    <td>
                                        <div class="acciones-grupo">
                                            <button class="btn-ver" data-id="<?php echo $postulante['id_postulante']; ?>" title="Ver detalles"><i class="fas fa-eye"></i></button>
                                            <button class="btn-editar" data-id="<?php echo $postulante['id_postulante']; ?>" title="Editar"><i class="fas fa-edit"></i></button>
                                            <button class="btn-eliminar" data-id="<?php echo $postulante['id_postulante']; ?>" title="Eliminar"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center;">No se encontraron postulantes</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Sección Documentos -->
            <section id="documentos" class="seccion-contenido">
                <h2><i class="fas fa-file-alt"></i> Validación de Documentos</h2>
                
                <div class="filtros">
                    <form method="GET" class="form-filtros">
                        <input type="hidden" name="seccion" value="documentos">
                        <div class="grupo-campos">
                            <select name="estado_documentos" id="filtro-estado-documentos">
                                <option value="todos" <?php echo $filtro_estado_documentos == 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                                <option value="pendiente" <?php echo $filtro_estado_documentos == 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                                <option value="aprobado" <?php echo $filtro_estado_documentos == 'aprobado' ? 'selected' : ''; ?>>Aprobados</option>
                                <option value="rechazado" <?php echo $filtro_estado_documentos == 'rechazado' ? 'selected' : ''; ?>>Rechazados</option>
                            </select>
                            <button type="submit" id="btn-aplicar-filtro" class="btn-primary"><i class="fas fa-filter"></i> Aplicar Filtro</button>
                        </div>
                    </form>
                </div>

                <div class="tabla-contenedor">
                    <table id="tabla-documentos">
                        <thead>
                            <tr>
                                <th>Postulante</th>
                                <th>CI</th>
                                <th>Documento</th>
                                <th>Fecha Carga</th>
                                <th>Estado</th>
                                <th>Validador</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla-documentos">
                            <?php if (!empty($documentos)): ?>
                                <?php foreach ($documentos as $documento): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($documento['nombres'] . ' ' . $documento['apellido_paterno'] . ' ' . $documento['apellido_materno']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($documento['ci']); ?></td>
                                    <td><?php echo htmlspecialchars($documento['nombre_documento'] ?? $documento['tipo_documento'] ?? 'Documento'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($documento['fecha_carga'])); ?></td>
                                    <td>
                                        <span class="estado-<?php echo $documento['estado_validacion']; ?>">
                                            <?php echo ucfirst($documento['estado_validacion']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($documento['validador_nombre'] ?? 'No validado'); ?></td>
                                    <td>
                                        <div class="acciones-grupo">
                                            <?php if ($documento['estado_validacion'] == 'pendiente'): ?>
                                                <button class="btn-validar btn-success" data-id="<?php echo $documento['id_doc']; ?>" data-estado="aprobado" title="Aprobar documento"><i class="fas fa-check"></i></button>
                                                <button class="btn-validar btn-danger" data-id="<?php echo $documento['id_doc']; ?>" data-estado="rechazado" title="Rechazar documento"><i class="fas fa-times"></i></button>
                                            <?php endif; ?>
                                            <?php if (!empty($documento['archivo_url'])): ?>
                                                <button class="btn-ver-doc btn-info" data-url="<?php echo htmlspecialchars($documento['archivo_url']); ?>" title="Ver documento"><i class="fas fa-eye"></i></button>
                                            <?php endif; ?>
                                            <?php if (!empty($documento['comentario'])): ?>
                                                <button class="btn-ver-comentario" data-comentario="<?php echo htmlspecialchars($documento['comentario']); ?>" title="Ver comentario"><i class="fas fa-comment"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No se encontraron documentos</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Sección Inscripciones -->
            <section id="inscripciones" class="seccion-contenido">
                <h2><i class="fas fa-clipboard-list"></i> Gestión de Inscripciones</h2>
                
                <div class="controles-inscripciones">
                    <div class="header-controles">
                        <button id="btn-nueva-inscripcion" class="btn-primary"><i class="fas fa-plus"></i> Nueva Inscripción</button>
                        <button id="btn-exportar-inscripciones" class="btn-secondary"><i class="fas fa-file-export"></i> Exportar a Excel</button>
                    </div>
                    
                    <form method="GET" class="form-filtros">
                        <input type="hidden" name="seccion" value="inscripciones">
                        <div class="grupo-campos">
                            <select name="estado_inscripciones" id="filtro-estado-inscripciones">
                                <option value="todas" <?php echo $filtro_estado_inscripciones == 'todas' ? 'selected' : ''; ?>>Todos los estados</option>
                                <option value="inscrito" <?php echo $filtro_estado_inscripciones == 'inscrito' ? 'selected' : ''; ?>>Inscrito</option>
                                <option value="confirmada" <?php echo $filtro_estado_inscripciones == 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                <option value="presento_examen" <?php echo $filtro_estado_inscripciones == 'presento_examen' ? 'selected' : ''; ?>>Presentó Examen</option>
                                <option value="admitido" <?php echo $filtro_estado_inscripciones == 'admitido' ? 'selected' : ''; ?>>Admitido</option>
                                <option value="no_admitido" <?php echo $filtro_estado_inscripciones == 'no_admitido' ? 'selected' : ''; ?>>No Admitido</option>
                                <option value="rechazada" <?php echo $filtro_estado_inscripciones == 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                            </select>
                            <button type="submit" class="btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                        </div>
                    </form>
                </div>

                <div class="tabla-contenedor">
                    <table id="tabla-inscripciones">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Postulante</th>
                                <th>CI</th>
                                <th>Carrera</th>
                                <th>Periodo</th>
                                <th>Opción</th>
                                <th>Estado</th>
                                <th>Doc. Pendientes</th>
                                <th>Fecha Inscripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla-inscripciones">
                            <?php if (!empty($inscripciones)): ?>
                                <?php foreach ($inscripciones as $inscripcion): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($inscripcion['numero_folio']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($inscripcion['nombres'] . ' ' . $inscripcion['apellido_paterno'] . ' ' . $inscripcion['apellido_materno']); ?></td>
                                    <td><?php echo htmlspecialchars($inscripcion['ci']); ?></td>
                                    <td><?php echo htmlspecialchars($inscripcion['carrera_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($inscripcion['nombre_periodo']); ?></td>
                                    <td><?php echo ucfirst($inscripcion['opcion_carrera']); ?></td>
                                    <td>
                                        <span class="estado-<?php echo $inscripcion['estado_inscripcion']; ?>">
                                            <?php echo ucfirst($inscripcion['estado_inscripcion']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($inscripcion['documentos_pendientes'] > 0): ?>
                                            <span class="badge badge-warning"><?php echo $inscripcion['documentos_pendientes']; ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-success">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($inscripcion['fecha_inscripcion'])); ?></td>
                                    <td>
                                        <div class="acciones-grupo">
                                            <button class="btn-editar-insc btn-warning" data-id="<?php echo $inscripcion['id_inscripcion']; ?>" title="Editar inscripción"><i class="fas fa-edit"></i></button>
                                            <button class="btn-resultado btn-info" data-id="<?php echo $inscripcion['id_inscripcion']; ?>" title="Registrar resultado"><i class="fas fa-chart-bar"></i></button>
                                            <button class="btn-eliminar-insc btn-danger" data-id="<?php echo $inscripcion['id_inscripcion']; ?>" title="Eliminar inscripción"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center;">No se encontraron inscripciones</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Sección Resultados -->
            <section id="resultados" class="seccion-contenido">
                <h2><i class="fas fa-chart-bar"></i> Resultados de Exámenes</h2>
                
                <div class="controles-resultados">
                    <form method="GET" class="form-filtros">
                        <input type="hidden" name="seccion" value="resultados">
                        <div class="grupo-campos">
                            <select name="carrera_resultados" id="filtro-carrera-resultados">
                                <option value="todas">Todas las carreras</option>
                                <?php foreach ($carreras as $carrera): ?>
                                <option value="<?php echo $carrera['id_carrera']; ?>" <?php echo $filtro_carrera_resultados == $carrera['id_carrera'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($carrera['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="aprobado_resultados" id="filtro-aprobado-resultados">
                                <option value="todos" <?php echo $filtro_aprobado_resultados == 'todos' ? 'selected' : ''; ?>>Todos los resultados</option>
                                <option value="aprobados" <?php echo $filtro_aprobado_resultados == 'aprobados' ? 'selected' : ''; ?>>Solo aprobados</option>
                                <option value="no_aprobados" <?php echo $filtro_aprobado_resultados == 'no_aprobados' ? 'selected' : ''; ?>>Solo no aprobados</option>
                            </select>
                            
                            <button type="submit" id="btn-cargar-resultados" class="btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                        </div>
                    </form>
                    
                    <div class="grupo-botones">
                        <button type="button" id="btn-nuevo-resultado" class="btn-primary"><i class="fas fa-plus"></i> Nuevo Resultado</button>
                        <button type="button" id="btn-publicar-resultados" class="btn-success"><i class="fas fa-bullhorn"></i> Publicar Resultados</button>
                        <button type="button" id="btn-exportar-resultados" class="btn-secondary"><i class="fas fa-file-export"></i> Exportar</button>
                    </div>
                </div>

                <div class="tabla-contenedor">
                    <table id="tabla-resultados">
                        <thead>
                            <tr>
                                <th>Folio Consulta</th>
                                <th>Postulante</th>
                                <th>CI</th>
                                <th>Carrera</th>
                                <th>Puntaje</th>
                                <th>Estado</th>
                                <th>Fecha Resultado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla-resultados">
                            <?php if (!empty($resultados)): ?>
                                <?php foreach ($resultados as $resultado): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($resultado['folio_consulta']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($resultado['nombres'] . ' ' . $resultado['apellido_paterno'] . ' ' . $resultado['apellido_materno']); ?></td>
                                    <td><?php echo htmlspecialchars($resultado['ci']); ?></td>
                                    <td><?php echo htmlspecialchars($resultado['carrera_nombre']); ?></td>
                                    <td>
                                        <span class="puntaje <?php echo $resultado['puntaje'] >= 60 ? 'puntaje-alto' : 'puntaje-bajo'; ?>">
                                            <?php echo $resultado['puntaje'] ?? 'N/A'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($resultado['aprobado']): ?>
                                            <span class="estado-aprobado"><i class="fas fa-check-circle"></i> Aprobado</span>
                                        <?php else: ?>
                                            <span class="estado-no_aprobado"><i class="fas fa-times-circle"></i> No Aprobado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($resultado['fecha_resultado'])); ?></td>
                                    <td>
                                        <div class="acciones-grupo">
                                            <button class="btn-ver-resultado btn-info" data-id="<?php echo $resultado['id_resultado']; ?>" title="Ver detalles"><i class="fas fa-eye"></i></button>
                                            <button class="btn-editar-resultado btn-warning" data-id="<?php echo $resultado['id_resultado']; ?>" title="Editar resultado"><i class="fas fa-edit"></i></button>
                                            <button class="btn-eliminar-resultado btn-danger" data-id="<?php echo $resultado['id_resultado']; ?>" title="Eliminar resultado"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No se encontraron resultados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal Global -->
    <div id="modal-global" class="modal">
        <div class="modal-contenido">
            <span class="cerrar-modal">&times;</span>
            <div id="modal-contenido"></div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading" class="loading" style="display: none;">
        <div class="spinner"></div>
        <p>Cargando...</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="personal_de_admision.js"></script>
    <script>
        // Datos para gráficos
        const postulantesCarrera = <?php echo json_encode($postulantesCarrera); ?>;
        const estadoDocumentos = <?php echo json_encode($estadoDocumentos); ?>;
        const carreras = <?php echo json_encode($carreras); ?>;
        const periodos = <?php echo json_encode($periodos); ?>;

        // Inicializar gráficos cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            inicializarGraficos();
            inicializarEventos();
        });

        function inicializarGraficos() {
            // Gráfico de postulantes por carrera
            if (postulantesCarrera && postulantesCarrera.length > 0) {
                const ctxCarreras = document.getElementById('grafico-carreras').getContext('2d');
                new Chart(ctxCarreras, {
                    type: 'bar',
                    data: {
                        labels: postulantesCarrera.map(item => item.carrera),
                        datasets: [{
                            label: 'Postulantes',
                            data: postulantesCarrera.map(item => item.cantidad),
                            backgroundColor: 'rgba(54, 162, 235, 0.8)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            } else {
                document.getElementById('grafico-carreras').innerHTML = '<p class="sin-datos">No hay datos disponibles</p>';
            }

            // Gráfico de estado de documentos
            if (estadoDocumentos && estadoDocumentos.length > 0) {
                const ctxDocumentos = document.getElementById('grafico-documentos').getContext('2d');
                new Chart(ctxDocumentos, {
                    type: 'pie',
                    data: {
                        labels: estadoDocumentos.map(item => {
                            const estados = {
                                'pendiente': 'Pendientes',
                                'aprobado': 'Aprobados',
                                'rechazado': 'Rechazados'
                            };
                            return estados[item.estado_validacion] || item.estado_validacion;
                        }),
                        datasets: [{
                            data: estadoDocumentos.map(item => item.cantidad),
                            backgroundColor: [
                                'rgba(255, 205, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(255, 99, 132, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 205, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(255, 99, 132, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            } else {
                document.getElementById('grafico-documentos').innerHTML = '<p class="sin-datos">No hay datos disponibles</p>';
            }
        }

        function inicializarEventos() {
            // Navegación entre secciones
            document.querySelectorAll('.enlace-menu').forEach(enlace => {
                enlace.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remover clase activa de todos los enlaces y secciones
                    document.querySelectorAll('.enlace-menu').forEach(item => item.classList.remove('activo'));
                    document.querySelectorAll('.seccion-contenido').forEach(seccion => seccion.classList.remove('activo'));
                    
                    // Agregar clase activa al enlace clickeado
                    this.classList.add('activo');
                    
                    // Mostrar la sección correspondiente
                    const seccionId = this.getAttribute('data-seccion');
                    const seccionActiva = document.getElementById(seccionId);
                    if (seccionActiva) {
                        seccionActiva.classList.add('activo');
                    }
                });
            });

            // Cerrar sesión
            document.getElementById('btn-cerrar-sesion').addEventListener('click', function() {
                if (confirm('¿Está seguro de que desea cerrar sesión?')) {
                    window.location.href = 'login.html';
                }
            });
        }
    </script>
</body>
</html>