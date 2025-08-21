<?php
// Crear estructura de carpetas para assets
$directories = [
    'assets',
    'assets/css',
    'assets/js',
    'assets/images',
    'assets/fonts'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Carpeta creada: $dir\n";
    } else {
        echo "Carpeta ya existe: $dir\n";
    }
}

echo "Estructura de assets creada correctamente.\n";
?>
