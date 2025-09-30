<?php
session_start();

// Configuración para mostrar errores (quitar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'admisiones_unificadas');

// Conexión a la base de datos con manejo de errores
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}

// Usuario por defecto para acceso libre
$usuario = [
    'nombre' => 'Usuario Invitado',
    'correo_electronico' => 'invitado@ejemplo.com',
    'rol' => 'invitado'
];

$es_admin = false;

// Si hay sesión activa, cargar datos reales del usuario
if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])) {
    $id_usuario = (int) $_SESSION['id_usuario'];
    
    $stmt = $conn->prepare("SELECT nombre, correo_electronico, rol FROM usuarios WHERE id_usuario = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            $es_admin = ($usuario['rol'] === 'admin');
        }
        $stmt->close();
    }
}

// Obtener facultades
$facultades = [];
$res = $conn->query("SELECT id_facultad, nombre FROM facultades ORDER BY nombre");
if ($res) {
    while ($f = $res->fetch_assoc()) {
        $facultades[] = $f;
    }
    $res->free();
}

// Parámetros de filtro
$selected_facultad_id = isset($_GET['facultad_id']) ? (int)$_GET['facultad_id'] : 0;
$selected_facultad_guia_id = isset($_GET['facultad_guia_id']) ? (int)$_GET['facultad_guia_id'] : 0;

// Obtener guía oficial - CORREGIDO: usar carrera_id en lugar de facultad_id
$guia_oficial = null;
$guia_query = "SELECT archivo_url FROM recursos WHERE tipo = 'guia' AND estado = 'activo'";

if ($selected_facultad_guia_id > 0) {
    // Para filtrar por facultad, necesitamos unir con la tabla carreras
    $guia_query .= " AND carrera_id IN (SELECT id_carrera FROM carreras WHERE facultad_id = $selected_facultad_guia_id)";
}
$guia_query .= " LIMIT 1";

$res = $conn->query($guia_query);
if ($res && $res->num_rows > 0) {
    $guia_oficial = $res->fetch_assoc();
}

// Obtener puntaje del examen (solo si hay usuario logueado)
$puntaje_examen = null;
if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])) {
    $id_usuario = (int) $_SESSION['id_usuario'];
    $stmt_puntaje = $conn->prepare("SELECT puntaje_examen FROM inscripciones WHERE Id_postulante = ?");
    if ($stmt_puntaje) {
        $stmt_puntaje->bind_param("i", $id_usuario);
        $stmt_puntaje->execute();
        $result_puntaje = $stmt_puntaje->get_result();
        
        if ($result_puntaje->num_rows > 0) {
            $puntaje_data = $result_puntaje->fetch_assoc();
            $puntaje_examen = $puntaje_data['puntaje_examen'] ?? null;
        }
        $stmt_puntaje->close();
    }
}

// Filtro para exámenes
$selected_facultad_examen_id = isset($_GET['facultad_examen_id']) ? (int)$_GET['facultad_examen_id'] : 0;

// Obtener exámenes pasados - CORREGIDO: usar carrera_id y unir con carreras para obtener facultad
$examenes_pasados = [];
$examenes_query = "
    SELECT r.titulo, r.archivo_url, r.fecha_publicacion, r.permitir_descarga, 
           f.nombre as facultad_nombre, f.id_facultad,
           c.nombre as carrera_nombre
    FROM recursos r 
    LEFT JOIN carreras c ON r.carrera_id = c.id_carrera 
    LEFT JOIN facultades f ON c.facultad_id = f.id_facultad 
    WHERE r.tipo = 'archivo' AND r.estado = 'activo'
";

if ($selected_facultad_examen_id > 0) {
    $examenes_query .= " AND c.facultad_id = " . $selected_facultad_examen_id;
}

$examenes_query .= " ORDER BY r.fecha_publicacion DESC";

$res_examenes = $conn->query($examenes_query);
if ($res_examenes) {
    while ($examen = $res_examenes->fetch_assoc()) {
        $examenes_pasados[] = $examen;
    }
    $res_examenes->free();
}

// Procesar archivo de guía
$archivo_url = ($guia_oficial && !empty($guia_oficial['archivo_url'])) ? $guia_oficial['archivo_url'] : null;
$extension = '';
if ($archivo_url) {
    $extension = strtolower(pathinfo($archivo_url, PATHINFO_EXTENSION));
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

</head>
<body>
<header>
    <h1>Panel de Usuario - Acceso Libre</h1>
    <div class="header-right">
        <span class="username">Usuario: <?= htmlspecialchars($usuario['nombre']) ?></span>
        <?php if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])): ?>
            <button class="btn-perfil" onclick="location.href='perfil1.php'">Perfil</button>
        <?php else: ?>
            <button class="btn-perfil" onclick="location.href='login.html'">Iniciar Sesión</button>
        <?php endif; ?>
        <div class="nav-menu" tabindex="0">
            <button>Menú ▼</button>
            <div class="nav-menu-content">
                <a href="biblioteca.php">Biblioteca</a>
                <?php if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])): ?>
                <?php endif; ?>
                <?php if ($es_admin): ?>
                    <a href="admin_panel.php" title="Administración">Administrar</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])): ?>
                    <a href="login.html">Cerrar sesión</a>
                <?php else: ?>
                    <a href="login.html">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<main>
    <h2 style="color: #000;">Bienvenido al sistema de admisiones ZAGA Z</h2>
    
    <?php if (!isset($_SESSION['id_usuario']) || empty($_SESSION['id_usuario'])): ?>
        <div class="login-prompt">
            <strong>🔓 Acceso Libre</strong> - Estás navegando como invitado. 
            <a href="login.html" style="color: #007bff; font-weight: bold;">Inicia sesión</a> para acceder a todas las funciones.
        </div>
    <?php endif; ?>
    
    <p>
        <strong>Email:</strong>
        <?= htmlspecialchars($usuario['correo_electronico']) ?>
    </p>

    <p>
        <strong>Rol:</strong> 
        <?= $es_admin ? "Administrador" : ($usuario['rol'] === 'invitado' ? "Invitado" : "Postulante") ?>
    </p>
   
    <?php if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])): ?>
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
    <?php else: ?>
        <div class="puntaje-container">
            <div class="puntaje-titulo">🔒 Acceso a Resultados</div>
            <p>Para ver tus resultados de examen, necesitas <a href="login.html" style="color: #007bff; font-weight: bold;">iniciar sesión</a>.</p>
        </div>
    <?php endif; ?>

    <section class="guia-section">
        <h2>📚 Guía Oficial de Admisión</h2>
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
                     style="max-width:100%; height:auto; border:none; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <p><small>Guía oficial de admisión</small></p>
            </div>
        <?php elseif ($archivo_url): ?>
            <div style="text-align:center; margin-top:20px;">
                <p>📄 <strong>Documento disponible:</strong></p>
                <a href="<?= htmlspecialchars($archivo_url) ?>" target="_blank" 
                   style="display: inline-block; background: #b3a575; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">
                    Ver Guía Completa
                </a>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #666; margin-top: 20px;">
                No hay guía disponible para esta facultad en este momento.
            </p>
        <?php endif; ?>
    </section>

    <section class="examenes-section">
        <h2>📝 Exámenes Pasados</h2>
        
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
                <input type="hidden" name="facultad_id" value="<?= htmlspecialchars($_GET['facultad_id']) ?>">
            <?php endif; ?>
            <?php if (isset($_GET['facultad_guia_id']) && $_GET['facultad_guia_id'] != 0): ?>
                <input type="hidden" name="facultad_guia_id" value="<?= htmlspecialchars($_GET['facultad_guia_id']) ?>">
            <?php endif; ?>
        </form>

        <?php if (!empty($examenes_pasados)): ?>
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo - Gestión</th>
                        <th>Facultad</th>
                        <th>Carrera</th>
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
                            <td><?= htmlspecialchars($tipo_examen) ?> - <?= $semestre ?> <?= $gestion ?></td>
                            <td><?= htmlspecialchars($examen['facultad_nombre'] ?? 'General') ?></td>
                            <td><?= htmlspecialchars($examen['carrera_nombre'] ?? 'General') ?></td>
                            <td><?= htmlspecialchars($nombre_archivo) ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($examen['archivo_url']) ?>" target="_blank" style="margin-right: 10px;">
                                    👁️ Ver
                                </a>
                                <?php if ($examen['permitir_descarga']): ?>
                                    <a href="<?= htmlspecialchars($examen['archivo_url']) ?>" download>
                                        📥 Descargar
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666; margin-top: 20px;">
                <?php if ($selected_facultad_examen_id > 0): ?>
                    No hay exámenes pasados disponibles para la facultad seleccionada.
                <?php else: ?>
                    No hay exámenes pasados disponibles en este momento.
                <?php endif; ?>
            </p>
        <?php endif; ?>

         <div class="page-end">
              <p>Fin del contenido - Admisiones ZAGA Z</p>
         </div>
    </section>
</main>
</body>
</html>