<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/document-workflow.php';
require_once 'lib/fpdf.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$workflow = new DocumentWorkflow($db);

// Verificar acceso
$auth->requireRole(['Escuela', 'Coordinador', 'Director de Escuela', 'Educación DNBC', 'Dirección Nacional']);

$documento_id = $_GET['id'] ?? 0;

if (!$documento_id) {
    die('ID de documento no válido');
}

// Obtener información del documento
$query_documento = "SELECT d.*, c.nombre as curso_nombre, c.numero_registro, c.fecha_inicio, c.fecha_fin, c.duracion_horas,
                           CONCAT(p.nombres, ' ', p.apellidos) as participante_nombre, p.cedula as participante_cedula,
                           CONCAT(u.nombres, ' ', u.apellidos) as generado_por_nombre,
                           e.nombre as escuela_nombre, e.direccion as escuela_direccion, e.telefono as escuela_telefono
                    FROM documentos d 
                    JOIN cursos c ON d.curso_id = c.id 
                    JOIN escuelas e ON c.escuela_id = e.id
                    LEFT JOIN participantes p ON d.participante_id = p.id
                    LEFT JOIN usuarios u ON d.generado_por = u.id
                    WHERE d.id = ?";
$stmt_documento = $db->prepare($query_documento);
$stmt_documento->execute([$documento_id]);
$documento = $stmt_documento->fetch();

if (!$documento) {
    die('Documento no encontrado');
}

// Obtener firmas
$query_firmas = "SELECT fd.*, CONCAT(u.nombres, ' ', u.apellidos) as firmante_nombre,
                        r.nombre as rol_nombre, fu.tipo_firma as tipo_firma_config, 
                        fu.contenido_firma
                 FROM firmas_documentos fd
                 JOIN usuarios u ON fd.usuario_id = u.id
                 JOIN roles r ON u.rol_id = r.id
                 LEFT JOIN firmas_usuarios fu ON u.id = fu.usuario_id AND fu.activa = TRUE
                 WHERE fd.documento_id = ?
                 ORDER BY fd.fecha_firma ASC";
$stmt_firmas = $db->prepare($query_firmas);
$stmt_firmas->execute([$documento_id]);
$firmas = $stmt_firmas->fetchAll();

// Crear PDF
class DocumentoPDF extends FPDF {
    private $documento;
    private $firmas;
    
    public function __construct($documento, $firmas) {
        parent::__construct();
        $this->documento = $documento;
        $this->firmas = $firmas;
    }
    
    function Header() {
        // Logo (placeholder)
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_decode(strtoupper($this->documento['escuela_nombre'])), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode($this->documento['escuela_direccion']), 0, 1, 'C');
        $this->Cell(0, 5, 'Tel: ' . $this->documento['escuela_telefono'], 0, 1, 'C');
        $this->Ln(10);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Documento generado el ' . date('d/m/Y H:i:s') . ' - Sistema ESIBOC', 0, 0, 'C');
    }
    
    function generarContenido() {
        // Título del documento
        $this->SetFont('Arial', 'B', 14);
        $titulo = '';
        switch ($this->documento['tipo']) {
            case 'acta':
                $titulo = 'ACTA DE FINALIZACIÓN DE CURSO';
                break;
            case 'informe':
                $titulo = 'INFORME DE CURSO';
                break;
            case 'certificado':
                $titulo = 'CERTIFICADO DE PARTICIPACIÓN';
                break;
            case 'directorio':
                $titulo = 'DIRECTORIO DE PARTICIPANTES';
                break;
        }
        
        $this->Cell(0, 10, utf8_decode($titulo), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode('Código: ' . $this->documento['codigo_unico']), 0, 1, 'C');
        $this->Ln(10);
        
        // Contenido específico según tipo
        if ($this->documento['tipo'] === 'certificado' && $this->documento['participante_nombre']) {
            $this->generarCertificado();
        } else {
            $this->generarDocumentoGeneral();
        }
        
        // Agregar firmas
        $this->agregarFirmas();
    }
    
    function generarCertificado() {
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, utf8_decode('Se certifica que:'), 0, 1, 'C');
        $this->Ln(5);
        
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, utf8_decode(strtoupper($this->documento['participante_nombre'])), 0, 1, 'C');
        $this->Ln(5);
        
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, utf8_decode('Con cédula de identidad: ' . $this->documento['participante_cedula']), 0, 1, 'C');
        $this->Ln(5);
        
        $this->Cell(0, 8, utf8_decode('Ha participado satisfactoriamente en el curso:'), 0, 1, 'C');
        $this->Ln(5);
        
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, utf8_decode(strtoupper($this->documento['curso_nombre'])), 0, 1, 'C');
        $this->Ln(5);
        
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, utf8_decode('Con una duración de ' . $this->documento['duracion_horas'] . ' horas académicas'), 0, 1, 'C');
        $this->Ln(5);
        
        $fecha_inicio = date('d/m/Y', strtotime($this->documento['fecha_inicio']));
        $fecha_fin = date('d/m/Y', strtotime($this->documento['fecha_fin']));
        $this->Cell(0, 8, utf8_decode('Realizado desde el ' . $fecha_inicio . ' hasta el ' . $fecha_fin), 0, 1, 'C');
        $this->Ln(10);
    }
    
    function generarDocumentoGeneral() {
        $this->SetFont('Arial', '', 11);
        
        $this->Cell(40, 8, utf8_decode('Curso:'), 0, 0, 'L');
        $this->Cell(0, 8, utf8_decode($this->documento['curso_nombre']), 0, 1, 'L');
        
        $this->Cell(40, 8, utf8_decode('Registro:'), 0, 0, 'L');
        $this->Cell(0, 8, utf8_decode($this->documento['numero_registro']), 0, 1, 'L');
        
        $this->Cell(40, 8, utf8_decode('Duración:'), 0, 0, 'L');
        $this->Cell(0, 8, utf8_decode($this->documento['duracion_horas'] . ' horas académicas'), 0, 1, 'L');
        
        $fecha_inicio = date('d/m/Y', strtotime($this->documento['fecha_inicio']));
        $fecha_fin = date('d/m/Y', strtotime($this->documento['fecha_fin']));
        $this->Cell(40, 8, utf8_decode('Período:'), 0, 0, 'L');
        $this->Cell(0, 8, utf8_decode('Del ' . $fecha_inicio . ' al ' . $fecha_fin), 0, 1, 'L');
        
        $this->Ln(10);
        
        // Contenido específico
        switch ($this->documento['tipo']) {
            case 'acta':
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(0, 8, utf8_decode('ACTA DE FINALIZACIÓN'), 0, 1, 'L');
                $this->SetFont('Arial', '', 10);
                $this->MultiCell(0, 6, utf8_decode('Por medio de la presente se hace constar que el curso mencionado ha sido completado satisfactoriamente, cumpliendo con todos los requisitos académicos establecidos.'));
                break;
            case 'informe':
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(0, 8, utf8_decode('INFORME EJECUTIVO'), 0, 1, 'L');
                $this->SetFont('Arial', '', 10);
                $this->MultiCell(0, 6, utf8_decode('Se presenta el informe correspondiente al desarrollo del curso, incluyendo estadísticas de participación, resultados académicos y observaciones generales del proceso formativo.'));
                break;
            case 'directorio':
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(0, 8, utf8_decode('DIRECTORIO DE PARTICIPANTES'), 0, 1, 'L');
                $this->SetFont('Arial', '', 10);
                $this->MultiCell(0, 6, utf8_decode('Listado oficial de los participantes inscritos y que completaron el curso de capacitación.'));
                break;
        }
        
        $this->Ln(10);
    }
    
    function agregarFirmas() {
        // Filtrar solo las firmas reales (no las revisiones)
        $firmas_reales = array_filter($this->firmas, function($firma) {
            return $firma['accion'] === 'firma';
        });
        
        if (count($firmas_reales) === 0) {
            return;
        }
        
        $this->Ln(20);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, utf8_decode('FIRMAS Y AUTORIZACIONES'), 0, 1, 'C');
        $this->Ln(10);
        
        $ancho_firma = 85;
        
        // Organizar firmas por rol para posicionamiento específico
        $firmas_por_rol = [];
        foreach ($firmas_reales as $firma) {
            $firmas_por_rol[$firma['rol_nombre']] = $firma;
        }

        $y_inicial = $this->GetY();

        // Primera fila: Dirección Nacional (centrado)
        if (isset($firmas_por_rol['Dirección Nacional'])) {
            $firma = $firmas_por_rol['Dirección Nacional'];
            $x_centro = 105 - ($ancho_firma / 2); // Centro de la página
            $this->dibujarFirma($firma, $x_centro, $y_inicial);
            unset($firmas_por_rol['Dirección Nacional']);
        }

        // Segunda fila: Director de Escuela (izquierda) y Coordinador (derecha)
        $y_segunda_fila = $y_inicial + 60;
        $x_izquierda = 20;
        $x_derecha = 105;

        if (isset($firmas_por_rol['Director de Escuela'])) {
            $firma = $firmas_por_rol['Director de Escuela'];
            $this->dibujarFirma($firma, $x_izquierda, $y_segunda_fila);
            unset($firmas_por_rol['Director de Escuela']);
        }

        if (isset($firmas_por_rol['Coordinador'])) {
            $firma = $firmas_por_rol['Coordinador'];
            $this->dibujarFirma($firma, $x_derecha, $y_segunda_fila);
            unset($firmas_por_rol['Coordinador']);
        }

        // Tercera fila: Otras firmas restantes
        if (count($firmas_por_rol) > 0) {
            $y_tercera_fila = $y_segunda_fila + 60;
            $x_pos = 20;
            foreach ($firmas_por_rol as $firma) {
                $this->dibujarFirma($firma, $x_pos, $y_tercera_fila);
                $x_pos += $ancho_firma + 10;
                if ($x_pos > 150) { // Nueva fila si no cabe
                    $y_tercera_fila += 60;
                    $x_pos = 20;
                }
            }
        }
        
        // Posicionar cursor después de todas las firmas
        $this->SetY(max($y_inicial + 120, $y_segunda_fila + 60, isset($y_tercera_fila) ? $y_tercera_fila + 60 : 0));
        
        // Agregar sección de revisiones si existen
        $this->agregarRevisiones();
    }

    function dibujarFirma($firma, $x, $y) {
        $ancho_firma = 85;
        
        // Área de firma
        $this->SetXY($x, $y);
        
        // Línea para la firma
        $this->Line($x, $y + 25, $x + $ancho_firma, $y + 25);
        
        // Contenido de la firma
        if ($firma['tipo_firma_config'] === 'texto' && $firma['contenido_firma']) {
            $this->SetXY($x, $y + 15);
            $this->SetFont('Arial', 'I', 12);
            $this->Cell($ancho_firma, 8, utf8_decode($firma['contenido_firma']), 0, 0, 'C');
        } else {
            // Para firmas de imagen, mostrar el nombre
            $this->SetXY($x, $y + 15);
            $this->SetFont('Arial', 'I', 10);
            $this->Cell($ancho_firma, 8, utf8_decode($firma['firmante_nombre']), 0, 0, 'C');
        }
        
        // Información del firmante
        $this->SetXY($x, $y + 30);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($ancho_firma, 5, utf8_decode($firma['firmante_nombre']), 0, 1, 'C');
        
        $this->SetXY($x, $y + 35);
        $this->SetFont('Arial', '', 8);
        $this->Cell($ancho_firma, 4, utf8_decode($firma['rol_nombre']), 0, 1, 'C');
        
        $this->SetXY($x, $y + 40);
        $fecha_firma = date('d/m/Y H:i', strtotime($firma['fecha_firma']));
        $this->Cell($ancho_firma, 4, utf8_decode('Firmado: ' . $fecha_firma), 0, 1, 'C');
        
        if ($firma['observaciones']) {
            $this->SetXY($x, $y + 45);
            $this->SetFont('Arial', 'I', 7);
            $this->Cell($ancho_firma, 3, utf8_decode(substr($firma['observaciones'], 0, 50) . '...'), 0, 1, 'C');
        }
    }

    function agregarRevisiones() {
        // Filtrar solo las revisiones
        $revisiones = array_filter($this->firmas, function($firma) {
            return $firma['accion'] === 'revision';
        });
        
        if (count($revisiones) === 0) {
            return;
        }
        
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, utf8_decode('REVISIONES Y APROBACIONES'), 0, 1, 'C');
        $this->Ln(5);
        
        foreach ($revisiones as $revision) {
            $this->SetFont('Arial', '', 9);
            $fecha_revision = date('d/m/Y H:i', strtotime($revision['fecha_firma']));
            
            $texto_revision = 'Revisado por: ' . $revision['firmante_nombre'] . 
                             ' (' . $revision['rol_nombre'] . ') - ' . $fecha_revision;
            
            $this->Cell(0, 6, utf8_decode($texto_revision), 0, 1, 'L');
            
            if ($revision['observaciones']) {
                $this->SetFont('Arial', 'I', 8);
                $this->Cell(10, 5, '', 0, 0); // Indentación
                $this->MultiCell(0, 4, utf8_decode('Observaciones: ' . $revision['observaciones']), 0, 'L');
            }
            
            $this->Ln(2);
        }
    }
}

// Generar PDF
$pdf = new DocumentoPDF($documento, $firmas);
$pdf->AddPage();
$pdf->generarContenido();

// Generar nombre del archivo
$filename = $documento['tipo'] . '_' . $documento['codigo_unico'] . '_' . date('Y-m-d') . '.pdf';

// Enviar PDF
$pdf->Output('D', $filename);
?>
