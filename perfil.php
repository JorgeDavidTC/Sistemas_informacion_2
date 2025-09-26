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

// Obtener datos de usuario y postulante
$sql = "SELECT u.nombre AS nombre_usuario, u.correo_electronico, u.contrasena, 
               p.apellido_paterno, p.apellido_materno, p.ci, p.fecha_nacimiento, 
               p.telefono, p.direccion_residencia, p.nacionalidad, p.foto_perfil_url
        FROM usuarios u
        LEFT JOIN postulantes p ON u.id_usuario = p.usuario_id
        WHERE u.id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "Usuario no encontrado.";
    exit();
}

$usuario = $result->fetch_assoc();

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $apellido_paterno = trim($_POST['apellido_paterno']);
    $apellido_materno = trim($_POST['apellido_materno']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion_residencia']);
    $nacionalidad = trim($_POST['nacionalidad']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $contrasena = $_POST['contrasena'];

    // Foto nueva si se sube
    $foto_perfil_url = $usuario['foto_perfil_url'];
    if(isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
        $carpeta_destino = 'uploads/';
        if(!is_dir($carpeta_destino)) mkdir($carpeta_destino, 0755, true);
        $nombreArchivo = time().'_'.basename($_FILES['foto_perfil']['name']);
        $rutaCompleta = $carpeta_destino.$nombreArchivo;
        move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $rutaCompleta);
        $foto_perfil_url = $rutaCompleta;
    }

    // Mantener la contraseña actual si el campo está vacío
    if($contrasena === '') {
        $contrasena = $usuario['contrasena'];
    }

    // Actualizar tabla usuarios
    $stmt_u = $conn->prepare("UPDATE usuarios SET nombre=?, correo_electronico=?, contrasena=? WHERE id_usuario=?");
    $stmt_u->bind_param("sssi", $nombre_usuario, $email, $contrasena, $id_usuario);
    $stmt_u->execute();

    // Actualizar tabla postulantes
    $stmt_p = $conn->prepare("UPDATE postulantes SET apellido_paterno=?, apellido_materno=?, telefono=?, direccion_residencia=?, nacionalidad=?, fecha_nacimiento=?, foto_perfil_url=? WHERE usuario_id=?");
    $stmt_p->bind_param("sssssssi", $apellido_paterno, $apellido_materno, $telefono, $direccion, $nacionalidad, $fecha_nacimiento, $foto_perfil_url, $id_usuario);
    $stmt_p->execute();

    header("Location: perfil.php");
    exit();
}

// Modo edición
$modo_editar = isset($_GET['editar']);
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
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; }
        button { margin-top: 15px; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-edit { background: #28a745; color: #fff; }
        .btn-back { background: #dc3545; color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <h1>Perfil de Usuario</h1>

    <?php if (!$modo_editar): ?>
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

        <a href="perfil.php?editar=1"><button class="btn-edit">Editar Perfil</button></a>
        <button class="btn-back" onclick="window.location.href='postulante_dashboard.php'">Volver</button>

    <?php else: ?>
        <form method="post" action="perfil.php" enctype="multipart/form-data">
            <label>Nombre:
                <input type="text" name="nombre_usuario" value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" required>
            </label>
            <label>Apellido Paterno:
                <input type="text" name="apellido_paterno" value="<?php echo htmlspecialchars($usuario['apellido_paterno']); ?>">
            </label>
            <label>Apellido Materno:
                <input type="text" name="apellido_materno" value="<?php echo htmlspecialchars($usuario['apellido_materno']); ?>">
            </label>
            <label>CI:
                <input type="text" value="<?php echo htmlspecialchars($usuario['ci']); ?>" disabled>
            </label>
            <label>Email:
                <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['correo_electronico']); ?>" required>
            </label>
            <label>Teléfono:
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
            </label>
            <label>Dirección:
                <textarea name="direccion_residencia"><?php echo htmlspecialchars($usuario['direccion_residencia']); ?></textarea>
            </label>
            <label>Nacionalidad:
                <input type="text" name="nacionalidad" value="<?php echo htmlspecialchars($usuario['nacionalidad']); ?>">
            </label>
            <label>Fecha de Nacimiento:
                <input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($usuario['fecha_nacimiento']); ?>">
            </label>
            <label>Contraseña (dejar vacío para mantener la actual):
                <input type="text" name="contrasena" placeholder="Nueva contraseña">
            </label>
            <label>Foto de Perfil:
                <input type="file" name="foto_perfil" accept=".jpg,.jpeg,.png">
            </label>

            
            <button type="button" class="btn-back" onclick="window.location.href='perfil.php';">Cancelar</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
