<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "admisiones_unificadas";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nombre_usuario = trim($_POST['nombre_usuario']);
        $apellido_paterno = trim($_POST['apellido_paterno']);
        $apellido_materno = trim($_POST['apellido_materno']);
        $ci = trim($_POST['ci']);
        $email = trim($_POST['email']);
        $contrasena = $_POST['contrasena']; // contraseña tal cual
        $telefono = trim($_POST['telefono']);
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $direccion_residencia = trim($_POST['direccion_residencia']);
        $nacionalidad = trim($_POST['nacionalidad']);
        $carrera_id = $_POST['carrera'];

        // Validaciones básicas
        if(empty($nombre_usuario) || empty($ci) || empty($email) || empty($contrasena) || empty($telefono) || empty($fecha_nacimiento) || empty($direccion_residencia) || empty($nacionalidad) || empty($carrera_id)) {
            throw new Exception("Todos los campos son obligatorios.");
        }
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Correo inválido");
        if(!preg_match('/^[0-9]{5,15}$/', $ci)) throw new Exception("CI inválido");
        if(!preg_match('/^[0-9]{7,15}$/', $telefono)) throw new Exception("Teléfono inválido");

        // Manejo de archivo de perfil
        $foto_perfil_url = null;
        if(isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
            $carpeta_destino = 'uploads/';
            if(!is_dir($carpeta_destino)) mkdir($carpeta_destino, 0755, true);
            $nombreArchivo = time().'_'.basename($_FILES['foto_perfil']['name']);
            $rutaCompleta = $carpeta_destino.$nombreArchivo;
            move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $rutaCompleta);
            $foto_perfil_url = $rutaCompleta;
        }

        // Insertar en usuarios (contraseña tal cual)
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, cedula_identidad, correo_electronico, contrasena, rol, estado) 
                                VALUES (:nombre, :ci, :email, :pass, 'postulante', 'activo')");
        $stmt->bindParam(':nombre', $nombre_usuario);
        $stmt->bindParam(':ci', $ci);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':pass', $contrasena);
        $stmt->execute();
        $usuario_id = $conn->lastInsertId();

        // Insertar en postulantes
        $stmt2 = $conn->prepare("INSERT INTO postulantes 
            (usuario_id, nombres, apellido_paterno, apellido_materno, ci, fecha_nacimiento, telefono, direccion_residencia, nacionalidad, foto_perfil_url, estado_postulacion) 
            VALUES (:usuario_id, :nombres, :apellido_p, :apellido_m, :ci, :fecha_nac, :telefono, :direccion, :nacionalidad, :foto, 'pendiente')");
        $stmt2->bindParam(':usuario_id', $usuario_id);
        $stmt2->bindParam(':nombres', $nombre_usuario);
        $stmt2->bindParam(':apellido_p', $apellido_paterno);
        $stmt2->bindParam(':apellido_m', $apellido_materno);
        $stmt2->bindParam(':ci', $ci);
        $stmt2->bindParam(':fecha_nac', $fecha_nacimiento);
        $stmt2->bindParam(':telefono', $telefono);
        $stmt2->bindParam(':direccion', $direccion_residencia);
        $stmt2->bindParam(':nacionalidad', $nacionalidad);
        $stmt2->bindParam(':foto', $foto_perfil_url);
        $stmt2->execute();
        $postulante_id = $conn->lastInsertId();

        // Crear inscripción automática
        $stmt3 = $conn->prepare("INSERT INTO inscripciones (id_postulante, id_carrera, periodo_id, opcion_carrera, numero_folio, estado_inscripcion)
                                 VALUES (:postulante_id, :carrera_id, 1, 'primera', :folio, 'inscrito')");
        $folio = 'FOLIO-'.time();
        $stmt3->bindParam(':postulante_id', $postulante_id);
        $stmt3->bindParam(':carrera_id', $carrera_id);
        $stmt3->bindParam(':folio', $folio);
        $stmt3->execute();

        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Registro Exitoso</title>
            <meta http-equiv='refresh' content='3;url=login.html'>
            <style>
                body { font-family: Arial,sans-serif; background-color: #d4edda; color: #155724; display: flex; justify-content: center; align-items: center; height: 100vh; }
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
    echo "<script>alert('Error: ".$e->getMessage()."'); window.history.back();</script>";
}
?>
