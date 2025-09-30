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
                    COALESCE(CAST(AVG(i.puntaje_examen) AS DECIMAL(10,2)), 0) as promedio_puntaje,
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
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Asegurar que los valores numéricos sean números
            if ($result) {
                $result['promedio_puntaje'] = floatval($result['promedio_puntaje']);
                $result['total_postulantes'] = intval($result['total_postulantes']);
                $result['carreras_con_postulantes'] = intval($result['carreras_con_postulantes']);
                $result['total_inscripciones'] = intval($result['total_inscripciones']);
                $result['total_admitidos'] = intval($result['total_admitidos']);
                $result['total_no_admitidos'] = intval($result['total_no_admitidos']);
                $result['documentos_aprobados'] = intval($result['documentos_aprobados']);
                $result['documentos_pendientes'] = intval($result['documentos_pendientes']);
            }
            
            return $result ?: [
                'total_postulantes' => 0,
                'carreras_con_postulantes' => 0,
                'total_inscripciones' => 0,
                'promedio_puntaje' => 0,
                'total_admitidos' => 0,
                'total_no_admitidos' => 0,
                'documentos_aprobados' => 0,
                'documentos_pendientes' => 0
            ];
            
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
                    COALESCE(CAST(AVG(i.puntaje_examen) AS DECIMAL(10,2)), 0) as promedio_puntaje,
                    c.cupos,
                    CASE 
                        WHEN c.cupos > 0 THEN ROUND((COUNT(i.id_inscripcion) / c.cupos) * 100, 2)
                        ELSE 0 
                    END as porcentaje_demanda
                FROM carreras c
                LEFT JOIN facultades f ON c.facultad_id = f.id_facultad
                LEFT JOIN inscripciones i ON c.id_carrera = i.id_carrera
                $where_periodo
                GROUP BY c.id_carrera, c.nombre, c.codigo, f.nombre, c.cupos
                HAVING total_inscripciones > 0
                ORDER BY total_inscripciones DESC
                LIMIT :limit
            ";
            
            $stmt = $this->conn->prepare($query);
            if ($periodo_id) {
                $stmt->bindValue(':periodo_id', $periodo_id, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir valores numéricos
            foreach ($results as &$row) {
                $row['promedio_puntaje'] = floatval($row['promedio_puntaje']);
                $row['total_inscripciones'] = intval($row['total_inscripciones']);
                $row['admitidos'] = intval($row['admitidos']);
                $row['no_admitidos'] = intval($row['no_admitidos']);
                $row['porcentaje_demanda'] = floatval($row['porcentaje_demanda']);
            }
            
            return $results;
            
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
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir valores numéricos
            foreach ($results as &$row) {
                $row['total_inscripciones'] = intval($row['total_inscripciones']);
                $row['postulantes_unicos'] = intval($row['postulantes_unicos']);
                $row['admitidos'] = intval($row['admitidos']);
            }
            
            return $results;
            
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
                    COALESCE(CAST(AVG(i.puntaje_examen) AS DECIMAL(10,2)), 0) as promedio_puntaje,
                    COALESCE(SUM(c.cupos), 0) as total_cupos,
                    CASE 
                        WHEN SUM(c.cupos) > 0 THEN ROUND((COUNT(DISTINCT i.id_inscripcion) / SUM(c.cupos)) * 100, 2)
                        ELSE 0 
                    END as porcentaje_demanda
                FROM facultades f
                LEFT JOIN carreras c ON f.id_facultad = c.facultad_id
                LEFT JOIN inscripciones i ON c.id_carrera = i.id_carrera
                $where_periodo
                GROUP BY f.id_facultad, f.nombre, f.codigo
                HAVING total_inscripciones > 0
                ORDER BY total_inscripciones DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir valores numéricos
            foreach ($results as &$row) {
                $row['promedio_puntaje'] = floatval($row['promedio_puntaje']);
                $row['total_inscripciones'] = intval($row['total_inscripciones']);
                $row['postulantes_unicos'] = intval($row['postulantes_unicos']);
                $row['admitidos'] = intval($row['admitidos']);
                $row['total_cupos'] = intval($row['total_cupos']);
                $row['porcentaje_demanda'] = floatval($row['porcentaje_demanda']);
            }
            
            return $results;
            
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
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir valores numéricos
            foreach ($results as &$row) {
                $row['cantidad_postulantes'] = intval($row['cantidad_postulantes']);
                $row['porcentaje'] = floatval($row['porcentaje']);
            }
            
            return $results;
            
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
    <link rel="stylesheet" href="css/estadisticas.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <h1>📊 Panel de Estadísticas de Admisión</h1>
                <button class="btn btn-secondary" onclick="volverAlLogin()">
                    ← Volver al Login
                </button>
            </div>
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
                
                // Asegurar que los valores sean números
                const promedio = parseFloat(stats.promedio_puntaje) || 0;
                const totalInscripciones = parseInt(stats.total_inscripciones) || 0;
                const totalAdmitidos = parseInt(stats.total_admitidos) || 0;
                
                const html = `
                    <div class="stat-card">
                        <h3>Total Postulantes</h3>
                        <div class="stat-value">${parseInt(stats.total_postulantes) || 0}</div>
                        <div class="stat-change change-positive">Únicos registrados</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Inscripciones</h3>
                        <div class="stat-value">${totalInscripciones}</div>
                        <div class="stat-change">En todas las carreras</div>
                    </div>
                    <div class="stat-card">
                        <h3>Carreras con Postulantes</h3>
                        <div class="stat-value">${parseInt(stats.carreras_con_postulantes) || 0}</div>
                        <div class="stat-change">Con demanda</div>
                    </div>
                    <div class="stat-card">
                        <h3>Promedio Puntaje</h3>
                        <div class="stat-value">${promedio.toFixed(2)}</div>
                        <div class="stat-change">Puntaje general</div>
                    </div>
                    <div class="stat-card">
                        <h3>Admitidos</h3>
                        <div class="stat-value">${totalAdmitidos}</div>
                        <div class="stat-change change-positive">${totalInscripciones ? ((totalAdmitidos / totalInscripciones) * 100).toFixed(1) + '%' : '0%'}</div>
                    </div>
                    <div class="stat-card">
                        <h3>Documentos Aprobados</h3>
                        <div class="stat-value">${parseInt(stats.documentos_aprobados) || 0}</div>
                        <div class="stat-change">${parseInt(stats.documentos_pendientes) || 0} pendientes</div>
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
                    const porcentajeDemanda = parseFloat(carrera.porcentaje_demanda) || 0;
                    const totalInscripciones = parseInt(carrera.total_inscripciones) || 0;
                    const admitidos = parseInt(carrera.admitidos) || 0;
                    const promedio = parseFloat(carrera.promedio_puntaje) || 0;
                    const porcentajeAdmitidos = totalInscripciones ? ((admitidos / totalInscripciones) * 100).toFixed(1) : '0';
                    
                    tablaHTML += `
                        <tr>
                            <td><strong>${escapeHtml(carrera.carrera || 'N/A')}</strong><br><small>${carrera.codigo || ''}</small></td>
                            <td>${escapeHtml(carrera.facultad || 'N/A')}</td>
                            <td>${totalInscripciones}</td>
                            <td>${admitidos} (${porcentajeAdmitidos}%)</td>
                            <td>${promedio.toFixed(2)}</td>
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
                            data: carreras.map(c => parseInt(c.total_inscripciones) || 0),
                            backgroundColor: 'rgba(102, 126, 234, 0.8)',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Admitidos',
                            data: carreras.map(c => parseInt(c.admitidos) || 0),
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
                            data: data.map(item => parseInt(item.total_inscripciones) || 0),
                            borderColor: 'rgba(102, 126, 234, 1)',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Postulantes Únicos',
                            data: data.map(item => parseInt(item.postulantes_unicos) || 0),
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
                            data: data.map(item => parseInt(item.total_inscripciones) || 0),
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
                            data: data.map(item => parseInt(item.cantidad_postulantes) || 0),
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
                                        return `${item.rango_puntaje}: ${parseInt(item.cantidad_postulantes) || 0} postulantes (${parseFloat(item.porcentaje) || 0}%)`;
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
            return div.innerHTML;
        }
        
        function exportarPDF() {
            alert('Funcionalidad de exportación PDF - Se implementaría con una librería como jsPDF');
            // Aquí se implementaría la generación de PDF
        }
        
        function volverAlLogin() {
            window.location.href = 'login.html';
        }
    </script>
</body>
</html>