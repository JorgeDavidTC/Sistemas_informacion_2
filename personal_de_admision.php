<?php
session_start();
require_once 'conexion.php';

class DashboardManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getEstadisticasDashboard() {
        $estadisticas = [];
        
        $queries = [
            'total_postulantes' => "SELECT COUNT(*) as total FROM postulantes",
            'documentos_pendientes' => "SELECT COUNT(*) as total FROM documentos_postulantes WHERE estado_validacion = 'pendiente'",
            'inscripciones_activas' => "SELECT COUNT(*) as total FROM inscripciones WHERE estado_inscripcion IN ('inscrito', 'confirmada')",
            'total_carreras' => "SELECT COUNT(*) as total FROM carreras WHERE estado = 'activa'"
        ];
        
        foreach ($queries as $key => $query) {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $estadisticas[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        }
        
        return $estadisticas;
    }
    
    public function getPostulantesPorCarrera() {
        $query = "SELECT c.nombre as carrera, COUNT(i.id_inscripcion) as cantidad 
                 FROM carreras c 
                 LEFT JOIN inscripciones i ON c.id_carrera = i.id_carrera 
                 WHERE c.estado = 'activa'
                 GROUP BY c.id_carrera, c.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getEstadoDocumentos() {
        $query = "SELECT estado_validacion, COUNT(*) as cantidad 
                 FROM documentos_postulantes 
                 GROUP BY estado_validacion";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class PostulanteManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function buscarPostulantes($busqueda = null) {
        if ($busqueda) {
            $query = "SELECT p.*, i.numero_folio, c.nombre as carrera_nombre, i.estado_inscripcion
                     FROM postulantes p 
                     LEFT JOIN inscripciones i ON p.id_postulante = i.id_postulante 
                     LEFT JOIN carreras c ON i.id_carrera = c.id_carrera 
                     WHERE p.nombres LIKE ? OR p.apellido_paterno LIKE ? OR p.apellido_materno LIKE ? OR p.ci LIKE ? OR i.numero_folio LIKE ?
                     ORDER BY p.creado_en DESC";
            $stmt = $this->conn->prepare($query);
            $param = "%$busqueda%";
            $stmt->execute([$param, $param, $param, $param, $param]);
        } else {
            $query = "SELECT p.*, i.numero_folio, c.nombre as carrera_nombre, i.estado_inscripcion
                     FROM postulantes p 
                     LEFT JOIN inscripciones i ON p.id_postulante = i.id_postulante 
                     LEFT JOIN carreras c ON i.id_carrera = c.id_carrera 
                     ORDER BY p.creado_en DESC 
                     LIMIT 50";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function crearPostulante($datos) {
        $query = "INSERT INTO postulantes (nombres, apellido_paterno, apellido_materno, ci, fecha_nacimiento, telefono, direccion_residencia, nacionalidad) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['nombres'],
            $datos['apellido_paterno'],
            $datos['apellido_materno'],
            $datos['ci'],
            $datos['fecha_nacimiento'],
            $datos['telefono'],
            $datos['direccion'],
            $datos['nacionalidad']
        ]);
    }
    
    public function obtenerPostulante($id) {
        $query = "SELECT p.*, i.id_inscripcion, i.numero_folio, i.id_carrera, i.estado_inscripcion
                 FROM postulantes p 
                 LEFT JOIN inscripciones i ON p.id_postulante = i.id_postulante 
                 WHERE p.id_postulante = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function actualizarPostulante($id, $datos) {
        $query = "UPDATE postulantes SET nombres = ?, apellido_paterno = ?, apellido_materno = ?, ci = ?, fecha_nacimiento = ?, telefono = ?, direccion_residencia = ?, nacionalidad = ? WHERE id_postulante = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['nombres'],
            $datos['apellido_paterno'],
            $datos['apellido_materno'],
            $datos['ci'],
            $datos['fecha_nacimiento'],
            $datos['telefono'],
            $datos['direccion'],
            $datos['nacionalidad'],
            $id
        ]);
    }
}

class DocumentoManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getDocumentos($filtro_estado = 'todos') {
        if ($filtro_estado == 'todos') {
            $query = "SELECT dp.*, p.nombres, p.apellido_paterno, p.apellido_materno, dr.nombre_documento
                     FROM documentos_postulantes dp
                     JOIN postulantes p ON dp.postulante_id = p.id_postulante
                     LEFT JOIN documentos_requeridos dr ON dp.documento_req_id = dr.id_documento_req
                     ORDER BY dp.fecha_carga DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        } else {
            $query = "SELECT dp.*, p.nombres, p.apellido_paterno, p.apellido_materno, dr.nombre_documento
                     FROM documentos_postulantes dp
                     JOIN postulantes p ON dp.postulante_id = p.id_postulante
                     LEFT JOIN documentos_requeridos dr ON dp.documento_req_id = dr.id_documento_req
                     WHERE dp.estado_validacion = ?
                     ORDER BY dp.fecha_carga DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$filtro_estado]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function validarDocumento($id_doc, $estado, $comentario = '') {
        $query = "UPDATE documentos_postulantes SET estado_validacion = ?, personal_validador_id = ?, fecha_validacion = NOW(), comentario = ? WHERE id_doc = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$estado, $_SESSION['usuario']['id_usuario'], $comentario, $id_doc]);
    }
}

class InscripcionManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getInscripciones() {
        $query = "SELECT i.*, p.nombres, p.apellido_paterno, p.apellido_materno, c.nombre as carrera_nombre, pa.nombre_periodo
                 FROM inscripciones i
                 JOIN postulantes p ON i.id_postulante = p.id_postulante
                 JOIN carreras c ON i.id_carrera = c.id_carrera
                 JOIN periodos_academicos pa ON i.periodo_id = pa.id_periodo
                 ORDER BY i.fecha_inscripcion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function crearInscripcion($datos) {
        // Generar número de folio único
        $folio = 'FOL-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $query = "INSERT INTO inscripciones (id_postulante, id_carrera, periodo_id, numero_folio, estado_inscripcion) 
                 VALUES (?, ?, ?, ?, 'inscrito')";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['id_postulante'],
            $datos['id_carrera'],
            $datos['periodo_id'],
            $folio
        ]);
    }
    
    public function eliminarInscripcion($id) {
        $query = "DELETE FROM inscripciones WHERE id_inscripcion = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
}

class ResultadoManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getResultados($filtro_carrera = 'todas') {
        if ($filtro_carrera == 'todas') {
            $query = "SELECT r.*, p.nombres, p.apellido_paterno, p.apellido_materno, c.nombre as carrera_nombre, i.numero_folio
                     FROM resultados r
                     JOIN postulantes p ON r.id_postulante = p.id_postulante
                     JOIN carreras c ON r.id_carrera = c.id_carrera
                     LEFT JOIN inscripciones i ON r.id_postulante = i.id_postulante AND r.id_carrera = i.id_carrera
                     ORDER BY r.fecha_resultado DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        } else {
            $query = "SELECT r.*, p.nombres, p.apellido_paterno, p.apellido_materno, c.nombre as carrera_nombre, i.numero_folio
                     FROM resultados r
                     JOIN postulantes p ON r.id_postulante = p.id_postulante
                     JOIN carreras c ON r.id_carrera = c.id_carrera
                     LEFT JOIN inscripciones i ON r.id_postulante = i.id_postulante AND r.id_carrera = i.id_carrera
                     WHERE r.id_carrera = ?
                     ORDER BY r.fecha_resultado DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$filtro_carrera]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function crearResultado($datos) {
        // Generar folio de consulta único
        $folio_consulta = 'RES-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $query = "INSERT INTO resultados (id_postulante, id_carrera, folio_consulta, puntaje, aprobado) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['id_postulante'],
            $datos['id_carrera'],
            $folio_consulta,
            $datos['puntaje'],
            $datos['aprobado']
        ]);
    }
    
    public function actualizarResultado($id, $datos) {
        $query = "UPDATE resultados SET puntaje = ?, aprobado = ? WHERE id_resultado = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['puntaje'],
            $datos['aprobado'],
            $id
        ]);
    }
}

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'validar_documento':
            $documentoManager = new DocumentoManager($conn);
            $result = $documentoManager->validarDocumento(
                $_POST['doc_id'],
                $_POST['estado'],
                $_POST['comentario'] ?? ''
            );
            echo json_encode(['success' => $result]);
            exit;
            
        case 'crear_postulante':
            $postulanteManager = new PostulanteManager($conn);
            $result = $postulanteManager->crearPostulante($_POST);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'crear_inscripcion':
            $inscripcionManager = new InscripcionManager($conn);
            $result = $inscripcionManager->crearInscripcion($_POST);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'eliminar_inscripcion':
            $inscripcionManager = new InscripcionManager($conn);
            $result = $inscripcionManager->eliminarInscripcion($_POST['id_inscripcion']);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'crear_resultado':
            $resultadoManager = new ResultadoManager($conn);
            $result = $resultadoManager->crearResultado($_POST);
            echo json_encode(['success' => $result]);
            exit;
    }
}

$database = new Database();
$conn = $database->getConnection();

$dashboardManager = new DashboardManager($conn);
$postulanteManager = new PostulanteManager($conn);
$documentoManager = new DocumentoManager($conn);
$inscripcionManager = new InscripcionManager($conn);
$resultadoManager = new ResultadoManager($conn);

$estadisticas = $dashboardManager->getEstadisticasDashboard();
$postulantesCarrera = $dashboardManager->getPostulantesPorCarrera();
$estadoDocumentos = $dashboardManager->getEstadoDocumentos();

$busqueda = isset($_GET['buscar']) && !empty($_GET['buscar']) ? $_GET['buscar'] : null;
$postulantes = $postulanteManager->buscarPostulantes($busqueda);

$filtro_estado = isset($_GET['estado_documentos']) ? $_GET['estado_documentos'] : 'todos';
$documentos = $documentoManager->getDocumentos($filtro_estado);

$inscripciones = $inscripcionManager->getInscripciones();

$filtro_carrera = isset($_GET['carrera_resultados']) ? $_GET['carrera_resultados'] : 'todas';
$resultados = $resultadoManager->getResultados($filtro_carrera);

// Obtener datos para formularios
$query_carreras = "SELECT id_carrera, nombre FROM carreras WHERE estado = 'activa'";
$stmt_carreras = $conn->prepare($query_carreras);
$stmt_carreras->execute();
$carreras = $stmt_carreras->fetchAll(PDO::FETCH_ASSOC);

$query_periodos = "SELECT id_periodo, nombre_periodo FROM periodos_academicos WHERE estado = 'activo'";
$stmt_periodos = $conn->prepare($query_periodos);
$stmt_periodos->execute();
$periodos = $stmt_periodos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Personal de Admisión</title>
    <link rel="stylesheet" href="personal_de_admision.css">
    
</head>
<body>
    <div class="contenedor-principal">
        <nav class="barra-navegacion">
            <div class="logo">
                <h1>Sistema de Admisiones</h1>
            </div>
            <div class="info-usuario">
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']['nombre']); ?></span>
                <button id="btn-cerrar-sesion">Cerrar Sesión</button>
            </div>
        </nav>
        <aside class="menu-lateral">
            <ul class="lista-menu">
                <li><a href="#" class="enlace-menu activo" data-seccion="dashboard">Panel de Inicio</a></li>
                <li><button class="btn-menu" onclick="window.location.href='gestion_postulantes.php'">Gestión de Postulantes</button></li>
                <li><button class="btn-menu" onclick="window.location.href='validacion_documentos.php'">Validación de Documentos</button></li>
                <li><button class="btn-menu" onclick="window.location.href='programar_examen.php'">Programar Examen</button></li>
                <li><button class="btn-menu" onclick="window.location.href='resultados.php'">Resultados</button></li>
            </ul>
        </aside>

        <main class="contenido-principal">
            <section id="dashboard" class="seccion-contenido activo">
                <h2>Dashboard</h2>
                <div class="tarjetas-resumen">
                    <div class="tarjeta">
                        <h3>Total Postulantes</h3>
                        <p id="total-postulantes"><?php echo $estadisticas['total_postulantes']; ?></p>
                    </div>
                    <div class="tarjeta">
                        <h3>Documentos Pendientes</h3>
                        <p id="documentos-pendientes"><?php echo $estadisticas['documentos_pendientes']; ?></p>
                    </div>
                    <div class="tarjeta">
                        <h3>Inscripciones Activas</h3>
                        <p id="inscripciones-activas"><?php echo $estadisticas['inscripciones_activas']; ?></p>
                    </div>
                    <div class="tarjeta">
                        <h3>Periodo Actual</h3>
                        <p id="periodo-actual">Gestión 2025-I</p>
                    </div>
                </div>
                <div class="graficos-dashboard">
                    <div class="grafico">
                        <h3>Postulantes por Carrera</h3>
                        <canvas id="grafico-carreras"></canvas>
                    </div>
                    <div class="grafico">
                        <h3>Estado de Documentos</h3>
                        <canvas id="grafico-documentos"></canvas>
                    </div>
                </div>
            </section>

            <section id="postulantes" class="seccion-contenido">
                <h2>Gestión de Postulantes</h2>
                <div class="controles-busqueda">
                    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" name="buscar" id="buscar-postulante" placeholder="Buscar por nombre, CI o folio..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                        <button type="submit" id="btn-buscar-postulante">Buscar</button>
                        <button type="button" id="btn-nuevo-postulante">Nuevo Postulante</button>
                    </form>
                </div>
                <div class="tabla-contenedor">
                    <table id="tabla-postulantes">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombres</th>
                                <th>CI</th>
                                <th>Carrera</th>
                                <th>Estado</th>
                                <th>Fecha Inscripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla-postulantes">
                            <?php foreach ($postulantes as $postulante): ?>
                            <tr>
                                <td><?php echo $postulante['id_postulante']; ?></td>
                                <td><?php echo htmlspecialchars($postulante['nombres'] . ' ' . $postulante['apellido_paterno'] . ' ' . $postulante['apellido_materno']); ?></td>
                                <td><?php echo htmlspecialchars($postulante['ci']); ?></td>
                                <td><?php echo htmlspecialchars($postulante['carrera_nombre'] ?? 'No asignada'); ?></td>
                                <td><?php echo htmlspecialchars($postulante['estado_postulacion']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($postulante['creado_en'])); ?></td>
                                <td>
                                    <button class="btn-ver" data-id="<?php echo $postulante['id_postulante']; ?>">Ver</button>
                                    <button class="btn-editar" data-id="<?php echo $postulante['id_postulante']; ?>">Editar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="documentos" class="seccion-contenido">
                <h2>Validación de Documentos</h2>
                <div class="filtros">
                    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <select name="estado_documentos" id="filtro-estado-documentos">
                            <option value="todos" <?php echo $filtro_estado == 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                            <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                            <option value="aprobado" <?php echo $filtro_estado == 'aprobado' ? 'selected' : ''; ?>>Aprobados</option>
                            <option value="rechazado" <?php echo $filtro_estado == 'rechazado' ? 'selected' : ''; ?>>Rechazados</option>
                        </select>
                        <button type="submit" id="btn-aplicar-filtro">Aplicar Filtro</button>
                    </form>
                </div>
                <div class="tabla-contenedor">
                    <table id="tabla-documentos">
                        <thead>
                            <tr>
                                <th>Postulante</th>
                                <th>Documento</th>
                                <th>Fecha Carga</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla-documentos">
                            <?php foreach ($documentos as $documento): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($documento['nombres'] . ' ' . $documento['apellido_paterno'] . ' ' . $documento['apellido_materno']); ?></td>
                                <td><?php echo htmlspecialchars($documento['nombre_documento'] ?? $documento['tipo_documento']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($documento['fecha_carga'])); ?></td>
                                <td>
                                    <span class="estado-<?php echo $documento['estado_validacion']; ?>">
                                        <?php echo ucfirst($documento['estado_validacion']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-validar" data-id="<?php echo $documento['id_doc']; ?>" data-estado="aprobado">Aprobar</button>
                                    <button class="btn-validar" data-id="<?php echo $documento['id_doc']; ?>" data-estado="rechazado">Rechazar</button>
                                    <button class="btn-ver-doc" data-url="<?php echo htmlspecialchars($documento['archivo_url']); ?>">Ver</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="inscripciones" class="seccion-contenido">
                <h2>Gestión de Inscripciones</h2>
                <div class="controles-inscripciones">
                    <button id="btn-nueva-inscripcion">Nueva Inscripción</button>
                    <button id="btn-exportar-inscripciones">Exportar a Excel</button>
                </div>
                <div class="tabla-contenedor">
                    <table id="tabla-inscripciones">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Postulante</th>
                                <th>Carrera</th>
                                <th>Periodo</th>
                                <th>Estado</th>
                                <th>Puntaje</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla-inscripciones">
                            <?php foreach ($inscripciones as $inscripcion): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inscripcion['numero_folio']); ?></td>
                                <td><?php echo htmlspecialchars($inscripcion['nombres'] . ' ' . $inscripcion['apellido_paterno'] . ' ' . $inscripcion['apellido_materno']); ?></td>
                                <td><?php echo htmlspecialchars($inscripcion['carrera_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($inscripcion['nombre_periodo']); ?></td>
                                <td><?php echo htmlspecialchars($inscripcion['estado_inscripcion']); ?></td>
                                <td><?php echo $inscripcion['puntaje_examen'] ?? 'N/A'; ?></td>
                                <td>
                                    <button class="btn-editar-insc" data-id="<?php echo $inscripcion['id_inscripcion']; ?>">Editar</button>
                                    <button class="btn-eliminar-insc" data-id="<?php echo $inscripcion['id_inscripcion']; ?>">Eliminar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="resultados" class="seccion-contenido">
                <h2>Resultados de Exámenes</h2>
                <div class="controles-resultados">
                    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <select name="carrera_resultados" id="filtro-carrera-resultados">
                            <option value="todas">Todas las carreras</option>
                            <?php foreach ($carreras as $carrera): ?>
                            <option value="<?php echo $carrera['id_carrera']; ?>" <?php echo $filtro_carrera == $carrera['id_carrera'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($carrera['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" id="btn-cargar-resultados">Cargar Resultados</button>
                        <button type="button" id="btn-publicar-resultados">Publicar Resultados</button>
                    </form>
                </div>
                <div class="tabla-contenedor">
                    <table id="tabla-resultados">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Postulante</th>
                                <th>Carrera</th>
                                <th>Puntaje</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla-resultados">
                            <?php foreach ($resultados as $resultado): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($resultado['folio_consulta']); ?></td>
                                <td><?php echo htmlspecialchars($resultado['nombres'] . ' ' . $resultado['apellido_paterno'] . ' ' . $resultado['apellido_materno']); ?></td>
                                <td><?php echo htmlspecialchars($resultado['carrera_nombre']); ?></td>
                                <td><?php echo $resultado['puntaje'] ?? 'N/A'; ?></td>
                                <td><?php echo $resultado['aprobado'] ? 'Aprobado' : 'No Aprobado'; ?></td>
                                <td>
                                    <button class="btn-ver-resultado" data-id="<?php echo $resultado['id_resultado']; ?>">Ver</button>
                                    <button class="btn-editar-resultado" data-id="<?php echo $resultado['id_resultado']; ?>">Editar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="personal_de_admision.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const postulantesCarrera = <?php echo json_encode($postulantesCarrera); ?>;
            const estadoDocumentos = <?php echo json_encode($estadoDocumentos); ?>;

            const ctxCarreras = document.getElementById('grafico-carreras').getContext('2d');
            new Chart(ctxCarreras, {
                type: 'bar',
                data: {
                    labels: postulantesCarrera.map(item => item.carrera),
                    datasets: [{
                        label: 'Postulantes',
                        data: postulantesCarrera.map(item => item.cantidad),
                        backgroundColor: 'rgba(54, 162, 235, 0.8)'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            const ctxDocumentos = document.getElementById('grafico-documentos').getContext('2d');
            new Chart(ctxDocumentos, {
                type: 'pie',
                data: {
                    labels: estadoDocumentos.map(item => item.estado_validacion),
                    datasets: [{
                        data: estadoDocumentos.map(item => item.cantidad),
                        backgroundColor: [
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(255, 99, 132, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true
                }
            });

            document.querySelectorAll('.enlace-menu').forEach(enlace => {
                enlace.addEventListener('click', function(e) {
                    e.preventDefault();
                    const seccion = this.getAttribute('data-seccion');
                    
                    document.querySelectorAll('.enlace-menu').forEach(item => {
                        item.classList.remove('activo');
                    });
                    this.classList.add('activo');
                    
                    document.querySelectorAll('.seccion-contenido').forEach(seccion => {
                        seccion.classList.remove('activo');
                    });
                    document.getElementById(seccion).classList.add('activo');
                });
            });

            document.getElementById('btn-cerrar-sesion').addEventListener('click', function() {
                if (confirm('¿Está seguro de que desea cerrar sesión?')) {
                    window.location.href = 'logout.php';
                }
            });
        });
    </script>
</body>
</html>