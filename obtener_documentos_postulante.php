<?php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID no proporcionado']);
    exit;
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
            
            // Obtener documentos
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
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    $documentoManager = new DocumentoManager($conn);
    
    $datos = $documentoManager->getDocumentosPostulante($_GET['id']);
    echo json_encode($datos);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>