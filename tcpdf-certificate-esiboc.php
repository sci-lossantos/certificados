<?php
// Certificado ESIBOC con formato exacto según ejemplos proporcionados
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

class CertificadoESIBOC extends TCPDF {
    protected $certificado_data;
    protected $config;
    
    public function __construct($certificado_data, $config) {
        // Configuración inicial del PDF en formato horizontal
        parent::__construct('L', 'mm', 'LETTER', true, 'UTF-8', false);
        
        $this->certificado_data = $certificado_data;
        $this->config = $config;
        
        // Configurar el PDF
        $this->setupPDF();
        
        // Generar el certificado
        $this->generarCertificado();
    }
    
    protected function setupPDF() {
        $this->SetCreator('ESIBOC-DNBC');
        $this->SetAuthor('Dirección Nacional de Bomberos Colombia');
        $this->SetTitle('Certificado - ' . $this->certificado_data['nombres'] . ' ' . $this->certificado_data['apellidos']);
        
        // Eliminar encabezado y pie de página
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        
        // Configurar márgenes (formato horizontal)
        $this->SetMargins(20, 20, 20);
        $this->SetAutoPageBreak(false);
    }
    
    protected function generarCertificado() {
        // Página 1: Certificado principal
        $this->generarPaginaCertificado();
        
        // Página 2: Contenido programático
        if ($this->config['mostrar_contenido_programatico']) {
            $this->generarPaginaContenido();
        }
    }
    
    protected function generarPaginaCertificado() {
        $this->AddPage();
        
        // Fondo con marca de agua "BOMBEROS DE COLOMBIA"
        $this->agregarMarcaAguaBomberos();
        
        // Encabezado institucional
        $this->agregarEncabezadoESIBOC();
        
        // Número consecutivo (esquina superior derecha)
        $this->agregarNumeroConsecutivo();
        
        // Cuerpo del certificado
        $this->agregarCuerpoCertificado();
        
        // Firmas
        $this->agregarFirmasPaginaUno();
        
        // Pie de página con código ESIBOC-CURSOS
        $this->agregarPieESIBOC();
    }
    
    protected function agregarMarcaAguaBomberos() {
        // Marca de agua vertical "BOMBEROS DE COLOMBIA"
        $this->SetFont('helvetica', 'B', 60);
        $this->SetTextColor(240, 240, 240); // Gris muy claro
        
        // Rotar texto 90 grados y posicionar verticalmente
        $this->StartTransform();
        $this->Rotate(90, 250, 140);
        $this->Text(200, 140, 'B O M B E R O S   D E   C O L O M B I A');
        $this->StopTransform();
        
        // Restablecer color de texto
        $this->SetTextColor(0, 0, 0);
    }
    
    protected function agregarEncabezadoESIBOC() {
        // Logo DNBC (si existe)
        if (file_exists('public/images/dnbc-logo.png')) {
            $this->Image('public/images/dnbc-logo.png', 25, 15, 25, 0, 'PNG');
        }
        
        // Textos del encabezado centrados
        $this->SetFont('helvetica', '', 12);
        $this->SetXY(20, 25);
        $this->Cell(239, 5, 'Cuerpo de Bomberos Los Santos Santander', 0, 1, 'C');
        
        $this->SetXY(20, 30);
        $this->Cell(239, 5, 'Escuela Internacional de Bomberos del Oriente Colombiano', 0, 1, 'C');
        
        $this->SetFont('helvetica', 'B', 14);
        $this->SetXY(20, 35);
        $this->Cell(239, 5, 'ESIBOC', 0, 1, 'C');
        
        // Autoridades (dos columnas)
        $this->SetFont('helvetica', '', 10);
        
        // Columna izquierda - Directora Nacional
        $this->SetXY(25, 45);
        $this->Cell(100, 4, 'CT. EN JEFE LINA MARÍA MARÍN RODRÍGUEZ', 0, 1, 'L');
        $this->SetXY(25, 49);
        $this->Cell(100, 4, 'Directora Nacional DNBC', 0, 1, 'L');
        
        // Columna derecha - Comandante
        $this->SetXY(140, 45);
        $this->Cell(100, 4, 'CT. MANUEL ENRIQUE SALAZAR HERNANDEZ', 0, 1, 'L');
        $this->SetXY(140, 49);
        $this->Cell(100, 4, 'Comandante Cuerpo de Bomberos Los Santos Sant.', 0, 1, 'L');
    }
    
    protected function agregarNumeroConsecutivo() {
        // Número consecutivo en formato vertical (esquina derecha)
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(0, 0, 0);
        
        $consecutivo = $this->certificado_data['numero_consecutivo'];
        
        $this->SetXY(230, 25);
        $this->Cell(25, 8, $consecutivo, 0, 0, 'C');
        
        $this->SetFont('helvetica', '', 10);
        $this->SetXY(230, 33);
        $this->Cell(25, 6, 'No consecutivo', 0, 0, 'C');
        
        $this->SetXY(230, 39);
        $this->Cell(25, 6, 'Certificado', 0, 0, 'C');
    }
    
    protected function agregarCuerpoCertificado() {
        $y_inicial = 70;
        
        // Nombre del participante (grande y centrado)
        $this->SetFont('helvetica', 'B', 18);
        $this->SetXY(20, $y_inicial);
        $nombre_completo = strtoupper($this->certificado_data['nombres'] . ' ' . $this->certificado_data['apellidos']);
        $this->Cell(239, 10, $nombre_completo, 0, 1, 'C');
        
        $y_inicial += 20;
        
        // Cédula
        $this->SetFont('helvetica', '', 12);
        $this->SetXY(20, $y_inicial);
        $cedula = $this->formatearCedula($this->certificado_data['cedula']);
        $this->Cell(239, 8, $cedula, 0, 1, 'C');
        
        $y_inicial += 15;
        
        // Texto "Bajo acta número..."
        $this->SetXY(20, $y_inicial);
        $texto_acta = $this->reemplazarVariables($this->config['texto_bajo_acta']);
        $this->Cell(239, 6, $texto_acta, 0, 1, 'C');
        
        $y_inicial += 10;
        
        // Duración
        $this->SetXY(20, $y_inicial);
        $texto_duracion = $this->reemplazarVariables($this->config['texto_duracion']);
        $this->Cell(239, 6, $texto_duracion, 0, 1, 'C');
        
        $y_inicial += 15;
        
        // "Certifica que:"
        $this->SetXY(20, $y_inicial);
        $this->Cell(239, 8, $this->config['texto_certifica_que'], 0, 1, 'C');
        
        $y_inicial += 10;
        
        // "Identificado con C.C. No."
        $this->SetXY(20, $y_inicial);
        $this->Cell(239, 8, $this->config['texto_identificado_con'], 0, 1, 'C');
        
        $y_inicial += 10;
        
        // "Asistió y aprobó los requisitos del Curso:"
        $this->SetXY(20, $y_inicial);
        $this->Cell(239, 8, $this->config['texto_asistio_aprobo'], 0, 1, 'C');
        
        $y_inicial += 15;
        
        // Nombre del curso (destacado)
        $this->SetFont('helvetica', 'B', 14);
        $this->SetXY(20, $y_inicial);
        $this->Cell(239, 10, strtoupper($this->certificado_data['curso_nombre']), 0, 1, 'C');
        
        $y_inicial += 15;
        
        // Curso autorizado
        $this->SetFont('helvetica', '', 12);
        $this->SetXY(20, $y_inicial);
        $texto_autorizado = $this->reemplazarVariables($this->config['texto_curso_autorizado']);
        $this->Cell(239, 6, $texto_autorizado, 0, 1, 'C');
        
        $y_inicial += 15;
        
        // Constancia
        $this->SetXY(20, $y_inicial);
        $texto_constancia = $this->reemplazarVariables($this->config['texto_constancia']);
        $this->Cell(239, 6, $texto_constancia, 0, 1, 'C');
        
        $y_inicial += 10;
        
        // Realizado en
        $this->SetXY(20, $y_inicial);
        $texto_realizado = $this->reemplazarVariables($this->config['texto_realizado_en']);
        $this->Cell(239, 6, $texto_realizado, 0, 1, 'C');
    }
    
    protected function agregarFirmasPaginaUno() {
        $y_firmas = 175;
        
        // Firmas en la parte inferior (dos columnas)
        if (isset($this->certificado_data['firmas'])) {
            $firmas = $this->certificado_data['firmas'];
            
            // Firma izquierda (Director Nacional)
            if (isset($firmas['director_nacional'])) {
                $this->agregarFirma($firmas['director_nacional'], 50, $y_firmas, 80);
            }
            
            // Firma derecha (Director de Escuela)
            if (isset($firmas['director_escuela'])) {
                $this->agregarFirma($firmas['director_escuela'], 150, $y_firmas, 80);
            }
        }
    }
    
    protected function agregarFirma($firma, $x, $y, $ancho) {
        // Imagen de la firma si existe
        if (!empty($firma['imagen']) && file_exists($firma['imagen'])) {
            $this->Image($firma['imagen'], $x + 10, $y - 15, $ancho - 20, 15);
        }
        
        // Línea de la firma
        $this->Line($x + 10, $y, $x + $ancho - 10, $y);
        
        // Nombre del firmante
        $this->SetFont('helvetica', 'B', 10);
        $this->SetXY($x, $y + 2);
        $this->Cell($ancho, 5, strtoupper($firma['nombre']), 0, 1, 'C');
        
        // Cargo
        $this->SetFont('helvetica', '', 9);
        $this->SetXY($x, $y + 7);
        $this->Cell($ancho, 4, $firma['cargo'], 0, 1, 'C');
    }
    
    protected function agregarPieESIBOC() {
        // Código ESIBOC-CURSOS en la parte inferior
        $this->SetFont('helvetica', '', 10);
        $this->SetXY(20, 200);
        $this->Cell(239, 6, 'ESIBOC-CURSOS', 0, 0, 'C');
    }
    
    protected function generarPaginaContenido() {
        $this->AddPage();
        
        // Fondo con marca de agua
        $this->agregarMarcaAguaBomberos();
        
        // Encabezado similar a la primera página
        $this->agregarEncabezadoESIBOC();
        
        // Título "CONTENIDO PROGRAMÁTICO"
        $this->SetFont('helvetica', 'B', 16);
        $this->SetXY(20, 70);
        $this->Cell(239, 10, 'CONTENIDO PROGRAMÁTICO', 0, 1, 'C');
        
        // Nombre del coordinador
        $this->SetFont('helvetica', '', 12);
        $this->SetXY(20, 85);
        if (isset($this->certificado_data['coordinador_nombre'])) {
            $this->Cell(239, 8, 'ST. ' . strtoupper($this->certificado_data['coordinador_nombre']), 0, 1, 'C');
            $this->SetXY(20, 93);
            $this->Cell(239, 6, 'Coordinador Curso', 0, 1, 'C');
        }
        
        // Contenido programático en lista numerada
        $this->agregarContenidoProgramatico();
        
        // Firma del coordinador (parte inferior derecha)
        if (isset($this->certificado_data['firmas']['coordinador'])) {
            $this->agregarFirma($this->certificado_data['firmas']['coordinador'], 150, 175, 80);
        }
        
        // Pie de página
        $this->agregarPieESIBOC();
    }
    
    protected function agregarContenidoProgramatico() {
        $y_inicial = 110;
        $contenido_default = [
            "INTRODUCCIÓN",
            "• Propósito",
            "• Objetivos de desempeño", 
            "• Objetivos de capacitación",
            "• Evaluaciones",
            "• Método",
            "• Reglas para participar",
            "",
            "ORIENTACIÓN E IMPLEMENTACIÓN DEL SCI",
            "• Contribución del SCI",
            "• Antecedentes del SCI",
            "• SCI como norma ISO",
            "• Ruta de Implementación",
            "• Cómo abordar las cinco fases del SCI",
            "• Detalle de acciones a desarrollar",
            "• Documentación",
            "",
            "CARACTERÍSTICAS Y PRINCIPIOS DEL SCI",
            "• Los incidentes y el SCI",
            "• El SCI: un marco común de atención",
            "• Definiciones relacionadas con el SCI",
            "• Aplicaciones del SCI Conceptos Principios y Características del SCI",
            "",
            "FUNCIONES, RESPONSABILIDADES Y CARACTERÍSTICAS DEL SCI",
            "• Funciones y responsabilidades",
            "• Organigrama del SCI",
            "• Staff de Comando y Secciones",
            "• Delegación de funciones",
            "• Terminología de la estructura"
        ];
        
        $contenido = !empty($this->certificado_data['contenido_tematico']) ? 
                    explode("\n", $this->certificado_data['contenido_tematico']) : 
                    $contenido_default;
        
        $this->SetFont('helvetica', '', 10);
        $y_actual = $y_inicial;
        $columna_actual = 1;
        $x_columna1 = 25;
        $x_columna2 = 145;
        $ancho_columna = 110;
        
        foreach ($contenido as $linea) {
            $linea = trim($linea);
            
            if ($y_actual > 170) {
                // Cambiar a segunda columna
                if ($columna_actual == 1) {
                    $columna_actual = 2;
                    $y_actual = $y_inicial;
                } else {
                    break; // No más espacio
                }
            }
            
            $x_pos = ($columna_actual == 1) ? $x_columna1 : $x_columna2;
            
            if (!empty($linea)) {
                // Texto en negrita para títulos principales
                if (!str_starts_with($linea, '•') && !empty($linea)) {
                    $this->SetFont('helvetica', 'B', 10);
                } else {
                    $this->SetFont('helvetica', '', 10);
                }
                
                $this->SetXY($x_pos, $y_actual);
                $this->MultiCell($ancho_columna, 5, $linea, 0, 'L');
                $y_actual += 6;
            } else {
                $y_actual += 3; // Espacio para líneas vacías
            }
        }
    }
    
    protected function formatearCedula($cedula) {
        $cedula_limpia = preg_replace('/[^0-9]/', '', $cedula);
        if (is_numeric($cedula_limpia) && !empty($cedula_limpia)) {
            return number_format($cedula_limpia, 0, '', '.');
        }
        return $cedula;
    }
    
    protected function reemplazarVariables($texto) {
        $variables = [
            '{numero_acta}' => $this->certificado_data['numero_acta'] ?? '021',
            '{fecha_acta}' => $this->formatearFechaTexto($this->certificado_data['fecha_acta'] ?? date('Y-m-d')),
            '{nombre_cuerpo_bomberos}' => 'Cuerpo de Bomberos Voluntarios Los Santos',
            '{horas}' => $this->certificado_data['duracion_horas'] ?? '24',
            '{registro_curso}' => $this->certificado_data['numero_registro_curso'] ?? '184-2025',
            '{lugar}' => $this->certificado_data['lugar_realizacion'] ?? 'Floridablanca – Santander',
            '{fecha_inicio_dia}' => date('d', strtotime($this->certificado_data['fecha_inicio'] ?? date('Y-m-d'))),
            '{fecha_inicio_mes}' => $this->obtenerNombreMes($this->certificado_data['fecha_inicio'] ?? date('Y-m-d')),
            '{fecha_fin_dia}' => date('d', strtotime($this->certificado_data['fecha_fin'] ?? date('Y-m-d'))),
            '{fecha_fin_mes}' => $this->obtenerNombreMes($this->certificado_data['fecha_fin'] ?? date('Y-m-d')),
            '{año}' => date('Y', strtotime($this->certificado_data['fecha_fin'] ?? date('Y-m-d'))),
            '{fecha_firma_dia}' => date('d', strtotime($this->certificado_data['fecha_firma'] ?? date('Y-m-d'))),
            '{fecha_firma_mes}' => $this->obtenerNombreMes($this->certificado_data['fecha_firma'] ?? date('Y-m-d')),
            '{fecha_firma_año}' => date('Y', strtotime($this->certificado_data['fecha_firma'] ?? date('Y-m-d')))
        ];
        
        foreach ($variables as $variable => $valor) {
            $texto = str_replace($variable, $valor, $texto);
        }
        
        return $texto;
    }
    
    protected function formatearFechaTexto($fecha) {
        return date('d', strtotime($fecha)) . ' ' . $this->obtenerNombreMes($fecha) . ' de ' . date('Y', strtotime($fecha));
    }
    
    protected function obtenerNombreMes($fecha) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $meses[intval(date('n', strtotime($fecha)))];
    }
}

// Código principal para generar el certificado
$auth = new ParticipanteAuth();
$auth->requireLogin();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID de documento no proporcionado");
}

$documento_id = intval($_GET['id']);
$participante_id = $_SESSION['participante_id'];

$numbering = new CertificateNumbering();
$documento = $numbering->getCertificateData($documento_id);

if (!$documento || $documento['participante_id'] != $participante_id) {
    die("Error: No se encontró el certificado o no está disponible para descarga");
}

// Generar numeración si no existe
if (empty($documento['numero_consecutivo'])) {
    $numbering->updateDocumentNumbering($documento_id);
    $documento = $numbering->getCertificateData($documento_id);
}

// Obtener configuración ESIBOC
$conn = getMySQLiConnection();
$config_sql = "SELECT * FROM configuracion_certificados WHERE nombre LIKE '%ESIBOC%' AND activo = 1 LIMIT 1";
$config_result = $conn->query($config_sql);
$config = $config_result->fetch_assoc();

if (!$config) {
    die("Error: No se encontró la configuración ESIBOC. Por favor, ejecute el script de configuración.");
}

// Obtener firmas
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
$firmas_array = $firmas_result->fetch_all(MYSQLI_ASSOC);

// Organizar firmas
$firmas = [];
foreach ($firmas_array as $firma) {
    $rol_id = intval($firma['rol_id']);
    
    if ($rol_id == 2) { // Dirección Nacional
        $firmas['director_nacional'] = [
            'nombre' => $firma['firmante_nombre'],
            'cargo' => 'Directora Nacional DNBC',
            'imagen' => $firma['firma_imagen_path']
        ];
    } elseif ($rol_id == 5) { // Director de Escuela
        $firmas['director_escuela'] = [
            'nombre' => $firma['firmante_nombre'],
            'cargo' => 'Comandante Cuerpo de Bomberos Los Santos Sant.',
            'imagen' => $firma['firma_imagen_path']
        ];
    } elseif ($rol_id == 6) { // Coordinador
        $firmas['coordinador'] = [
            'nombre' => $firma['firmante_nombre'],
            'cargo' => 'Coordinador Curso',
            'imagen' => $firma['firma_imagen_path']
        ];
    }
}

// Preparar datos del certificado
$certificado_data = array_merge($documento, [
    'firmas' => $firmas,
    'coordinador_nombre' => isset($firmas['coordinador']) ? $firmas['coordinador']['nombre'] : 'JORGE E. SERRANO PRADA'
]);

// Generar PDF
$pdf = new CertificadoESIBOC($certificado_data, $config);

// Nombre del archivo
$filename = 'Certificado_ESIBOC_' . 
           str_replace(' ', '_', $documento['nombres'] . '_' . $documento['apellidos']) . '_' . 
           preg_replace('/[^0-9]/', '', $documento['cedula']) . '.pdf';

// Descargar PDF
$pdf->Output($filename, 'D');
exit();
?>
