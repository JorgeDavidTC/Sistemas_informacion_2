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

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?></title>
<link rel="stylesheet" href="css/principal.css" />
<style>
/* --- estilos básicos --- */
header h1 { margin: 0; font-size: 1.8rem; }
.header-right { display: flex; align-items: center; gap: 20px; }
.username { font-weight: 600; font-size: 1rem; }
.btn-perfil { background-color: #b3a575; color: white; border: none; padding: 8px 16px; border-radius: 25px; cursor: pointer; font-weight: 600; }
.btn-perfil:hover { background-color:#c1b487; }
.nav-menu { position: relative; display: inline-block; }
.nav-menu button { background-color: #b3a575; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
.nav-menu button:hover { background-color:rgb(174, 159, 107); }
.nav-menu-content { display: none; position: absolute; background-color: #b3a575; min-width: 180px; box-shadow: 0 8px 16px rgba(0,0,0,0.3); border-radius: 5px; z-index: 1000; right: 0; }
.nav-menu-content a { color: #ecf0f1; padding: 12px 16px; text-decoration: none; display: block; }
.nav-menu-content a:hover { background-color:#c1b487; }
.nav-menu:hover .nav-menu-content { display: block; }
.btn-ranking { background-color: #5a4d3c; color: white; border: none; padding: 10px 20px; margin: 10px 0; border-radius: 5px; cursor: pointer; font-size: 1rem; }
.btn-ranking:hover { background-color:rgb(102, 86, 64); }
.ranking-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
.ranking-table th, .ranking-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
.ranking-table th { background-color: #5a4d3c; color: white; }
</style>
</head>
<body>
<header>
    <h1>Panel de Usuario</h1>
    <div class="header-right">
        <span class="username">Usuario: <?= htmlspecialchars($usuario['nombre']) ?></span>
        <button class="btn-perfil" onclick="location.href='perfil.php'">Perfil</button>
        <div class="nav-menu" tabindex="0">
            <button>Menú ▼</button>
            <div class="nav-menu-content">
                <a href="biblioteca.php">Biblioteca</a>
                <a href="inscripciones.php">Mis inscripciones</a>
                <?php if ($es_admin): ?>
                    <a href="admin_panel.php" title="Administración">Administrar</a>
                <?php endif; ?>
                <a href="login.php">Cerrar sesión</a>
            </div>
        </div>
    </div>
</header>

<main>
    <h2>Bienvenido al sistema de admisiones</h2>
    <p>Email registrado: <?= htmlspecialchars($usuario['correo_electronico']) ?></p>
    <p>Rol: <?= $es_admin ? "Administrador" : "Postulante" ?></p>

    <!-- Ranking de carreras -->
    <h2>Top carreras más demandadas</h2>
    <table class="ranking-table">
        <thead>
            <tr>
                <th>Posición</th>
                <th>Carrera</th>
                <th>Postulantes</th>
                <th>% del total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ranking_data as $index => $carrera): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($carrera['nombre']) ?></td>
                <td><?= $carrera['postulantes_count'] ?></td>
                <td><?= $carrera['porcentaje_total'] ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>
