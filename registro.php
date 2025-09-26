<?php
// registro.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "admisiones";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nombre_usuario = trim($_POST['nombre_usuario']);
        $ci = trim($_POST['ci']);
        $email = trim($_POST['email']);
        $contrasena = $_POST['contrasena'];
        $telefono = trim($_POST['telefono']);

        if (empty($nombre_usuario) || empty($ci) || empty($email) || empty($contrasena) || empty($telefono)) {
            throw new Exception("Todos los campos son obligatorios.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El correo electrónico no es válido.");
        }

        if (!preg_match('/^[0-9]{7,15}$/', $telefono)) {
            throw new Exception("El teléfono debe tener solo números, entre 7 y 15 dígitos.");
        }

        // Insertar en tabla usuarios
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, ci, email, contrasena) VALUES (:nombre, :ci, :email, :pass)");
        $stmt->bindParam(':nombre', $nombre_usuario);
        $stmt->bindParam(':ci', $ci);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':pass', $contrasena);
        $stmt->execute();

        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Registro Exitoso</title>
            <meta http-equiv='refresh' content='3;url=login.html'>
            <style>
                body { font-family: Arial, sans-serif; background-color: #d4edda; color: #155724; display: flex; justify-content: center; align-items: center; height: 100vh; }
                .mensaje { background-color: #c3e6cb; padding: 30px; border-radius: 8px; border: 1px solid #155724; text-align: center; }
            </style>
        </head>
        <body>
            <div class='mensaje'>
                <h2>Registro exitoso</h2>
                <p>Serás redirigido a la página de inicio de sesión en 3 segundos...</p>
            </div>
        </body>
        </html>";
        exit();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Error en Registro</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8d7da; color: #721c24; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .error-container { background-color: #f5c6cb; padding: 20px; border-radius: 8px; border: 1px solid #f1b0b7; max-width: 400px; text-align: center; }
        a { color: #721c24; text-decoration: underline; font-weight: bold; }
    </style>
</head>
<body>
    <div class="error-container">
        <h2>Error en el Registro</h2>
        <p><?php echo isset($error) ? htmlspecialchars($error) : "Error desconocido."; ?></p>
        <p><a href="registro.html">Volver al formulario de registro</a></p>
    </div>
</body>
</html>

