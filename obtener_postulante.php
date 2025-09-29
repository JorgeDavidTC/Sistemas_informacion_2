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

class PostulanteManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
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
            throw new Exception("Error al cargar el postulante: " . $e->getMessage());
        }
    }
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    $postulanteManager = new PostulanteManager($conn);
    
    $postulante = $postulanteManager->obtenerPostulante($_GET['id']);
    echo json_encode($postulante);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>