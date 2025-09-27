<?php
// ----------------------------
// Temarios completos (mini-libro)
// ----------------------------
$temarios = [
    "Algoritmos I" => [
        "Descripción" => "Guía completa para aprender Algoritmos I, con ejemplos y ejercicios.",
        "Temas" => [
            "1. Introducción a algoritmos" => [
                "Definición de algoritmo",
                "Importancia en programación",
                "Ejemplo: Suma de dos números"
            ],
            "2. Variables y tipos de datos" => [
                "Enteros, flotantes, cadenas, booleanos",
                "Declaración y asignación",
                "Ejemplo: Variables en pseudocódigo"
            ],
            "3. Estructuras de control" => [
                "Condicionales (if, else)",
                "Bucles (for, while)",
                "Ejemplo: Serie de números pares"
            ],
            "4. Funciones y procedimientos" => [
                "Definición de funciones",
                "Parámetros y retorno",
                "Ejemplo: Función para calcular factorial"
            ]
        ]
    ],
    "Base de Datos" => [
        "Descripción" => "Guía completa para aprender Bases de Datos y SQL básico/avanzado.",
        "Temas" => [
            "1. Modelado relacional" => [
                "Concepto de entidad y relación",
                "Diagrama ER",
                "Ejemplo: Universidad"
            ],
            "2. SQL básico" => [
                "SELECT, INSERT, UPDATE, DELETE",
                "Filtrado con WHERE",
                "Ejemplo práctico"
            ],
            "3. Consultas avanzadas" => [
                "JOIN, GROUP BY, HAVING",
                "Subconsultas",
                "Ejemplo: Reporte de estudiantes por carrera"
            ],
            "4. Integridad y claves" => [
                "Claves primarias y foráneas",
                "Restricciones de integridad",
                "Ejemplo: Relación entre tablas"
            ]
        ]
    ]
    // ... puedes agregar todos los temarios
];

// ----------------------------
// Recibir título desde GET
// ----------------------------
$titulo = $_GET['titulo'] ?? '';
$titulo = trim($titulo);

if (!$titulo || !isset($temarios[$titulo])) {
    die("Temario no encontrado.");
}

// ----------------------------
// Crear contenido tipo libro
// ----------------------------
$contenido = "📘 Temario: $titulo\n\n";
$contenido .= "Descripción: " . $temarios[$titulo]["Descripción"] . "\n\n";
$contenido .= "========================================\n\n";

foreach ($temarios[$titulo]["Temas"] as $tema => $subtemas) {
    $contenido .= $tema . "\n";
    foreach ($subtemas as $sub) {
        $contenido .= "  - $sub\n";
    }
    $contenido .= "\n";
}

// ----------------------------
// Preparar descarga
// ----------------------------
$nombreArchivo = str_replace(' ', '_', $titulo) . "_libro.txt";

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . strlen($contenido));

echo $contenido;
exit();
