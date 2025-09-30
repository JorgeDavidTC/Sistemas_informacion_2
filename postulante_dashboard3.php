<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.html");
    exit();
}


define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'admisiones_unificadas');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
    die("Error al conectar con la base de datos: " . $conn->connect_error);
}

$id_usuario = (int) $_SESSION['id_usuario'];


$stmt = $conn->prepare("SELECT nombre, correo_electronico, rol FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: login.html");
    exit();
}
$usuario = $result->fetch_assoc();
$stmt->close();


$es_admin = ($usuario['rol'] === 'admin');


$facultades = [];
$res = $conn->query("SELECT id_facultad, nombre FROM facultades ORDER BY nombre");
while ($f = $res->fetch_assoc()) {
    $facultades[] = $f;
}
$res->free();


$selected_facultad_id = isset($_GET['facultad_id']) ? (int)$_GET['facultad_id'] : 0;
$selected_facultad_guia_id = isset($_GET['facultad_guia_id']) ? (int)$_GET['facultad_guia_id'] : 0;


$guia_oficial = null;
$guia_query = "
    SELECT archivo_url 
    FROM recursos 
    WHERE tipo = 'guia' AND estado = 'activo'
";

if ($selected_facultad_guia_id > 0) {
    $guia_query .= " AND facultad_id = $selected_facultad_guia_id";
}
$guia_query .= " LIMIT 1";
$res = $conn->query($guia_query);
if ($res->num_rows > 0) {
    $guia_oficial = $res->fetch_assoc();
}



$stmt_puntaje = $conn->prepare("SELECT puntaje_examen FROM inscripciones WHERE Id_postulante = ?");
$stmt_puntaje->bind_param("i", $id_usuario);
$stmt_puntaje->execute();
$result_puntaje = $stmt_puntaje->get_result();

// Debug temporal
if ($result_puntaje->num_rows === 0) {
    error_log("DEBUG: No se encontró inscripción para el usuario ID: " . $id_usuario);
} else {
    $puntaje_data = $result_puntaje->fetch_assoc();
    error_log("DEBUG: Puntaje encontrado: " . ($puntaje_data['puntaje_examen'] ?? 'NULL'));
}

$puntaje_examen = $puntaje_data['puntaje_examen'] ?? null;
$stmt_puntaje->close();



$selected_facultad_examen_id = isset($_GET['facultad_examen_id']) ? (int)$_GET['facultad_examen_id'] : 0;

$examenes_pasados = [];
$examenes_query = "
    SELECT r.titulo, r.archivo_url, r.fecha_publicacion, r.permitir_descarga, 
           f.nombre as facultad_nombre, f.id_facultad
    FROM recursos r 
    LEFT JOIN facultades f ON r.facultad_id = f.id_facultad 
    WHERE r.tipo = 'archivo' AND r.estado = 'activo'
";

if ($selected_facultad_examen_id > 0) {
    $examenes_query .= " AND r.facultad_id = $selected_facultad_examen_id";
}

$examenes_query .= " ORDER BY r.fecha_publicacion DESC";

$res_examenes = $conn->query($examenes_query);
while ($examen = $res_examenes->fetch_assoc()) {
    $examenes_pasados[] = $examen;
}
$res_examenes->free();


$archivo_url = ($guia_oficial && !empty($guia_oficial['archivo_url'])) ? $guia_oficial['archivo_url'] : null;
$extension = strtolower(pathinfo($archivo_url ?? '', PATHINFO_EXTENSION));

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
.ranking-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
.ranking-table th, .ranking-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
.ranking-table th { background-color: #5a4d3c; color: white; }
select { padding: 5px 10px; margin: 10px 0; border-radius: 5px; }
</style>
</head>
<body>
<header>
    <h1>Panel de Usuario</h1>
    <div class="header-right">
        <span class="username">Usuario: <?= htmlspecialchars($usuario['nombre']) ?></span>
        <button class="btn-perfil" onclick="location.href='perfil1.php'">Perfil</button>
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
    <h2 style="color: #000;">Bienvenido al sistema de admisiones ZAGA Z</h2>
    <p>
        <strong>Email registrado:</strong>
        <?= htmlspecialchars($usuario['correo_electronico']) ?>
    </p>

    <p>
        <strong>Rol:</strong> 
         <?= $es_admin ? "Administrador" : "Postulante" ?>
    </p>
   
    <p> 
        <strong>Calificación del examen:</strong> 
        <?= $puntaje_examen !== null ? $puntaje_examen . " puntos" : "Pendiente de calificación" ?> 
    </p>

    <div class="puntaje-container">
        <div class="puntaje-titulo">📊 Resultado de tu Examen de Admisión</div>
        
        <?php if ($puntaje_examen !== null): ?>
            <div class="puntaje-valor"><?= $puntaje_examen ?> puntos</div>
            <p>¡Tu examen ha sido calificado exitosamente!</p>
        <?php else: ?>
            <div class="puntaje-pendiente">⏳ Tu examen aún no ha sido calificado</div>
            <p>Revisa más tarde para ver tu resultado.</p>
        <?php endif; ?>
    </div>

<style>
.guia-section {
    margin-top: 25px; 
}
</style>
<section class="guia-section">
    <h2> Guía oficial</h2>
    <form method="get" action="">
        <label for="facultad_guia">Filtrar por Facultad:</label>
        <select name="facultad_guia_id" id="facultad_guia" onchange="this.form.submit()">
            <option value="0">-- Todas las Facultades --</option>
            <?php foreach ($facultades as $f): ?>
                <option value="<?= $f['id_facultad'] ?>" <?= $selected_facultad_guia_id === (int)$f['id_facultad'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="facultad_id" value="<?= $selected_facultad_id ?>">
    </form>

    <?php if ($archivo_url && in_array($extension, ['jpg','jpeg','png','gif'])): ?>
        <div style="text-align:center; margin-top:20px;">
            <img src="<?= htmlspecialchars($archivo_url) ?>" 
                 style="max-width:100%; height:auto; border:none;">
        </div>
    <?php elseif ($archivo_url): ?>
        <p>Este recurso no es una imagen: <?= htmlspecialchars($archivo_url) ?></p>
    <?php else: ?>
        <p>No hay guía disponible para esta facultad.</p>
    <?php endif; ?>
</section>


<section class="examenes-section">
    <h2>EXAMENES PASADOS</h2>
    
    <form method="get" action="">
        <label for="facultad_examen">Filtrar por Facultad:</label>
        <select name="facultad_examen_id" id="facultad_examen" onchange="this.form.submit()">
            <option value="0">-- Todas las Facultades --</option>
            <?php foreach ($facultades as $f): ?>
                <option value="<?= $f['id_facultad'] ?>" <?= $selected_facultad_examen_id === (int)$f['id_facultad'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <?php if (isset($_GET['facultad_id']) && $_GET['facultad_id'] != 0): ?>
            <input type="hidden" name="facultad_id" value="<?= $_GET['facultad_id'] ?>">
        <?php endif; ?>
        <?php if (isset($_GET['facultad_guia_id']) && $_GET['facultad_guia_id'] != 0): ?>
            <input type="hidden" name="facultad_guia_id" value="<?= $_GET['facultad_guia_id'] ?>">
        <?php endif; ?>
    </form>

    <?php if (!empty($examenes_pasados)): ?>
        <table class="ranking-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo - Gestión</th>
                    <th>Facultad</th>
                    <th>Archivo</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($examenes_pasados as $index => $examen): 
                    $nombre_archivo = basename($examen['archivo_url']);
                    $gestion = date('Y', strtotime($examen['fecha_publicacion']));
                    $semestre = (date('n', strtotime($examen['fecha_publicacion'])) <= 6) ? '1er Semestre' : '2do Semestre';
                    
                    $tipo_examen = "Examen de Ingreso";
                    if (strpos($nombre_archivo, 'Primer') !== false) {
                        $tipo_examen = "Primer Examen";
                    } elseif (strpos($nombre_archivo, 'Segundo') !== false) {
                        $tipo_examen = "Segundo Examen";
                    } elseif (strpos($nombre_archivo, 'Unico') !== false) {
                        $tipo_examen = "Único Examen";
                    } elseif (strpos($nombre_archivo, 'Parcial') !== false) {
                        $tipo_examen = "Examen Parcial";
                    }
                ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= $tipo_examen ?> - <?= $semestre ?> <?= $gestion ?></td>
                        <td><?= htmlspecialchars($examen['facultad_nombre'] ?? 'General') ?></td>
                        <td><?= $nombre_archivo ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($examen['archivo_url']) ?>" target="_blank">
                                Ver
                            </a>
                            <?php if ($examen['permitir_descarga']): ?>
                                <a href="<?= htmlspecialchars($examen['archivo_url']) ?>" download>
                                    Descargar
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>
            <?php if ($selected_facultad_examen_id > 0): ?>
                No hay exámenes pasados disponibles para la facultad seleccionada.
            <?php else: ?>
                No hay exámenes pasados disponibles en este momento.
            <?php endif; ?>
        </p>
    <?php endif; ?>

     <div class="page-end">
          <p2>Fin del contenido - Admisiones ZAGA Z</p2>
     </div>
</section>
</main>
</body>
</html>
