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

$carreras_db = [];
$sql = "SELECT id_carrera, nombre FROM carreras ORDER BY nombre";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $carreras_db[$row['id_carrera']] = $row['nombre'];
    }
}

$facultades = [
    "Ingeniería" => ["Ingeniería de Sistemas", "Ingeniería Civil"],
    "Medicina"   => ["Medicina"],
    "Derecho"    => ["Derecho"]
];

$temarios = [
    "Ingeniería de Sistemas" => [
        ["titulo" => "Algoritmos I", "desc" => "Temario de Algoritmos I", "link" => "http://imagenes.uniremington.edu.co/moodle/M%C3%B3dulos%20de%20aprendizaje/algiritmos%201/Algoritmos_I_modulo_listo_ok2016.pdf"],
        ["titulo" => "Base de Datos", "desc" => "Guía de SQL y Modelado", "link" => "https://openstax.org/details/books/introduction-to-databases"]
    ],
    "Ingeniería Civil" => [
        ["titulo" => "Estática", "desc" => "Conceptos de mecánica de cuerpos rígidos", "link" => "https://open.umn.edu/opentextbooks/textbooks/engineering-mechanics-statics"],
        ["titulo" => "Topografía", "desc" => "Manual práctico de topografía", "link" => "https://www.freebookcentre.net/Engineering/Surveying-Books.html"]
    ],
    "Medicina" => [
        ["titulo" => "Anatomía Humana", "desc" => "Guía de estudio de anatomía", "link" => "https://openstax.org/details/books/anatomy-and-physiology"]
    ],
    "Derecho" => [
        ["titulo" => "Derecho Civil", "desc" => "Fundamentos de Derecho Civil", "link" => "https://www.freebookcentre.net/Law.html"]
    ]
];

$facultad_sel = $_GET['facultad'] ?? "";
$carrera_sel  = $_GET['carrera'] ?? "";
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Biblioteca Virtual</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #74ebd5, #ACB6E5);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    header {
      background: rgba(0,0,0,0.7);
      color: white;
      padding: 20px;
      text-align: center;
      font-size: 1.8em;
      letter-spacing: 2px;
    }
    .inicio-btn {
      position: absolute;
      top: 15px;
      left: 15px;
      background: #4e73df;
      color: white;
      padding: 10px 15px;
      border-radius: 8px;
      text-decoration: none;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.95em;
    }
    .inicio-btn:hover {
      background: #2e59d9;
    }
    main {
      flex: 1;
      padding: 20px;
      max-width: 1000px;
      margin: auto;
    }
    form {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }
    select {
      padding: 10px;
      border-radius: 8px;
      border: none;
      font-size: 1em;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
    }
    .card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    }
    .card h3 {
      margin: 0 0 10px;
      color: #333;
    }
    .card p {
      font-size: 0.9em;
      color: #555;
    }
    .card a {
      display: inline-block;
      margin-top: 10px;
      text-decoration: none;
      background: #4e73df;
      color: white;
      padding: 8px 12px;
      border-radius: 6px;
      transition: background 0.3s;
    }
    .card a:hover {
      background: #2e59d9;
    }
    footer {
      background: rgba(0,0,0,0.8);
      color: white;
      text-align: center;
      padding: 10px;
      font-size: 0.9em;
    }
  </style>
</head>
<body>

  <a href="postulante_dashboard.php" class="inicio-btn">
    🏠 Inicio
  </a>

  <header>Biblioteca Virtual Universitaria</header>

  <main>
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
    </form>

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
        <p>Selecciona una carrera para ver sus temarios.</p>
      <?php else: ?>
        <p>Selecciona una facultad y carrera para empezar.</p>
      <?php endif; ?>
    </div>
  </main>

  <footer>© 2025 Biblioteca Virtual Universitaria</footer>

</body>
</html>
