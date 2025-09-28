<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'admisiones_unificadas');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
    die("Error al conectar con la base de datos: " . $conn->connect_error);
}

// Facultades y carreras
$facultades = [
    "Tecnologia" => ["Ingeniería de Sistemas", "Ingeniería Civil", "Ingeniería Mecánica", "Ingeniería Electrónica"],
    "Medicina"   => ["Medicina", "Enfermería", "Odontología"],
    "Derecho"    => ["Derecho", "Criminología"],
    "Arquitectura" => ["Arquitectura", "Diseño Urbano"]
];

// Temarios por carrera
$temarios = [
    "Ingeniería de Sistemas" => [
        ["titulo" => "Algoritmos I", "desc" => "Temario de Algoritmos I", "link" => "http://imagenes.uniremington.edu.co/moodle/M%C3%B3dulos%20de%20aprendizaje/algiritmos%201/Algoritmos_I_modulo_listo_ok2016.pdf"],
        ["titulo" => "Base de Datos", "desc" => "Guía de SQL y Modelado", "link" => "https://bdigital.uvhm.edu.mx/wp-content/uploads/2020/05/Bases-de-Datos.pdf"],
        ["titulo" => "Programación Orientada a Objetos", "desc" => "POO en Java y C++", "link" => "https://unefazuliasistemas.wordpress.com/wp-content/uploads/2011/04/programacion-orientada-a-objetos-luis-joyanes-aguilar.pdf"],
        ["titulo" => "Redes de Computadoras", "desc" => "Conceptos de redes y protocolos", "link" => "https://libros.metabiblioteca.org/server/api/core/bitstreams/2deaa017-ef04-4f73-866c-9a81f23ad1c0/content"]
    ],
    "Ingeniería Civil" => [
        ["titulo" => "Estática", "desc" => "Conceptos de mecánica de cuerpos rígidos", "link" => "https://bdigital.uncuyo.edu.ar/objetos_digitales/11832/llano.pdf"],
        ["titulo" => "Topografía", "desc" => "Manual práctico de topografía", "link" => "https://repositorio.una.edu.ni/3179/1/NP31G192t.pdf"],
        ["titulo" => "Hidráulica", "desc" => "Principios de hidráulica aplicada", "link" => "https://www.imta.gob.mx/biblioteca/libros_html/hidraulica/Libro-hidraulica-basica.pdf"],
        ["titulo" => "Materiales de Construcción", "desc" => "Propiedades de materiales", "link" => "https://topodata.com/wp-content/uploads/2020/02/Apuntes-de-Materiales-de-Construccion.pdf"]
    ],
    "Ingeniería Mecánica" => [
        ["titulo" => "Mecánica de Fluidos", "desc" => "Temario de mecánica de fluidos", "link" => "https://oa.upm.es/6531/1/amd-apuntes-fluidos.pdf"],
        ["titulo" => "Termodinámica", "desc" => "Estudio de energía y sistemas", "link" => "https://3ciencias.com/wp-content/uploads/2021/12/Termodina%CC%81mica_.pdf"]
    ],
    "Ingeniería Electrónica" => [
        ["titulo" => "Circuitos Eléctricos", "desc" => "Análisis de circuitos eléctricos", "link" => "https://tecnicadelaindia.edu.ar/wp-content/uploads/2020/03/Circuito-Electrico-y-Redes-bibliografia-N%C2%B01.pdf"],
        ["titulo" => "Electrónica Digital", "desc" => "Fundamentos de lógica digital", "link" => "https://proyectodescartes.org/iCartesiLibri/PDF/Electronica_Digital.pdf"]
    ],
    "Medicina" => [
        ["titulo" => "Anatomía Humana", "desc" => "Guía de estudio de anatomía", "link" => "https://medicina.uca.es/wp-content/uploads/2023/08/Anatomia-Humana-2022-1.pdf"],
        ["titulo" => "Fisiología", "desc" => "Funciones del cuerpo humano", "link" => "https://cbtis54.edu.mx/wp-content/uploads/2024/04/Principios-de-Anatomia-y-Fisiologia-Tortora-Derrickson.pdf"],
        ["titulo" => "Bioquímica", "desc" => "Procesos bioquímicos", "link" => "https://3ciencias.com/wp-content/uploads/2018/10/LIBRO-BIOQUIMICA.pdf"]
    ],
    "Enfermería" => [
        ["titulo" => "Fundamentos de Enfermería", "desc" => "Guía práctica de enfermería", "link" => "https://mawil.us/wp-content/uploads/2021/04/fundamentos-teoricos-y-practicos-de-enfermeria.pdf"]
    ],
    "Odontología" => [
        ["titulo" => "Anatomía Dental", "desc" => "Estructura y funciones dentales", "link" => "https://www.odonto.unam.mx/sites/default/files/inline-files/1_anat_dent.pdf"]
    ],
    "Derecho" => [
        ["titulo" => "Derecho Civil", "desc" => "Fundamentos de Derecho Civil", "link" => "https://www.oas.org/dil/esp/codigo_civil_bolivia.pdf"],
        ["titulo" => "Derecho Penal", "desc" => "Conceptos de derecho penal", "link" => "https://img.lpderecho.pe/wp-content/uploads/2020/03/derecho_penal_-_parte_general_-_claus_roxin-LP.pdf"]
    ],
    "Criminología" => [
        ["titulo" => "Introducción a la Criminología", "desc" => "Estudio del delito y criminalidad", "link" => "https://gc.scalahed.com/recursos/files/r161r/w25670w/SaberMas_U1/01_INTRODUCCION_AL_ESTUDIO_DE_LA_CRIMINOLOG.pdf"]
    ],
    "Arquitectura" => [
        ["titulo" => "Diseño Arquitectónico", "desc" => "Principios de diseño", "link" => "https://librosoa.unam.mx/bitstream/handle/123456789/3188/El_disen%C3%9Eo_arquitectoi%CC%80nico_digital.pdf?sequence=1&isAllowed=y"],
        ["titulo" => "Historia de la Arquitectura", "desc" => "Estilos y corrientes", "link" => "https://www.aliat.click/BibliotecasDigitales/construccion/Historia_de_la_arquitectura_I/Historia_de_la_arquitectura_I-Parte1.pdf"]
    ],
    "Diseño Urbano" => [
        ["titulo" => "Urbanismo", "desc" => "Planificación de ciudades", "link" => "https://oa.upm.es/11050/1/capitulo_01.pdf"]
    ]
];

$facultad_sel = $_GET['facultad'] ?? "";
$carrera_sel  = $_GET['carrera'] ?? "";
$desde_login = isset($_GET['login']) && $_GET['login'] == 1;
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Biblioteca Virtual</title>
  <link rel="stylesheet" href="css/biblioteca.css">
</head>
<body>
  <header>📚 Biblioteca Virtual Universitaria</header>
  <main>
    <!-- Formulario -->
    <form method="get">
      <select name="facultad" onchange="this.form.submit()">
        <option value="">Seleccione Facultad</option>
        <?php foreach ($facultades as $fac => $list): ?>
          <option value="<?= $fac ?>" <?= $facultad_sel == $fac ? "selected" : "" ?>>
            <?= $fac ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="carrera" <?= !$facultad_sel ? "disabled" : "" ?> onchange="this.form.submit()">
        <option value="">Seleccione Carrera</option>
        <?php if ($facultad_sel): ?>
          <?php foreach ($facultades[$facultad_sel] as $c): ?>
            <option value="<?= $c ?>" <?= $carrera_sel == $c ? "selected" : "" ?>>
              <?= $c ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>

      <button type="submit">Ver Temarios</button>
      <?php if ($desde_login): ?>
        <input type="hidden" name="login" value="1">
      <?php endif; ?>
    </form>

    <!-- Temarios -->
    <div class="grid">
      <?php if ($carrera_sel && isset($temarios[$carrera_sel])): ?>
        <?php foreach ($temarios[$carrera_sel] as $t): ?>
          <div class="card">
            <h3><?= $t['titulo'] ?></h3>
            <p><?= $t['desc'] ?></p>
            <a href="<?= $t['link'] ?>" target="_blank">📥 Descargar / Ver Libro</a>
          </div>
        <?php endforeach; ?>
      <?php elseif ($facultad_sel && !$carrera_sel): ?>
        <p class="msg">👉 Selecciona una carrera para ver sus temarios.</p>
      <?php else: ?>
        <p class="msg">👉 Selecciona una facultad y carrera para empezar.</p>
      <?php endif; ?>
    </div>

    <div class="volver">
      <?php if ($desde_login): ?>
        <button onclick="window.location.href='login.html'">⬅ Volver</button>
      <?php else: ?>
        <button onclick="window.location.href='postulante_dashboard.php'">⬅ Volver</button>
      <?php endif; ?>
    </div>

  </main>
  <footer>© 2025 Biblioteca Virtual Universitaria</footer>
</body>
</html>
