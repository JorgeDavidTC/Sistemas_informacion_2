<?php
session_start();

// Redirigir si no está autenticado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.html");
    exit();
}

// Configuración de conexión a la base de datos admisiones_unificadas
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'admisiones_unificadas');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
    error_log("Error de conexión a BD: " . $conn->connect_error);
    die("Error al conectar con la base de datos. Intente más tarde.");
}

$id_usuario = (int) $_SESSION['id_usuario'];

// Obtener datos de usuario
$query = "SELECT nombre, correo_electronico, rol FROM usuarios WHERE id_usuario = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: login.html");
    exit();
}
$usuario = $result->fetch_assoc();
$stmt->close();

// Determinar si es admin
$es_admin = ($usuario['rol'] === 'admin');

// Obtener ranking de carreras directamente desde PHP
$ranking_data = [];
$ranking_query = "
    SELECT c.nombre, COUNT(i.id_inscripcion) AS postulantes_count,
           ROUND(COUNT(i.id_inscripcion) / (SELECT COUNT(*) FROM inscripciones) * 100, 2) AS porcentaje_total
    FROM carreras c
    LEFT JOIN inscripciones i ON i.id_carrera = c.id_carrera
    GROUP BY c.id_carrera, c.nombre
    ORDER BY postulantes_count DESC
    LIMIT 10
";
if ($ranking_result = $conn->query($ranking_query)) {
    while ($row = $ranking_result->fetch_assoc()) {
        $ranking_data[] = $row;
    }
    $ranking_result->free();
}

$conn->close();
?>