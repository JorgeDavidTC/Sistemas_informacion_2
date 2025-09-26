<?php
// ------------------
// Datos simulados
// ------------------
$carreras = [
    "ingenieria" => ["Sistemas", "Civil", "Electrónica"],
    "economia"   => ["Administración", "Contaduría", "Economía"],
    "medicina"   => ["Medicina General", "Enfermería", "Nutrición"]
];

$temarios = [
    "Sistemas" => [
        ["titulo" => "Algoritmos I", "desc" => "Temario de Algoritmos I", "link" => "#"],
        ["titulo" => "Base de Datos", "desc" => "Guía de SQL y Modelado", "link" => "#"]
    ],
    "Civil" => [
        ["titulo" => "Estática", "desc" => "Conceptos de mecánica de cuerpos rígidos", "link" => "#"],
        ["titulo" => "Topografía", "desc" => "Manual práctico de topografía", "link" => "#"]
    ],
    "Electrónica" => [
        ["titulo" => "Circuitos I", "desc" => "Introducción a circuitos eléctricos", "link" => "#"]
    ],
    "Administración" => [
        ["titulo" => "Contabilidad", "desc" => "Fundamentos contables", "link" => "#"],
        ["titulo" => "Marketing", "desc" => "Guía de marketing básico", "link" => "#"]
    ],
    "Contaduría" => [
        ["titulo" => "Auditoría I", "desc" => "Conceptos de auditoría", "link" => "#"]
    ],
    "Economía" => [
        ["titulo" => "Microeconomía", "desc" => "Oferta, demanda y equilibrio", "link" => "#"]
    ],
    "Medicina General" => [
        ["titulo" => "Anatomía Humana", "desc" => "Guía de estudio de anatomía", "link" => "#"]
    ],
    "Enfermería" => [
        ["titulo" => "Cuidados Básicos", "desc" => "Manual de procedimientos básicos", "link" => "#"]
    ],
    "Nutrición" => [
        ["titulo" => "Dietética I", "desc" => "Bases de la nutrición saludable", "link" => "#"]
    ]
];

// ------------------
// Lógica PHP
// ------------------
$facultad = $_GET['facultad'] ?? "";
$carrera  = $_GET['carrera'] ?? "";
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
    select, button {
      padding: 10px;
      border-radius: 8px;
      border: none;
      font-size: 1em;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    button {
      background: #4e73df;
      color: white;
      cursor: pointer;
      transition: background 0.3s;
    }
    button:hover {
      background: #2e59d9;
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
  <header>📚 Biblioteca Virtual Universitaria</header>
  <main>
    <!-- Formulario -->
    <form method="get">
      <select name="facultad" onchange="this.form.submit()">
        <option value="">Seleccione Facultad</option>
        <?php foreach ($carreras as $fac => $list): ?>
          <option value="<?= $fac ?>" <?= $facultad == $fac ? "selected" : "" ?>>
            <?= ucfirst($fac) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="carrera" <?= !$facultad ? "disabled" : "" ?> onchange="this.form.submit()">
        <option value="">Seleccione Carrera</option>
        <?php if ($facultad): ?>
          <?php foreach ($carreras[$facultad] as $c): ?>
            <option value="<?= $c ?>" <?= $carrera == $c ? "selected" : "" ?>>
              <?= $c ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
      <button type="submit">Ver Temarios</button>
    </form>

    <!-- Temarios -->
    <div class="grid">
      <?php if ($carrera && isset($temarios[$carrera])): ?>
        <?php foreach ($temarios[$carrera] as $t): ?>
          <div class="card">
            <h3><?= $t['titulo'] ?></h3>
            <p><?= $t['desc'] ?></p>
            <a href="<?= $t['link'] ?>" target="_blank">📥 Ver Temario</a>
          </div>
        <?php endforeach; ?>
      <?php elseif ($facultad && !$carrera): ?>
        <p>👉 Selecciona una carrera para ver sus temarios.</p>
      <?php else: ?>
        <p>👉 Selecciona una facultad y carrera para empezar.</p>
      <?php endif; ?>
      <button class="btn-volver" onclick="window.location.href='postulante_dashboard.php'">⬅ Volver</button>

    </div>
  </main>
  <footer>© 2025 Biblioteca Virtual Universitaria</footer>
</body>
</html>
