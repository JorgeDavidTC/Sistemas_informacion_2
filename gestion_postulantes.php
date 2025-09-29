<?php
session_start();
require_once 'conexion.php';

class PostulanteManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function buscarPostulantes($busqueda = null, $filtro_estado = 'todos') {
        try {
            $query = "SELECT p.*, i.numero_folio, c.nombre as carrera_nombre, i.estado_inscripcion,
                             COUNT(dp.id_doc) as total_documentos,
                             SUM(CASE WHEN dp.estado_validacion = 'pendiente' THEN 1 ELSE 0 END) as documentos_pendientes
                     FROM postulantes p 
                     LEFT JOIN inscripciones i ON p.id_postulante = i.id_postulante 
                     LEFT JOIN carreras c ON i.id_carrera = c.id_carrera 
                     LEFT JOIN documentos_postulantes dp ON p.id_postulante = dp.postulante_id";
            
            $conditions = [];
            $params = [];
            
            if ($busqueda) {
                $conditions[] = "(p.nombres LIKE ? OR p.apellido_paterno LIKE ? OR p.apellido_materno LIKE ? OR p.ci LIKE ? OR i.numero_folio LIKE ?)";
                $param = "%$busqueda%";
                $params = array_merge($params, [$param, $param, $param, $param, $param]);
            }
            
            if ($filtro_estado != 'todos') {
                $conditions[] = "p.estado_postulacion = ?";
                $params[] = $filtro_estado;
            }
            
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $query .= " GROUP BY p.id_postulante ORDER BY p.creado_en DESC LIMIT 100";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en buscarPostulantes: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerPostulante($id) {
        try {
            $query = "SELECT p.*, i.id_inscripcion, i.numero_folio, i.id_carrera, i.estado_inscripcion,
                             c.nombre as carrera_nombre, pa.nombre_periodo
                     FROM postulantes p 
                     LEFT JOIN inscripciones i ON p.id_postulante = i.id_postulante 
                     LEFT JOIN carreras c ON i.id_carrera = c.id_carrera
                     LEFT JOIN periodos_academicos pa ON i.periodo_id = pa.id_periodo
                     WHERE p.id_postulante = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception("Postulante no encontrado");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error en obtenerPostulante: " . $e->getMessage());
            throw new Exception("Error al cargar el postulante");
        }
    }
    
    public function crearPostulante($datos) {
        $this->conn->beginTransaction();
        
        try {
            // Validar datos requeridos
            if (empty($datos['nombres']) || empty($datos['apellido_paterno']) || empty($datos['ci'])) {
                throw new Exception("Los campos nombres, apellido paterno y CI son obligatorios");
            }
            
            // Insertar postulante
            $query = "INSERT INTO postulantes (nombres, apellido_paterno, apellido_materno, ci, fecha_nacimiento, telefono, direccion_residencia, nacionalidad, estado_postulacion) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $datos['nombres'],
                $datos['apellido_paterno'],
                $datos['apellido_materno'] ?? '',
                $datos['ci'],
                $datos['fecha_nacimiento'] ?? null,
                $datos['telefono'] ?? '',
                $datos['direccion'] ?? '',
                $datos['nacionalidad'] ?? 'Boliviana',
                $datos['estado_postulacion'] ?? 'pendiente'
            ]);
            
            $id_postulante = $this->conn->lastInsertId();
            
            // Si se proporcionó carrera, crear inscripción
            if (!empty($datos['id_carrera']) && !empty($datos['periodo_id'])) {
                $folio = 'FOL-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $query_insc = "INSERT INTO inscripciones (id_postulante, id_carrera, periodo_id, numero_folio, estado_inscripcion) 
                              VALUES (?, ?, ?, ?, 'inscrito')";
                $stmt_insc = $this->conn->prepare($query_insc);
                $stmt_insc->execute([
                    $id_postulante,
                    $datos['id_carrera'],
                    $datos['periodo_id'],
                    $folio
                ]);
            }
            
            $this->conn->commit();
            return $id_postulante;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en crearPostulante: " . $e->getMessage());
            throw new Exception("Error al crear el postulante: " . $e->getMessage());
        }
    }
    
    public function actualizarPostulante($id, $datos) {
        try {
            $query = "UPDATE postulantes SET nombres = ?, apellido_paterno = ?, apellido_materno = ?, ci = ?, 
                             fecha_nacimiento = ?, telefono = ?, direccion_residencia = ?, nacionalidad = ?,
                             estado_postulacion = ?
                     WHERE id_postulante = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $datos['nombres'],
                $datos['apellido_paterno'],
                $datos['apellido_materno'] ?? '',
                $datos['ci'],
                $datos['fecha_nacimiento'] ?? null,
                $datos['telefono'] ?? '',
                $datos['direccion'] ?? '',
                $datos['nacionalidad'] ?? 'Boliviana',
                $datos['estado_postulacion'] ?? 'pendiente',
                $id
            ]);
            
            if (!$result) {
                throw new Exception("Error al actualizar el postulante");
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error en actualizarPostulante: " . $e->getMessage());
            throw new Exception("Error al actualizar el postulante");
        }
    }
    
    public function eliminarPostulante($id) {
        $this->conn->beginTransaction();
        
        try {
            // Verificar si existe el postulante
            $query_check = "SELECT COUNT(*) FROM postulantes WHERE id_postulante = ?";
            $stmt_check = $this->conn->prepare($query_check);
            $stmt_check->execute([$id]);
            
            if ($stmt_check->fetchColumn() == 0) {
                throw new Exception("El postulante no existe");
            }
            
            // Eliminar documentos primero
            $query_docs = "DELETE FROM documentos_postulantes WHERE postulante_id = ?";
            $stmt_docs = $this->conn->prepare($query_docs);
            $stmt_docs->execute([$id]);
            
            // Eliminar inscripciones
            $query_insc = "DELETE FROM inscripciones WHERE id_postulante = ?";
            $stmt_insc = $this->conn->prepare($query_insc);
            $stmt_insc->execute([$id]);
            
            // Eliminar postulante
            $query_post = "DELETE FROM postulantes WHERE id_postulante = ?";
            $stmt_post = $this->conn->prepare($query_post);
            $stmt_post->execute([$id]);
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en eliminarPostulante: " . $e->getMessage());
            throw new Exception("Error al eliminar el postulante: " . $e->getMessage());
        }
    }
    
    public function getEstadisticasPostulantes() {
        try {
            $estadisticas = [];
            
            // Total postulantes
            $query_total = "SELECT COUNT(*) as total FROM postulantes";
            $stmt_total = $this->conn->prepare($query_total);
            $stmt_total->execute();
            $estadisticas['total'] = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Postulantes por estado
            $query_estados = "SELECT estado_postulacion, COUNT(*) as cantidad 
                             FROM postulantes 
                             GROUP BY estado_postulacion";
            $stmt_estados = $this->conn->prepare($query_estados);
            $stmt_estados->execute();
            $estadisticas['por_estado'] = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);
            
            return $estadisticas;
        } catch (PDOException $e) {
            error_log("Error en getEstadisticasPostulantes: " . $e->getMessage());
            return ['total' => 0, 'por_estado' => []];
        }
    }
}

class DocumentoManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getDocumentosPostulante($postulante_id) {
        try {
            // Primero obtener información del postulante
            $query_postulante = "SELECT p.*, i.numero_folio, c.nombre as carrera_nombre 
                               FROM postulantes p 
                               LEFT JOIN inscripciones i ON p.id_postulante = i.id_postulante 
                               LEFT JOIN carreras c ON i.id_carrera = c.id_carrera 
                               WHERE p.id_postulante = ?";
            $stmt_postulante = $this->conn->prepare($query_postulante);
            $stmt_postulante->execute([$postulante_id]);
            $postulante = $stmt_postulante->fetch(PDO::FETCH_ASSOC);
            
            if (!$postulante) {
                throw new Exception("Postulante no encontrado");
            }
            
            // Obtener documentos - CORREGIDO según tu estructura de BD
            $query = "SELECT dp.*, dr.nombre_documento, dr.obligatorio,
                             u.nombre as validador_nombre,
                             DATE(dp.fecha_carga) as fecha_carga_formateada,
                             DATE(dp.fecha_validacion) as fecha_validacion_formateada
                     FROM documentos_postulantes dp
                     LEFT JOIN documentos_requeridos dr ON dp.documento_req_id = dr.id_documento_req
                     LEFT JOIN usuarios u ON dp.personal_validador_id = u.id_usuario
                     WHERE dp.postulante_id = ?
                     ORDER BY dr.obligatorio DESC, dr.nombre_documento";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$postulante_id]);
            $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'postulante' => $postulante,
                'documentos' => $documentos
            ];
        } catch (PDOException $e) {
            error_log("Error en getDocumentosPostulante: " . $e->getMessage());
            throw new Exception("Error al cargar los documentos: " . $e->getMessage());
        }
    }
    
    public function validarDocumento($id_doc, $estado, $comentario = '') {
        try {
            $query = "UPDATE documentos_postulantes SET estado_validacion = ?, personal_validador_id = ?, 
                             fecha_validacion = NOW(), comentario = ? 
                     WHERE id_doc = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $estado, 
                $_SESSION['usuario']['id_usuario'] ?? null, 
                $comentario, 
                $id_doc
            ]);
            
            if (!$result) {
                throw new Exception("Error al validar el documento");
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error en validarDocumento: " . $e->getMessage());
            throw new Exception("Error al validar el documento: " . $e->getMessage());
        }
    }
}
// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    $postulanteManager = new PostulanteManager($conn);
    $documentoManager = new DocumentoManager($conn);
    
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'crear_postulante':
                $id_postulante = $postulanteManager->crearPostulante($_POST);
                $response['success'] = true;
                $response['message'] = 'Postulante creado exitosamente';
                $response['id_postulante'] = $id_postulante;
                break;
                
            case 'actualizar_postulante':
                $result = $postulanteManager->actualizarPostulante($_POST['id_postulante'], $_POST);
                $response['success'] = true;
                $response['message'] = 'Postulante actualizado exitosamente';
                break;
                
            case 'eliminar_postulante':
                $result = $postulanteManager->eliminarPostulante($_POST['id_postulante']);
                $response['success'] = true;
                $response['message'] = 'Postulante eliminado exitosamente';
                break;
                
            case 'validar_documento':
                $result = $documentoManager->validarDocumento(
                    $_POST['doc_id'],
                    $_POST['estado'],
                    $_POST['comentario'] ?? ''
                );
                $response['success'] = true;
                $response['message'] = 'Documento validado exitosamente';
                break;
                
            default:
                $response['message'] = 'Acción no válida';
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Obtener datos para la vista
$database = new Database();
$conn = $database->getConnection();
$postulanteManager = new PostulanteManager($conn);

// Parámetros de búsqueda y filtros
$busqueda = $_GET['buscar'] ?? '';
$filtro_estado = $_GET['filtro_estado'] ?? 'todos';

// Obtener postulantes
$postulantes = $postulanteManager->buscarPostulantes($busqueda, $filtro_estado);
$estadisticas = $postulanteManager->getEstadisticasPostulantes();

// Obtener datos para formularios
try {
    $query_carreras = "SELECT id_carrera, nombre FROM carreras WHERE estado = 'activa' ORDER BY nombre";
    $stmt_carreras = $conn->prepare($query_carreras);
    $stmt_carreras->execute();
    $carreras = $stmt_carreras->fetchAll(PDO::FETCH_ASSOC);

    $query_periodos = "SELECT id_periodo, nombre_periodo FROM periodos_academicos WHERE estado = 'activo' ORDER BY fecha_inicio_inscripciones DESC";
    $stmt_periodos = $conn->prepare($query_periodos);
    $stmt_periodos->execute();
    $periodos = $stmt_periodos->fetchAll(PDO::FETCH_ASSOC);

    $query_documentos_req = "SELECT id_documento_req, nombre_documento, obligatorio 
                             FROM documentos_requeridos 
                             WHERE estado = 'activo' 
                             ORDER BY obligatorio DESC, nombre_documento";
    $stmt_docs_req = $conn->prepare($query_documentos_req);
    $stmt_docs_req->execute();
    $documentos_requeridos = $stmt_docs_req->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar datos del formulario: " . $e->getMessage());
    $carreras = [];
    $periodos = [];
    $documentos_requeridos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Postulantes - Sistema de Admisiones</title>
    <style>
        /* Estilos generales */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .contenedor-principal {
            display: flex;
            min-height: 100vh;
        }

        /* Barra de navegación */
        .barra-navegacion {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .info-usuario {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        #btn-cerrar-sesion {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #btn-cerrar-sesion:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Menú lateral */
        .menu-lateral {
            background: white;
            width: 250px;
            position: fixed;
            top: 70px;
            left: 0;
            bottom: 0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 999;
        }

        .lista-menu {
            list-style: none;
            padding: 1rem 0;
        }

        .enlace-menu {
            display: block;
            padding: 0.8rem 1.5rem;
            color: #555;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .enlace-menu:hover {
            background: #f8f9fa;
            color: #667eea;
        }

        .enlace-menu.activo {
            background: #f0f4ff;
            color: #667eea;
            border-left-color: #667eea;
        }

        /* Contenido principal */
        .contenido-principal {
            flex: 1;
            margin-left: 250px;
            margin-top: 70px;
            padding: 2rem;
            min-height: calc(100vh - 70px);
        }

        .cabecera-seccion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .cabecera-seccion h1 {
            color: #333;
            font-size: 2rem;
            font-weight: 600;
        }

        .controles-superiores {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Botones */
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-buscar {
            background: #667eea;
            color: white;
            padding: 0.6rem 1rem;
        }

        /* Estadísticas */
        .estadisticas-rapidas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tarjeta-estadistica {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease;
        }

        .tarjeta-estadistica:hover {
            transform: translateY(-5px);
        }

        .icono-estadistica {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .icono-estadistica.estado-pendiente { background: #fff3cd; color: #856404; }
        .icono-estadistica.estado-documentos_aprobados { background: #d1ecf1; color: #0c5460; }
        .icono-estadistica.estado-documentos_rechazados { background: #f8d7da; color: #721c24; }
        .icono-estadistica.estado-admitido { background: #d4edda; color: #155724; }
        .icono-estadistica.estado-no_admitido { background: #f8d7da; color: #721c24; }

        .info-estadistica h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .numero {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }

        /* Panel de filtros */
        .panel-filtros {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-filtros {
            width: 100%;
        }

        .grupo-filtros {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .campo-busqueda {
            flex: 1;
            min-width: 250px;
            display: flex;
        }

        .campo-busqueda input {
            flex: 1;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            border-right: none;
        }

        .campo-busqueda .btn-buscar {
            border-radius: 0 5px 5px 0;
        }

        .grupo-filtros select {
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 180px;
        }

        /* Tabla */
        .tabla-contenedor {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-datos th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 1px solid #dee2e6;
        }

        .tabla-datos td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        .tabla-datos tr:hover {
            background: #f8f9fa;
        }

        .sin-resultados {
            text-align: center;
            color: #666;
            padding: 2rem;
        }

        /* Badges de estado */
        .badge {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .estado-pendiente { background: #fff3cd; color: #856404; }
        .estado-documentos_aprobados { background: #d1ecf1; color: #0c5460; }
        .estado-documentos_rechazados { background: #f8d7da; color: #721c24; }
        .estado-admitido { background: #d4edda; color: #155724; }
        .estado-no_admitido { background: #f8d7da; color: #721c24; }

        /* Acciones de tabla */
        .acciones-tabla {
            display: flex;
            gap: 0.5rem;
        }

        .btn-accion {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-ver { background: #17a2b8; color: white; }
        .btn-editar { background: #ffc107; color: white; }
        .btn-documentos { background: #6f42c1; color: white; }
        .btn-eliminar { background: #dc3545; color: white; }

        .btn-accion:hover {
            transform: scale(1.1);
        }

        /* Modales */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            overflow-y: auto;
        }

        .modal-contenido {
            background: white;
            margin: 2rem auto;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-grande {
            max-width: 800px;
        }

        .modal-cabecera {
            padding: 1.5rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-cabecera h2 {
            color: #333;
            font-size: 1.5rem;
        }

        .btn-cerrar-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .modal-cuerpo {
            padding: 1.5rem;
        }

        .form-modal {
            padding: 1.5rem;
        }

        .grupo-campos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .campo-form {
            display: flex;
            flex-direction: column;
        }

        .campo-form label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .campo-form input,
        .campo-form select,
        .campo-form textarea {
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .campo-form textarea {
            resize: vertical;
            min-height: 80px;
        }

        .seccion-inscripcion {
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .seccion-inscripcion h3 {
            margin-bottom: 1rem;
            color: #555;
        }

        .modal-pie {
            padding: 1.5rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Documentos */
        .info-postulante-docs {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }

        .lista-documentos {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .documento-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .info-documento {
            flex: 1;
            min-width: 250px;
        }

        .info-documento h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .obligatorio {
            background: #dc3545;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }

        .estado-documento {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-weight: 500;
            min-width: 100px;
            text-align: center;
        }

        .estado-documento.aprobado { background: #d4edda; color: #155724; }
        .estado-documento.rechazado { background: #f8d7da; color: #721c24; }
        .estado-documento.pendiente { background: #fff3cd; color: #856404; }

        .acciones-documento {
            display: flex;
            gap: 0.5rem;
        }

        /* Alertas */
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Paginación */
        .paginacion {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .info-paginacion {
            color: #666;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .menu-lateral {
                width: 200px;
            }
            .contenido-principal {
                margin-left: 200px;
            }
        }

        @media (max-width: 768px) {
            .menu-lateral {
                width: 60px;
            }
            .contenido-principal {
                margin-left: 60px;
            }
            .enlace-menu span {
                display: none;
            }
            .cabecera-seccion {
                flex-direction: column;
                align-items: flex-start;
            }
            .controles-superiores {
                width: 100%;
            }
            .grupo-filtros {
                flex-direction: column;
            }
            .campo-busqueda {
                min-width: 100%;
            }
            .tabla-contenedor {
                overflow-x: auto;
            }
            .tabla-datos {
                min-width: 800px;
            }
            .documento-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .acciones-documento {
                width: 100%;
                justify-content: flex-end;
            }
        }

        @media (max-width: 480px) {
            .barra-navegacion {
                padding: 1rem;
            }
            .logo h1 {
                font-size: 1.2rem;
            }
            .contenido-principal {
                padding: 1rem;
                margin-left: 0;
            }
            .menu-lateral {
                display: none;
            }
            .estadisticas-rapidas {
                grid-template-columns: 1fr;
            }
            .modal-contenido {
                width: 95%;
                margin: 1rem auto;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="contenedor-principal">
        <!-- Barra de navegación -->
        <nav class="barra-navegacion">
            <div class="logo">
                <h1>Sistema de Admisiones</h1>
            </div>
            <div class="info-usuario">
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Usuario'); ?></span>
                <button id="btn-cerrar-sesion">Cerrar Sesión</button>
            </div>
        </nav>
        
        <!-- Menú lateral -->
        <aside class="menu-lateral">
            <ul class="lista-menu">
                <li><a href="personal_de_admision.php" class="enlace-menu"><i class="fas fa-home"></i> <span>Panel de Inicio</span></a></li>
                <li><a href="gestion_postulantes.php" class="enlace-menu activo"><i class="fas fa-users"></i> <span>Gestión de Postulantes</span></a></li>
                <li><a href="validacion_documentos.php" class="enlace-menu"><i class="fas fa-file-alt"></i> <span>Validación de Documentos</span></a></li>
                <li><a href="programar_examen.php" class="enlace-menu"><i class="fas fa-calendar-alt"></i> <span>Programar Examen</span></a></li>
                <li><a href="resultados.php" class="enlace-menu"><i class="fas fa-chart-bar"></i> <span>Resultados</span></a></li>
            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="contenido-principal">
            <div class="cabecera-seccion">
                <h1>Gestión de Postulantes</h1>
                <div class="controles-superiores">
                    <button id="btn-nuevo-postulante" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Postulante
                    </button>
                    <button id="btn-exportar" class="btn btn-secondary">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="estadisticas-rapidas">
                <div class="tarjeta-estadistica">
                    <div class="icono-estadistica">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info-estadistica">
                        <h3>Total Postulantes</h3>
                        <p class="numero"><?php echo $estadisticas['total']; ?></p>
                    </div>
                </div>
                <?php foreach ($estadisticas['por_estado'] as $estado): ?>
                <div class="tarjeta-estadistica">
                    <div class="icono-estadistica estado-<?php echo $estado['estado_postulacion']; ?>">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="info-estadistica">
                        <h3><?php echo ucfirst(str_replace('_', ' ', $estado['estado_postulacion'])); ?></h3>
                        <p class="numero"><?php echo $estado['cantidad']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Panel de filtros -->
            <div class="panel-filtros">
                <form method="GET" class="form-filtros">
                    <div class="grupo-filtros">
                        <div class="campo-busqueda">
                            <input type="text" name="buscar" id="buscar-postulante" 
                                   placeholder="Buscar por nombre, CI o folio..." 
                                   value="<?php echo htmlspecialchars($busqueda); ?>">
                            <button type="submit" class="btn btn-buscar">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <select name="filtro_estado" id="filtro-estado">
                            <option value="todos" <?php echo $filtro_estado == 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                            <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="documentos_aprobados" <?php echo $filtro_estado == 'documentos_aprobados' ? 'selected' : ''; ?>>Documentos Aprobados</option>
                            <option value="documentos_rechazados" <?php echo $filtro_estado == 'documentos_rechazados' ? 'selected' : ''; ?>>Documentos Rechazados</option>
                            <option value="admitido" <?php echo $filtro_estado == 'admitido' ? 'selected' : ''; ?>>Admitido</option>
                            <option value="no_admitido" <?php echo $filtro_estado == 'no_admitido' ? 'selected' : ''; ?>>No Admitido</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                        
                        <?php if ($busqueda || $filtro_estado != 'todos'): ?>
                        <a href="gestion_postulantes.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabla de postulantes -->
            <div class="tabla-contenedor">
                <table id="tabla-postulantes" class="tabla-datos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Postulante</th>
                            <th>CI</th>
                            <th>Carrera</th>
                            <th>Estado</th>
                            <th>Documentos</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($postulantes)): ?>
                        <tr>
                            <td colspan="8" class="sin-resultados">
                                No se encontraron postulantes con los criterios de búsqueda especificados.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($postulantes as $postulante): ?>
                        <tr data-id="<?php echo $postulante['id_postulante']; ?>">
                            <td><?php echo $postulante['id_postulante']; ?></td>
                            <td>
                                <div class="info-postulante">
                                    <strong><?php echo htmlspecialchars($postulante['nombres'] . ' ' . $postulante['apellido_paterno'] . ' ' . $postulante['apellido_materno']); ?></strong>
                                    <?php if ($postulante['numero_folio']): ?>
                                    <br><small>Folio: <?php echo htmlspecialchars($postulante['numero_folio']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($postulante['ci']); ?></td>
                            <td><?php echo htmlspecialchars($postulante['carrera_nombre'] ?? 'No asignada'); ?></td>
                            <td>
                                <span class="badge estado-<?php echo $postulante['estado_postulacion']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $postulante['estado_postulacion'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="estado-documentos">
                                    <span class="documentos-totales"><?php echo $postulante['total_documentos'] ?? 0; ?></span>
                                    <?php if ($postulante['documentos_pendientes'] > 0): ?>
                                    <span class="documentos-pendientes">(<?php echo $postulante['documentos_pendientes']; ?> pendientes)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($postulante['creado_en'])); ?></td>
                            <td>
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-ver" data-id="<?php echo $postulante['id_postulante']; ?>" title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-accion btn-editar" data-id="<?php echo $postulante['id_postulante']; ?>" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-accion btn-documentos" data-id="<?php echo $postulante['id_postulante']; ?>" title="Gestionar Documentos">
                                        <i class="fas fa-folder"></i>
                                    </button>
                                    <button class="btn-accion btn-eliminar" data-id="<?php echo $postulante['id_postulante']; ?>" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="paginacion">
                <button class="btn btn-secondary" disabled>Anterior</button>
                <span class="info-paginacion">Página 1 de 1</span>
                <button class="btn btn-secondary" disabled>Siguiente</button>
            </div>
        </main>
    </div>

    <!-- Modal para nuevo/editar postulante -->
    <div id="modal-postulante" class="modal">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h2 id="modal-titulo">Nuevo Postulante</h2>
                <button class="btn-cerrar-modal">&times;</button>
            </div>
            <form id="form-postulante" class="form-modal">
                <input type="hidden" name="action" id="form-action">
                <input type="hidden" name="id_postulante" id="id-postulante">
                
                <div class="grupo-campos">
                    <div class="campo-form">
                        <label for="nombres">Nombres *</label>
                        <input type="text" id="nombres" name="nombres" required>
                    </div>
                    
                    <div class="campo-form">
                        <label for="apellido_paterno">Apellido Paterno *</label>
                        <input type="text" id="apellido_paterno" name="apellido_paterno" required>
                    </div>
                    
                    <div class="campo-form">
                        <label for="apellido_materno">Apellido Materno</label>
                        <input type="text" id="apellido_materno" name="apellido_materno">
                    </div>
                </div>
                
                <div class="grupo-campos">
                    <div class="campo-form">
                        <label for="ci">Cédula de Identidad *</label>
                        <input type="text" id="ci" name="ci" required>
                    </div>
                    
                    <div class="campo-form">
                        <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">
                    </div>
                    
                    <div class="campo-form">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono">
                    </div>
                </div>
                
                <div class="campo-form">
                    <label for="direccion">Dirección</label>
                    <textarea id="direccion" name="direccion" rows="3"></textarea>
                </div>
                
                <div class="grupo-campos">
                    <div class="campo-form">
                        <label for="nacionalidad">Nacionalidad</label>
                        <input type="text" id="nacionalidad" name="nacionalidad" value="Boliviana">
                    </div>
                    
                    <div class="campo-form">
                        <label for="estado_postulacion">Estado de Postulación</label>
                        <select id="estado_postulacion" name="estado_postulacion">
                            <option value="pendiente">Pendiente</option>
                            <option value="documentos_aprobados">Documentos Aprobados</option>
                            <option value="documentos_rechazados">Documentos Rechazados</option>
                            <option value="admitido">Admitido</option>
                            <option value="no_admitido">No Admitido</option>
                        </select>
                    </div>
                </div>
                
                <div class="seccion-inscripcion">
                    <h3>Inscripción a Carrera (Opcional)</h3>
                    <div class="grupo-campos">
                        <div class="campo-form">
                            <label for="id_carrera">Carrera</label>
                            <select id="id_carrera" name="id_carrera">
                                <option value="">Seleccionar carrera...</option>
                                <?php foreach ($carreras as $carrera): ?>
                                <option value="<?php echo $carrera['id_carrera']; ?>">
                                    <?php echo htmlspecialchars($carrera['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="campo-form">
                            <label for="periodo_id">Periodo Académico</label>
                            <select id="periodo_id" name="periodo_id">
                                <option value="">Seleccionar periodo...</option>
                                <?php foreach ($periodos as $periodo): ?>
                                <option value="<?php echo $periodo['id_periodo']; ?>">
                                    <?php echo htmlspecialchars($periodo['nombre_periodo']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-pie">
                    <button type="button" class="btn btn-secondary btn-cancelar">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Postulante</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para documentos -->
    <div id="modal-documentos" class="modal">
        <div class="modal-contenido modal-grande">
            <div class="modal-cabecera">
                <h2>Gestión de Documentos</h2>
                <button class="btn-cerrar-modal">&times;</button>
            </div>
            <div class="modal-cuerpo">
                <div id="info-postulante-docs" class="info-postulante-docs"></div>
                
                <div class="lista-documentos" id="lista-documentos">
                    <!-- Los documentos se cargarán aquí dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script>
        class GestionPostulantes {
            constructor() {
                this.initEventListeners();
                this.initModals();
            }

            initEventListeners() {
                // Botón nuevo postulante
                document.getElementById('btn-nuevo-postulante').addEventListener('click', () => {
                    this.abrirModalPostulante();
                });

                // Botones de acción en la tabla
                document.addEventListener('click', (e) => {
                    if (e.target.closest('.btn-ver')) {
                        const id = e.target.closest('.btn-ver').dataset.id;
                        this.verPostulante(id);
                    } else if (e.target.closest('.btn-editar')) {
                        const id = e.target.closest('.btn-editar').dataset.id;
                        this.editarPostulante(id);
                    } else if (e.target.closest('.btn-documentos')) {
                        const id = e.target.closest('.btn-documentos').dataset.id;
                        this.gestionarDocumentos(id);
                    } else if (e.target.closest('.btn-eliminar')) {
                        const id = e.target.closest('.btn-eliminar').dataset.id;
                        this.eliminarPostulante(id);
                    }
                });

                // Cerrar modales
                document.querySelectorAll('.btn-cerrar-modal, .btn-cancelar').forEach(btn => {
                    btn.addEventListener('click', () => {
                        this.cerrarModales();
                    });
                });

                // Formulario postulante
                document.getElementById('form-postulante').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.guardarPostulante();
                });

                // Exportar
                document.getElementById('btn-exportar').addEventListener('click', () => {
                    this.exportarPostulantes();
                });

                // Cerrar sesión
                document.getElementById('btn-cerrar-sesion').addEventListener('click', () => {
                    if (confirm('¿Está seguro de que desea cerrar sesión?')) {
                        window.location.href = 'logout.php';
                    }
                });
            }

            initModals() {
                // Cerrar modal al hacer click fuera
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            this.cerrarModales();
                        }
                    });
                });
            }

            abrirModalPostulante(datos = null) {
                const modal = document.getElementById('modal-postulante');
                const titulo = document.getElementById('modal-titulo');
                const form = document.getElementById('form-postulante');
                
                if (datos) {
                    titulo.textContent = 'Editar Postulante';
                    document.getElementById('form-action').value = 'actualizar_postulante';
                    document.getElementById('id-postulante').value = datos.id_postulante;
                    this.llenarFormulario(datos);
                } else {
                    titulo.textContent = 'Nuevo Postulante';
                    document.getElementById('form-action').value = 'crear_postulante';
                    form.reset();
                }
                
                modal.style.display = 'block';
            }

            llenarFormulario(datos) {
                Object.keys(datos).forEach(key => {
                    const campo = document.getElementById(key);
                    if (campo) {
                        campo.value = datos[key] || '';
                    }
                });
            }

            async verPostulante(id) {
                try {
                    const response = await this.obtenerPostulante(id);
                    this.abrirModalPostulante(response);
                    
                    // Deshabilitar campos para solo lectura
                    document.querySelectorAll('#form-postulante input, #form-postulante select, #form-postulante textarea')
                        .forEach(campo => campo.disabled = true);
                    
                } catch (error) {
                    this.mostrarAlerta('Error al cargar el postulante: ' + error.message, 'error');
                }
            }

            async editarPostulante(id) {
                try {
                    const response = await this.obtenerPostulante(id);
                    this.abrirModalPostulante(response);
                    
                    // Habilitar campos para edición
                    document.querySelectorAll('#form-postulante input, #form-postulante select, #form-postulante textarea')
                        .forEach(campo => campo.disabled = false);
                        
                } catch (error) {
                    this.mostrarAlerta('Error al cargar el postulante: ' + error.message, 'error');
                }
            }

            async obtenerPostulante(id) {
                const response = await fetch(`obtener_postulante.php?id=${id}`);
                if (!response.ok) {
                    throw new Error('Error al obtener postulante');
                }
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                return data;
            }

            async gestionarDocumentos(id) {
                try {
                    const response = await fetch(`obtener_documentos_postulante.php?id=${id}`);
                    if (!response.ok) {
                        throw new Error('Error al obtener documentos');
                    }
                    const data = await response.json();
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    this.mostrarModalDocumentos(data);
                    
                } catch (error) {
                    this.mostrarAlerta('Error al cargar los documentos: ' + error.message, 'error');
                }
            }

            mostrarModalDocumentos(data) {
                const modal = document.getElementById('modal-documentos');
                const infoPostulante = document.getElementById('info-postulante-docs');
                const listaDocumentos = document.getElementById('lista-documentos');
                
                // Información del postulante
                infoPostulante.innerHTML = `
                    <h4>${data.postulante.nombres} ${data.postulante.apellido_paterno} ${data.postulante.apellido_materno || ''}</h4>
                    <p>CI: ${data.postulante.ci} | Folio: ${data.postulante.numero_folio || 'No asignado'}</p>
                `;
                
                // Lista de documentos
                if (data.documentos && data.documentos.length > 0) {
                    listaDocumentos.innerHTML = data.documentos.map(doc => `
                        <div class="documento-item">
                            <div class="info-documento">
                                <h4>${doc.nombre_documento || doc.tipo_documento || 'Documento'}</h4>
                                ${doc.obligatorio ? '<span class="obligatorio">OBLIGATORIO</span>' : ''}
                                <p>Subido: ${doc.fecha_carga ? new Date(doc.fecha_carga).toLocaleDateString() : 'No disponible'}</p>
                                ${doc.comentario ? `<p><strong>Comentario:</strong> ${doc.comentario}</p>` : ''}
                            </div>
                            <div class="estado-documento ${doc.estado_validacion || 'pendiente'}">
                                ${(doc.estado_validacion || 'pendiente').toUpperCase()}
                            </div>
                            <div class="acciones-documento">
                                ${doc.archivo_url ? 
                                    `<a href="${doc.archivo_url}" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> Ver
                                     </a>` : ''
                                }
                                ${(doc.estado_validacion || 'pendiente') === 'pendiente' ? `
                                    <button class="btn btn-success btn-validar-doc" data-id="${doc.id_doc}" data-estado="aprobado">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    <button class="btn btn-danger btn-validar-doc" data-id="${doc.id_doc}" data-estado="rechazado">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `).join('');
                } else {
                    listaDocumentos.innerHTML = '<p>No hay documentos registrados para este postulante.</p>';
                }
                
                // Event listeners para validación de documentos
                listaDocumentos.querySelectorAll('.btn-validar-doc').forEach(btn => {
                    btn.addEventListener('click', () => {
                        this.validarDocumento(
                            btn.dataset.id,
                            btn.dataset.estado,
                            data.postulante.id_postulante
                        );
                    });
                });
                
                modal.style.display = 'block';
            }

            async guardarPostulante() {
                const form = document.getElementById('form-postulante');
                const formData = new FormData(form);
                
                try {
                    const response = await fetch('gestion_postulantes.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.mostrarAlerta(result.message, 'success');
                        this.cerrarModales();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        this.mostrarAlerta(result.message, 'error');
                    }
                    
                } catch (error) {
                    this.mostrarAlerta('Error de conexión: ' + error.message, 'error');
                }
            }

            async validarDocumento(docId, estado, postulanteId) {
                const comentario = prompt('Ingrese un comentario (opcional):');
                
                try {
                    const response = await fetch('gestion_postulantes.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=validar_documento&doc_id=${docId}&estado=${estado}&comentario=${encodeURIComponent(comentario || '')}`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.mostrarAlerta(result.message, 'success');
                        // Recargar la gestión de documentos
                        this.gestionarDocumentos(postulanteId);
                    } else {
                        this.mostrarAlerta(result.message, 'error');
                    }
                    
                } catch (error) {
                    this.mostrarAlerta('Error de conexión: ' + error.message, 'error');
                }
            }

            async eliminarPostulante(id) {
                if (confirm('¿Está seguro de que desea eliminar este postulante? Esta acción no se puede deshacer.')) {
                    try {
                        const response = await fetch('gestion_postulantes.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=eliminar_postulante&id_postulante=${id}`
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            this.mostrarAlerta(result.message, 'success');
                            // Eliminar fila de la tabla
                            const fila = document.querySelector(`tr[data-id="${id}"]`);
                            if (fila) {
                                fila.remove();
                            }
                        } else {
                            this.mostrarAlerta(result.message, 'error');
                        }
                    } catch (error) {
                        this.mostrarAlerta('Error de conexión: ' + error.message, 'error');
                    }
                }
            }

            exportarPostulantes() {
                // Implementar exportación a Excel
                const busqueda = document.getElementById('buscar-postulante').value;
                const filtroEstado = document.getElementById('filtro-estado').value;
                
                let url = 'exportar_postulantes.php';
                const params = [];
                
                if (busqueda) params.push(`buscar=${encodeURIComponent(busqueda)}`);
                if (filtroEstado !== 'todos') params.push(`filtro_estado=${filtroEstado}`);
                
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                
                window.location.href = url;
            }

            cerrarModales() {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
                
                // Habilitar todos los campos
                document.querySelectorAll('#form-postulante input, #form-postulante select, #form-postulante textarea')
                    .forEach(campo => campo.disabled = false);
            }

            mostrarAlerta(mensaje, tipo) {
                // Remover alertas existentes
                const alertasExistentes = document.querySelectorAll('.alert');
                alertasExistentes.forEach(alerta => alerta.remove());
                
                const alerta = document.createElement('div');
                alerta.className = `alert alert-${tipo === 'success' ? 'success' : 'error'}`;
                alerta.textContent = mensaje;
                
                document.querySelector('.contenido-principal').insertBefore(alerta, document.querySelector('.cabecera-seccion').nextSibling);
                
                // Auto-remover después de 5 segundos
                setTimeout(() => {
                    if (alerta.parentNode) {
                        alerta.remove();
                    }
                }, 5000);
            }
        }

        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', () => {
            new GestionPostulantes();
        });
    </script>
</body>
</html>