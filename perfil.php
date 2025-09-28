<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.html");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "admisiones_unificadas";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener datos de usuario, postulante, carrera y facultad
$sql = "SELECT u.nombre AS nombre_usuario, u.correo_electronico, u.contrasena, 
               p.apellido_paterno, p.apellido_materno, p.ci, p.fecha_nacimiento, 
               p.telefono, p.direccion_residencia, p.nacionalidad, p.foto_perfil_url,
               c.nombre AS carrera, f.nombre AS facultad
        FROM usuarios u
        LEFT JOIN postulantes p ON u.id_usuario = p.usuario_id
        LEFT JOIN inscripciones i ON p.id_postulante = i.id_postulante
        LEFT JOIN carreras c ON i.id_carrera = c.id_carrera
        LEFT JOIN facultades f ON c.facultad_id = f.id_facultad
        WHERE u.id_usuario = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "Usuario no encontrado.";
    exit();
}

$usuario = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Perfil de Usuario</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 30px; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; }
        img { max-width: 150px; border-radius: 50%; display: block; margin-bottom: 15px; }
        p { margin: 8px 0; }
        strong { color: #333; }
        .btn-back { background: #007bff; color: #fff; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Perfil de Usuario</h1>

    <?php if($usuario['foto_perfil_url']): ?>
        <img src="<?php echo htmlspecialchars($usuario['foto_perfil_url']); ?>" alt="Foto de Perfil">
    <?php endif; ?>

    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre_usuario']); ?></p>
    <p><strong>Apellido Paterno:</strong> <?php echo htmlspecialchars($usuario['apellido_paterno']); ?></p>
    <p><strong>Apellido Materno:</strong> <?php echo htmlspecialchars($usuario['apellido_materno']); ?></p>
    <p><strong>CI:</strong> <?php echo htmlspecialchars($usuario['ci']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['correo_electronico']); ?></p>
    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($usuario['telefono']); ?></p>
    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($usuario['direccion_residencia']); ?></p>
    <p><strong>Nacionalidad:</strong> <?php echo htmlspecialchars($usuario['nacionalidad']); ?></p>
    <p><strong>Fecha de Nacimiento:</strong> <?php echo htmlspecialchars($usuario['fecha_nacimiento']); ?></p>
    <p><strong>Facultad:</strong> <?php echo htmlspecialchars($usuario['facultad'] ?? 'No asignada'); ?></p>
    <p><strong>Carrera:</strong> <?php echo htmlspecialchars($usuario['carrera'] ?? 'No asignada'); ?></p>

    <button class="btn-back" onclick="window.location.href='postulante_dashboard.php'">Volver</button>
</div>
</body>
</html>
