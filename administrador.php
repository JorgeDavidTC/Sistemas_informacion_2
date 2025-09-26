<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}

$usuario = htmlspecialchars($_SESSION['usuario']);

// Conexión a la base de datos con tu clase Database (PDO)
require_once "conexion.php";
$database = new Database();
$conn = $database->getConnection();

// Consulta: lista de usuarios
$usuarios = $conn->query("
    SELECT id_usuario, nombre, correo_electronico, rol, estado
    FROM usuarios
");

// Consulta: lista de carreras
$carreras = $conn->query("
    SELECT codigo, nombre, cupos, estado
    FROM carreras
");

// Consulta: periodos académicos
$periodos = $conn->query("
    SELECT nombre_periodo, fecha_inicio_inscripciones, fecha_fin_inscripciones, estado
    FROM periodos_academicos
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrador</title>
    <link rel="stylesheet" href="css/adminprincipal.css">
    <style>
        /* Pequeños estilos extra para embellecer tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        table th, table td {
            padding: 12px 15px;
            text-align: left;
        }
        table th {
            background: #2c3e50;
            color: #fff;
            font-weight: bold;
        }
        table tr:nth-child(even) {
            background: #f8f9fa;
        }
        h2 {
            margin-top: 40px;
            color: #2c3e50;
            border-left: 6px solid #27ae60;
            padding-left: 10px;
        }
        .panel-derecho {
            padding: 20px;
        }
        .main-content h1 {
            color: #34495e;
        }
    </style>
</head>
<body>

<!-- Menú lateral -->
<div class="sidebar">
    <div class="admin-header">
        <h2>Administrador</h2>
        <p>Hola, <?php echo $usuario; ?> 👋</p>
    </div>

    <div class="btn-group">
        <form action="registrar_usuario.php" method="get">
            <button type="submit" class="btn">➕ Registrar Usuario</button>
        </form>
        <form action="administrar_roles.php" method="get">
            <button type="submit" class="btn">⚙️ Administrar Usuarios</button>
        </form>
        <form action="registrar_carreras.php" method="get">
            <button type="submit" class="btn">🎓 Registrar Carreras</button>
        </form>
        <form action="registrar_periodos.php" method="get">
            <button type="submit" class="btn">📅 Registrar Periodos</button>
        </form>
    </div>

    <form action="logout.php" method="post">
        <button type="submit" class="btn btn-logout">🚪 Cerrar Sesión</button>
    </form>
</div>

<!-- Contenido principal -->
<div class="main-content">
    <h1>Bienvenido al Panel de Administración</h1>

    <div class="panel-derecho">

        <!-- Usuarios -->
        <h2>👥 Usuarios registrados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $usuarios->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= $row["id_usuario"] ?></td>
                    <td><?= $row["nombre"] ?></td>
                    <td><?= $row["correo_electronico"] ?></td>
                    <td><strong><?= ucfirst($row["rol"]) ?></strong></td>
                    <td><?= ucfirst($row["estado"]) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Carreras -->
        <h2>📚 Carreras disponibles</h2>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Cupos</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $carreras->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= $row["codigo"] ?></td>
                    <td><?= $row["nombre"] ?></td>
                    <td><?= $row["cupos"] ?></td>
                    <td><?= ucfirst($row["estado"]) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Periodos -->
        <h2>📅 Periodos Académicos</h2>
        <table>
            <thead>
                <tr>
                    <th>Nombre Periodo</th>
                    <th>Inicio Inscripciones</th>
                    <th>Fin Inscripciones</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $periodos->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= $row["nombre_periodo"] ?></td>
                    <td><?= $row["fecha_inicio_inscripciones"] ?></td>
                    <td><?= $row["fecha_fin_inscripciones"] ?></td>
                    <td><?= ucfirst($row["estado"]) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>
</div>
</body>
</html>
