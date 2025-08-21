<?php
// Script para verificar y reparar la instalación de FPDF
require_once 'lib/fpdf.php';

echo "<h1>Diagnóstico de FPDF</h1>";

// Verificar versión de FPDF
echo "<h2>Versión de FPDF</h2>";
echo "<p>Versión instalada: " . (defined('FPDF_VERSION') ? FPDF_VERSION : 'No detectada') . "</p>";

// Verificar directorio de fuentes
echo "<h2>Directorio de fuentes</h2>";
$font_dir = dirname(__FILE__) . '/lib/font';
echo "<p>Directorio esperado: $font_dir</p>";
echo "<p>¿Existe el directorio? " . (is_dir($font_dir) ? 'Sí' : 'No') . "</p>";

// Si no existe el directorio, crearlo
if (!is_dir($font_dir)) {
    echo "<p>Creando directorio de fuentes...</p>";
    if (mkdir($font_dir, 0755, true)) {
        echo "<p>Directorio creado correctamente.</p>";
    } else {
        echo "<p>Error al crear el directorio.</p>";
    }
}

// Verificar fuentes core
echo "<h2>Fuentes core</h2>";
$core_fonts = array('courier', 'helvetica', 'times', 'symbol', 'zapfdingbats');
echo "<ul>";
foreach ($core_fonts as $font) {
    echo "<li>$font: " . (class_exists('FPDF') ? 'Disponible (integrada en FPDF)' : 'No disponible') . "</li>";
}
echo "</ul>";

// Probar creación de PDF básico
echo "<h2>Prueba de creación de PDF</h2>";
try {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Courier', '', 12);
    $pdf->Cell(0, 10, 'Prueba de FPDF', 0, 1, 'C');
    $pdf_content = $pdf->Output('', 'S');
    echo "<p>PDF generado correctamente (" . strlen($pdf_content) . " bytes)</p>";
    echo "<p><a href='simple-certificate.php' target='_blank'>Descargar PDF de prueba</a></p>";
} catch (Exception $e) {
    echo "<p>Error al generar PDF: " . $e->getMessage() . "</p>";
}

// Verificar configuración de PHP
echo "<h2>Configuración de PHP</h2>";
echo "<p>Versión de PHP: " . phpversion() . "</p>";
echo "<p>Memory limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max execution time: " . ini_get('max_execution_time') . " segundos</p>";
echo "<p>Output buffering: " . (ini_get('output_buffering') ? 'Activado' : 'Desactivado') . "</p>";

// Verificar permisos de escritura
echo "<h2>Permisos de escritura</h2>";
$temp_dir = sys_get_temp_dir();
echo "<p>Directorio temporal: $temp_dir</p>";
echo "<p>¿Permisos de escritura? " . (is_writable($temp_dir) ? 'Sí' : 'No') . "</p>";

// Sugerencias
echo "<h2>Sugerencias</h2>";
echo "<ul>";
echo "<li>Asegúrate de que el directorio de fuentes exista y tenga permisos de escritura.</li>";
echo "<li>Usa solo fuentes core de FPDF: courier, helvetica, times, symbol, zapfdingbats.</li>";
echo "<li>Evita caracteres especiales o usa utf8_decode() para textos con acentos.</li>";
echo "<li>Aumenta el límite de memoria si es necesario.</li>";
echo "</ul>";

echo "<h2>Solución alternativa</h2>";
echo "<p>Si continúas teniendo problemas con FPDF, considera usar una alternativa como mPDF o TCPDF.</p>";
echo "<p><a href='tcpdf-certificate.php'>Probar generación con TCPDF</a></p>";
?>
