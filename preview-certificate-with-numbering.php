<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'tcpdf-certificate-configurable.php';

// Inicializar base de datos
$database = new Database();
$db = $database->getConnection();

// No requerimos autenticación para esta vista previa simple
// $auth = new Auth($db);
// $auth->requireAuth();

// Obtener ID del certificado (configuración) y matrícula
$certificado_id = isset($_GET['certificado_id']) ? intval($_GET['certificado_id']) : 1;
$matricula_id = isset($_GET['matricula_id']) ? intval($_GET['matricula_id']) : null;
$curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : null;
$participante_id = isset($_GET['participante_id']) ? intval($_GET['participante_id']) : null;

// Datos de prueba si no se proporciona matrícula
$datos_prueba = [
    'nombres' => 'JUAN CARLOS',
    'apellidos' => 'PÉREZ RODRÍGUEZ',
    'cedula' => '1234567890',
    'curso_nombre' => 'CURSO BÁSICO DE BOMBEROS',
    'duracion_horas' => 40,
    'escuela_nombre' => 'ESCUELA DE BOMBEROS DE COLOMBIA',
    'fecha_inicio' => '2024-05-01',
    'fecha_fin' => '2024-05-15',
    'lugar_realizacion' => 'BOGOTÁ D.C.',
    'numero_registro_curso' => 'REG-DNBC-2024-001',
    'numero_acta' => 'ACTA-001-2024',
    'fecha_acta' => '2024-05-16',
    'nombre_cuerpo_bomberos' => 'CUERPO DE BOMBEROS VOLUNTARIOS DE BOGOTÁ'
];

// Si tenemos matrícula, obtenemos los datos reales
if ($matricula_id) {
    try {
        // Consulta para obtener datos de la matrícula, participante y curso
        $query = "SELECT 
                    p.nombres, p.apellidos, p.cedula, p.fotografia,
                    c.nombre as curso_nombre, c.duracion_horas, c.fecha_inicio, c.fecha_fin,
                    c.lugar_realizacion, c.numero_registro_curso,
                    e.nombre as escuela_nombre,
                    m.calificacion, m.aprobado
                FROM matriculas m
                JOIN participantes p ON m.participante_id = p.id
                JOIN cursos c ON m.curso_id = c.id
                JOIN escuelas e ON c.escuela_id = e.id
                WHERE m.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$matricula_id]);
        
        if ($datos = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Reemplazar datos de prueba con datos reales
            $datos_prueba = array_merge($datos_prueba, $datos);
            
            // Generar número de acta y fecha si no existen
            if (empty($datos_prueba['numero_acta'])) {
                $datos_prueba['numero_acta'] = 'ACTA-' . date('Y') . '-' . str_pad(rand(1, 100), 3, '0', STR_PAD_LEFT);
                $datos_prueba['fecha_acta'] = date('Y-m-d');
            }
        }
    } catch (Exception $e) {
        // Si hay error, continuamos con los datos de prueba
        error_log("Error al obtener datos de matrícula: " . $e->getMessage());
    }
} elseif ($curso_id && $participante_id) {
    try {
        // Consulta para obtener datos del participante y curso directamente
        $query = "SELECT 
                    p.nombres, p.apellidos, p.cedula, p.fotografia,
                    c.nombre as curso_nombre, c.duracion_horas, c.fecha_inicio, c.fecha_fin,
                    c.lugar_realizacion, c.numero_registro_curso,
                    e.nombre as escuela_nombre
                FROM participantes p
                JOIN cursos c ON c.id = ?
                JOIN escuelas e ON c.escuela_id = e.id
                WHERE p.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$curso_id, $participante_id]);
        
        if ($datos = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Reemplazar datos de prueba con datos reales
            $datos_prueba = array_merge($datos_prueba, $datos);
            
            // Generar número de acta y fecha si no existen
            if (empty($datos_prueba['numero_acta'])) {
                $datos_prueba['numero_acta'] = 'ACTA-' . date('Y') . '-' . str_pad(rand(1, 100), 3, '0', STR_PAD_LEFT);
                $datos_prueba['fecha_acta'] = date('Y-m-d');
            }
        }
    } catch (Exception $e) {
        // Si hay error, continuamos con los datos de prueba
        error_log("Error al obtener datos de curso y participante: " . $e->getMessage());
    }
}

// Obtener configuración del certificado
try {
    $query_config = "SELECT * FROM configuracion_certificados WHERE id = ?";
    $stmt_config = $db->prepare($query_config);
    $stmt_config->execute([$certificado_id]);
    $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        // Si no existe la configuración, usamos la primera disponible
        $stmt_config = $db->query("SELECT * FROM configuracion_certificados ORDER BY id LIMIT 1");
        $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            throw new Exception("No se encontró ninguna configuración de certificado");
        }
    }
} catch (Exception $e) {
    die("Error al obtener configuración del certificado: " . $e->getMessage());
}

// Generar número consecutivo para el certificado
$numero_consecutivo = "CERT-" . date('Y') . "-" . str_pad(rand(1, 1000), 4, '0', STR_PAD_LEFT);

// Obtener firmas para el certificado
$firmas = [];
try {
    // Intentamos obtener las firmas configuradas
    $query_firmas = "SELECT f.*, u.nombres, u.apellidos, u.cargo 
                    FROM firmas f 
                    JOIN usuarios u ON f.usuario_id = u.id 
                    WHERE f.activa = 1 
                    ORDER BY f.tipo_firma ASC 
                    LIMIT 3";
    $stmt_firmas = $db->query($query_firmas);
    $firmas_db = $stmt_firmas->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($firmas_db)) {
        foreach ($firmas_db as $firma) {
            $firmas[] = [
                'ruta_imagen' => $firma['ruta_imagen'],
                'nombre' => $firma['nombres'] . ' ' . $firma['apellidos'],
                'cargo' => $firma['cargo']
            ];
        }
    }
} catch (Exception $e) {
    // Si hay error, usamos firmas de prueba
    error_log("Error al obtener firmas: " . $e->getMessage());
}

// Si no hay firmas, usamos datos de prueba
if (empty($firmas)) {
    $firmas = [
        [
            'ruta_imagen' => 'uploads/firmas/firma_3_1749438453.png',
            'nombre' => 'CARLOS GERMAN MORALES',
            'cargo' => 'DIRECTOR NACIONAL'
        ],
        [
            'ruta_imagen' => 'uploads/firmas/firma_4_1749442832.png',
            'nombre' => 'MARÍA FERNANDA ROJAS',
            'cargo' => 'DIRECTORA DE ESCUELA'
        ],
        [
            'ruta_imagen' => 'uploads/firmas/firma_5_1749442941.png',
            'nombre' => 'JOSÉ LUIS MARTÍNEZ',
            'cargo' => 'COORDINADOR ACADÉMICO'
        ]
    ];
}

// Obtener contenido temático del curso (para la segunda página)
$contenido_tematico = "1. INTRODUCCIÓN A LA ACTIVIDAD BOMBERIL\n";
$contenido_tematico .= "2. EQUIPOS DE PROTECCIÓN PERSONAL\n";
$contenido_tematico .= "3. COMPORTAMIENTO DEL FUEGO\n";
$contenido_tematico .= "4. TÉCNICAS DE EXTINCIÓN\n";
$contenido_tematico .= "5. PRIMEROS AUXILIOS BÁSICOS\n";
$contenido_tematico .= "6. RESCATE BÁSICO\n";
$contenido_tematico .= "7. MANEJO DE MANGUERAS Y EQUIPOS\n";
$contenido_tematico .= "8. COMUNICACIONES DE EMERGENCIA\n";
$contenido_tematico .= "9. MATERIALES PELIGROSOS - AWARENESS\n";
$contenido_tematico .= "10. SISTEMA COMANDO DE INCIDENTES\n";

// Intentar obtener el contenido temático real del curso
if ($curso_id) {
    try {
        $query_contenido = "SELECT contenido_tematico FROM cursos WHERE id = ?";
        $stmt_contenido = $db->prepare($query_contenido);
        $stmt_contenido->execute([$curso_id]);
        $resultado = $stmt_contenido->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && !empty($resultado['contenido_tematico'])) {
            $contenido_tematico = $resultado['contenido_tematico'];
        }
    } catch (Exception $e) {
        // Si hay error, continuamos con el contenido de prueba
        error_log("Error al obtener contenido temático: " . $e->getMessage());
    }
}

// Preparar datos para el certificado
$certificado_data = [
    'numero_consecutivo' => $numero_consecutivo,
    'nombres' => $datos_prueba['nombres'],
    'apellidos' => $datos_prueba['apellidos'],
    'cedula' => $datos_prueba['cedula'],
    'curso_nombre' => $datos_prueba['curso_nombre'],
    'duracion_horas' => $datos_prueba['duracion_horas'],
    'escuela_nombre' => $datos_prueba['escuela_nombre'],
    'fecha_inicio' => $datos_prueba['fecha_inicio'],
    'fecha_fin' => $datos_prueba['fecha_fin'],
    'lugar_realizacion' => $datos_prueba['lugar_realizacion'],
    'numero_registro_curso' => $datos_prueba['numero_registro_curso'],
    'numero_acta' => $datos_prueba['numero_acta'],
    'fecha_acta' => $datos_prueba['fecha_acta'],
    'nombre_cuerpo_bomberos' => $datos_prueba['nombre_cuerpo_bomberos'],
    'fecha_firma' => date('Y-m-d'),
    'firmas' => $firmas,
    'contenido_tematico' => $contenido_tematico,
    'config' => $config
];

// Generar el certificado
$pdf = new CertificadoTCPDF($certificado_data);
$pdf->Output('certificado_preview.pdf', 'I');
