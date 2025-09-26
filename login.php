<?php
session_start();

// Conexión a MySQL
$host = "localhost";
$usuarioBD = "root";
$contrasenaBD = "";
$baseDeDatos = "admisiones";

$conn = new mysqli($host, $usuarioBD, $contrasenaBD, $baseDeDatos);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$usuario = $_POST['usuario'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($usuario) || empty($password)) {
    echo "<script>alert('Por favor, complete todos los campos'); window.history.back();</script>";
    exit;
}

// Revisar usuario en tabla 'usuarios'
$stmt = $conn->prepare("SELECT id_usuario, nombre, contrasena, es_admin FROM usuarios WHERE nombre = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();

    if ($fila['contrasena'] === $password) { // Para producción usar password_hash
        $_SESSION['id_usuario'] = $fila['id_usuario'];
        $_SESSION['usuario'] = $fila['nombre'];
        $_SESSION['es_admin'] = $fila['es_admin'];

        // Redirigir a página principal según rol
        if ($fila['es_admin']) {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: postulante_dashboard.php");
        }
        exit;
    } else {
        echo "<script>alert('Contraseña incorrecta'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Usuario no encontrado'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
?>
