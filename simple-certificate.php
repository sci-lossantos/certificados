<?php
// Script simplificado para probar FPDF
require_once 'lib/fpdf.php';

// Crear una instancia básica de FPDF
$pdf = new FPDF();
$pdf->AddPage();

// Configurar fuente - Asegurarse de que sea una fuente core de FPDF
$pdf->SetFont('Courier', '', 12); // Usar Courier que es una fuente core

// Texto simple
$pdf->Cell(0, 10, 'Prueba de FPDF', 0, 1, 'C');
$pdf->Cell(0, 10, 'Si puedes ver este texto, FPDF funciona correctamente', 0, 1, 'C');

// Generar el PDF
$pdf->Output('test.pdf', 'D');
exit();
?>
