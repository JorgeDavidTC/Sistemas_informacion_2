<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "admisiones_unificadas";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // -----------------------------
        // Recoger datos del formulario
        // -----------------------------
        $nombre_usuario = trim($_POST['nombre_usuario']);
        $apellido_paterno = trim($_POST['apellido_paterno']);
        $apellido_materno = trim($_POST['apellido_materno']);
        $ci = trim($_POST['ci']);
        $email = trim($_POST['email']);
        $contrasena = $_POST['contrasena']; // texto plano
        $telefono = trim($_POST['telefono']);
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $direccion_residencia = trim($_POST['direccion_residencia']);
        $nacionalidad = trim($_POST['nacionalidad']);
        $facultad_id = $_POST['facultad'];
        $carrera_id = $_POST['carrera'];

        // -----------------------------
        // Validaciones básicas
        // -----------------------------
        if (empty($nombre_usuario) || empty($ci) || empty($email) || empty($telefono) || empty($fecha_nacimiento) || empty($direccion_residencia) || empty($nacionalidad) || empty($facultad_id) || empty($carrera_id)) {
            throw new Exception("Todos los campos son obligatorios.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Correo inválido");
        if (!preg_match('/^[0-9]{5,15}$/', $ci)) throw new Exception("CI inválido");
        if (!preg_match('/^[0-9]{7,15}$/', $telefono)) throw new Exception("Teléfono inválido");

        // -----------------------------
        // Validar que carrera pertenezca a facultad
        // -----------------------------
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM carreras WHERE id_carrera = :carrera AND facultad_id = :facultad");
        $stmtCheck->execute([':carrera' => $carrera_id, ':facultad' => $facultad_id]);
        if ($stmtCheck->fetchColumn() == 0) throw new Exception("La carrera seleccionada no corresponde a la facultad elegida.");

        // -----------------------------
        // Manejo de archivo de perfil
        // -----------------------------
        $foto_perfil_url = null;
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
            $carpeta_destino = 'uploads/';
            if (!is_dir($carpeta_destino)) mkdir($carpeta_destino, 0755, true);
            $nombreArchivo = time().'_'.basename($_FILES['foto_perfil']['name']);
            $rutaCompleta = $carpeta_destino.$nombreArchivo;
            move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $rutaCompleta);
            $foto_perfil_url = $rutaCompleta;
        }

        // -----------------------------
        // Insertar en tabla usuarios
        // -----------------------------
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, cedula_identidad, correo_electronico, contrasena, rol, estado) 
                                VALUES (:nombre, :ci, :email, :pass, 'postulante', 'activo')");
        $stmt->execute([
            ':nombre' => $nombre_usuario,
            ':ci' => $ci,
            ':email' => $email,
            ':pass' => $contrasena
        ]);
        $usuario_id = $conn->lastInsertId();

        // -----------------------------
        // Insertar en tabla postulantes
        // -----------------------------
        $stmt2 = $conn->prepare("INSERT INTO postulantes 
            (usuario_id, nombres, apellido_paterno, apellido_materno, ci, fecha_nacimiento, telefono, direccion_residencia, nacionalidad, foto_perfil_url, estado_postulacion) 
            VALUES (:usuario_id, :nombres, :apellido_p, :apellido_m, :ci, :fecha_nac, :telefono, :direccion, :nacionalidad, :foto, 'pendiente')");
        $stmt2->execute([
            ':usuario_id' => $usuario_id,
            ':nombres' => $nombre_usuario,
            ':apellido_p' => $apellido_paterno,
            ':apellido_m' => $apellido_materno,
            ':ci' => $ci,
            ':fecha_nac' => $fecha_nacimiento,
            ':telefono' => $telefono,
            ':direccion' => $direccion_residencia,
            ':nacionalidad' => $nacionalidad,
            ':foto' => $foto_perfil_url
        ]);
        $postulante_id = $conn->lastInsertId();

        // -----------------------------
        // Crear inscripción automática
        // -----------------------------
        $folio = 'FOLIO-'.time();
        $stmt3 = $conn->prepare("INSERT INTO inscripciones (id_postulante, id_carrera, periodo_id, opcion_carrera, numero_folio, estado_inscripcion)
                                 VALUES (:postulante_id, :carrera_id, 1, 'primera', :folio, 'inscrito')");
        $stmt3->execute([
            ':postulante_id' => $postulante_id,
            ':carrera_id' => $carrera_id,
            ':folio' => $folio
        ]);

        // -----------------------------
        // Registro exitoso
        // -----------------------------
        echo "<script>alert('Registro exitoso'); window.location.href='login.html';</script>";
        exit();
    }
} catch (Exception $e) {
    echo "<script>alert('Error: ".$e->getMessage()."'); window.history.back();</script>";
}
?>
