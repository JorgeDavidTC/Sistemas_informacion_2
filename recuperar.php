<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ----------------------------
// Conexión a la base de datos
// ----------------------------
$host = "localhost";
$user = "root";
$pass = "";
$db = "admisiones_unificadas";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$mensaje = "";
$clase_mensaje = "";

// ----------------------------
// Si enviaron el formulario
// ----------------------------
if (isset($_POST['recuperar'])) {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $mensaje = "⚠️ Ingresa un correo electrónico.";
        $clase_mensaje = "error";
    } else {
        $query = $conn->prepare("SELECT contrasena FROM usuarios WHERE correo_electronico = ?");
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $contrasena = $row['contrasena'];

            // En localhost mostramos en pantalla en lugar de enviar correo
            $mensaje = "🔑 Tu contraseña registrada es: <b>" . $contrasena . "</b>";
            $clase_mensaje = "success";
        } else {
            $mensaje = "❌ El correo no está registrado.";
            $clase_mensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar Contraseña</title>
<link rel="stylesheet" href="css/recuperar.css">
<style>
body {
    font-family: Arial, sans-serif;
    background: #f2f2f2;
}
.container {
    width: 400px;
    margin: 100px auto;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.2);
    text-align: center;
}
h2 {
    margin-bottom: 20px;
}
input[type="email"] {
    width: 90%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
}
button {
    padding: 10px 20px;
    margin: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn-primary {
    background: #007bff;
    color: #fff;
}
.btn-cancelar {
    background: #dc3545;
    color: #fff;
}
.mensaje {
    margin: 15px 0;
    padding: 10px;
    border-radius: 5px;
}
.mensaje.success {
    background: #d4edda;
    color: #155724;
}
.mensaje.error {
    background: #f8d7da;
    color: #721c24;
}
</style>
</head>
<body>
<div class="container">
    <button class="btn-cancelar" onclick="location.href='login.html'">Cancelar</button>

    <h2>Recuperar Contraseña</h2>

    <?php if ($mensaje): ?>
      <div class="mensaje <?php echo $clase_mensaje; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="email" name="email" placeholder="Correo registrado" required>
        <button type="submit" name="recuperar" class="btn-primary">Recuperar</button>
    </form>
</div>
</body>
</html>
