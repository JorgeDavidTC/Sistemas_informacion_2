<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.html");
    exit;
}

$usuario = htmlspecialchars($_SESSION['usuario']);

require_once "conexion.php";
$database = new Database();
$conn = $database->getConnection();

$usuarios = $conn->query("
    SELECT id_usuario, nombre, correo_electronico, rol, estado
    FROM usuarios
");

$carreras = $conn->query("
    SELECT codigo, nombre, cupos, estado
    FROM carreras
");


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