<?php
require_once 'conexion.php';

class EstadisticasManager {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Obtener estadísticas generales
    public function getEstadisticasGenerales($periodo_id = null) {
        try {
            $where_periodo = $periodo_id ? "WHERE i.periodo_id = :periodo_id" : "";
            $params = $periodo_id ? [':periodo_id' => $periodo_id] : [];
            
            $query = "
                SELECT 
                    COUNT(DISTINCT p.id_postulante) as total_postulantes,
                    COUNT(DISTINCT i.id_carrera) as carreras_con_postulantes,
                    COUNT(DISTINCT i.id_inscripcion) as total_inscripciones,
                    AVG(i.puntaje_examen) as promedio_puntaje,
                    COUNT(DISTINCT CASE WHEN i.estado_inscripcion = 'admitido' THEN i.id_inscripcion END) as total_admitidos,
                    COUNT(DISTINCT CASE WHEN i.estado_inscripcion = 'no_admitido' THEN i.id_inscripcion END) as total_no_admitidos,
                    COUNT(DISTINCT CASE WHEN dp.estado_validacion = 'aprobado' THEN dp.id_doc END) as documentos_aprobados,
                    COUNT(DISTINCT CASE WHEN dp.estado_validacion = 'pendiente' THEN dp.id_doc END) as documentos_pendientes
                FROM postulantes p
                LEFT JOIN inscripciones i ON p.id_postulante = i.id_postulante
                LEFT JOIN documentos_postulantes dp ON p.id_postulante = dp.postulante_id
                $where_periodo
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getEstadisticasGenerales: " . $e->getMessage());
            return ['error' => 'Error al obtener estadísticas generales'];
        }
    }
    
    // Carreras más demandadas
    public function getCarrerasMasDemandadas($periodo_id = null, $limit = 10) {
        try {
            $where_periodo = $periodo_id ? "WHERE i.periodo_id = :periodo_id" : "";
            $params = $periodo_id ? [':periodo_id' => $periodo_id] : [];
            
            $query = "
                SELECT 
                    c.nombre as carrera,
                    c.codigo,
                    f.nombre as facultad,
                    COUNT(i.id_inscripcion) as total_inscripciones,
                    COUNT(CASE WHEN i.estado_inscripcion = 'admitido' THEN 1 END) as admitidos,
                    COUNT(CASE WHEN i.estado_inscripcion = 'no_admitido' THEN 1 END) as no_admitidos,
                    AVG(i.puntaje_examen) as promedio_puntaje,
                    c.cupos,
                    ROUND((COUNT(i.id_inscripcion) / NULLIF(c.cupos, 0)) * 100, 2) as porcentaje_demanda
                FROM carreras c
                LEFT JOIN facultades f ON c.facultad_id = f.id_facultad
                LEFT JOIN inscripciones i ON c.id_carrera = i.id_carrera
                $where_periodo
                GROUP BY c.id_carrera, c.nombre, c.codigo, f.nombre, c.cupos
                ORDER BY total_inscripciones DESC
                LIMIT :limit
            ";
            
            $stmt = $this->conn->prepare($query);
            if ($periodo_id) {
                $stmt->bindValue(':periodo_id', $periodo_id, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getCarrerasMasDemandadas: " . $e->getMessage());
            return ['error' => 'Error al obtener carreras demandadas'];
        }
    }
    
    // Evolución de postulaciones por mes
    public function getEvolucionPostulaciones($anio = null) {
        try {
            $anio = $anio ?: date('Y');
            $params = [':anio' => $anio];
            
            $query = "
                SELECT 
                    YEAR(i.fecha_inscripcion) as anio,
                    MONTH(i.fecha_inscripcion) as mes,
                    MONTHNAME(i.fecha_inscripcion) as nombre_mes,
                    COUNT(i.id_inscripcion) as total_inscripciones,
                    COUNT(DISTINCT i.id_postulante) as postulantes_unicos,
                    COUNT(CASE WHEN i.estado_inscripcion = 'admitido' THEN 1 END) as admitidos
                FROM inscripciones i
                WHERE YEAR(i.fecha_inscripcion) = :anio
                GROUP BY YEAR(i.fecha_inscripcion), MONTH(i.fecha_inscripcion), MONTHNAME(i.fecha_inscripcion)
                ORDER BY anio, mes
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getEvolucionPostulaciones: " . $e->getMessage());
            return ['error' => 'Error al obtener evolución de postulaciones'];
        }
    }
    
    // Estadísticas por facultad
    public function getEstadisticasPorFacultad($periodo_id = null) {
        try {
            $where_periodo = $periodo_id ? "WHERE i.periodo_id = :periodo_id" : "";
            $params = $periodo_id ? [':periodo_id' => $periodo_id] : [];
            
            $query = "
                SELECT 
                    f.nombre as facultad,
                    f.codigo,
                    COUNT(DISTINCT c.id_carrera) as total_carreras,
                    COUNT(DISTINCT i.id_inscripcion) as total_inscripciones,
                    COUNT(DISTINCT i.id_postulante) as postulantes_unicos,
                    COUNT(CASE WHEN i.estado_inscripcion = 'admitido' THEN 1 END) as admitidos,
                    AVG(i.puntaje_examen) as promedio_puntaje,
                    SUM(c.cupos) as total_cupos,
                    ROUND((COUNT(DISTINCT i.id_inscripcion) / NULLIF(SUM(c.cupos), 0)) * 100, 2) as porcentaje_demanda
                FROM facultades f
                LEFT JOIN carreras c ON f.id_facultad = c.facultad_id
                LEFT JOIN inscripciones i ON c.id_carrera = i.id_carrera
                $where_periodo
                GROUP BY f.id_facultad, f.nombre, f.codigo
                ORDER BY total_inscripciones DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getEstadisticasPorFacultad: " . $e->getMessage());
            return ['error' => 'Error al obtener estadísticas por facultad'];
        }
    }
    
    // Distribución de puntajes
    public function getDistribucionPuntajes($carrera_id = null, $periodo_id = null) {
        try {
            $where = "WHERE i.puntaje_examen IS NOT NULL";
            $params = [];
            
            if ($carrera_id) {
                $where .= " AND i.id_carrera = :carrera_id";
                $params[':carrera_id'] = $carrera_id;
            }
            
            if ($periodo_id) {
                $where .= " AND i.periodo_id = :periodo_id";
                $params[':periodo_id'] = $periodo_id;
            }
            
            // Primero obtenemos el total para calcular porcentajes
            $countQuery = "SELECT COUNT(*) as total FROM inscripciones i $where";
            $countStmt = $this->conn->prepare($countQuery);
            $countStmt->execute($params);
            $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'] ?: 1;
            
            $query = "
                SELECT 
                    CASE 
                        WHEN i.puntaje_examen >= 90 THEN '90-100'
                        WHEN i.puntaje_examen >= 80 THEN '80-89'
                        WHEN i.puntaje_examen >= 70 THEN '70-79'
                        WHEN i.puntaje_examen >= 60 THEN '60-69'
                        WHEN i.puntaje_examen >= 50 THEN '50-59'
                        ELSE '0-49'
                    END as rango_puntaje,
                    COUNT(*) as cantidad_postulantes,
                    ROUND((COUNT(*) * 100.0 / :total), 2) as porcentaje
                FROM inscripciones i
                $where
                GROUP BY rango_puntaje
                ORDER BY MIN(i.puntaje_examen) DESC
            ";
            
            $params[':total'] = $total;
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getDistribucionPuntajes: " . $e->getMessage());
            return ['error' => 'Error al obtener distribución de puntajes'];
        }
    }
    
    // Obtener periodos académicos
    public function getPeriodosAcademicos() {
        try {
            $query = "
                SELECT id_periodo, nombre_periodo, fecha_inicio_inscripciones, fecha_fin_inscripciones 
                FROM periodos_academicos 
                WHERE estado = 'activo'
                ORDER BY fecha_inicio_inscripciones DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getPeriodosAcademicos: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener carreras
    public function getCarreras() {
        try {
            $query = "
                SELECT id_carrera, nombre, codigo 
                FROM carreras 
                WHERE estado = 'activa'
                ORDER BY nombre
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getCarreras: " . $e->getMessage());
            return [];
        }
    }
}

// Procesar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $estadisticas = new EstadisticasManager();
    
    try {
        switch ($_POST['action']) {
            case 'estadisticas_generales':
                $periodo_id = !empty($_POST['periodo_id']) ? $_POST['periodo_id'] : null;
                echo json_encode($estadisticas->getEstadisticasGenerales($periodo_id));
                break;
                
            case 'carreras_demandadas':
                $periodo_id = !empty($_POST['periodo_id']) ? $_POST['periodo_id'] : null;
                $limit = !empty($_POST['limit']) ? $_POST['limit'] : 10;
                echo json_encode($estadisticas->getCarrerasMasDemandadas($periodo_id, $limit));
                break;
                
            case 'evolucion_postulaciones':
                $anio = !empty($_POST['anio']) ? $_POST['anio'] : date('Y');
                echo json_encode($estadisticas->getEvolucionPostulaciones($anio));
                break;
                
            case 'estadisticas_facultad':
                $periodo_id = !empty($_POST['periodo_id']) ? $_POST['periodo_id'] : null;
                echo json_encode($estadisticas->getEstadisticasPorFacultad($periodo_id));
                break;
                
            case 'distribucion_puntajes':
                $carrera_id = !empty($_POST['carrera_id']) ? $_POST['carrera_id'] : null;
                $periodo_id = !empty($_POST['periodo_id']) ? $_POST['periodo_id'] : null;
                echo json_encode($estadisticas->getDistribucionPuntajes($carrera_id, $periodo_id));
                break;
                
            default:
                echo json_encode(['error' => 'Acción no válida']);
        }
    } catch (Exception $e) {
        error_log("Error en procesamiento AJAX: " . $e->getMessage());
        echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
    }
    exit;
}

// Si no es una solicitud AJAX, mostrar la página HTML
try {
    $estadisticas = new EstadisticasManager();
    $periodos = $estadisticas->getPeriodosAcademicos();
    $carreras = $estadisticas->getCarreras();
} catch (Exception $e) {
    error_log("Error al inicializar: " . $e->getMessage());
    $periodos = [];
    $carreras = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Admisión - Sistema Universitario</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }

        select, button {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #667eea;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 12px;
            font-weight: 600;
        }

        .change-positive {
            color: #28a745;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .chart-card h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.2rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin-top: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .chart-card {
                padding: 15px;
            }
            
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Panel de Estadísticas de Admisión</h1>
            <p>Análisis completo del proceso de admisión universitaria</p>
        </div>
        
        <div class="controls">
            <div class="filter-group">
                <label for="periodoSelect">Periodo Académico:</label>
                <select id="periodoSelect">
                    <option value="">Todos los periodos</option>
                    <?php foreach ($periodos as $periodo): ?>
                        <option value="<?= htmlspecialchars($periodo['id_periodo']) ?>">
                            <?= htmlspecialchars($periodo['nombre_periodo']) ?> 
                            (<?= date('d/m/Y', strtotime($periodo['fecha_inicio_inscripciones'])) ?> - <?= date('d/m/Y', strtotime($periodo['fecha_fin_inscripciones'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="carreraSelect">Carrera (para puntajes):</label>
                <select id="carreraSelect">
                    <option value="">Todas las carreras</option>
                    <?php foreach ($carreras as $carrera): ?>
                        <option value="<?= htmlspecialchars($carrera['id_carrera']) ?>">
                            <?= htmlspecialchars($carrera['nombre']) ?> (<?= $carrera['codigo'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="anioSelect">Año (evolución):</label>
                <select id="anioSelect">
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>>
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <button class="btn btn-primary" onclick="cargarEstadisticas()">
                🔄 Actualizar Estadísticas
            </button>
            
            <button class="btn btn-secondary" onclick="exportarPDF()">
                📄 Exportar PDF
            </button>
        </div>
        
        <!-- Estadísticas Generales -->
        <div class="stats-grid" id="statsGeneral">
            <div class="loading">
                <div class="spinner"></div>
                Cargando estadísticas...
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="charts-container">
            <!-- Carreras más demandadas -->
            <div class="chart-card">
                <h3>🏆 Carreras Más Demandadas</h3>
                <div class="chart-container">
                    <canvas id="chartCarrerasDemandadas"></canvas>
                </div>
            </div>
            
            <!-- Evolución mensual -->
            <div class="chart-card">
                <h3>📈 Evolución Mensual de Postulaciones</h3>
                <div class="chart-container">
                    <canvas id="chartEvolucion"></canvas>
                </div>
            </div>
            
            <!-- Distribución por facultad -->
            <div class="chart-card">
                <h3>🎓 Distribución por Facultad</h3>
                <div class="chart-container">
                    <canvas id="chartFacultades"></canvas>
                </div>
            </div>
            
            <!-- Distribución de puntajes -->
            <div class="chart-card">
                <h3>📊 Distribución de Puntajes</h3>
                <div class="chart-container">
                    <canvas id="chartPuntajes"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tabla de carreras detallada -->
        <div class="chart-card" style="margin: 20px;">
            <h3>📋 Detalle de Carreras</h3>
            <div id="tablaCarreras">
                <div class="loading">
                    <div class="spinner"></div>
                    Cargando datos de carreras...
                </div>
            </div>
        </div>
    </div>

    <script>
        let charts = {};
        
        // Cargar todas las estadísticas al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarEstadisticas();
        });
        
        function cargarEstadisticas() {
            const periodo_id = document.getElementById('periodoSelect').value;
            const carrera_id = document.getElementById('carreraSelect').value;
            const anio = document.getElementById('anioSelect').value;
            
            cargarEstadisticasGenerales(periodo_id);
            cargarCarrerasDemandadas(periodo_id);
            cargarEvolucionPostulaciones(anio);
            cargarEstadisticasFacultad(periodo_id);
            cargarDistribucionPuntajes(carrera_id, periodo_id);
        }
        
        function cargarEstadisticasGenerales(periodo_id) {
            const formData = new FormData();
            formData.append('action', 'estadisticas_generales');
            if (periodo_id) formData.append('periodo_id', periodo_id);
            
            fetch('estadisticas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    mostrarError('statsGeneral', data.error);
                    return;
                }
                
                const stats = data;
                const html = `
                    <div class="stat-card">
                        <h3>Total Postulantes</h3>
                        <div class="stat-value">${stats.total_postulantes || 0}</div>
                        <div class="stat-change change-positive">Únicos registrados</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Inscripciones</h3>
                        <div class="stat-value">${stats.total_inscripciones || 0}</div>
                        <div class="stat-change">En todas las carreras</div>
                    </div>
                    <div class="stat-card">
                        <h3>Carreras con Postulantes</h3>
                        <div class="stat-value">${stats.carreras_con_postulantes || 0}</div>
                        <div class="stat-change">Con demanda</div>
                    </div>
                    <div class="stat-card">
                        <h3>Promedio Puntaje</h3>
                        <div class="stat-value">${stats.promedio_puntaje ? stats.promedio_puntaje.toFixed(2) : '0.00'}</div>
                        <div class="stat-change">Puntaje general</div>
                    </div>
                    <div class="stat-card">
                        <h3>Admitidos</h3>
                        <div class="stat-value">${stats.total_admitidos || 0}</div>
                        <div class="stat-change change-positive">${stats.total_inscripciones ? ((stats.total_admitidos / stats.total_inscripciones) * 100).toFixed(1) + '%' : '0%'}</div>
                    </div>
                    <div class="stat-card">
                        <h3>Documentos Aprobados</h3>
                        <div class="stat-value">${stats.documentos_aprobados || 0}</div>
                        <div class="stat-change">${stats.documentos_pendientes || 0} pendientes</div>
                    </div>
                `;
                
                document.getElementById('statsGeneral').innerHTML = html;
            })
            .catch(error => {
                mostrarError('statsGeneral', 'Error al cargar estadísticas generales: ' + error);
            });
        }
        
        function cargarCarrerasDemandadas(periodo_id) {
            const formData = new FormData();
            formData.append('action', 'carreras_demandadas');
            formData.append('limit', '10');
            if (periodo_id) formData.append('periodo_id', periodo_id);
            
            fetch('estadisticas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    mostrarError('tablaCarreras', data.error);
                    return;
                }
                
                const carreras = data;
                
                // Actualizar tabla
                let tablaHTML = `
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Carrera</th>
                                    <th>Facultad</th>
                                    <th>Inscripciones</th>
                                    <th>Admitidos</th>
                                    <th>Promedio</th>
                                    <th>Demanda</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                carreras.forEach(carrera => {
                    const porcentajeDemanda = carrera.porcentaje_demanda || 0;
                    const porcentajeAdmitidos = carrera.total_inscripciones ? ((carrera.admitidos / carrera.total_inscripciones) * 100).toFixed(1) : '0';
                    
                    tablaHTML += `
                        <tr>
                            <td><strong>${escapeHtml(carrera.carrera || 'N/A')}</strong><br><small>${carrera.codigo || ''}</small></td>
                            <td>${escapeHtml(carrera.facultad || 'N/A')}</td>
                            <td>${carrera.total_inscripciones || 0}</td>
                            <td>${carrera.admitidos || 0} (${porcentajeAdmitidos}%)</td>
                            <td>${carrera.promedio_puntaje ? carrera.promedio_puntaje.toFixed(2) : 'N/A'}</td>
                            <td>
                                ${porcentajeDemanda}%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: ${Math.min(porcentajeDemanda, 100)}%"></div>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                tablaHTML += `</tbody></table></div>`;
                document.getElementById('tablaCarreras').innerHTML = tablaHTML;
                
                // Actualizar gráfico
                if (charts.carreras) {
                    charts.carreras.destroy();
                }
                
                const ctx = document.getElementById('chartCarrerasDemandadas').getContext('2d');
                charts.carreras = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: carreras.map(c => {
                            const nombre = c.carrera || 'N/A';
                            return nombre.length > 20 ? nombre.substring(0, 20) + '...' : nombre;
                        }),
                        datasets: [{
                            label: 'Total Inscripciones',
                            data: carreras.map(c => c.total_inscripciones || 0),
                            backgroundColor: 'rgba(102, 126, 234, 0.8)',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Admitidos',
                            data: carreras.map(c => c.admitidos || 0),
                            backgroundColor: 'rgba(40, 167, 69, 0.8)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            })
            .catch(error => {
                mostrarError('tablaCarreras', 'Error al cargar carreras demandadas: ' + error);
            });
        }
        
        function cargarEvolucionPostulaciones(anio) {
            const formData = new FormData();
            formData.append('action', 'evolucion_postulaciones');
            formData.append('anio', anio);
            
            fetch('estadisticas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                
                if (charts.evolucion) {
                    charts.evolucion.destroy();
                }
                
                const ctx = document.getElementById('chartEvolucion').getContext('2d');
                charts.evolucion = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(item => item.nombre_mes || ''),
                        datasets: [{
                            label: 'Total Inscripciones',
                            data: data.map(item => item.total_inscripciones || 0),
                            borderColor: 'rgba(102, 126, 234, 1)',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Postulantes Únicos',
                            data: data.map(item => item.postulantes_unicos || 0),
                            borderColor: 'rgba(40, 167, 69, 1)',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        }
        
        function cargarEstadisticasFacultad(periodo_id) {
            const formData = new FormData();
            formData.append('action', 'estadisticas_facultad');
            if (periodo_id) formData.append('periodo_id', periodo_id);
            
            fetch('estadisticas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                
                if (charts.facultades) {
                    charts.facultades.destroy();
                }
                
                const ctx = document.getElementById('chartFacultades').getContext('2d');
                charts.facultades = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.map(item => item.facultad || ''),
                        datasets: [{
                            data: data.map(item => item.total_inscripciones || 0),
                            backgroundColor: [
                                '#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe',
                                '#43e97b', '#38f9d7', '#fa709a', '#fee140', '#a8edea'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            }
                        }
                    }
                });
            });
        }
        
        function cargarDistribucionPuntajes(carrera_id, periodo_id) {
            const formData = new FormData();
            formData.append('action', 'distribucion_puntajes');
            if (carrera_id) formData.append('carrera_id', carrera_id);
            if (periodo_id) formData.append('periodo_id', periodo_id);
            
            fetch('estadisticas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                
                if (charts.puntajes) {
                    charts.puntajes.destroy();
                }
                
                const ctx = document.getElementById('chartPuntajes').getContext('2d');
                charts.puntajes = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data.map(item => item.rango_puntaje || ''),
                        datasets: [{
                            data: data.map(item => item.cantidad_postulantes || 0),
                            backgroundColor: [
                                '#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#3498db', '#9b59b6'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const item = data[context.dataIndex];
                                        return `${item.rango_puntaje}: ${item.cantidad_postulantes} postulantes (${item.porcentaje}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            });
        }
        
        function mostrarError(elementId, mensaje) {
            document.getElementById(elementId).innerHTML = `
                <div class="error">
                    <strong>Error:</strong> ${escapeHtml(mensaje)}
                </div>
            `;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;s
        }
        
        function exportarPDF() {
            alert('Funcionalidad de exportación PDF - Se implementaría con una librería como jsPDF');
            // Aquí se implementaría la generación de PDF
        }
    </script>
</body>
</html>