<?php
// Certificado profesional con el formato específico requerido
require_once 'participante-auth.php';
require_once 'config/database.php';
require_once 'certificate-numbering.php';

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

// Inicializar clase de numeración
$numbering = new CertificateNumbering();

// Obtener datos completos del certificado
$documento = $numbering->getCertificateData($documento_id);

if (!$documento || $documento['participante_id'] != $participante_id) {
    die("Error: No se encontró el certificado o no está disponible para descarga");
}

// Generar numeración si no existe
if (empty($documento['numero_consecutivo'])) {
    $numbering->updateDocumentNumbering($documento_id);
    $documento = $numbering->getCertificateData($documento_id); // Recargar datos
}

// Obtener configuración del certificado para esta escuela
$conn = getMySQLiConnection();
$config_sql = "SELECT * FROM configuracion_certificados WHERE escuela_id = ?";
$stmt_config = $conn->prepare($config_sql);
$stmt_config->bind_param("i", $documento['escuela_id']);
$stmt_config->execute();
$config_result = $stmt_config->get_result();
$config = $config_result->fetch_assoc();

// Si no hay configuración, usar valores por defecto
if (!$config) {
    $config = [
        'titulo_principal' => 'DIRECCIÓN NACIONAL DE BOMBEROS DE COLOMBIA',
        'subtitulo_certificado' => 'CERTIFICADO DE APROBACIÓN',
        'texto_certifica_que' => 'Certifica que:',
        'texto_identificado' => 'Identificado con C.C. No.',
        'texto_asistio_aprobo' => 'Asistió y aprobó los requisitos del Curso:',
        'texto_curso_autorizado' => 'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia',
        'texto_bajo_acta' => 'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}',
        'texto_duracion' => 'Con una duración de: {horas} horas académicas',
        'texto_realizado_en' => 'Realizado en {lugar} del {fecha_inicio} al {fecha_fin}',
        'texto_constancia' => 'En constancia de lo anterior, se firma a los {fecha_firma}',
        'mostrar_numero_consecutivo' => true,
        'texto_numero_consecutivo' => 'Certificado No. {consecutivo}',
        'mostrar_firma_director_nacional' => true,
        'mostrar_firma_director_escuela' => true,
        'mostrar_firma_coordinador' => true,
        'titulo_contenido' => 'CONTENIDO PROGRAMÁTICO'
    ];
}

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

// Variables para reemplazar en los textos
$variables = [
    'consecutivo' => $documento['numero_consecutivo'] ?? 'CERT-' . $documento['id'],
    'registro_curso' => $documento['numero_registro_curso'] ?? 'REG-CURSO-' . $documento['curso_id'],
    'numero_acta' => $documento['numero_acta'] ?? '001',
    'fecha_acta' => $numbering->formatearFecha($documento['fecha_acta'] ?? date('Y-m-d'), 'texto'),
    'nombre_cuerpo_bomberos' => $documento['escuela_nombre'],
    'horas' => $documento['duracion_horas'],
    'lugar' => $documento['lugar_realizacion'] ?? $documento['escuela_nombre'],
    'fecha_inicio' => $numbering->formatearFecha($documento['fecha_inicio']),
    'fecha_fin' => $numbering->formatearFecha($documento['fecha_fin']),
    'fecha_firma' => $numbering->formatearFecha($config['fecha_firma_certificados'] ?? date('Y-m-d'), 'texto')
];

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
//$pdf = new TCPDF('L', 'mm', 'LETTER', true, 'UTF-8', false);

// Configurar documento
//$pdf->SetCreator('ESIBOC-DNBC');
//$pdf->SetAuthor($config['titulo_principal']);
//$pdf->SetTitle('Certificado - ' . $documento['nombres'] . ' ' . $documento['apellidos']);

// Eliminar cabecera y pie de página predeterminados
//$pdf->setPrintHeader(false);
//$pdf->setPrintFooter(false);

// Establecer márgenes
//$pdf->SetMargins(20, 20, 20);
//$pdf->SetAutoPageBreak(false);

// ==================== PÁGINA 1: CERTIFICADO PRINCIPAL ====================
//$pdf->AddPage();

// AGREGAR IMAGEN DE FONDO PÁGINA 1 si está configurada
//if ($config['usar_imagen_fondo'] && !empty($config['imagen_fondo_pagina1'])) {
//    if (file_exists($config['imagen_fondo_pagina1'])) {
//        $pdf->Image($config['imagen_fondo_pagina1'], 0, 0, 279.4, 215.9, '', '', '', false, 300, '', false, false, 0);
//    }
//}

// Logo institucional
//if (file_exists('public/images/dnbc-logo.png')) {
//    $pdf->Image('public/images/dnbc-logo.png', 25, 15, 30);
//}

// NÚMERO CONSECUTIVO DEL CERTIFICADO (parte superior derecha)
//if ($config['mostrar_numero_consecutivo']) {
//    $pdf->SetFont('helvetica', 'B', 12);
//    $pdf->SetXY(180, 15);
//    $pdf->Cell(75, 8, reemplazarVariables($config['texto_numero_consecutivo'], $variables), 0, 0, 'R');
//}

// Encabezado institucional
//$pdf->SetTextColor(0, 0, 0);
//$pdf->SetFont('helvetica', 'B', 18);
//$pdf->SetXY(20, 30);
//$pdf->Cell(239, 10, $config['titulo_principal'], 0, 0, 'C');

//$pdf->SetFont('helvetica', 'B', 16);
//$pdf->SetXY(20, 42);
//$pdf->Cell(239, 8, strtoupper($documento['escuela_nombre']), 0, 0, 'C');

//$pdf->SetFont('helvetica', 'B', 14);
//$pdf->SetXY(20, 54);
//$pdf->Cell(239, 8, $config['subtitulo_certificado'], 0, 0, 'C');

// Línea separadora
//$pdf->SetDrawColor(0, 0, 0);
//$pdf->SetLineWidth(0.5);
//$pdf->Line(25, 68, 255, 68);

// CONTENIDO DEL CERTIFICADO SIGUIENDO EL ORDEN ESPECIFICADO
//$y_actual = 78;

// "Certifica que:"
//$pdf->SetFont('helvetica', '', 14);
//$pdf->SetXY(20, $y_actual);
//$pdf->Cell(239, 8, $config['texto_certifica_que'], 0, 0, 'C');
//$y_actual += 12;

// Nombre del participante
//$pdf->SetFont('helvetica', 'B', 20);
//$pdf->SetXY(20, $y_actual);
//$nombre_completo = strtoupper($documento['nombres'] . ' ' . $documento['apellidos']);
//$pdf->Cell(239, 12, $nombre_completo, 0, 0, 'C');
//$y_actual += 16;

// Identificado con C.C. No.
//$pdf->SetFont('helvetica', '', 14);
//$pdf->SetXY(20, $y_actual);
//$cedula_formateada = formatearCedula($documento['cedula']);
//$pdf->Cell(239, 8, $config['texto_identificado'] . ' ' . $cedula_formateada, 0, 0, 'C');
//$y_actual += 12;

// Asistió y aprobó los requisitos del Curso:
//$pdf->SetXY(20, $y_actual);
//$pdf->Cell(239, 8, $config['texto_asistio_aprobo'], 0, 0, 'C');
//$y_actual += 10;

// Nombre del curso
//$pdf->SetFont('helvetica', 'B', 16);
//$pdf->SetXY(20, $y_actual);
//$pdf->Cell(239, 10, strtoupper($documento['curso_nombre']), 0, 0, 'C');
//$y_actual += 14;

// Curso autorizado bajo registro
//$pdf->SetFont('helvetica', '', 12);
//$pdf->SetXY(20, $y_actual);
//$pdf->Cell(239, 6, reemplazarVariables($config['texto_curso_autorizado'], $variables), 0, 0, 'C');
//$y_actual += 10;

// Bajo acta número
//$pdf->SetXY(20, $y_actual);
//$pdf->Cell(239, 6, reemplazarVariables($config['texto_bajo_acta'], $variables), 0, 0, 'C');
//$y_actual += 10;

// Con una duración de
//$pdf->SetXY(20, $y_actual);
//$pdf->Cell(239, 6, reemplazarVariables($config['texto_duracion'], $variables), 0, 0, 'C');
//$y_actual += 10;

// Realizado en
//$pdf->SetXY(20, $y_actual);
//$intervalo_fechas = $numbering->formatearIntervaloFechas($documento['fecha_inicio'], $documento['fecha_fin']);
//$variables['fecha_inicio'] = $numbering->formatearFecha($documento['fecha_inicio']);
//$variables['fecha_fin'] = $numbering->formatearFecha($documento['fecha_fin']);
//$pdf->Cell(239, 6, reemplazarVariables($config['texto_realizado_en'], $variables), 0, 0, 'C');
//$y_actual += 12;

// En constancia de lo anterior
//$pdf->SetXY(20, $y_actual);
//$pdf->Cell(239, 6, reemplazarVariables($config['texto_constancia'], $variables), 0, 0, 'C');

// FIRMAS EN LA PARTE INFERIOR
//$y_firmas = 175;

// Firma Director Nacional (parte inferior izquierda)
//if ($config['mostrar_firma_director_nacional'] && $firma_director_nacional) {
    // Imagen de firma si existe
//    if (!empty($firma_director_nacional['firma_imagen_path']) && file_exists($firma_director_nacional['firma_imagen_path'])) {
//        $pdf->Image($firma_director_nacional['firma_imagen_path'], 50, $y_firmas - 5, 50, 20);
//    }
    
    // Línea para la firma
//    $pdf->Line(50, $y_firmas + 10, 120, $y_firmas + 10);
    
    // Información del firmante
//    $pdf->SetFont('helvetica', 'B', 10);
//    $pdf->SetXY(50, $y_firmas + 12);
//    $pdf->Cell(70, 4, strtoupper($firma_director_nacional['firmante_nombre']), 0, 0, 'C');
    
//    $pdf->SetFont('helvetica', '', 9);
//    $pdf->SetXY(50, $y_firmas + 16);
//    $pdf->Cell(70, 3, 'Director Nacional', 0, 0, 'C');
//    $pdf->SetXY(50, $y_firmas + 19);
//    $pdf->Cell(70, 3, 'Bomberos de Colombia', 0, 0, 'C');
//}

// Firma Director de Escuela (parte inferior derecha)
//if ($config['mostrar_firma_director_escuela'] && $firma_director_escuela) {
    // Imagen de firma si existe
//    if (!empty($firma_director_escuela['firma_imagen_path']) && file_exists($firma_director_escuela['firma_imagen_path'])) {
//        $pdf->Image($firma_director_escuela['firma_imagen_path'], 160, $y_firmas - 5, 50, 20);
//    }
    
    // Línea para la firma
//    $pdf->Line(160, $y_firmas + 10, 230, $y_firmas + 10);
    
    // Información del firmante
//    $pdf->SetFont('helvetica', 'B', 10);
//    $pdf->SetXY(160, $y_firmas + 12);
//    $pdf->Cell(70, 4, strtoupper($firma_director_escuela['firmante_nombre']), 0, 0, 'C');
    
//    $pdf->SetFont('helvetica', '', 9);
//    $pdf->SetXY(160, $y_firmas + 16);
//    $pdf->Cell(70, 3, 'Director de Escuela', 0, 0, 'C');
    
//    $pdf->SetFont('helvetica', '', 8);
//    $pdf->SetXY(160, $y_firmas + 19);
//    $pdf->Cell(70, 3, $documento['escuela_nombre'], 0, 0, 'C');
//}

// ==================== PÁGINA 2: CONTENIDO PROGRAMÁTICO ====================
//$pdf->AddPage();

// AGREGAR IMAGEN DE FONDO PÁGINA 2 si está configurada
//if ($config['usar_imagen_fondo'] && !empty($config['imagen_fondo_pagina2'])) {
//    if (file_exists($config['imagen_fondo_pagina2'])) {
//        $pdf->Image($config['imagen_fondo_pagina2'], 0, 0, 279.4, 215.9, '', '', '', false, 300, '', false, false, 0);
//    }
//}

// Logo institucional
//if (file_exists('public/images/dnbc-logo.png')) {
//    $pdf->Image('public/images/dnbc-logo.png', 25, 15, 30);
//}

// Encabezado página 2
//$pdf->SetTextColor(0, 0, 0);
//$pdf->SetFont('helvetica', 'B', 18);
//$pdf->SetXY(20, 30);
//$pdf->Cell(239, 10, $config['titulo_principal'], 0, 0, 'C');

//$pdf->SetFont('helvetica', 'B', 16);
//$pdf->SetXY(20, 42);
//$pdf->Cell(239, 8, strtoupper($documento['escuela_nombre']), 0, 0, 'C');

//$pdf->SetFont('helvetica', 'B', 14);
//$pdf->SetXY(20, 54);
//$pdf->Cell(239, 8, $config['titulo_contenido'], 0, 0, 'C');

// Línea separadora
//$pdf->Line(25, 68, 255, 68);

// CONTENIDO PROGRAMÁTICO EN DOS COLUMNAS
//$y_contenido = 78;
//$columna_izq_x = 25;
//$columna_der_x = 145;
//$ancho_columna = 110;

//$pdf->SetFont('helvetica', '', 11);

//if (!empty($documento['contenido_tematico'])) {
//    $contenido_lineas = explode("\n", $documento['contenido_tematico']);
//    $contador = 1;
//    $y_actual = $y_contenido;
//    $columna_actual = 'izquierda';
    
//    foreach ($contenido_lineas as $linea) {
//        $linea = trim($linea);
//        if (!empty($linea)) {
            // Si la línea no empieza con número, agregarle numeración
//            if (!preg_match('/^\d+\./', $linea)) {
//                $linea = $contador . '. ' . $linea;
//                $contador++;
//            }
            
            // Determinar posición según columna actual
//            $x_pos = ($columna_actual == 'izquierda') ? $columna_izq_x : $columna_der_x;
            
//            $pdf->SetXY($x_pos, $y_actual);
            
            // Usar MultiCell para texto largo
//            $pdf->MultiCell($ancho_columna, 6, $linea, 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'T');
            
//            $y_actual += 8;
            
            // Cambiar de columna si se llega al final de la página
//            if ($y_actual > 160) {
//                if ($columna_actual == 'izquierda') {
//                    $columna_actual = 'derecha';
//                    $y_actual = $y_contenido;
//                } else {
                    // Si ya se llenaron ambas columnas, continuar en nueva página
//                    break;
//                }
//            }
//        }
//    }
//} else {
    // Contenido por defecto si no hay contenido específico
//    $contenido_default = [
//        "Fundamentos teóricos del área de estudio",
//        "Desarrollo de competencias técnicas específicas",
//        "Aplicación práctica de conocimientos adquiridos",
//        "Metodologías y procedimientos operativos",
//        "Normatividad y reglamentación vigente",
//        "Casos prácticos y estudios de campo",
//        "Evaluación continua de competencias",
//        "Certificación de conocimientos y habilidades"
//    ];
    
//    $y_actual = $y_contenido;
//    $columna_actual = 'izquierda';
    
//    foreach ($contenido_default as $index => $tema) {
//        $x_pos = ($columna_actual == 'izquierda') ? $columna_izq_x : $columna_der_x;
        
//        $pdf->SetXY($x_pos, $y_actual);
//        $pdf->MultiCell($ancho_columna, 6, ($index + 1) . '. ' . $tema, 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'T');
        
//        $y_actual += 8;
        
//        if ($y_actual > 160 && $columna_actual == 'izquierda') {
//            $columna_actual = 'derecha';
//            $y_actual = $y_contenido;
//        }
//    }
//}

// Firma del Coordinador (parte inferior derecha)
//if ($config['mostrar_firma_coordinador'] && $firma_coordinador) {
//    $y_coord = 175;
    
    // Imagen de firma si existe
//    if (!empty($firma_coordinador['firma_imagen_path']) && file_exists($firma_coordinador['firma_imagen_path'])) {
//        $pdf->Image($firma_coordinador['firma_imagen_path'], 170, $y_coord - 5, 50, 20);
//    }
    
    // Línea para la firma
//    $pdf->Line(160, $y_coord + 10, 230, $y_coord + 10);
    
    // Información del firmante
//    $pdf->SetFont('helvetica', 'B', 10);
//    $pdf->SetXY(160, $y_coord + 12);
//    $pdf->Cell(70, 4, strtoupper($firma_coordinador['firmante_nombre']), 0, 0, 'C');
    
//    $pdf->SetFont('helvetica', '', 9);
//    $pdf->SetXY(160, $y_coord + 16);
//    $pdf->Cell(70, 3, 'Coordinador del Curso', 0, 0, 'C');
//}

// Generar el PDF
//$filename = 'Certificado_' . str_replace(' ', '_', $documento['nombres'] . '_' . $documento['apellidos']) . '_' . preg_replace('/[^0-9]/', '', $documento['cedula']) . '.pdf';
//$pdf->Output($filename, 'D');
//exit();
?>
<?php
// Incluir la biblioteca TCPDF
require_once('lib/tcpdf/tcpdf.php');

class CertificadoTCPDF extends TCPDF {
    protected $certificado_data;
    protected $config;
    protected $textos;
    
    public function __construct($certificado_data) {
        // Configuración inicial del PDF
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Guardar datos del certificado
        $this->certificado_data = $certificado_data;
        $this->config = $certificado_data['config'];
        
        // Configurar textos del certificado
        $this->setupTextos();
        
        // Configurar el PDF
        $this->setupPDF();
        
        // Generar el certificado
        $this->generarCertificado();
    }
    
    protected function setupTextos() {
        // Configurar textos del certificado con valores predeterminados
        $this->textos = [
            'certifica_que' => $this->config['texto_certifica_que'] ?? 'Certifica que:',
            'identificado' => $this->config['texto_identificado'] ?? 'Identificado con C.C. No.',
            'asistio_aprobo' => $this->config['texto_asistio_aprobo'] ?? 'Asistió y aprobó los requisitos del Curso:',
            'curso_autorizado' => $this->config['texto_curso_autorizado'] ?? 'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia',
            'bajo_acta' => $this->config['texto_bajo_acta'] ?? 'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}',
            'duracion' => $this->config['texto_duracion'] ?? 'Con una duración de: {horas} horas académicas',
            'realizado_en' => $this->config['texto_realizado_en'] ?? 'Realizado en {lugar} del {fecha_inicio} al {fecha_fin}',
            'constancia' => $this->config['texto_constancia'] ?? 'En constancia de lo anterior, se firma a los {fecha_firma}'
        ];
        
        // Reemplazar variables en los textos
        $this->reemplazarVariables();
    }
    
    protected function reemplazarVariables() {
        // Formatear fechas
        $fecha_inicio = !empty($this->certificado_data['fecha_inicio']) ? 
            date('d/m/Y', strtotime($this->certificado_data['fecha_inicio'])) : 'N/A';
        $fecha_fin = !empty($this->certificado_data['fecha_fin']) ? 
            date('d/m/Y', strtotime($this->certificado_data['fecha_fin'])) : 'N/A';
        $fecha_acta = !empty($this->certificado_data['fecha_acta']) ? 
            date('d/m/Y', strtotime($this->certificado_data['fecha_acta'])) : date('d/m/Y');
        $fecha_firma = !empty($this->certificado_data['fecha_firma']) ? 
            date('d/m/Y', strtotime($this->certificado_data['fecha_firma'])) : date('d/m/Y');
        
        // Variables a reemplazar
        $variables = [
            '{registro_curso}' => $this->certificado_data['numero_registro_curso'] ?? 'N/A',
            '{numero_acta}' => $this->certificado_data['numero_acta'] ?? 'N/A',
            '{fecha_acta}' => $fecha_acta,
            '{nombre_cuerpo_bomberos}' => $this->certificado_data['nombre_cuerpo_bomberos'] ?? 'N/A',
            '{horas}' => $this->certificado_data['duracion_horas'] ?? 'N/A',
            '{lugar}' => $this->certificado_data['lugar_realizacion'] ?? 'N/A',
            '{fecha_inicio}' => $fecha_inicio,
            '{fecha_fin}' => $fecha_fin,
            '{fecha_firma}' => $fecha_firma
        ];
        
        // Reemplazar variables en cada texto
        foreach ($this->textos as $key => $texto) {
            foreach ($variables as $var => $valor) {
                $this->textos[$key] = str_replace($var, $valor, $this->textos[$key]);
            }
        }
    }
    
    protected function setupPDF() {
        // Configurar el PDF
        $this->SetCreator('ESIBOC-DNBC');
        $this->SetAuthor('Dirección Nacional de Bomberos Colombia');
        $this->SetTitle('Certificado de Curso');
        $this->SetSubject('Certificado de Aprobación de Curso');
        $this->SetKeywords('Certificado, Bomberos, Curso, Aprobación');
        
        // Eliminar encabezado y pie de página
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        
        // Configurar márgenes
        $this->SetMargins(15, 15, 15);
        
        // Configurar fuente
        $this->SetFont('helvetica', '', 12);
        
        // Configurar auto page break
        $this->SetAutoPageBreak(true, 15);
    }
    
    protected function generarCertificado() {
        // Generar primera página (certificado)
        $this->generarPaginaCertificado();
        
        // Generar segunda página (contenido temático)
        $this->generarPaginaContenido();
    }
    
    protected function generarPaginaCertificado() {
        // Agregar página
        $this->AddPage('L', 'Letter');
        
        // Aplicar fondo si está configurado
        $this->aplicarFondo();
        
        // Agregar número consecutivo
        $this->agregarConsecutivo();
        
        // Agregar encabezado
        $this->agregarEncabezado();
        
        // Agregar cuerpo del certificado
        $this->agregarCuerpoCertificado();
        
        // Agregar firmas
        $this->agregarFirmas();
    }
    
    protected function aplicarFondo() {
        // Verificar si hay imagen de fondo configurada
        if (!empty($this->config['fondo_certificado']) && file_exists($this->config['fondo_certificado'])) {
            $this->Image($this->config['fondo_certificado'], 0, 0, $this->getPageWidth(), $this->getPageHeight(), '', '', '', false, 300, '', false, false, 0);
        } else {
            // Fondo predeterminado con borde
            $this->SetFillColor(255, 255, 255);
            $this->Rect(0, 0, $this->getPageWidth(), $this->getPageHeight(), 'F');
            $this->SetDrawColor(0, 48, 135); // Azul DNBC
            $this->SetLineWidth(1.5);
            $this->Rect(10, 10, $this->getPageWidth() - 20, $this->getPageHeight() - 20, 'D');
        }
    }
    
    protected function agregarConsecutivo() {
        // Agregar número consecutivo en la esquina superior derecha
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(0, 48, 135); // Azul DNBC
        $this->SetXY($this->getPageWidth() - 70, 15);
        $this->Cell(50, 10, 'No. ' . $this->certificado_data['numero_consecutivo'], 0, 0, 'R');
    }
    
    protected function agregarEncabezado() {
        // Posición Y inicial
        $y = 25;
        
        // Logo de la DNBC
        if (file_exists('public/images/dnbc-logo.png')) {
            $this->Image('public/images/dnbc-logo.png', 30, $y, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Título del certificado
        $this->SetFont('helvetica', 'B', 24);
        $this->SetTextColor(0, 48, 135); // Azul DNBC
        $this->SetXY(0, $y + 5);
        $this->Cell($this->getPageWidth(), 15, 'DIRECCIÓN NACIONAL DE BOMBEROS COLOMBIA', 0, 1, 'C');
        
        // Subtítulo
        $this->SetFont('helvetica', 'B', 18);
        $this->SetXY(0, $y + 20);
        $this->Cell($this->getPageWidth(), 15, $this->certificado_data['escuela_nombre'], 0, 1, 'C');
        
        // Línea divisoria
        $this->SetDrawColor(200, 0, 0); // Rojo
        $this->SetLineWidth(0.5);
        $this->Line(30, $y + 40, $this->getPageWidth() - 30, $y + 40);
        
        // Título "CERTIFICADO"
        $this->SetFont('helvetica', 'B', 28);
        $this->SetTextColor(200, 0, 0); // Rojo
        $this->SetXY(0, $y + 45);
        $this->Cell($this->getPageWidth(), 15, 'CERTIFICADO', 0, 1, 'C');
    }
    
    protected function agregarCuerpoCertificado() {
        // Posición Y inicial
        $y = 90;
        
        // Configurar fuente para el cuerpo
        $this->SetFont('helvetica', '', 12);
        $this->SetTextColor(0, 0, 0);
        
        // Certifica que:
        $this->SetXY(30, $y);
        $this->Cell($this->getPageWidth() - 60, 10, $this->textos['certifica_que'], 0, 1, 'C');
        $y += 10;
        
        // Nombre del participante
        $this->SetFont('helvetica', 'B', 16);
        $this->SetXY(30, $y);
        $this->Cell($this->getPageWidth() - 60, 10, strtoupper($this->certificado_data['nombres'] . ' ' . $this->certificado_data['apellidos']), 0, 1, 'C');
        $y += 15;
        
        // Identificación
        $this->SetFont('helvetica', '', 12);
        $this->SetXY(30, $y);
        $this->Cell($this->getPageWidth() - 60, 10, $this->textos['identificado'] . ' ' . $this->certificado_data['cedula'], 0, 1, 'C');
        $y += 15;
        
        // Asistió y aprobó
        $this->SetXY(30, $y);
        $this->Cell($this->getPageWidth() - 60, 10, $this->textos['asistio_aprobo'], 0, 1, 'C');
        $y += 10;
        
        // Nombre del curso
        $this->SetFont('helvetica', 'B', 16);
        $this->SetXY(30, $y);
        $this->Cell($this->getPageWidth() - 60, 10, strtoupper($this->certificado_data['curso_nombre']), 0, 1, 'C');
        $y += 15;
        
        // Curso autorizado
        $this->SetFont('helvetica', '', 12);
        $this->SetXY(30, $y);
        $this->Cell($this->getPageWidth() - 60, 10, $this->textos['curso_autorizado'], 0, 1, 'C');
        $y += 10;
        
        // Bajo acta número
        $this->SetXY(30, $y);
        $this->Cell($this->getPageWidth() - 60, 10, $this->textos['bajo_acta'], 0, 1, 'C');
        $y += 10;
        
        // Duración
        $this->SetXY(30, $y);
        $this->Cell($this->getPageWidth() - 60, 10, $this->textos['duracion'], 0, 1, 'C');
        $y += 10;
        
        // Realizado en
        $this->SetXY(30, $y);
        $this->Cell($this->getPageWidth() - 60, 10, $this->textos['realizado_en'], 0, 1, 'C');
        $y += 15;
        
        // Constancia
        $this->SetXY(30, $y);
        $this->Cell($this->getPageWidth() - 60, 10, $this->textos['constancia'], 0, 1, 'C');
    }
    
    protected function agregarFirmas() {
        // Posición Y para firmas
        $y = 210;
        
        // Verificar si hay firmas configuradas
        if (empty($this->certificado_data['firmas'])) {
            return;
        }
        
        // Ancho disponible para firmas
        $ancho_disponible = $this->getPageWidth() - 60;
        $ancho_firma = $ancho_disponible / 2;
        
        // Director Nacional (izquierda)
        if (isset($this->certificado_data['firmas'][0])) {
            $firma = $this->certificado_data['firmas'][0];
            $this->agregarFirma($firma, 30, $y, $ancho_firma);
        }
        
        // Director Escuela (derecha)
        if (isset($this->certificado_data['firmas'][1])) {
            $firma = $this->certificado_data['firmas'][1];
            $this->agregarFirma($firma, 30 + $ancho_firma, $y, $ancho_firma);
        }
    }
    
    protected function agregarFirma($firma, $x, $y, $ancho) {
        // Imagen de la firma
        if (!empty($firma['ruta_imagen']) && file_exists($firma['ruta_imagen'])) {
            $this->Image($firma['ruta_imagen'], $x + ($ancho/2) - 25, $y, 50, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Línea de la firma
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.2);
        $this->Line($x + 20, $y + 25, $x + $ancho - 20, $y + 25);
        
        // Nombre del firmante
        $this->SetFont('helvetica', 'B', 10);
        $this->SetXY($x, $y + 26);
        $this->Cell($ancho, 10, strtoupper($firma['nombre']), 0, 1, 'C');
        
        // Cargo del firmante
        $this->SetFont('helvetica', '', 10);
        $this->SetXY($x, $y + 32);
        $this->Cell($ancho, 10, strtoupper($firma['cargo']), 0, 1, 'C');
    }
    
    protected function generarPaginaContenido() {
        // Verificar si hay contenido temático
        if (empty($this->certificado_data['contenido_tematico'])) {
            return;
        }
        
        // Agregar página
        $this->AddPage('L', 'Letter');
        
        // Aplicar fondo si está configurado
        $this->aplicarFondo();
        
        // Título de la página
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(0, 48, 135); // Azul DNBC
        $this->SetXY(0, 25);
        $this->Cell($this->getPageWidth(), 10, 'CONTENIDO PROGRAMÁTICO', 0, 1, 'C');
        
        // Subtítulo con nombre del curso
        $this->SetFont('helvetica', 'B', 14);
        $this->SetXY(0, 35);
        $this->Cell($this->getPageWidth(), 10, strtoupper($this->certificado_data['curso_nombre']), 0, 1, 'C');
        
        // Línea divisoria
        $this->SetDrawColor(200, 0, 0); // Rojo
        $this->SetLineWidth(0.5);
        $this->Line(30, 50, $this->getPageWidth() - 30, 50);
        
        // Contenido temático en dos columnas
        $this->SetFont('helvetica', '', 11);
        $this->SetTextColor(0, 0, 0);
        $this->SetXY(30, 60);
        
        // Dividir el contenido en líneas
        $lineas = explode("\n", $this->certificado_data['contenido_tematico']);
        
        // Calcular número de líneas por columna
        $total_lineas = count($lineas);
        $lineas_por_columna = ceil($total_lineas / 2);
        
        // Primera columna
        $y = 60;
        for ($i = 0; $i < $lineas_por_columna && $i < $total_lineas; $i++) {
            $this->SetXY(30, $y);
            $this->MultiCell($this->getPageWidth()/2 - 40, 8, $lineas[$i], 0, 'L');
            $y += 8;
        }
        
        // Segunda columna
        $y = 60;
        for ($i = $lineas_por_columna; $i < $total_lineas; $i++) {
            $this->SetXY($this->getPageWidth()/2 + 10, $y);
            $this->MultiCell($this->getPageWidth()/2 - 40, 8, $lineas[$i], 0, 'L');
            $y += 8;
        }
        
        // Firma del coordinador (parte inferior derecha)
        if (isset($this->certificado_data['firmas'][2])) {
            $firma = $this->certificado_data['firmas'][2];
            $this->agregarFirma($firma, $this->getPageWidth()/2, 180, $this->getPageWidth()/2 - 30);
        }
    }
}

// Incluir archivos necesarios
require_once 'participante-auth.php';
require_once 'config/database.php';
require_once 'certificate-numbering.php';

$auth = new ParticipanteAuth();
$auth->requireLogin();

// Verificar que se proporcionó un ID de documento
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID de documento no proporcionado");
}

$documento_id = intval($_GET['id']);
$participante_id = $_SESSION['participante_id'];

// Inicializar clase de numeración
$numbering = new CertificateNumbering();

// Obtener datos completos del certificado
$documento = $numbering->getCertificateData($documento_id);

if (!$documento || $documento['participante_id'] != $participante_id) {
    die("Error: No se encontró el certificado o no está disponible para descarga");
}

// Generar numeración si no existe
if (empty($documento['numero_consecutivo'])) {
    $numbering->updateDocumentNumbering($documento_id);
    $documento = $numbering->getCertificateData($documento_id); // Recargar datos
}

// Obtener configuración del certificado para esta escuela
$conn = getMySQLiConnection();
$config_sql = "SELECT * FROM configuracion_certificados WHERE escuela_id = ?";
$stmt_config = $conn->prepare($config_sql);
$stmt_config->bind_param("i", $documento['escuela_id']);
$stmt_config->execute();
$config_result = $stmt_config->get_result();
$config = $config_result->fetch_assoc();

// Si no hay configuración, usar valores por defecto
if (!$config) {
    $config = [
        'titulo_principal' => 'DIRECCIÓN NACIONAL DE BOMBEROS DE COLOMBIA',
        'subtitulo_certificado' => 'CERTIFICADO DE APROBACIÓN',
        'texto_certifica_que' => 'Certifica que:',
        'texto_identificado' => 'Identificado con C.C. No.',
        'texto_asistio_aprobo' => 'Asistió y aprobó los requisitos del Curso:',
        'texto_curso_autorizado' => 'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia',
        'texto_bajo_acta' => 'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}',
        'texto_duracion' => 'Con una duración de: {horas} horas académicas',
        'texto_realizado_en' => 'Realizado en {lugar} del {fecha_inicio} al {fecha_fin}',
        'texto_constancia' => 'En constancia de lo anterior, se firma a los {fecha_firma}',
        'mostrar_numero_consecutivo' => true,
        'texto_numero_consecutivo' => 'Certificado No. {consecutivo}',
        'mostrar_firma_director_nacional' => true,
        'mostrar_firma_director_escuela' => true,
        'mostrar_firma_coordinador' => true,
        'titulo_contenido' => 'CONTENIDO PROGRAMÁTICO'
    ];
}

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

// Variables para reemplazar en los textos
$variables = [
    'consecutivo' => $documento['numero_consecutivo'] ?? 'CERT-' . $documento['id'],
    'registro_curso' => $documento['numero_registro_curso'] ?? 'REG-CURSO-' . $documento['curso_id'],
    'numero_acta' => $documento['numero_acta'] ?? '001',
    'fecha_acta' => $numbering->formatearFecha($documento['fecha_acta'] ?? date('Y-m-d'), 'texto'),
    'nombre_cuerpo_bomberos' => $documento['escuela_nombre'],
    'horas' => $documento['duracion_horas'],
    'lugar' => $documento['lugar_realizacion'] ?? $documento['escuela_nombre'],
    'fecha_inicio' => $numbering->formatearFecha($documento['fecha_inicio']),
    'fecha_fin' => $numbering->formatearFecha($documento['fecha_fin']),
    'fecha_firma' => $numbering->formatearFecha($config['fecha_firma_certificados'] ?? date('Y-m-d'), 'texto')
];

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

// Preparar datos para el constructor del certificado
$certificado_data = [
    'id' => $documento['id'],
    'nombres' => $documento['nombres'],
    'apellidos' => $documento['apellidos'],
    'cedula' => $documento['cedula'],
    'curso_nombre' => $documento['curso_nombre'],
    'numero_registro_curso' => $documento['numero_registro_curso'],
    'numero_acta' => $documento['numero_acta'],
    'fecha_acta' => $documento['fecha_acta'],
    'nombre_cuerpo_bomberos' => $documento['escuela_nombre'],
    'duracion_horas' => $documento['duracion_horas'],
    'lugar_realizacion' => $documento['lugar_realizacion'],
    'fecha_inicio' => $documento['fecha_inicio'],
    'fecha_fin' => $documento['fecha_fin'],
    'numero_consecutivo' => $documento['numero_consecutivo'],
    'escuela_nombre' => $documento['escuela_nombre'],
    'contenido_tematico' => $documento['contenido_tematico'],
    'config' => $config,
    'firmas' => []
];

// Organizar firmas para el certificado
if ($firma_director_nacional) {
    $certificado_data['firmas'][] = [
        'nombre' => $firma_director_nacional['firmante_nombre'],
        'cargo' => $firma_director_nacional['firmante_cargo'],
        'ruta_imagen' => $firma_director_nacional['firma_imagen_path']
    ];
}

if ($firma_director_escuela) {
    $certificado_data['firmas'][] = [
        'nombre' => $firma_director_escuela['firmante_nombre'],
        'cargo' => $firma_director_escuela['firmante_cargo'],
        'ruta_imagen' => $firma_director_escuela['firma_imagen_path']
    ];
}

if ($firma_coordinador) {
    $certificado_data['firmas'][] = [
        'nombre' => $firma_coordinador['firmante_nombre'],
        'cargo' => $firma_coordinador['firmante_cargo'],
        'ruta_imagen' => $firma_coordinador['firma_imagen_path']
    ];
}

// Crear instancia del certificado y generar el PDF
$pdf = new CertificadoTCPDF($certificado_data);

// Nombre del archivo
$filename = 'Certificado_' . str_replace(' ', '_', $documento['nombres'] . '_' . $documento['apellidos']) . '_' . preg_replace('/[^0-9]/', '', $documento['cedula']) . '.pdf';

// Output del PDF
$pdf->Output($filename, 'D');
exit();
?>
