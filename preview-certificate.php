<?php
// Vista previa de certificado con configuración personalizable
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Solo usuarios autenticados pueden ver la vista previa
$auth->requireAuth();

// Verificar si TCPDF está disponible
if (!file_exists('lib/tcpdf/tcpdf.php')) {
    echo "<h1>TCPDF no está instalado</h1>";
    echo "<p>Por favor, descarga TCPDF desde <a href='https://github.com/tecnickcom/TCPDF/releases'>https://github.com/tecnickcom/TCPDF/releases</a> y colócalo en el directorio lib/tcpdf/</p>";
    exit();
}

require_once 'lib/tcpdf/tcpdf.php';

// Obtener ID de escuela
$escuela_id = $_GET['escuela_id'] ?? null;

if (!$escuela_id) {
    die("Error: ID de escuela no proporcionado");
}

// Obtener configuración del certificado para esta escuela
$config_sql = "SELECT * FROM configuracion_certificados WHERE escuela_id = ?";
$stmt_config = $db->prepare($config_sql);
$stmt_config->execute([$escuela_id]);
$config = $stmt_config->fetch();

// Obtener información de la escuela
$escuela_sql = "SELECT * FROM escuelas WHERE id = ?";
$stmt_escuela = $db->prepare($escuela_sql);
$stmt_escuela->execute([$escuela_id]);
$escuela = $stmt_escuela->fetch();

if (!$escuela) {
    die("Error: Escuela no encontrada");
}

// Si no hay configuración, usar valores por defecto
if (!$config) {
    $config = [
        'titulo_principal' => 'DIRECCIÓN NACIONAL DE BOMBEROS DE COLOMBIA',
        'subtitulo_certificado' => 'CERTIFICADO DE APROBACIÓN',
        'texto_certifica' => "La Dirección Nacional de Bomberos de Colombia\nPor medio del presente certifica que:",
        'texto_aprobacion' => 'Ha aprobado satisfactoriamente el curso:',
        'texto_intensidad' => 'Con una intensidad horaria de {horas} horas académicas',
        'texto_realizacion' => 'Realizado en el año {año}',
        'mostrar_firma_director_nacional' => true,
        'texto_director_nacional' => "Dirección Nacional\nBomberos de Colombia",
        'mostrar_firma_director_escuela' => true,
        'texto_director_escuela' => 'Director de Escuela',
        'mostrar_firma_coordinador' => true,
        'texto_coordinador' => 'Coordinador del Curso',
        'titulo_contenido' => 'CONTENIDO TEMÁTICO',
        'mostrar_info_curso_pagina2' => true,
        'texto_codigo_verificacion' => 'Código de verificación: {codigo}',
        'texto_expedicion' => 'Expedido el {fecha}',
        'usar_imagen_fondo' => false,
        'opacidad_fondo' => 1.0,
        'ajustar_imagen_fondo' => 'stretch'
    ];
}

// Datos de prueba
$datos_prueba = [
    'nombres' => 'JUAN CARLOS',
    'apellidos' => 'PÉREZ GONZÁLEZ',
    'cedula' => '12345678',
    'curso_nombre' => 'CURSO DE BOMBEROS BÁSICO',
    'duracion_horas' => '40',
    'contenido_tematico' => "Fundamentos de la actividad bomberil\nPrimeros auxilios básicos\nManejo de equipos contra incendios\nTécnicas de rescate\nPrevención de riesgos\nNormatividad y protocolos\nPráctica en campo\nEvaluación final",
    'escuela_nombre' => $escuela['nombre'],
    'codigo_unico' => 'PREV-' . date('Y') . '-' . $escuela_id,
    'calificacion' => '85'
];

// Función para formatear cédula
function formatearCedula($cedula) {
    $cedula_limpia = preg_replace('/[^0-9]/', '', $cedula);
    if (is_numeric($cedula_limpia) && !empty($cedula_limpia)) {
        return number_format($cedula_limpia, 0, '', '.');
    }
    return $cedula;
}

// Función para reemplazar variables en texto
function reemplazarVariables($texto, $variables) {
    foreach ($variables as $variable => $valor) {
        $texto = str_replace('{' . $variable . '}', $valor, $texto);
    }
    return $texto;
}

// Función para agregar imagen de fondo
function agregarImagenFondo($pdf, $ruta_imagen, $opacidad = 1.0, $ajuste = 'stretch') {
    if (empty($ruta_imagen) || !file_exists($ruta_imagen)) {
        return false;
    }
    
    // Obtener dimensiones de la página (formato LETTER horizontal)
    $ancho_pagina = 279.4; // mm
    $alto_pagina = 215.9;  // mm
    
    try {
        // Determinar si es PDF o imagen
        $extension = strtolower(pathinfo($ruta_imagen, PATHINFO_EXTENSION));
        
        if ($extension === 'pdf') {
            // Para archivos PDF, usar como plantilla
            $pdf->setSourceFile($ruta_imagen);
            $tplId = $pdf->importPage(1);
            $pdf->useTemplate($tplId, 0, 0, $ancho_pagina, $alto_pagina);
        } else {
            // Para imágenes, ajustar según configuración
            switch ($ajuste) {
                case 'fit':
                    // Mantener proporción, ajustar al tamaño de página
                    $pdf->Image($ruta_imagen, 0, 0, $ancho_pagina, $alto_pagina, '', '', '', false, 300, '', false, false, 0, false, false, false);
                    break;
                case 'fill':
                    // Llenar toda la página, puede cortar imagen
                    $pdf->Image($ruta_imagen, 0, 0, $ancho_pagina, $alto_pagina, '', '', '', false, 300, '', false, false, 0, false, false, true);
                    break;
                case 'stretch':
                default:
                    // Estirar para llenar exactamente
                    $pdf->Image($ruta_imagen, 0, 0, $ancho_pagina, $alto_pagina, '', '', '', false, 300, '', false, false, 0);
                    break;
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error al agregar imagen de fondo: " . $e->getMessage());
        return false;
    }
}

// Variables para reemplazar en los textos
$variables = [
    'horas' => $datos_prueba['duracion_horas'],
    'año' => date('Y'),
    'codigo' => $datos_prueba['codigo_unico'],
    'fecha' => date('d/m/Y')
];

// Crear PDF con TCPDF en formato horizontal
$pdf = new TCPDF('L', 'mm', 'LETTER', true, 'UTF-8', false);

// Configurar documento
$pdf->SetCreator('ESIBOC-DNBC');
$pdf->SetAuthor($config['titulo_principal']);
$pdf->SetTitle('Vista Previa Certificado - ' . $escuela['nombre']);

// Eliminar cabecera y pie de página predeterminados
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Establecer márgenes
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(false);

// ==================== PÁGINA 1: CERTIFICADO PRINCIPAL ====================
$pdf->AddPage();

// AGREGAR IMAGEN DE FONDO PÁGINA 1
if ($config['usar_imagen_fondo'] && !empty($config['imagen_fondo_pagina1'])) {
    agregarImagenFondo($pdf, $config['imagen_fondo_pagina1'], 
                      $config['opacidad_fondo'] ?? 1.0, 
                      $config['ajustar_imagen_fondo'] ?? 'stretch');
}

// Logo institucional
if (file_exists('public/images/dnbc-logo.png')) {
    $pdf->Image('public/images/dnbc-logo.png', 25, 20, 30);
}

// Encabezado institucional usando configuración
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetXY(20, 25);
$pdf->Cell(239, 10, $config['titulo_principal'], 0, 0, 'C');

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY(20, 35);
$pdf->Cell(239, 8, strtoupper($datos_prueba['escuela_nombre']), 0, 0, 'C');

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, 45);
$pdf->Cell(239, 8, $config['subtitulo_certificado'], 0, 0, 'C');

// Línea separadora
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.5);
$pdf->Line(25, 58, 255, 58);

// Contenido del certificado usando configuración
$pdf->SetFont('helvetica', '', 14);
$texto_certifica_lineas = explode("\n", $config['texto_certifica']);
$y_actual = 70;

foreach ($texto_certifica_lineas as $linea) {
    $pdf->SetXY(20, $y_actual);
    $pdf->Cell(239, 8, trim($linea), 0, 0, 'C');
    $y_actual += 8;
}

// Nombre del participante
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetXY(20, 95);
$nombre_completo = strtoupper($datos_prueba['nombres'] . ' ' . $datos_prueba['apellidos']);
$pdf->Cell(239, 12, $nombre_completo, 0, 0, 'C');

// Información del participante
$pdf->SetFont('helvetica', '', 14);
$pdf->SetXY(20, 115);
$cedula_formateada = formatearCedula($datos_prueba['cedula']);
$pdf->Cell(239, 8, 'Identificado(a) con Cédula de Ciudadanía No. ' . $cedula_formateada, 0, 0, 'C');

$pdf->SetXY(20, 130);
$pdf->Cell(239, 8, $config['texto_aprobacion'], 0, 0, 'C');

// Nombre del curso
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY(20, 145);
$pdf->Cell(239, 10, strtoupper($datos_prueba['curso_nombre']), 0, 0, 'C');

// Información del curso usando configuración
$pdf->SetFont('helvetica', '', 14);
$pdf->SetXY(20, 160);
$pdf->Cell(239, 8, reemplazarVariables($config['texto_intensidad'], $variables), 0, 0, 'C');
$pdf->SetXY(20, 170);
$pdf->Cell(239, 8, reemplazarVariables($config['texto_realizacion'], $variables), 0, 0, 'C');

// FIRMAS EN LA PARTE INFERIOR
$y_firmas = 185;

// Firma Director Nacional (solo si está habilitada)
if ($config['mostrar_firma_director_nacional']) {
    // Línea para la firma
    $pdf->Line(50, $y_firmas + 10, 120, $y_firmas + 10);
    
    // Información del firmante
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(50, $y_firmas + 12);
    $pdf->Cell(70, 4, '[DIRECTOR NACIONAL]', 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $texto_director_lineas = explode("\n", $config['texto_director_nacional']);
    $y_texto = $y_firmas + 16;
    
    foreach ($texto_director_lineas as $linea) {
        $pdf->SetXY(50, $y_texto);
        $pdf->Cell(70, 3, trim($linea), 0, 0, 'C');
        $y_texto += 3;
    }
}

// Firma Director de Escuela (solo si está habilitada)
if ($config['mostrar_firma_director_escuela']) {
    // Línea para la firma
    $pdf->Line(160, $y_firmas + 10, 230, $y_firmas + 10);
    
    // Información del firmante
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(160, $y_firmas + 12);
    $pdf->Cell(70, 4, '[DIRECTOR DE ESCUELA]', 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY(160, $y_firmas + 16);
    $pdf->Cell(70, 3, $config['texto_director_escuela'], 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(160, $y_firmas + 19);
    $pdf->Cell(70, 3, $datos_prueba['escuela_nombre'], 0, 0, 'C');
}

// ==================== PÁGINA 2: CONTENIDO TEMÁTICO ====================
$pdf->AddPage();

// AGREGAR IMAGEN DE FONDO PÁGINA 2
if ($config['usar_imagen_fondo'] && !empty($config['imagen_fondo_pagina2'])) {
    agregarImagenFondo($pdf, $config['imagen_fondo_pagina2'], 
                      $config['opacidad_fondo'] ?? 1.0, 
                      $config['ajustar_imagen_fondo'] ?? 'stretch');
}

// Logo institucional
if (file_exists('public/images/dnbc-logo.png')) {
    $pdf->Image('public/images/dnbc-logo.png', 25, 20, 30);
}

// Encabezado página 2
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetXY(20, 25);
$pdf->Cell(239, 10, $config['titulo_principal'], 0, 0, 'C');

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY(20, 35);
$pdf->Cell(239, 8, strtoupper($datos_prueba['escuela_nombre']), 0, 0, 'C');

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, 45);
$pdf->Cell(239, 8, $config['titulo_contenido'], 0, 0, 'C');

// Línea separadora
$pdf->Line(25, 58, 255, 58);

// Información del curso en página 2 (solo si está habilitada)
$y_contenido = 70;

if ($config['mostrar_info_curso_pagina2']) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetXY(25, $y_contenido);
    $pdf->Cell(0, 8, 'CURSO: ' . strtoupper($datos_prueba['curso_nombre']), 0, 0, 'L');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetXY(25, $y_contenido + 15);
    $pdf->Cell(120, 6, 'Duración: ' . $datos_prueba['duracion_horas'] . ' horas académicas', 0, 0, 'L');
    $pdf->SetXY(150, $y_contenido + 15);
    $pdf->Cell(0, 6, 'Año: ' . date('Y'), 0, 0, 'L');
    
    $pdf->SetXY(25, $y_contenido + 25);
    $pdf->Cell(120, 6, 'Participante: ' . $datos_prueba['nombres'] . ' ' . $datos_prueba['apellidos'], 0, 0, 'L');
    $pdf->SetXY(150, $y_contenido + 25);
    $pdf->Cell(0, 6, 'Cédula: ' . formatearCedula($datos_prueba['cedula']), 0, 0, 'L');
    
    $y_contenido += 45;
}

// CONTENIDO TEMÁTICO
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetXY(25, $y_contenido);
$pdf->Cell(0, 8, 'CONTENIDO TEMÁTICO:', 0, 0, 'L');

$pdf->SetFont('helvetica', '', 11);
$y_contenido += 15;

if (!empty($datos_prueba['contenido_tematico'])) {
    // Usar el contenido temático de prueba
    $contenido_lineas = explode("\n", $datos_prueba['contenido_tematico']);
    $contador = 1;
    
    foreach ($contenido_lineas as $linea) {
        $linea = trim($linea);
        if (!empty($linea)) {
            // Si la línea no empieza con número, agregarle numeración
            if (!preg_match('/^\d+\./', $linea)) {
                $linea = $contador . '. ' . $linea;
                $contador++;
            }
            
            $pdf->SetXY(25, $y_contenido);
            $pdf->Cell(0, 6, $linea, 0, 0, 'L');
            $y_contenido += 8;
        }
    }
}

// Firma del Coordinador (solo si está habilitada)
if ($config['mostrar_firma_coordinador']) {
    $y_coord = 175;
    
    // Línea para la firma
    $pdf->Line(160, $y_coord + 10, 230, $y_coord + 10);
    
    // Información del firmante
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(160, $y_coord + 12);
    $pdf->Cell(70, 4, '[COORDINADOR DEL CURSO]', 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY(160, $y_coord + 16);
    $pdf->Cell(70, 3, $config['texto_coordinador'], 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(160, $y_coord + 19);
    $pdf->Cell(70, 3, $datos_prueba['escuela_nombre'], 0, 0, 'C');
}

// Pie de página con configuración personalizada
$pdf->setPage(1);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(20, 205);
$pdf->Cell(239, 4, reemplazarVariables($config['texto_codigo_verificacion'], $variables), 0, 0, 'C');
$pdf->SetXY(20, 209);
$pdf->Cell(239, 4, reemplazarVariables($config['texto_expedicion'], $variables), 0, 0, 'C');

$pdf->setPage(2);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(20, 205);
$pdf->Cell(239, 4, reemplazarVariables($config['texto_codigo_verificacion'], $variables), 0, 0, 'C');
$pdf->SetXY(20, 209);
$pdf->Cell(239, 4, reemplazarVariables($config['texto_expedicion'], $variables), 0, 0, 'C');

// Generar el PDF
$filename = 'Vista_Previa_Certificado_' . str_replace(' ', '_', $escuela['nombre']) . '.pdf';
$pdf->Output($filename, 'D');
exit();
?>
