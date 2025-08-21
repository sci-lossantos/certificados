<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/document-workflow.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$workflow = new DocumentWorkflow($db);

// Verificar acceso
$auth->requireRole(['Escuela', 'Coordinador', 'Director de Escuela', 'Educación DNBC', 'Dirección Nacional']);

$documento_id = $_GET['id'] ?? 0;

if (!$documento_id) {
    header('Location: documentos.php');
    exit;
}

// Obtener información del documento
$query_documento = "SELECT d.*, c.nombre as curso_nombre, c.numero_registro, c.fecha_inicio, c.fecha_fin, c.duracion_horas,
                           CONCAT(p.nombres, ' ', p.apellidos) as participante_nombre, p.cedula as participante_cedula,
                           CONCAT(u.nombres, ' ', u.apellidos) as generado_por_nombre,
                           e.nombre as escuela_nombre, e.direccion as escuela_direccion, e.telefono as escuela_telefono, e.id as escuela_id
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
    header('Location: documentos.php');
    exit;
}

// Obtener configuración de la escuela para el directorio
$query_escuela_config = "SELECT * FROM escuelas WHERE id = ?";
$stmt_escuela_config = $db->prepare($query_escuela_config);
$stmt_escuela_config->execute([$documento['escuela_id']]);
$escuela_config = $stmt_escuela_config->fetch();

// Si es un directorio, obtener los participantes del curso
$participantes_directorio = [];
$coordinador_curso = null;
if ($documento['tipo'] === 'directorio') {
    $query_participantes = "SELECT p.*, m.calificacion 
                       FROM participantes p 
                       JOIN matriculas m ON p.id = m.participante_id 
                       WHERE m.curso_id = ?
                       ORDER BY p.apellidos, p.nombres";
    $stmt_participantes = $db->prepare($query_participantes);
    $stmt_participantes->execute([$documento['curso_id']]);
    $participantes_directorio = $stmt_participantes->fetchAll();
    
    // Obtener información del coordinador del curso
    $query_coordinador = "SELECT u.*, CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo
                         FROM usuarios u 
                         JOIN cursos c ON u.id = c.coordinador_id 
                         WHERE c.id = ?";
    $stmt_coordinador = $db->prepare($query_coordinador);
    $stmt_coordinador->execute([$documento['curso_id']]);
    $coordinador_curso = $stmt_coordinador->fetch();
}

// Obtener historial de firmas
$historial_firmas = $workflow->getDocumentHistory($documento_id);

// Obtener firmas con detalles completos
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

$page_title = 'Ver Documento - ' . $documento['codigo_unico'];

include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-file-alt text-blue-600 mr-3"></i>Documento: <?php echo htmlspecialchars($documento['codigo_unico']); ?>
            </h1>
            <p class="text-gray-600">
                <?php echo ucfirst($documento['tipo']); ?> - <?php echo htmlspecialchars($documento['curso_nombre']); ?>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="documentos.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
            <a href="generar-pdf-simple.php?id=<?php echo $documento['id']; ?>" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
                <i class="fas fa-file-pdf mr-2"></i>Descargar PDF
            </a>
        </div>
    </div>
</div>

<!-- Información del documento -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Detalles del documento -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalles del Documento</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                <p class="mt-1 text-sm text-gray-900"><?php echo ucfirst($documento['tipo']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <p class="mt-1">
                    <?php 
// Obtener todas las firmas y revisiones para determinar el estado real
$query_check_completion = "SELECT fd.accion, u.rol_id, r.nombre as rol_nombre
                          FROM firmas_documentos fd
                          JOIN usuarios u ON fd.usuario_id = u.id
                          JOIN roles r ON u.rol_id = r.id
                          WHERE fd.documento_id = ?";
$stmt_check = $db->prepare($query_check_completion);
$stmt_check->execute([$documento_id]);
$acciones_realizadas = $stmt_check->fetchAll();

// Definir flujos requeridos por tipo de documento
$flujos_requeridos = [
    'acta' => [
        'Coordinador' => 'firma',
        'Director de Escuela' => 'firma', 
        'Educación DNBC' => 'revision',
        'Dirección Nacional' => 'firma'
    ],
    'certificado' => [
        'Coordinador' => 'firma',
        'Director de Escuela' => 'firma',
        'Educación DNBC' => 'revision', 
        'Dirección Nacional' => 'firma'
    ],
    'informe' => [
        'Coordinador' => 'firma',
        'Director de Escuela' => 'revision',
        'Educación DNBC' => 'revision'
    ],
    'directorio' => [
        'Coordinador' => 'revision',
        'Director de Escuela' => 'revision',
        'Educación DNBC' => 'revision'
    ]
];

$flujo_actual = $flujos_requeridos[$documento['tipo']] ?? [];

// Verificar qué roles han completado sus acciones
$roles_completados = [];
foreach ($acciones_realizadas as $accion) {
    $rol = $accion['rol_nombre'];
    $tipo_accion = $accion['accion'];
    
    // Verificar si este rol completó la acción requerida
    if (isset($flujo_actual[$rol]) && $flujo_actual[$rol] === $tipo_accion) {
        $roles_completados[] = $rol;
    }
}

// Determinar si el documento está realmente completado
$documento_completado = true;
foreach ($flujo_actual as $rol_requerido => $accion_requerida) {
    if (!in_array($rol_requerido, $roles_completados)) {
        $documento_completado = false;
        break;
    }
}

// Determinar el estado actual basado en el progreso real
$estado_actual_calculado = $documento['estado'];
if ($documento_completado) {
    $estado_actual_calculado = 'completado';
}

$estado_info = [
    'generado' => ['bg-gray-100 text-gray-800', 'Generado'],
    'firmado_coordinador' => ['bg-blue-100 text-blue-800', 'Firmado por Coordinador'],
    'revisado_directorio_coordinador' => ['bg-blue-100 text-blue-800', 'Revisado por Coordinador'],
    'firmado_director_escuela' => ['bg-purple-100 text-purple-800', 'Firmado por Director'],
    'revisado_director_escuela' => ['bg-indigo-100 text-indigo-800', 'Revisado por Director'],
    'aprobado_educacion_dnbc' => ['bg-orange-100 text-orange-800', 'Aprobado por Educación'],
    'firmado_director_nacional' => ['bg-yellow-100 text-yellow-800', 'Firmado por Dir. Nacional'],
    'completado' => ['bg-green-100 text-green-800', 'Completado']
];

// Usar el estado calculado en lugar del estado de la base de datos
$estado_class = $estado_info[$estado_actual_calculado][0] ?? 'bg-gray-100 text-gray-800';
$estado_text = $estado_info[$estado_actual_calculado][1] ?? ucfirst($estado_actual_calculado);
                    ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $estado_class; ?>">
                        <?php echo $estado_text; ?>
                    </span>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Curso</label>
                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($documento['curso_nombre']); ?></p>
                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($documento['numero_registro']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Escuela</label>
                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($documento['escuela_nombre']); ?></p>
            </div>
            <?php if ($documento['participante_nombre']): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700">Participante</label>
                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($documento['participante_nombre']); ?></p>
                <p class="text-xs text-gray-500">Cédula: <?php echo htmlspecialchars($documento['participante_cedula']); ?></p>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-sm font-medium text-gray-700">Generado por</label>
                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($documento['generado_por_nombre']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha de generación</label>
                <p class="mt-1 text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($documento['created_at'])); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Estado del flujo -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Progreso del Documento</h3>
        
        <div class="space-y-4">
            <?php
$flujo_estados = [];
switch ($documento['tipo']) {
    case 'acta':
    case 'certificado':
        $flujo_estados = [
            'generado' => 'Generado',
            'firmado_coordinador' => 'Firmado por Coordinador',
            'firmado_director_escuela' => 'Firmado por Director',
            'aprobado_educacion_dnbc' => 'Aprobado por Educación',
            'firmado_director_nacional' => 'Firmado por Dir. Nacional',
            'completado' => 'Completado'
        ];
        break;
    case 'informe':
        $flujo_estados = [
            'generado' => 'Generado',
            'firmado_coordinador' => 'Firmado por Coordinador',
            'revisado_director_escuela' => 'Revisado por Director',
            'aprobado_educacion_dnbc' => 'Aprobado por Educación',
            'completado' => 'Completado'
        ];
        break;
    case 'directorio':
        $flujo_estados = [
            'generado' => 'Generado',
            'revisado_directorio_coordinador' => 'Revisado por Coordinador',
            'revisado_director_escuela' => 'Revisado por Director',
            'aprobado_educacion_dnbc' => 'Aprobado por Educación',
            'completado' => 'Completado'
        ];
        break;
}

$estados_array = array_keys($flujo_estados);

// Usar el estado calculado para determinar el progreso
$estado_actual_index = array_search($estado_actual_calculado, $estados_array);

// Si el documento está completado, mostrar todos los pasos como completados
if ($estado_actual_calculado === 'completado') {
    $estado_actual_index = count($estados_array) - 1; // Último índice
}

foreach ($flujo_estados as $estado => $texto) {
    $index = array_search($estado, $estados_array);
    $is_current = $estado === $estado_actual_calculado && $estado_actual_calculado !== 'completado';
    $is_completed = $index < $estado_actual_index || 
                   ($estado_actual_calculado === 'completado' && $index < count($estados_array) - 1) ||
                   ($estado === 'completado' && $estado_actual_calculado === 'completado');
    
    $icon_class = $is_completed ? 'fa-check-circle text-green-600' : 
                 ($is_current ? 'fa-clock text-yellow-600' : 'fa-circle text-gray-300');
    $text_class = $is_completed ? 'text-green-600' : 
                 ($is_current ? 'text-yellow-600 font-semibold' : 'text-gray-400');
?>
            <div class="flex items-center">
                <i class="fas <?php echo $icon_class; ?> mr-3"></i>
                <span class="<?php echo $text_class; ?>"><?php echo $texto; ?></span>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Vista previa del documento -->
<div class="bg-white rounded-xl shadow-lg p-8 mb-6">
    <?php if ($documento['tipo'] === 'directorio'): ?>
<!-- Vista previa del directorio -->
<div id="directorio-preview">
    <!-- Portada -->
    <div class="min-h-screen flex flex-col page-break">
        <!-- Encabezado -->
        <div class="border-2 border-black p-3 mb-6">
            <div class="grid grid-cols-3 gap-4 items-start text-xs">
                <div class="text-left">
                    <div class="font-bold">Código: <?php echo $escuela_config['codigo_formato'] ?? 'ESIBOC-FO-03'; ?></div>
                    <div class="font-bold mt-1">FORMATO DIRECTORIO FINALIZACIÓN DE CURSO</div>
                </div>
                <div class="text-center">
                    <div class="font-bold text-sm leading-tight"><?php echo $escuela_config['nombre_completo'] ?? $documento['escuela_nombre']; ?></div>
                    <?php if (!empty($escuela_config['nombre_estacion'])): ?>
                    <div class="font-bold text-sm leading-tight mt-1"><?php echo $escuela_config['nombre_estacion']; ?></div>
                    <?php endif; ?>
                    <div class="font-bold text-sm leading-tight mt-1"><?php echo $escuela_config['nombre'] ?? $documento['escuela_nombre']; ?></div>
                </div>
                <div class="text-right">
                    <div class="font-bold">Versión: <?php echo $escuela_config['version_formato'] ?? '1'; ?></div>
                    <div class="font-bold mt-1">Vigente Desde:</div>
                    <div class="font-bold"><?php echo date('d/m/Y', strtotime($escuela_config['fecha_vigencia'] ?? 'now')); ?></div>
                </div>
            </div>
        </div>

        <!-- Contenido centrado de la portada -->
        <div class="flex-1 flex flex-col justify-center items-center text-center py-20">
            <h1 class="text-8xl font-bold mb-32 tracking-wider">DIRECTORIO</h1>
            
            <div class="space-y-6 text-xl max-w-4xl">
                <div class="font-bold text-2xl leading-relaxed">
                    <?php echo strtoupper($documento['curso_nombre']); ?>
                </div>
                <div class="font-bold text-xl">
                    REGISTRO <?php echo $documento['numero_registro']; ?>
                </div>
                <div class="font-bold text-xl">
                    DEL <?php 
                    $fecha_inicio = new DateTime($documento['fecha_inicio']);
                    $fecha_fin = new DateTime($documento['fecha_fin']);
                    echo $fecha_inicio->format('j') . ' DE ' . strtoupper($fecha_inicio->format('F')) . ' DE ' . $fecha_inicio->format('Y');
                    ?> AL <?php 
                    echo $fecha_fin->format('j') . ' DE ' . strtoupper($fecha_fin->format('F')) . ' DE ' . $fecha_fin->format('Y');
                    ?>
                </div>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="text-center text-lg font-bold leading-relaxed">
            <?php if (!empty($escuela_config['pie_pagina'])): ?>
                <?php echo nl2br(htmlspecialchars($escuela_config['pie_pagina'])); ?>
            <?php else: ?>
                <?php echo $escuela_config['nombre_completo'] ?? $documento['escuela_nombre']; ?><br>
                <?php echo $escuela_config['nombre'] ?? $documento['escuela_nombre']; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php 
    // Dividir participantes en páginas de 4
    $participantes_por_pagina = 4;
    $total_paginas = ceil(count($participantes_directorio) / $participantes_por_pagina);
    
    for ($pagina = 0; $pagina < $total_paginas; $pagina++): 
        $inicio = $pagina * $participantes_por_pagina;
        $participantes_pagina = array_slice($participantes_directorio, $inicio, $participantes_por_pagina);
    ?>
    
    <!-- Página de Participantes -->
    <div class="min-h-screen page-break">
        <!-- Encabezado repetido -->
        <div class="border-2 border-black p-3 mb-6">
            <div class="grid grid-cols-3 gap-4 items-start text-xs">
                <div class="text-left">
                    <div class="font-bold">Código: <?php echo $escuela_config['codigo_formato'] ?? 'ESIBOC-FO-03'; ?></div>
                    <div class="font-bold mt-1">FORMATO DIRECTORIO FINALIZACIÓN DE CURSO</div>
                </div>
                <div class="text-center">
                    <div class="font-bold text-sm leading-tight"><?php echo $escuela_config['nombre_completo'] ?? $documento['escuela_nombre']; ?></div>
                    <?php if (!empty($escuela_config['nombre_estacion'])): ?>
                    <div class="font-bold text-sm leading-tight mt-1"><?php echo $escuela_config['nombre_estacion']; ?></div>
                    <?php endif; ?>
                    <div class="font-bold text-sm leading-tight mt-1"><?php echo $escuela_config['nombre'] ?? $documento['escuela_nombre']; ?></div>
                </div>
                <div class="text-right">
                    <div class="font-bold">Versión: <?php echo $escuela_config['version_formato'] ?? '1'; ?></div>
                    <div class="font-bold mt-1">Vigente Desde:</div>
                    <div class="font-bold"><?php echo date('d/m/Y', strtotime($escuela_config['fecha_vigencia'] ?? 'now')); ?></div>
                </div>
            </div>
        </div>

        <?php if ($pagina === 0): ?>
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold">PARTICIPANTES</h2>
        </div>
        <?php endif; ?>

        <div class="space-y-8">
            <?php foreach ($participantes_pagina as $index => $participante): 
                $numero_global = $inicio + $index + 1;
            ?>
            <div class="border-2 border-black p-4">
    <div class="grid grid-cols-12 gap-4">
        <!-- Número del participante -->
        <div class="col-span-1 flex items-start justify-center">
            <div class="text-2xl font-bold mt-2"><?php echo $numero_global; ?></div>
        </div>
        
        <!-- Información del participante -->
        <div class="col-span-8 space-y-3">
            <!-- Primera fila: Nombres y Cédula -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <div class="font-bold text-sm mb-1">NOMBRES Y APELLIDOS:</div>
                    <div class="font-bold text-lg leading-tight">
                        <?php echo strtoupper($participante['nombres'] . ' ' . $participante['apellidos']); ?>
                    </div>
                </div>
                <div>
                    <div class="font-bold text-sm mb-1">CEDULA</div>
                    <div class="text-lg font-bold"><?php echo $participante['cedula']; ?></div>
                </div>
            </div>
            
            <!-- Segunda fila: Entidad -->
            <div>
                <div class="font-bold text-sm mb-1">ENTIDAD:</div>
                <div class="font-bold text-base leading-tight">
                    <?php echo strtoupper($participante['entidad'] ?? 'NO ESPECIFICADA'); ?>
                </div>
            </div>
            
            <!-- Tercera fila: Email y Celular -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <div class="font-bold text-sm mb-1">E-MAIL:</div>
                    <div class="text-base"><?php echo $participante['email'] ?? ''; ?></div>
                </div>
                <div>
                    <div class="font-bold text-sm mb-1">CELULAR:</div>
                    <div class="text-base"><?php echo $participante['celular'] ?? ''; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Fotografía del participante -->
        <div class="col-span-3 flex flex-col items-center">
            <div class="font-bold text-sm mb-2">FOTOGRAFIA:</div>
            <div class="border-2 border-gray-400 w-32 h-40 flex items-center justify-center bg-gray-50">
                <?php if (!empty($participante['fotografia']) && file_exists($participante['fotografia'])): ?>
                    <img src="<?php echo htmlspecialchars($participante['fotografia']); ?>" 
                         alt="Foto de <?php echo htmlspecialchars($participante['nombres']); ?>" 
                         class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="text-center text-gray-500 text-xs p-2">
                        <i class="fas fa-user text-2xl mb-2"></i>
                        <div>Sin fotografía</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endfor; ?>

    <!-- Página de Coordinador e Instructores -->
    <div class="min-h-screen page-break">
        <!-- Encabezado repetido -->
        <div class="border-2 border-black p-3 mb-6">
            <div class="grid grid-cols-3 gap-4 items-start text-xs">
                <div class="text-left">
                    <div class="font-bold">Código: <?php echo $escuela_config['codigo_formato'] ?? 'ESIBOC-FO-03'; ?></div>
                    <div class="font-bold mt-1">FORMATO DIRECTORIO FINALIZACIÓN DE CURSO</div>
                </div>
                <div class="text-center">
                    <div class="font-bold text-sm leading-tight"><?php echo $escuela_config['nombre_completo'] ?? $documento['escuela_nombre']; ?></div>
                    <?php if (!empty($escuela_config['nombre_estacion'])): ?>
                    <div class="font-bold text-sm leading-tight mt-1"><?php echo $escuela_config['nombre_estacion']; ?></div>
                    <?php endif; ?>
                    <div class="font-bold text-sm leading-tight mt-1"><?php echo $escuela_config['nombre'] ?? $documento['escuela_nombre']; ?></div>
                </div>
                <div class="text-right">
                    <div class="font-bold">Versión: <?php echo $escuela_config['version_formato'] ?? '1'; ?></div>
                    <div class="font-bold mt-1">Vigente Desde:</div>
                    <div class="font-bold"><?php echo date('d/m/Y', strtotime($escuela_config['fecha_vigencia'] ?? 'now')); ?></div>
                </div>
            </div>
        </div>

        <div class="mb-8">
            <h2 class="text-2xl font-bold">COORDINADOR E INSTRUCTORES</h2>
        </div>

        <div class="space-y-8">
            <?php if ($coordinador_curso): ?>
            <div class="border-2 border-black p-4">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-1 flex items-start justify-center">
                        <div class="text-2xl font-bold mt-2">1</div>
                    </div>
                    <div class="col-span-11 space-y-3">
                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <div class="font-bold text-sm mb-1">NOMBRES Y APELLIDOS:</div>
                                <div class="font-bold text-lg leading-tight"><?php echo strtoupper($coordinador_curso['nombre_completo']); ?></div>
                            </div>
                            <div>
                                <div class="font-bold text-sm mb-1">CEDULA</div>
                                <div class="text-lg font-bold"><?php echo $coordinador_curso['cedula'] ?? 'NO ESPECIFICADA'; ?></div>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold text-sm mb-1">ENTIDAD:</div>
                            <div class="font-bold text-base leading-tight"><?php echo strtoupper($coordinador_curso['entidad'] ?? 'NO ESPECIFICADA'); ?></div>
                        </div>
                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <div class="font-bold text-sm mb-1">E-MAIL:</div>
                                <div class="text-base"><?php echo $coordinador_curso['email'] ?? ''; ?></div>
                            </div>
                            <div>
                                <div class="font-bold text-sm mb-1">CELULAR:</div>
                                <div class="text-base"><?php echo $coordinador_curso['telefono'] ?? $coordinador_curso['celular'] ?? ''; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="border-2 border-black p-4">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-1 flex items-start justify-center">
                        <div class="text-2xl font-bold mt-2">1</div>
                    </div>
                    <div class="col-span-11 space-y-3">
                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <div class="font-bold text-sm mb-1">NOMBRES Y APELLIDOS:</div>
                                <div class="font-bold text-lg leading-tight">COORDINADOR NO ASIGNADO</div>
                            </div>
                            <div>
                                <div class="font-bold text-sm mb-1">CEDULA</div>
                                <div class="text-lg font-bold">-</div>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold text-sm mb-1">ENTIDAD:</div>
                            <div class="font-bold text-base leading-tight">-</div>
                        </div>
                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <div class="font-bold text-sm mb-1">E-MAIL:</div>
                                <div class="text-base">-</div>
                            </div>
                            <div>
                                <div class="font-bold text-sm mb-1">CELULAR:</div>
                                <div class="text-base">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Página de Logística -->
    <div class="min-h-screen page-break">
        <!-- Encabezado repetido -->
        <div class="border-2 border-black p-3 mb-6">
            <div class="grid grid-cols-3 gap-4 items-start text-xs">
                <div class="text-left">
                    <div class="font-bold">Código: <?php echo $escuela_config['codigo_formato'] ?? 'ESIBOC-FO-03'; ?></div>
                    <div class="font-bold mt-1">FORMATO DIRECTORIO FINALIZACIÓN DE CURSO</div>
                </div>
                <div class="text-center">
                    <div class="font-bold text-sm leading-tight"><?php echo $escuela_config['nombre_completo'] ?? $documento['escuela_nombre']; ?></div>
                    <?php if (!empty($escuela_config['nombre_estacion'])): ?>
                    <div class="font-bold text-sm leading-tight mt-1"><?php echo $escuela_config['nombre_estacion']; ?></div>
                    <?php endif; ?>
                    <div class="font-bold text-sm leading-tight mt-1"><?php echo $escuela_config['nombre'] ?? $documento['escuela_nombre']; ?></div>
                </div>
                <div class="text-right">
                    <div class="font-bold">Versión: <?php echo $escuela_config['version_formato'] ?? '1'; ?></div>
                    <div class="font-bold mt-1">Vigente Desde:</div>
                    <div class="font-bold"><?php echo date('d/m/Y', strtotime($escuela_config['fecha_vigencia'] ?? 'now')); ?></div>
                </div>
            </div>
        </div>

        <div class="mb-8">
            <h2 class="text-2xl font-bold">LOGISTICA</h2>
        </div>

        <div class="space-y-8">
            <div class="border-2 border-black p-4">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-1 flex items-start justify-center">
                        <div class="text-2xl font-bold mt-2">1</div>
                    </div>
                    <div class="col-span-11 space-y-3">
                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <div class="font-bold text-sm mb-1">NOMBRES Y APELLIDOS:</div>
                                <div class="font-bold text-lg leading-tight">JORGE ELIECER SERRANO</div>
                            </div>
                            <div>
                                <div class="font-bold text-sm mb-1">CEDULA</div>
                                <div class="text-lg font-bold">91355840</div>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold text-sm mb-1">ENTIDAD:</div>
                            <div class="font-bold text-base leading-tight">CUERPO BOMBEROS VOLUNTARIOS LOS SANTOS</div>
                        </div>
                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <div class="font-bold text-sm mb-1">E-MAIL:</div>
                                <div class="text-base">direccionacademica@esiboc.edu.co</div>
                            </div>
                            <div>
                                <div class="font-bold text-sm mb-1">CELULAR:</div>
                                <div class="text-base">3003272507</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
    <!-- Vista previa original para otros tipos de documento -->
    <h3 class="text-lg font-semibold text-gray-900 mb-6">Vista Previa del Documento</h3>
    
    <!-- Encabezado del documento -->
    <div class="text-center mb-8">
        <div class="mb-4">
            <img src="/placeholder.svg?height=80&width=200&text=Logo+Institución" alt="Logo" class="mx-auto h-20">
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            <?php echo strtoupper($documento['escuela_nombre']); ?>
        </h1>
        <p class="text-gray-600"><?php echo htmlspecialchars($documento['escuela_direccion']); ?></p>
        <p class="text-gray-600">Tel: <?php echo htmlspecialchars($documento['escuela_telefono']); ?></p>
    </div>
    
    <!-- Título del documento -->
    <div class="text-center mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-2">
            <?php 
            switch ($documento['tipo']) {
                case 'acta':
                    echo 'ACTA DE FINALIZACIÓN DE CURSO';
                    break;
                case 'informe':
                    echo 'INFORME DE CURSO';
                    break;
                case 'certificado':
                    echo 'CERTIFICADO DE PARTICIPACIÓN';
                    break;
                case 'directorio':
                    echo 'DIRECTORIO DE PARTICIPANTES';
                    break;
            }
            ?>
        </h2>
        <p class="text-gray-600">Código: <?php echo htmlspecialchars($documento['codigo_unico']); ?></p>
    </div>
    
    <!-- Contenido del documento -->
    <div class="mb-8 space-y-4">
        <?php if ($documento['tipo'] === 'certificado' && $documento['participante_nombre']): ?>
        <!-- Certificado individual -->
        <div class="text-center space-y-4">
            <p class="text-lg">Se certifica que:</p>
            <p class="text-2xl font-bold text-blue-600"><?php echo strtoupper($documento['participante_nombre']); ?></p>
            <p>Con cédula de identidad: <strong><?php echo htmlspecialchars($documento['participante_cedula']); ?></strong></p>
            <p>Ha participado satisfactoriamente en el curso:</p>
            <p class="text-xl font-bold"><?php echo strtoupper($documento['curso_nombre']); ?></p>
            <p>Con una duración de <strong><?php echo $documento['duracion_horas']; ?> horas académicas</strong></p>
            <p>Realizado desde el <strong><?php echo date('d/m/Y', strtotime($documento['fecha_inicio'])); ?></strong> 
               hasta el <strong><?php echo date('d/m/Y', strtotime($documento['fecha_fin'])); ?></strong></p>
        </div>
        <?php else: ?>
        <!-- Otros tipos de documento -->
        <div class="space-y-4">
            <p><strong>Curso:</strong> <?php echo htmlspecialchars($documento['curso_nombre']); ?></p>
            <p><strong>Número de Registro:</strong> <?php echo htmlspecialchars($documento['numero_registro']); ?></p>
            <p><strong>Duración:</strong> <?php echo $documento['duracion_horas']; ?> horas académicas</p>
            <p><strong>Período:</strong> Del <?php echo date('d/m/Y', strtotime($documento['fecha_inicio'])); ?> 
               al <?php echo date('d/m/Y', strtotime($documento['fecha_fin'])); ?></p>
            
            <?php if ($documento['tipo'] === 'acta'): ?>
            <div class="mt-6">
                <p class="font-semibold">ACTA DE FINALIZACIÓN</p>
                <p>Por medio de la presente se hace constar que el curso mencionado ha sido completado satisfactoriamente, 
                   cumpliendo con todos los requisitos académicos establecidos.</p>
            </div>
            <?php elseif ($documento['tipo'] === 'informe'): ?>
            <div class="mt-6">
                <p class="font-semibold">INFORME EJECUTIVO</p>
                <p>Se presenta el informe correspondiente al desarrollo del curso, incluyendo estadísticas de participación, 
                   resultados académicos y observaciones generales del proceso formativo.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>

<!-- Sección de firmas -->
<?php 
// Separar firmas reales de revisiones
$firmas_reales = array_filter($firmas, function($firma) {
    return $firma['accion'] === 'firma';
});

$revisiones = array_filter($firmas, function($firma) {
    return $firma['accion'] === 'revision';
});

// Organizar firmas por rol para el layout específico
$firmas_organizadas = [
    'director_nacional' => null,
    'director_escuela' => null,
    'coordinador' => null,
    'otras' => []
];

foreach ($firmas_reales as $firma) {
    switch (strtolower($firma['rol_nombre'])) {
        case 'dirección nacional':
            $firmas_organizadas['director_nacional'] = $firma;
            break;
        case 'director de escuela':
            $firmas_organizadas['director_escuela'] = $firma;
            break;
        case 'coordinador':
            $firmas_organizadas['coordinador'] = $firma;
            break;
        default:
            $firmas_organizadas['otras'][] = $firma;
            break;
    }
}
?>

<?php if (count($firmas_reales) > 0): ?>
<div class="mt-12 border-t pt-8">
    <h4 class="text-lg font-semibold text-gray-900 mb-6">Firmas y Autorizaciones</h4>
    
    <!-- Primera fila: Dirección Nacional centrada -->
    <?php if ($firmas_organizadas['director_nacional']): ?>
    <div class="flex justify-center mb-8">
        <div class="text-center w-80">
            <?php $firma = $firmas_organizadas['director_nacional']; ?>
            <div class="mb-4">
                <?php if ($firma['tipo_firma_config'] === 'texto'): ?>
                <!-- Firma de texto -->
                <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                    <span class="font-signature text-xl text-gray-800">
                        <?php echo htmlspecialchars($firma['contenido_firma']); ?>
                    </span>
                </div>
                <?php elseif ($firma['tipo_firma_config'] === 'canvas' || $firma['tipo_firma_config'] === 'upload'): ?>
                <!-- Firma de imagen -->
                <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                    <img src="<?php echo htmlspecialchars($firma['contenido_firma']); ?>" 
                         alt="Firma" class="max-h-16 max-w-full" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <span class="hidden font-signature text-xl text-gray-800">
                        <?php echo htmlspecialchars($firma['firmante_nombre']); ?>
                    </span>
                </div>
                <?php else: ?>
                <!-- Firma por defecto -->
                <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                    <span class="font-signature text-xl text-gray-800">
                        <?php echo htmlspecialchars($firma['firmante_nombre']); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="text-sm">
                <p class="font-semibold"><?php echo htmlspecialchars($firma['firmante_nombre']); ?></p>
                <p class="text-gray-600"><?php echo htmlspecialchars($firma['rol_nombre']); ?></p>
                <p class="text-xs text-gray-500">
                    Firmado: <?php echo date('d/m/Y H:i', strtotime($firma['fecha_firma'])); ?>
                </p>
                <?php if ($firma['observaciones']): ?>
                <p class="text-xs text-gray-500 mt-1">
                    <em><?php echo htmlspecialchars($firma['observaciones']); ?></em>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Segunda fila: Director de Escuela (izquierda) y Coordinador (derecha) -->
    <?php if ($firmas_organizadas['director_escuela'] || $firmas_organizadas['coordinador']): ?>
    <div class="grid grid-cols-2 gap-8 mb-8">
        <!-- Director de Escuela (izquierda) -->
        <div class="text-center">
            <?php if ($firmas_organizadas['director_escuela']): ?>
                <?php $firma = $firmas_organizadas['director_escuela']; ?>
                <div class="mb-4">
                    <?php if ($firma['tipo_firma_config'] === 'texto'): ?>
                    <!-- Firma de texto -->
                    <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                        <span class="font-signature text-xl text-gray-800">
                            <?php echo htmlspecialchars($firma['contenido_firma']); ?>
                        </span>
                    </div>
                    <?php elseif ($firma['tipo_firma_config'] === 'canvas' || $firma['tipo_firma_config'] === 'upload'): ?>
                    <!-- Firma de imagen -->
                    <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                        <img src="<?php echo htmlspecialchars($firma['contenido_firma']); ?>" 
                             alt="Firma" class="max-h-16 max-w-full" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <span class="hidden font-signature text-xl text-gray-800">
                            <?php echo htmlspecialchars($firma['firmante_nombre']); ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <!-- Firma por defecto -->
                    <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                        <span class="font-signature text-xl text-gray-800">
                            <?php echo htmlspecialchars($firma['firmante_nombre']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-sm">
                    <p class="font-semibold"><?php echo htmlspecialchars($firma['firmante_nombre']); ?></p>
                    <p class="text-gray-600"><?php echo htmlspecialchars($firma['rol_nombre']); ?></p>
                    <p class="text-xs text-gray-500">
                        Firmado: <?php echo date('d/m/Y H:i', strtotime($firma['fecha_firma'])); ?>
                    </p>
                    <?php if ($firma['observaciones']): ?>
                    <p class="text-xs text-gray-500 mt-1">
                        <em><?php echo htmlspecialchars($firma['observaciones']); ?></em>
                    </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Coordinador (derecha) -->
        <div class="text-center">
            <?php if ($firmas_organizadas['coordinador']): ?>
                <?php $firma = $firmas_organizadas['coordinador']; ?>
                <div class="mb-4">
                    <?php if ($firma['tipo_firma_config'] === 'texto'): ?>
                    <!-- Firma de texto -->
                    <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                        <span class="font-signature text-xl text-gray-800">
                            <?php echo htmlspecialchars($firma['contenido_firma']); ?>
                        </span>
                    </div>
                    <?php elseif ($firma['tipo_firma_config'] === 'canvas' || $firma['tipo_firma_config'] === 'upload'): ?>
                    <!-- Firma de imagen -->
                    <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                        <img src="<?php echo htmlspecialchars($firma['contenido_firma']); ?>" 
                             alt="Firma" class="max-h-16 max-w-full" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <span class="hidden font-signature text-xl text-gray-800">
                            <?php echo htmlspecialchars($firma['firmante_nombre']); ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <!-- Firma por defecto -->
                    <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                        <span class="font-signature text-xl text-gray-800">
                            <?php echo htmlspecialchars($firma['firmante_nombre']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-sm">
                    <p class="font-semibold"><?php echo htmlspecialchars($firma['firmante_nombre']); ?></p>
                    <p class="text-gray-600"><?php echo htmlspecialchars($firma['rol_nombre']); ?></p>
                    <p class="text-xs text-gray-500">
                        Firmado: <?php echo date('d/m/Y H:i', strtotime($firma['fecha_firma'])); ?>
                    </p>
                    <?php if ($firma['observaciones']): ?>
                    <p class="text-xs text-gray-500 mt-1">
                        <em><?php echo htmlspecialchars($firma['observaciones']); ?></em>
                    </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tercera fila: Otras firmas si las hay -->
    <?php if (count($firmas_organizadas['otras']) > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($firmas_organizadas['otras'] as $firma): ?>
        <div class="text-center">
            <div class="mb-4">
                <?php if ($firma['tipo_firma_config'] === 'texto'): ?>
                <!-- Firma de texto -->
                <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                    <span class="font-signature text-xl text-gray-800">
                        <?php echo htmlspecialchars($firma['contenido_firma']); ?>
                    </span>
                </div>
                <?php elseif ($firma['tipo_firma_config'] === 'canvas' || $firma['tipo_firma_config'] === 'upload'): ?>
                <!-- Firma de imagen -->
                <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                    <img src="<?php echo htmlspecialchars($firma['contenido_firma']); ?>" 
                         alt="Firma" class="max-h-16 max-w-full" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <span class="hidden font-signature text-xl text-gray-800">
                        <?php echo htmlspecialchars($firma['firmante_nombre']); ?>
                    </span>
                </div>
                <?php else: ?>
                <!-- Firma por defecto -->
                <div class="border-b-2 border-gray-400 pb-2 mb-2 min-h-[60px] flex items-end justify-center">
                    <span class="font-signature text-xl text-gray-800">
                        <?php echo htmlspecialchars($firma['firmante_nombre']); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="text-sm">
                <p class="font-semibold"><?php echo htmlspecialchars($firma['firmante_nombre']); ?></p>
                <p class="text-gray-600"><?php echo htmlspecialchars($firma['rol_nombre']); ?></p>
                <p class="text-xs text-gray-500">
                    Firmado: <?php echo date('d/m/Y H:i', strtotime($firma['fecha_firma'])); ?>
                </p>
                <?php if ($firma['observaciones']): ?>
                <p class="text-xs text-gray-500 mt-1">
                    <em><?php echo htmlspecialchars($firma['observaciones']); ?></em>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Sección de revisiones -->
<?php if (count($revisiones) > 0): ?>
<div class="mt-8 border-t pt-6">
    <h4 class="text-lg font-semibold text-gray-900 mb-4">Revisiones y Aprobaciones</h4>
    
    <div class="space-y-3">
        <?php foreach ($revisiones as $revision): ?>
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-eye text-blue-600"></i>
                </div>
                <div class="ml-3 flex-grow">
                    <div class="flex items-center justify-between">
                        <h5 class="font-semibold text-gray-900">
                            <?php echo htmlspecialchars($revision['firmante_nombre']); ?>
                        </h5>
                        <span class="text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($revision['fecha_firma'])); ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-600">
                        <?php echo htmlspecialchars($revision['rol_nombre']); ?> - Documento revisado
                    </p>
                    <?php if ($revision['observaciones']): ?>
                    <p class="text-sm text-gray-700 mt-2">
                        <strong>Observaciones:</strong> <?php echo htmlspecialchars($revision['observaciones']); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
    
    <!-- Pie de documento -->
    <div class="mt-12 text-center text-sm text-gray-500 border-t pt-4">
        <p>Documento generado el <?php echo date('d/m/Y H:i:s', strtotime($documento['created_at'])); ?></p>
        <p>Sistema ESIBOC - Gestión de Capacitaciones</p>
        <p>Código de verificación: <?php echo htmlspecialchars($documento['codigo_unico']); ?></p>
    </div>
</div>

<!-- Historial de procesamiento -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Historial de Procesamiento</h3>
    
    <?php if (count($historial_firmas) > 0): ?>
    <div class="space-y-4">
        <?php foreach ($historial_firmas as $item): ?>
        <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg">
            <div class="flex-shrink-0">
                <i class="fas fa-<?php echo $item['accion'] === 'firma' ? 'signature' : 'eye'; ?> text-blue-600"></i>
            </div>
            <div class="flex-grow">
                <div class="flex items-center justify-between">
                    <h4 class="font-semibold text-gray-900">
                        <?php echo htmlspecialchars($item['firmante_nombre']); ?>
                    </h4>
                    <span class="text-sm text-gray-500">
                        <?php echo date('d/m/Y H:i', strtotime($item['fecha_firma'])); ?>
                    </span>
                </div>
                <p class="text-sm text-gray-600">
                    <?php echo htmlspecialchars($item['rol_nombre']); ?> - 
                    <?php echo ucfirst($item['accion']); ?> el documento
                </p>
                <?php if ($item['observaciones']): ?>
                <p class="text-sm text-gray-500 mt-1">
                    <em>"<?php echo htmlspecialchars($item['observaciones']); ?>"</em>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-gray-500 text-center py-4">No hay historial de procesamiento disponible.</p>
    <?php endif; ?>
</div>

<style>
.font-signature {
    font-family: 'Brush Script MT', 'Lucida Handwriting', cursive;
}
@media print {
    .page-break {
        page-break-before: always;
    }
    .page-break:first-child {
        page-break-before: auto;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
