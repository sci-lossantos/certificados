<?php
// Certificado profesional simplificado - SOLO 2 PÁGINAS
require_once 'participante-auth.php';
require_once 'config/database.php';

// Verificar si TCPDF está disponible
if (!file_exists('lib/tcpdf/tcpdf.php')) {
    echo "<h1>TCPDF no está instalado</h1>";
    echo "<p>Por favor, descarga TCPDF desde <a href='https://github.com/tecnickcom/TCPDF/releases'>https://github.com/tecnickcom/TCPDF/releases</a> y colócalo en el directorio lib/tcpdf/</p>";
    exit();
}

require_once 'lib/tcpdf/tcpdf.php';

$auth = new ParticipanteAuth();
$auth->requireLogin();

// Verificar que se proporcionó un ID de documento
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID de documento no proporcionado");
}

$documento_id = intval($_GET['id']);
$participante_id = $_SESSION['participante_id'];

// Obtener conexión a la base de datos
$conn = getMySQLiConnection();

// Verificar que el documento pertenece al participante y obtener datos completos
$sql = "
    SELECT d.*, c.nombre as curso_nombre, c.duracion_horas, c.contenido_tematico, c.descripcion,
           p.nombres, p.apellidos, p.cedula,
           e.nombre as escuela_nombre, e.direccion as escuela_direccion,
           (SELECT COUNT(*) FROM firmas_documentos fd WHERE fd.documento_id = d.id AND fd.es_rechazo = 0) as firmas_completadas
    FROM documentos d
    INNER JOIN cursos c ON d.curso_id = c.id
    INNER JOIN participantes p ON d.participante_id = p.id
    INNER JOIN escuelas e ON c.escuela_id = e.id
    WHERE d.id = ? AND d.participante_id = ? AND d.tipo = 'certificado'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $documento_id, $participante_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Error: No se encontró el certificado o no está disponible para descarga");
}

$documento = $result->fetch_assoc();

// Función para formatear cédula
function formatearCedula($cedula) {
    $cedula_limpia = preg_replace('/[^0-9]/', '', $cedula);
    if (is_numeric($cedula_limpia) && !empty($cedula_limpia)) {
        return number_format($cedula_limpia, 0, '', '.');
    }
    return $cedula;
}

// Obtener las firmas del documento
$firmas_sql = "
    SELECT fd.*, 
           CONCAT(u.nombres, ' ', u.apellidos) as firmante_nombre, 
           r.nombre as firmante_cargo,
           r.id as rol_id,
           fu.contenido_firma as firma_imagen_path
    FROM firmas_documentos fd
    INNER JOIN usuarios u ON fd.usuario_id = u.id
    INNER JOIN roles r ON u.rol_id = r.id
    LEFT JOIN firmas_usuarios fu ON u.id = fu.usuario_id
    WHERE fd.documento_id = ? AND fd.es_rechazo = 0
    ORDER BY fd.fecha_firma ASC
";

$stmt = $conn->prepare($firmas_sql);
$stmt->bind_param("i", $documento_id);
$stmt->execute();
$firmas_result = $stmt->get_result();
$firmas = $firmas_result->fetch_all(MYSQLI_ASSOC);

// Organizar firmas por rol
$firma_director_nacional = null;
$firma_director_escuela = null;
$firma_coordinador = null;

foreach ($firmas as $firma) {
    $rol_id = intval($firma['rol_id']);
    
    if ($rol_id == 2) { // Dirección Nacional
        $firma_director_nacional = $firma;
    } elseif ($rol_id == 5) { // Director de Escuela
        $firma_director_escuela = $firma;
    } elseif ($rol_id == 6) { // Coordinador
        $firma_coordinador = $firma;
    }
}

// Crear PDF con TCPDF en formato horizontal
$pdf = new TCPDF('L', 'mm', 'LETTER', true, 'UTF-8', false);

// Configurar documento
$pdf->SetCreator('ESIBOC-DNBC');
$pdf->SetAuthor('Dirección Nacional de Bomberos de Colombia');
$pdf->SetTitle('Certificado de Curso - ' . $documento['nombres'] . ' ' . $documento['apellidos']);

// Eliminar cabecera y pie de página predeterminados
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Establecer márgenes
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(false); // IMPORTANTE: Desactivar salto automático de página

// ==================== PÁGINA 1: CERTIFICADO PRINCIPAL ====================
$pdf->AddPage();

// Logo institucional
if (file_exists('public/images/dnbc-logo.png')) {
    $pdf->Image('public/images/dnbc-logo.png', 25, 20, 30);
}

// Encabezado institucional
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetXY(20, 25);
$pdf->Cell(239, 10, 'DIRECCIÓN NACIONAL DE BOMBEROS DE COLOMBIA', 0, 0, 'C');

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY(20, 35);
$pdf->Cell(239, 8, strtoupper($documento['escuela_nombre']), 0, 0, 'C');

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, 45);
$pdf->Cell(239, 8, 'CERTIFICADO DE APROBACIÓN', 0, 0, 'C');

// Línea separadora
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.5);
$pdf->Line(25, 58, 255, 58);

// Contenido del certificado
$pdf->SetFont('helvetica', '', 14);
$pdf->SetXY(20, 70);
$pdf->Cell(239, 8, 'La Dirección Nacional de Bomberos de Colombia', 0, 0, 'C');
$pdf->SetXY(20, 78);
$pdf->Cell(239, 8, 'Por medio del presente certifica que:', 0, 0, 'C');

// Nombre del participante
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetXY(20, 95);
$nombre_completo = strtoupper($documento['nombres'] . ' ' . $documento['apellidos']);
$pdf->Cell(239, 12, $nombre_completo, 0, 0, 'C');

// Información del participante
$pdf->SetFont('helvetica', '', 14);
$pdf->SetXY(20, 115);
$cedula_formateada = formatearCedula($documento['cedula']);
$pdf->Cell(239, 8, 'Identificado(a) con Cédula de Ciudadanía No. ' . $cedula_formateada, 0, 0, 'C');

$pdf->SetXY(20, 130);
$pdf->Cell(239, 8, 'Ha aprobado satisfactoriamente el curso:', 0, 0, 'C');

// Nombre del curso
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY(20, 145);
$pdf->Cell(239, 10, strtoupper($documento['curso_nombre']), 0, 0, 'C');

// Información del curso
$pdf->SetFont('helvetica', '', 14);
$pdf->SetXY(20, 160);
$pdf->Cell(239, 8, 'Con una intensidad horaria de ' . $documento['duracion_horas'] . ' horas académicas', 0, 0, 'C');
$pdf->SetXY(20, 170);
$pdf->Cell(239, 8, 'Realizado en el año ' . date('Y'), 0, 0, 'C');

// FIRMAS EN LA PARTE INFERIOR
$y_firmas = 185;

// Firma Director Nacional (Izquierda)
if ($firma_director_nacional) {
    // Imagen de firma si existe
    if (!empty($firma_director_nacional['firma_imagen_path']) && file_exists($firma_director_nacional['firma_imagen_path'])) {
        $pdf->Image($firma_director_nacional['firma_imagen_path'], 50, $y_firmas - 10, 50, 15);
    }
    
    // Línea para la firma
    $pdf->Line(50, $y_firmas + 10, 120, $y_firmas + 10);
    
    // Información del firmante
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(50, $y_firmas + 12);
    $pdf->Cell(70, 4, strtoupper($firma_director_nacional['firmante_nombre']), 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY(50, $y_firmas + 16);
    $pdf->Cell(70, 3, 'Dirección Nacional', 0, 0, 'C');
    
    $pdf->SetXY(50, $y_firmas + 19);
    $pdf->Cell(70, 3, 'Bomberos de Colombia', 0, 0, 'C');
}

// Firma Director de Escuela (Derecha)
if ($firma_director_escuela) {
    // Imagen de firma si existe
    if (!empty($firma_director_escuela['firma_imagen_path']) && file_exists($firma_director_escuela['firma_imagen_path'])) {
        $pdf->Image($firma_director_escuela['firma_imagen_path'], 160, $y_firmas - 10, 50, 15);
    }
    
    // Línea para la firma
    $pdf->Line(160, $y_firmas + 10, 230, $y_firmas + 10);
    
    // Información del firmante
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(160, $y_firmas + 12);
    $pdf->Cell(70, 4, strtoupper($firma_director_escuela['firmante_nombre']), 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY(160, $y_firmas + 16);
    $pdf->Cell(70, 3, 'Director de Escuela', 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(160, $y_firmas + 19);
    $pdf->Cell(70, 3, $documento['escuela_nombre'], 0, 0, 'C');
}

// ==================== PÁGINA 2: CONTENIDO TEMÁTICO ====================
$pdf->AddPage();

// Logo institucional
if (file_exists('public/images/dnbc-logo.png')) {
    $pdf->Image('public/images/dnbc-logo.png', 25, 20, 30);
}

// Encabezado página 2
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetXY(20, 25);
$pdf->Cell(239, 10, 'DIRECCIÓN NACIONAL DE BOMBEROS DE COLOMBIA', 0, 0, 'C');

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY(20, 35);
$pdf->Cell(239, 8, strtoupper($documento['escuela_nombre']), 0, 0, 'C');

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, 45);
$pdf->Cell(239, 8, 'CONTENIDO TEMÁTICO', 0, 0, 'C');

// Línea separadora
$pdf->Line(25, 58, 255, 58);

// Información del curso
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(25, 70);
$pdf->Cell(0, 8, 'CURSO: ' . strtoupper($documento['curso_nombre']), 0, 0, 'L');

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(25, 85);
$pdf->Cell(120, 6, 'Duración: ' . $documento['duracion_horas'] . ' horas académicas', 0, 0, 'L');
$pdf->SetXY(150, 85);
$pdf->Cell(0, 6, 'Año: ' . date('Y'), 0, 0, 'L');

$pdf->SetXY(25, 95);
$pdf->Cell(120, 6, 'Participante: ' . $documento['nombres'] . ' ' . $documento['apellidos'], 0, 0, 'L');
$pdf->SetXY(150, 95);
$pdf->Cell(0, 6, 'Cédula: ' . $cedula_formateada, 0, 0, 'L');

// CONTENIDO TEMÁTICO
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetXY(25, 110);
$pdf->Cell(0, 8, 'CONTENIDO TEMÁTICO:', 0, 0, 'L');

$pdf->SetFont('helvetica', '', 11);
$y_contenido = 125;

if (!empty($documento['contenido_tematico'])) {
    // Usar el contenido temático de la base de datos
    $contenido_lineas = explode("\n", $documento['contenido_tematico']);
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
} else {
    // Contenido por defecto si no hay contenido específico
    $contenido_default = [
        "Fundamentos teóricos del área de estudio",
        "Desarrollo de competencias técnicas específicas",
        "Aplicación práctica de conocimientos adquiridos",
        "Metodologías y procedimientos operativos",
        "Normatividad y reglamentación vigente",
        "Casos prácticos y estudios de campo",
        "Evaluación continua de competencias",
        "Certificación de conocimientos y habilidades"
    ];
    
    foreach ($contenido_default as $index => $tema) {
        $pdf->SetXY(25, $y_contenido);
        $pdf->Cell(0, 6, ($index + 1) . '. ' . $tema, 0, 0, 'L');
        $y_contenido += 8;
    }
}

// Firma del Coordinador en la parte inferior derecha
if ($firma_coordinador) {
    $y_coord = 175;
    
    // Imagen de firma si existe
    if (!empty($firma_coordinador['firma_imagen_path']) && file_exists($firma_coordinador['firma_imagen_path'])) {
        $pdf->Image($firma_coordinador['firma_imagen_path'], 160, $y_coord - 10, 50, 15);
    }
    
    // Línea para la firma
    $pdf->Line(160, $y_coord + 10, 230, $y_coord + 10);
    
    // Información del firmante
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(160, $y_coord + 12);
    $pdf->Cell(70, 4, strtoupper($firma_coordinador['firmante_nombre']), 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY(160, $y_coord + 16);
    $pdf->Cell(70, 3, 'Coordinador del Curso', 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(160, $y_coord + 19);
    $pdf->Cell(70, 3, $documento['escuela_nombre'], 0, 0, 'C');
}

// Pie de página con código de verificación - SOLO EN LAS 2 PÁGINAS
$pdf->setPage(1);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(20, 205);
$codigo_verificacion = $documento['codigo_unico'] ?? 'CERT-' . $documento['id'] . '-' . date('Y');
$pdf->Cell(239, 4, 'Código de verificación: ' . $codigo_verificacion, 0, 0, 'C');
$pdf->SetXY(20, 209);
$pdf->Cell(239, 4, 'Expedido el ' . date('d/m/Y'), 0, 0, 'C');

$pdf->setPage(2);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(20, 205);
$pdf->Cell(239, 4, 'Código de verificación: ' . $codigo_verificacion, 0, 0, 'C');
$pdf->SetXY(20, 209);
$pdf->Cell(239, 4, 'Expedido el ' . date('d/m/Y'), 0, 0, 'C');

// Generar el PDF
$filename = 'Certificado_' . str_replace(' ', '_', $documento['nombres'] . '_' . $documento['apellidos']) . '_' . preg_replace('/[^0-9]/', '', $documento['cedula']) . '.pdf';
$pdf->Output($filename, 'D');
exit();
?>
