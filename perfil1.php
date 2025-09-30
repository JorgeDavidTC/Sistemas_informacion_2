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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Perfil de Usuario - Sistema de Admisiones</title>
    <link rel="stylesheet" href="css/perfil.css" />
</head>
<body>
<div class="container">
    <h1>Perfil de Usuario</h1>

    <div class="profile-picture-section">
        <?php if($usuario['foto_perfil_url']): ?>
            <div class="profile-picture-container">
                <img src="<?php echo htmlspecialchars($usuario['foto_perfil_url']); ?>" 
                     alt="Foto de Perfil" class="profile-picture">
            </div>
        <?php endif; ?>
    </div>

    <div class="profile-grid">
        <div class="profile-card">
            <h3>Información Personal</h3>
            <div class="info-item">
                <div class="info-icon">👤</div>
                <span class="info-label">Nombre:</span>
                <span class="info-value"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></span>
            </div>
            <div class="info-item">
                <div class="info-icon">📝</div>
                <span class="info-label">Apellidos:</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($usuario['apellido_paterno'] . ' ' . $usuario['apellido_materno']); ?>
                </span>
            </div>
            <div class="info-item">
                <div class="info-icon">🆔</div>
                <span class="info-label">CI:</span>
                <span class="info-value"><?php echo htmlspecialchars($usuario['ci']); ?></span>
            </div>
            <div class="info-item">
                <div class="info-icon">🎂</div>
                <span class="info-label">Fecha Nacimiento:</span>
                <span class="info-value"><?php echo htmlspecialchars($usuario['fecha_nacimiento']); ?></span>
            </div>
        </div>

        <div class="profile-card">
            <h3>Información de Contacto</h3>
            <div class="info-item">
                <div class="info-icon">📧</div>
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($usuario['correo_electronico']); ?></span>
            </div>
            <div class="info-item">
                <div class="info-icon">📱</div>
                <span class="info-label">Teléfono:</span>
                <span class="info-value"><?php echo htmlspecialchars($usuario['telefono']); ?></span>
            </div>
            <div class="info-item">
                <div class="info-icon">🏠</div>
                <span class="info-label">Dirección:</span>
                <span class="info-value"><?php echo htmlspecialchars($usuario['direccion_residencia']); ?></span>
            </div>
            <div class="info-item">
                <div class="info-icon">🌍</div>
                <span class="info-label">Nacionalidad:</span>
                <span class="info-value"><?php echo htmlspecialchars($usuario['nacionalidad']); ?></span>
            </div>
        </div>
    </div>

    <div class="academic-section">
        <h3>Información Académica</h3>
        <div class="academic-info">
            <div class="info-item">
                <div class="info-icon">🏛️</div>
                <span class="info-label">Facultad:</span>
                <span class="info-value"><?php echo htmlspecialchars($usuario['facultad'] ?? 'No asignada'); ?></span>
            </div>
            <div class="info-item">
                <div class="info-icon">🎓</div>
                <span class="info-label">Carrera:</span>
                <span class="info-value"><?php echo htmlspecialchars($usuario['carrera'] ?? 'No asignada'); ?></span>
            </div>
        </div>
    </div>

    <button class="btn-back" onclick="window.location.href='postulante_dashboard.php'">
        ← Volver
    </button>
</div>
</body>
</html>