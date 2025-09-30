<?php
session_start();

// Conexión a MySQL
$host = "localhost";
$usuarioBD = "root";
$contrasenaBD = "";
$baseDeDatos = "admisiones_unificadas";

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
$stmt = $conn->prepare("SELECT id_usuario, nombre, contrasena, rol FROM usuarios WHERE nombre = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();

    // Aquí usamos la contraseña tal cual se ingresó
    if ($fila['contrasena'] === $password) {
        $_SESSION['id_usuario'] = $fila['id_usuario'];
        $_SESSION['usuario'] = $fila['nombre'];
        $_SESSION['rol'] = $fila['rol'];

        // Redirigir según rol - SOLO DOS ROLES PERMITIDOS
        if ($fila['rol'] === 'personal_admision') {
            header("Location: personal_de_admision.php");
        } elseif ($fila['rol'] === 'postulante') {
            header("Location: postulante_dashboard.php");
        } else {
            // Si el rol no es uno de los permitidos
            echo "<script>alert('Rol de usuario no válido'); window.history.back();</script>";
            exit;
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