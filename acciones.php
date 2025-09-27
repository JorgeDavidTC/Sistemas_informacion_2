<?php
require_once 'conexion.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$accion = $_REQUEST['accion'] ?? '';

switch($accion) {
    case 'cargar_carreras':
        cargarCarreras($db);
        break;
        
    case 'cargar_postulantes':
        cargarPostulantes($db);
        break;
        
    case 'guardar_postulante':
        guardarPostulante($db);
        break;
        
    case 'cambiar_estado':
        cambiarEstado($db);
        break;
        
    case 'programar_examen':
        programarExamen($db);
        break;
        
    case 'cargar_periodos':
        cargarPeriodos($db);
        break;
        
    default:
        echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida']);
}

function cargarCarreras($db) {
    $consulta = "SELECT id, nombre, codigo, duracion_semestres, modalidad, cupos_disponibles 
              FROM carreras 
              WHERE activa = 1 
              ORDER BY nombre";
    $stmt = $db->prepare($consulta);
    $stmt->execute();
    
    $carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($carreras);
}

function cargarPeriodos() {
    $periodos = [];
    $año_actual = date('Y');
    
    for ($i = 0; $i < 3; $i++) {
        $año = $año_actual + $i;
        $periodos[] = $año . '-I';
        $periodos[] = $año . '-II';
    }
    
    echo json_encode($periodos);
}

function cargarPostulantes($db) {
    $carrera_id = $_GET['carrera_id'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $documento = $_GET['documento'] ?? '';
    $periodo = $_GET['periodo'] ?? '';
    
    $consulta = "SELECT p.*, c.nombre as nombre_carrera, c.codigo as codigo_carrera
              FROM postulantes p 
              INNER JOIN carreras c ON p.carrera_id = c.id 
              WHERE 1=1";
    
    $parametros = [];
    
    if (!empty($carrera_id)) {
        $consulta .= " AND p.carrera_id = ?";
        $parametros[] = $carrera_id;
    }
    
    if (!empty($estado)) {
        $consulta .= " AND p.estado = ?";
        $parametros[] = $estado;
    }
    
    if (!empty($documento)) {
        $consulta .= " AND p.documento_numero LIKE ?";
        $parametros[] = "%$documento%";
    }
    
    if (!empty($periodo)) {
        $consulta .= " AND p.periodo_postulacion = ?";
        $parametros[] = $periodo;
    }
    
    $consulta .= " ORDER BY p.fecha_postulacion DESC";
    
    $stmt = $db->prepare($consulta);
    $stmt->execute($parametros);
    
    $postulantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($postulantes as &$postulante) {
        $postulante['nombre_completo'] = $postulante['nombre'] . ' ' . $postulante['apellido'];
        $postulante['fecha_postulacion'] = date('d/m/Y H:i', strtotime($postulante['fecha_postulacion']));
        
        $fecha_nac = new DateTime($postulante['fecha_nacimiento']);
        $hoy = new DateTime();
        $postulante['edad'] = $hoy->diff($fecha_nac)->y;
        
        if ($postulante['fecha_examen']) {
            $postulante['fecha_examen'] = date('d/m/Y H:i', strtotime($postulante['fecha_examen']));
        }
        
        $postulante['fecha_nacimiento'] = date('d/m/Y', strtotime($postulante['fecha_nacimiento']));
    }
    
    echo json_encode($postulantes);
}

function guardarPostulante($db) {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $documento_tipo = $_POST['documento_tipo'] ?? 'DNI';
    $documento_numero = $_POST['documento_numero'] ?? '';
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $genero = $_POST['genero'] ?? 'Otro';
    $nacionalidad = $_POST['nacionalidad'] ?? 'bolivia';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $celular = $_POST['celular'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';
    $departamento = $_POST['departamento'] ?? '';
    $colegio_procedencia = $_POST['colegio_procedencia'] ?? '';
    $tipo_colegio = $_POST['tipo_colegio'] ?? 'Público';
    $año_egreso = $_POST['año_egreso'] ?? '';
    $promedio_secundaria = $_POST['promedio_secundaria'] ?? '';
    $carrera_id = $_POST['carrera_id'] ?? '';
    $periodo_postulacion = $_POST['periodo_postulacion'] ?? '';
    
    $campos_obligatorios = [
        'nombre', 'apellido', 'documento_numero', 'fecha_nacimiento', 'email',
        'carrera_id', 'periodo_postulacion'
    ];
    
    foreach ($campos_obligatorios as $campo) {
        if (empty($$campo)) {
            echo json_encode(['exito' => false, 'mensaje' => 'El campo ' . $campo . ' es obligatorio']);
            return;
        }
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['exito' => false, 'mensaje' => 'El formato del email no es válido']);
        return;
    }
    
    $consulta = "SELECT id FROM postulantes WHERE documento_numero = ?";
    $stmt = $db->prepare($consulta);
    $stmt->execute([$documento_numero]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['exito' => false, 'mensaje' => 'El documento ya está registrado']);
        return;
    }
    
    $consulta = "SELECT id FROM postulantes WHERE email = ?";
    $stmt = $db->prepare($consulta);
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['exito' => false, 'mensaje' => 'El email ya está registrado']);
        return;
    }
    
    $consulta = "INSERT INTO postulantes (
                nombre, apellido, documento_tipo, documento_numero, fecha_nacimiento, genero,
                nacionalidad, email, telefono, celular, direccion, ciudad, departamento,
                colegio_procedencia, tipo_colegio, año_egreso, promedio_secundaria,
                carrera_id, periodo_postulacion
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($consulta);
    
    $parametros = [
        $nombre, $apellido, $documento_tipo, $documento_numero, $fecha_nacimiento, $genero,
        $nacionalidad, $email, $telefono, $celular, $direccion, $ciudad, $departamento,
        $colegio_procedencia, $tipo_colegio, $año_egreso, $promedio_secundaria,
        $carrera_id, $periodo_postulacion
    ];
    
    if ($stmt->execute($parametros)) {
        echo json_encode(['exito' => true, 'mensaje' => 'Postulante guardado correctamente']);
    } else {
        echo json_encode(['exito' => false, 'mensaje' => 'Error al guardar el postulante']);
    }
}

function cambiarEstado($db) {
    $postulante_id = $_POST['postulante_id'] ?? '';
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    
    if (empty($postulante_id) || empty($nuevo_estado)) {
        echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos']);
        return;
    }
    
    $consulta = "UPDATE postulantes SET estado = ? WHERE id = ?";
    $stmt = $db->prepare($consulta);
    
    if ($stmt->execute([$nuevo_estado, $postulante_id])) {
        echo json_encode(['exito' => true, 'mensaje' => 'Estado actualizado correctamente']);
    } else {
        echo json_encode(['exito' => false, 'mensaje' => 'Error al actualizar el estado']);
    }
}

function programarExamen($db) {
    $postulante_id = $_POST['postulante_id'] ?? '';
    $fecha_examen = $_POST['fecha_examen'] ?? '';
    $aula_examen = $_POST['aula_examen'] ?? '';
    $nota_examen = $_POST['nota_examen'] ?? null;
    
    if (empty($postulante_id) || empty($fecha_examen) || empty($aula_examen)) {
        echo json_encode(['exito' => false, 'mensaje' => 'Todos los campos son obligatorios']);
        return;
    }
    
    $consulta = "UPDATE postulantes SET fecha_examen = ?, aula_examen = ?, nota_examen = ?, estado = 'habilitado' WHERE id = ?";
    $stmt = $db->prepare($consulta);
    
    if ($stmt->execute([$fecha_examen, $aula_examen, $nota_examen, $postulante_id])) {
        echo json_encode(['exito' => true, 'mensaje' => 'Examen programado correctamente']);
    } else {
        echo json_encode(['exito' => false, 'mensaje' => 'Error al programar el examen']);
    }
}
?>