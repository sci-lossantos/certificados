<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/document-workflow.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$workflow = new DocumentWorkflow($db);

// Solo roles específicos pueden acceder según su función
$auth->requireRole(['Escuela', 'Coordinador', 'Director de Escuela', 'Educación DNBC', 'Dirección Nacional']);

$page_title = 'Gestión de Documentos';
$message = '';
$error = '';

// Obtener el rol del usuario actual
$rol_actual = $_SESSION['user_role'];

// Procesar formulario
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate' && $rol_actual === 'Escuela') {
        $tipo = $_POST['tipo'] ?? '';
        $curso_id = $_POST['curso_id'] ?? '';
        $participante_id = $_POST['participante_id'] ?? null;
        
        // Validar participante_id - convertir cadena vacía a NULL
        if ($participante_id === '' || $participante_id === '0') {
            $participante_id = null;
        }
        
        if ($tipo && $curso_id) {
            try {
                // Generar código único para el documento
                $codigo_unico = strtoupper($tipo) . '-' . date('Y') . '-' . str_pad($curso_id, 4, '0', STR_PAD_LEFT) . '-' . uniqid();
                
                // Crear documento con manejo correcto de NULL
                $query_insert = "INSERT INTO documentos (tipo, curso_id, participante_id, codigo_unico, generado_por) 
                                VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = $db->prepare($query_insert);
                $stmt_insert->execute([$tipo, $curso_id, $participante_id, $codigo_unico, $_SESSION['user_id']]);
                
                $message = 'Documento generado exitosamente con código: ' . $codigo_unico;

                if ($tipo === 'certificado' && $participante_id) {
                    // Verificar si el participante ya tiene contraseña configurada
                    $query_check_password = "SELECT password FROM participantes WHERE id = ?";
                    $stmt_check = $db->prepare($query_check_password);
                    $stmt_check->execute([$participante_id]);
                    $participante_data = $stmt_check->fetch();
                    
                    // Si no tiene contraseña, configurar una por defecto (su cédula)
                    if (!$participante_data['password']) {
                        $query_participante = "SELECT cedula FROM participantes WHERE id = ?";
                        $stmt_part = $db->prepare($query_participante);
                        $stmt_part->execute([$participante_id]);
                        $part_info = $stmt_part->fetch();
                        
                        if ($part_info) {
                            $default_password = md5($part_info['cedula']);
                            $query_update_password = "UPDATE participantes SET password = ? WHERE id = ?";
                            $stmt_update = $db->prepare($query_update_password);
                            $stmt_update->execute([$default_password, $participante_id]);
                        }
                    }
                }
            } catch (Exception $e) {
                $error = 'Error al generar documento: ' . $e->getMessage();
            }
        } else {
            $error = 'Debe seleccionar el tipo de documento y el curso';
        }
    } elseif ($action === 'process') {
        $documento_id = $_POST['documento_id'] ?? '';
        $observaciones = $_POST['observaciones'] ?? '';
        $accion_proceso = $_POST['accion_proceso'] ?? 'aprobar'; // 'aprobar' o 'rechazar'
        
        if ($documento_id) {
            $result = $workflow->processDocument($documento_id, $_SESSION['user_id'], $rol_actual, $observaciones, $accion_proceso);
            
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Debe seleccionar un documento';
        }
    } elseif ($action === 'restart_flow') {
        // Nueva acción para reiniciar el flujo de un documento rechazado
        $documento_id = $_POST['documento_id'] ?? '';
        
        if ($documento_id) {
            $result = $workflow->restartDocumentFlow($documento_id, $_SESSION['user_id'], $rol_actual);
            
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Debe seleccionar un documento';
        }
    }
}

// Obtener cursos según el rol
$cursos = [];
if ($rol_actual === 'Escuela') {
    // Escuela puede generar documentos para todos los cursos
    $query_cursos = "SELECT c.id, c.nombre, c.numero_registro 
                    FROM cursos c 
                    WHERE c.activo = 1 
                    ORDER BY c.nombre";
    $stmt_cursos = $db->prepare($query_cursos);
    $stmt_cursos->execute();
    $cursos = $stmt_cursos->fetchAll();
}

// Obtener documentos según el rol
$documentos = [];
$documentos_rechazados = [];

if ($rol_actual === 'Escuela') {
    // Escuela ve todos los documentos que ha generado
    $query_documentos = "SELECT d.*, c.nombre as curso_nombre, c.numero_registro,
                                CONCAT(p.nombres, ' ', p.apellidos) as participante_nombre,
                                CONCAT(u.nombres, ' ', u.apellidos) as generado_por_nombre,
                                e.nombre as escuela_nombre
                         FROM documentos d 
                         JOIN cursos c ON d.curso_id = c.id 
                         JOIN escuelas e ON c.escuela_id = e.id
                         LEFT JOIN participantes p ON d.participante_id = p.id
                         LEFT JOIN usuarios u ON d.generado_por = u.id
                         WHERE d.id NOT IN (
                             SELECT DISTINCT documento_id 
                             FROM firmas_documentos 
                             WHERE es_rechazo = 1
                         )
                         ORDER BY d.created_at DESC";
    $stmt_documentos = $db->prepare($query_documentos);
    $stmt_documentos->execute();
    $documentos = $stmt_documentos->fetchAll();
    
    // Obtener documentos rechazados que necesitan corrección
    $documentos_rechazados = $workflow->getRejectedDocuments($_SESSION['user_id']);
    
} elseif ($rol_actual === 'Coordinador') {
    // Coordinador ve documentos pendientes pero NO ve rechazados
    $documentos = $workflow->getPendingDocuments($rol_actual);
    // No obtener documentos rechazados para el coordinador
    
} elseif ($rol_actual === 'Director de Escuela') {
    // Director de Escuela ve documentos de su escuela
    $escuela_id = $auth->getUserEscuelaId();
    if ($escuela_id) {
        // Obtener TODOS los documentos de la escuela para seguimiento (excluyendo rechazados)
        $query_documentos = "SELECT d.*, c.nombre as curso_nombre, c.numero_registro,
                                    CONCAT(p.nombres, ' ', p.apellidos) as participante_nombre,
                                    CONCAT(u.nombres, ' ', u.apellidos) as generado_por_nombre,
                                    e.nombre as escuela_nombre
                             FROM documentos d 
                             JOIN cursos c ON d.curso_id = c.id 
                             JOIN escuelas e ON c.escuela_id = e.id
                             LEFT JOIN participantes p ON d.participante_id = p.id
                             LEFT JOIN usuarios u ON d.generado_por = u.id
                             WHERE e.id = ?
                             AND d.id NOT IN (
                                 SELECT DISTINCT documento_id 
                                 FROM firmas_documentos 
                                 WHERE es_rechazo = 1
                             )
                             ORDER BY d.created_at DESC";
        $stmt_documentos = $db->prepare($query_documentos);
        $stmt_documentos->execute([$escuela_id]);
        $documentos = $stmt_documentos->fetchAll();
    } else {
        $error = 'No tiene una escuela asignada. Contacte al administrador.';
    }
} else {
    // Otros roles ven documentos pendientes de su procesamiento
    $documentos = $workflow->getPendingDocuments($rol_actual);
}

include 'includes/header.php';
?>

<!-- Estilos personalizados para botones -->
<style>
    .btn-orange {
        background-color: #f97316 !important; /* orange-500 */
        color: white !important;
        padding: 0.5rem 1rem !important;
        border-radius: 0.375rem !important;
        font-weight: 500 !important;
        display: inline-flex !important;
        align-items: center !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
    }
    
    .btn-orange:hover {
        background-color: #ea580c !important; /* orange-600 */
    }
    
    .btn-orange i {
        margin-right: 0.5rem !important;
    }
</style>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-file-alt text-blue-600 mr-3"></i>Gestión de Documentos
            </h1>
            <p class="text-gray-600">
                <?php 
                if ($rol_actual === 'Escuela') {
                    echo 'Genera documentos del sistema';
                } elseif ($rol_actual === 'Coordinador') {
                    echo 'Firma actas, certificados e informes - Revisa directorios';
                } elseif ($rol_actual === 'Director de Escuela') {
                    echo 'Firma actas y certificados - Revisa informes y directorios';
                } elseif ($rol_actual === 'Educación DNBC') {
                    echo 'Revisa y aprueba todos los tipos de documentos';
                } elseif ($rol_actual === 'Dirección Nacional') {
                    echo 'Firma final de actas y certificados';
                }
                ?>
            </p>
            <?php if ($rol_actual === 'Director de Escuela'): ?>
            <p class="text-sm text-blue-600">
                <i class="fas fa-info-circle mr-1"></i>
                Escuela asignada: <?php echo htmlspecialchars($escuela_id ? (isset($documentos[0]) ? $documentos[0]['escuela_nombre'] : 'ID: '.$escuela_id) : 'Ninguna'); ?>
            </p>
            <?php endif; ?>
        </div>
        <div class="flex space-x-3">
            <?php if (in_array($rol_actual, ['Coordinador', 'Director de Escuela', 'Dirección Nacional'])): ?>
            <a href="configurar-firma.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
                <i class="fas fa-signature mr-2"></i>Configurar Firma
            </a>
            <?php endif; ?>
            
            <?php if ($rol_actual === 'Escuela'): ?>
            <button onclick="openGenerateModal()" class="btn-primary text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
                <i class="fas fa-plus mr-2"></i>Generar Documento
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
</div>
<?php endif; ?>

<!-- Documentos rechazados (solo para Escuela y Coordinador) -->
<?php if ($rol_actual === 'Escuela' && count($documentos_rechazados) > 0): ?>
<div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
    <h3 class="text-lg font-semibold text-red-800 mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Documentos Devueltos para Corrección (<?php echo count($documentos_rechazados); ?>)
    </h3>
    
    <div class="bg-red-100 border border-red-300 rounded-lg p-4 mb-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-red-600 mr-2 mt-1"></i>
            <div class="text-sm text-red-800">
                <p class="font-semibold mb-2">¿Cómo corregir un documento rechazado?</p>
                <ol class="list-decimal list-inside space-y-1">
                    <li>Revise el motivo del rechazo en la columna "Motivo"</li>
                    <li>Realice las correcciones necesarias al documento</li>
                    <li>Haga clic en "Corregir y Reenviar" para reiniciar el flujo</li>
                    <li>El documento volverá al estado inicial y deberá pasar por todas las firmas nuevamente</li>
                </ol>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-red-200">
            <thead class="bg-red-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">Documento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">Rechazado por</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">Motivo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-red-100">
                <?php foreach ($documentos_rechazados as $doc): ?>
                <tr class="hover:bg-red-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-excel text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($doc['codigo_unico']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Rechazado: <?php echo date('d/m/Y H:i', strtotime($doc['fecha_rechazo'])); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <?php echo ucfirst($doc['tipo']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($doc['curso_nombre']); ?>
                        <div class="text-xs text-gray-500">
                            <?php echo htmlspecialchars($doc['numero_registro']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($doc['rechazado_por']); ?>
                        <div class="text-xs text-gray-500">
                            <?php echo htmlspecialchars($doc['rol_rechazo']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                        <div class="truncate" title="<?php echo htmlspecialchars($doc['motivo_rechazo']); ?>">
                            <?php echo htmlspecialchars($doc['motivo_rechazo']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button 
                            onclick="restartDocumentFlow(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['codigo_unico']); ?>')" 
                            class="btn-orange" 
                            title="Corregir y reenviar documento"
                        >
                            <i class="fas fa-redo"></i>Corregir y Reenviar
                        </button>
                        
                        <a href="ver-documento.php?id=<?php echo $doc['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Ver documento">
                            <i class="fas fa-eye"></i>
                        </a>
                        
                        <a href="generar-pdf-simple.php?id=<?php echo $doc['id']; ?>" class="text-red-600 hover:text-red-900" title="Descargar">
                            <i class="fas fa-download"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Flujo de documentos actualizado -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
    <h3 class="text-lg font-semibold text-blue-800 mb-4">Flujo de Documentos por Tipo</h3>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Actas y Certificados -->
        <div class="bg-white rounded-lg p-4">
            <h4 class="font-semibold text-gray-800 mb-3">📋 Actas y 🏆 Certificados</h4>
            <div class="space-y-2 text-sm">
                <div class="flex items-center">
                    <span class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-2">1</span>
                    <span>Escuela genera</span>
                </div>
                <div class="flex items-center <?php echo $rol_actual === 'Coordinador' ? 'bg-yellow-100 p-2 rounded' : ''; ?>">
                    <span class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-2">2</span>
                    <span>Coordinador <strong>FIRMA</strong> <?php echo $rol_actual === 'Coordinador' ? '← USTED ESTÁ AQUÍ' : ''; ?></span>
                </div>
                <div class="flex items-center <?php echo $rol_actual === 'Director de Escuela' ? 'bg-yellow-100 p-2 rounded' : ''; ?>">
                    <span class="w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-xs mr-2">3</span>
                    <span>Director de Escuela <strong>FIRMA</strong> <?php echo $rol_actual === 'Director de Escuela' ? '← USTED ESTÁ AQUÍ' : ''; ?></span>
                </div>
                <div class="flex items-center <?php echo $rol_actual === 'Educación DNBC' ? 'bg-yellow-100 p-2 rounded' : ''; ?>">
                    <span class="w-6 h-6 bg-orange-500 text-white rounded-full flex items-center justify-center text-xs mr-2">4</span>
                    <span>Educación DNBC <strong>REVISA</strong> <?php echo $rol_actual === 'Educación DNBC' ? '← USTED ESTÁ AQUÍ' : ''; ?></span>
                </div>
                <div class="flex items-center <?php echo $rol_actual === 'Dirección Nacional' ? 'bg-yellow-100 p-2 rounded' : ''; ?>">
                    <span class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs mr-2">5</span>
                    <span>Dirección Nacional <strong>FIRMA</strong> <?php echo $rol_actual === 'Dirección Nacional' ? '← USTED ESTÁ AQUÍ' : ''; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Informes -->
        <div class="bg-white rounded-lg p-4">
            <h4 class="font-semibold text-gray-800 mb-3">📊 Informes</h4>
            <div class="space-y-2 text-sm">
                <div class="flex items-center">
                    <span class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-2">1</span>
                    <span>Escuela genera</span>
                </div>
                <div class="flex items-center <?php echo $rol_actual === 'Coordinador' ? 'bg-yellow-100 p-2 rounded' : ''; ?>">
                    <span class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-2">2</span>
                    <span>Coordinador <strong>FIRMA</strong> <?php echo $rol_actual === 'Coordinador' ? '← USTED ESTÁ AQUÍ' : ''; ?></span>
                </div>
                <div class="flex items-center <?php echo $rol_actual === 'Director de Escuela' ? 'bg-yellow-100 p-2 rounded' : ''; ?>">
                    <span class="w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-xs mr-2">3</span>
                    <span>Director de Escuela <strong>REVISA</strong> <?php echo $rol_actual === 'Director de Escuela' ? '← USTED ESTÁ AQUÍ' : ''; ?></span>
                </div>
                <div class="flex items-center <?php echo $rol_actual === 'Educación DNBC' ? 'bg-yellow-100 p-2 rounded' : ''; ?>">
                    <span class="w-6 h-6 bg-orange-500 text-white rounded-full flex items-center justify-center text-xs mr-2">4</span>
                    <span>Educación DNBC <strong>REVISA</strong> <?php echo $rol_actual === 'Educación DNBC' ? '← USTED ESTÁ AQUÍ' : ''; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Directorios -->
        <div class="bg-white rounded-lg p-4">
            <h4 class="font-semibold text-gray-800 mb-3">📁 Directorios</h4>
            <div class="space-y-2 text-sm">
                <div class="flex items-center">
                    <span class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-2">1</span>
                    <span>Escuela genera</span>
                </div>
                <div class="flex items-center <?php echo $rol_actual === 'Coordinador' ? 'bg-yellow-100 p-2 rounded' : ''; ?>">
                    <span class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs mr-2">2</span>
                    <span>Coordinador <strong>REVISA</strong> <?php echo $rol_actual === 'Coordinador' ? '← USTED ESTÁ AQUÍ' : ''; ?></span>
                </div>
                <div class="flex items-center <?php echo $rol_actual === 'Director de Escuela' ? 'bg-yellow-100 p-2 rounded' : ''; ?>">
                    <span class="w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-xs mr-2">3</span>
                    <span>Director de Escuela <strong>REVISA</strong> <?php echo $rol_actual === 'Director de Escuela' ? '← USTED ESTÁ AQUÍ' : ''; ?></span>
                </div>
                <div class="flex items-center <?php echo $rol_actual === 'Educación DNBC' ? 'bg-yellow-100 p-2 rounded' : ''; ?>">
                    <span class="w-6 h-6 bg-orange-500 text-white rounded-full flex items-center justify-center text-xs mr-2">4</span>
                    <span>Educación DNBC <strong>REVISA</strong> <?php echo $rol_actual === 'Educación DNBC' ? '← USTED ESTÁ AQUÍ' : ''; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Información específica por rol -->
<?php if ($rol_actual === 'Coordinador'): ?>
<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
    <h3 class="text-lg font-semibold text-green-800 mb-2">Sus responsabilidades como Coordinador:</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <h4 class="font-semibold text-green-700">FIRMA:</h4>
            <ul class="list-disc list-inside text-green-600">
                <li>Actas de finalización</li>
                <li>Certificados de participación</li>
                <li>Informes de curso</li>
            </ul>
        </div>
        <div>
            <h4 class="font-semibold text-green-700">REVISA:</h4>
            <ul class="list-disc list-inside text-green-600">
                <li>Directorios de participantes</li>
            </ul>
        </div>
    </div>
    <div class="mt-3 bg-green-100 p-3 rounded-lg">
        <p class="text-sm text-green-800 font-medium">
            <i class="fas fa-info-circle mr-1"></i>
            Puede aprobar documentos o devolverlos para corrección si encuentra errores.
        </p>
    </div>
</div>
<?php elseif ($rol_actual === 'Director de Escuela'): ?>
<div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
    <h3 class="text-lg font-semibold text-purple-800 mb-2">Sus responsabilidades como Director de Escuela:</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <h4 class="font-semibold text-purple-700">FIRMA:</h4>
            <ul class="list-disc list-inside text-purple-600">
                <li>Actas (después del Coordinador)</li>
                <li>Certificados (después del Coordinador)</li>
            </ul>
        </div>
        <div>
            <h4 class="font-semibold text-purple-700">REVISA:</h4>
            <ul class="list-disc list-inside text-purple-600">
                <li>Informes (después del Coordinador)</li>
                <li>Directorios (después del Coordinador)</li>
            </ul>
        </div>
    </div>
    <div class="mt-3 bg-purple-100 p-3 rounded-lg">
        <p class="text-sm text-purple-800 font-medium">
            <i class="fas fa-info-circle mr-1"></i>
            Puede aprobar documentos o devolverlos para corrección si encuentra errores.
        </p>
    </div>
</div>
<?php elseif ($rol_actual === 'Educación DNBC'): ?>
<div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
    <h3 class="text-lg font-semibold text-orange-800 mb-2">Sus responsabilidades como Educación DNBC:</h3>
    <div class="text-sm">
        <h4 class="font-semibold text-orange-700">REVISA Y APRUEBA:</h4>
        <ul class="list-disc list-inside text-orange-600">
            <li>Actas (después del Director de Escuela)</li>
            <li>Certificados (después del Director de Escuela)</li>
            <li>Informes (después del Director de Escuela)</li>
            <li>Directorios (después del Director de Escuela)</li>
        </ul>
        <p class="mt-2 text-orange-700 font-medium">
            <i class="fas fa-info-circle mr-1"></i>
            Puede aprobar documentos o devolverlos para corrección si encuentra errores.
        </p>
    </div>
</div>
<?php elseif ($rol_actual === 'Dirección Nacional'): ?>
<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
    <h3 class="text-lg font-semibold text-red-800 mb-2">Sus responsabilidades como Dirección Nacional:</h3>
    <div class="text-sm">
        <h4 class="font-semibold text-red-700">FIRMA FINAL:</h4>
        <ul class="list-disc list-inside text-red-600">
            <li>Actas (después de Educación DNBC)</li>
            <li>Certificados (después de Educación DNBC)</li>
        </ul>
        <p class="mt-2 text-red-700 font-medium">Nota: Su firma es la autorización final para actas y certificados.</p>
    </div>
</div>
<?php endif; ?>

<!-- Tabla de documentos -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">
                <?php 
                if ($rol_actual === 'Escuela') {
                    echo 'Mis Documentos';
                } elseif ($rol_actual === 'Director de Escuela') {
                    echo 'Documentos de la Escuela';
                } else {
                    echo 'Documentos Pendientes';
                }
                ?>
                <span class="text-sm font-normal text-gray-500">(<?php echo count($documentos); ?> documentos)</span>
            </h3>
            <div class="flex items-center space-x-2">
                <select id="filterDocumentType" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los tipos</option>
                    <option value="acta">Actas</option>
                    <option value="informe">Informes</option>
                    <option value="certificado">Certificados</option>
                    <option value="directorio">Directorios</option>
                </select>
                <select id="filterDocumentStatus" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los estados</option>
                    <option value="generado">Generado</option>
                    <option value="firmado_coordinador">Firmado por Coordinador</option>
                    <option value="revisado_coordinador">Revisado por Coordinador</option>
                    <option value="firmado_director_escuela">Firmado por Director</option>
                    <option value="revisado_director_escuela">Revisado por Director</option>
                    <option value="revisado_educacion_dnbc">Revisado por Educación</option>
                    <option value="firmado_director_nacional">Firmado por Dir. Nacional</option>
                    <option value="completado">Completado</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado Detallado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($documentos) > 0): ?>
                <?php foreach ($documentos as $documento): ?>
                <?php 
                $puede_procesar = $workflow->canProcessDocument($documento['id'], $rol_actual);
                $action_type = $workflow->getActionType($documento['tipo'], $rol_actual);

                // Definir el flujo esperado según el tipo de documento
                $flujos_esperados = [
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

                // Obtener información de quién ya procesó (excluyendo rechazos)
                $query_progreso = "SELECT fd.accion, r.nombre as rol_nombre
                   FROM firmas_documentos fd
                   JOIN usuarios u ON fd.usuario_id = u.id
                   JOIN roles r ON u.rol_id = r.id
                   WHERE fd.documento_id = ? AND fd.es_rechazo = 0";
                $stmt_progreso = $db->prepare($query_progreso);
                $stmt_progreso->execute([$documento['id']]);
                $progreso = $stmt_progreso->fetchAll();

                // Crear array de roles que ya procesaron
                $roles_procesados = [];
                foreach ($progreso as $p) {
                    $roles_procesados[] = $p['rol_nombre'];
                }

                $flujo_actual = $flujos_esperados[$documento['tipo']] ?? [];
                
                // Determinar si el documento está completado (todos los roles han procesado)
                $esta_completado = true;
                foreach ($flujo_actual as $rol => $tipo_accion) {
                    if (!in_array($rol, $roles_procesados)) {
                        $esta_completado = false;
                        break;
                    }
                }
                ?>
                <tr class="hover:bg-gray-50 transition-colors <?php echo $puede_procesar ? 'bg-yellow-50' : ''; ?>" data-tipo="<?php echo $documento['tipo']; ?>" data-estado="<?php echo $documento['estado']; ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <?php 
                            $icon_class = '';
                            switch ($documento['tipo']) {
                                case 'acta':
                                    $icon_class = 'fa-file-contract text-purple-600';
                                    break;
                                case 'informe':
                                    $icon_class = 'fa-file-alt text-blue-600';
                                    break;
                                case 'certificado':
                                    $icon_class = 'fa-certificate text-green-600';
                                    break;
                                case 'directorio':
                                    $icon_class = 'fa-folder text-yellow-600';
                                    break;
                            }
                            ?>
                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                <i class="fas <?php echo $icon_class; ?> text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($documento['codigo_unico']); ?>
                                    <?php if ($puede_procesar): ?>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                        ¡Pendiente!
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    ID: <?php echo $documento['id']; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            <?php 
                            switch ($documento['tipo']) {
                                case 'acta':
                                    echo 'bg-purple-100 text-purple-800';
                                    break;
                                case 'informe':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'certificado':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'directorio':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                            }
                            ?>">
                            <?php echo ucfirst($documento['tipo']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($documento['curso_nombre']); ?>
                        <div class="text-xs text-gray-500">
                            <?php echo htmlspecialchars($documento['numero_registro']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="space-y-2">
                            <?php 
                            // Mostrar estado según si está completado o no
                            if ($esta_completado) {
                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">';
                                echo 'Completado';
                                echo '</span>';
                            } else {
                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">';
                                echo 'En Proceso';
                                echo '</span>';
                            }
                            ?>

                            <!-- Mostrar progreso por roles -->
                            <div class="space-y-1">
                                <?php foreach ($flujo_actual as $rol => $tipo_accion): ?>
                                <?php 
                                $ya_proceso = in_array($rol, $roles_procesados);
                                $es_siguiente = !$ya_proceso && $puede_procesar && $rol === $rol_actual;
                                
                                if ($ya_proceso) {
                                    $color_class = 'text-green-600';
                                    $icon = 'fa-check-circle';
                                    $texto_accion = $tipo_accion === 'firma' ? 'Firmado' : 'Revisado';
                                } elseif ($es_siguiente) {
                                    $color_class = 'text-yellow-600 font-semibold';
                                    $icon = 'fa-clock';
                                    $texto_accion = $tipo_accion === 'firma' ? 'Pendiente Firma' : 'Pendiente Revisión';
                                } else {
                                    $color_class = 'text-red-500';
                                    $icon = 'fa-times-circle';
                                    $texto_accion = $tipo_accion === 'firma' ? 'Sin Firmar' : 'Sin Revisar';
                                }
                                ?>
                                <div class="text-xs <?php echo $color_class; ?>">
                                    <i class="fas <?php echo $icon; ?> mr-1"></i>
                                    <?php echo $rol; ?>: <?php echo $texto_accion; ?>
                                    <?php if ($es_siguiente): ?>
                                    <span class="ml-1 text-yellow-800">(Su turno)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('d/m/Y H:i', strtotime($documento['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <?php if ($puede_procesar): ?>
                        <?php 
                        $button_text = $action_type === 'firma' ? 'Firmar' : 'Revisar';
                        $button_icon = $action_type === 'firma' ? 'signature' : 'eye';
                        $button_color = $action_type === 'firma' ? 'green' : 'blue';
                        ?>
                        <button 
                            onclick="processDocument(<?php echo $documento['id']; ?>, '<?php echo $documento['tipo']; ?>', '<?php echo $action_type; ?>')" 
                            class="bg-<?php echo $button_color; ?>-600 hover:bg-<?php echo $button_color; ?>-700 text-white px-3 py-1 rounded text-sm mr-2" 
                            title="<?php echo $button_text; ?> documento"
                        >
                            <i class="fas fa-<?php echo $button_icon; ?>"></i> <?php echo $button_text; ?>
                        </button>
                        <?php endif; ?>
                        
                        <a href="ver-documento.php?id=<?php echo $documento['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Ver documento">
                            <i class="fas fa-eye"></i>
                        </a>
                        
                        <button onclick="showDocumentHistory(<?php echo $documento['id']; ?>)" class="text-purple-600 hover:text-purple-900 mr-3" title="Ver historial">
                            <i class="fas fa-history"></i>
                        </button>
                        
                        <a href="generar-pdf-simple.php?id=<?php echo $documento['id']; ?>" class="text-red-600 hover:text-red-900" title="Descargar">
                            <i class="fas fa-download"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                        <i class="fas fa-folder-open text-gray-400 text-4xl mb-3"></i>
                        <p>No hay documentos disponibles</p>
                        <?php if ($rol_actual === 'Director de Escuela' && !$escuela_id): ?>
                        <p class="text-red-500 mt-2">No tiene una escuela asignada. Contacte al administrador.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para generar documento (solo para Escuela) -->
<?php if ($rol_actual === 'Escuela'): ?>
<div id="generateDocumentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Generar Nuevo Documento</h3>
            <button onclick="closeGenerateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="generate">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Documento *</label>
                <select name="tipo" id="tipoDocumento" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccionar tipo</option>
                    <option value="acta">Acta</option>
                    <option value="informe">Informe</option>
                    <option value="certificado">Certificado</option>
                    <option value="directorio">Directorio</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Curso *</label>
                <select name="curso_id" id="cursoDocumento" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccionar curso</option>
                    <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>">
                        <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['numero_registro']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="participanteSection" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Participante</label>
                <select name="participante_id" id="participanteDocumento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccionar participante (opcional)</option>
                </select>
                <p class="text-sm text-gray-500 mt-1">Para certificados individuales, seleccione un participante específico</p>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeGenerateModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary px-6 py-2 text-white rounded-lg">
                    <i class="fas fa-file-plus mr-2"></i>Generar Documento
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal para procesar documento -->
<?php if ($rol_actual !== 'Escuela'): ?>
<div id="processDocumentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900" id="processModalTitle">Procesar Documento</h3>
            <button onclick="closeProcessModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="process">
            <input type="hidden" name="documento_id" id="processDocumentoId">
            <input type="hidden" name="accion_proceso" id="accionProceso" value="aprobar">
            
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    <p class="text-sm text-blue-800" id="processMessage">
                        Está a punto de procesar este documento
                    </p>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                <textarea name="observaciones" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Observaciones sobre el procesamiento (opcional)"></textarea>
            </div>
            
            <?php if (in_array($rol_actual, ['Educación DNBC', 'Dirección Nacional', 'Coordinador', 'Director de Escuela'])): ?>
            <div class="flex items-center space-x-4 pt-2">
                <div class="flex items-center">
                    <input type="radio" id="aprobar" name="accion_radio" value="aprobar" checked class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                    <label for="aprobar" class="ml-2 text-sm font-medium text-gray-700">Aprobar y continuar</label>
                </div>
                <div class="flex items-center">
                    <input type="radio" id="rechazar" name="accion_radio" value="rechazar" class="w-4 h-4 text-red-600 border-gray-300 focus:ring-red-500">
                    <label for="rechazar" class="ml-2 text-sm font-medium text-gray-700">Devolver para corrección</label>
                </div>
            </div>

            <div id="rechazoInfo" class="bg-red-50 p-4 rounded-lg hidden">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    <p class="text-sm text-red-800">
                        Al devolver el documento, se notificará al generador para que realice las correcciones necesarias.
                        <strong>Por favor, especifique claramente los motivos del rechazo en las observaciones.</strong>
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeProcessModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 px-6 py-2 text-white rounded-lg" id="processButton">
                    <i class="fas fa-signature mr-2"></i>Procesar Documento
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal para confirmar reinicio de flujo -->
<div id="restartFlowModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Corregir y Reenviar Documento</h3>
            <button onclick="closeRestartFlowModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="restart_flow">
            <input type="hidden" name="documento_id" id="restartDocumentoId">
            
            <div class="bg-orange-50 p-4 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-orange-600 mr-2 mt-1"></i>
                    <div class="text-sm text-orange-800">
                        <p class="font-semibold mb-2">¿Está seguro de que desea corregir y reenviar este documento?</p>
                        <p class="mb-2"><strong>Documento:</strong> <span id="restartDocumentoCodigo"></span></p>
                        <p class="mb-2">Esta acción:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Eliminará todas las firmas y revisiones anteriores</li>
                            <li>Reiniciará el documento al estado "Generado"</li>
                            <li>El documento deberá pasar nuevamente por todo el flujo de aprobación</li>
                            <li>Se registrará como una corrección en el historial</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeRestartFlowModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="btn-orange">
                    <i class="fas fa-redo"></i>Sí, Corregir y Reenviar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para historial de documento -->
<div id="historyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Historial del Documento</h3>
            <button onclick="closeHistoryModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="historyContent">
            <!-- El contenido se cargará dinámicamente -->
        </div>
    </div>
</div>

<script>
<?php if ($rol_actual === 'Escuela'): ?>
function openGenerateModal() {
    document.getElementById('generateDocumentModal').classList.remove('hidden');
}

function closeGenerateModal() {
    document.getElementById('generateDocumentModal').classList.add('hidden');
}

// Mostrar/ocultar sección de participante según el tipo de documento
document.getElementById('tipoDocumento').addEventListener('change', function() {
    const participanteSection = document.getElementById('participanteSection');
    if (this.value === 'certificado') {
        participanteSection.classList.remove('hidden');
    } else {
        participanteSection.classList.add('hidden');
        document.getElementById('participanteDocumento').value = '';
    }
});

// Cerrar modal al hacer click fuera
document.getElementById('generateDocumentModal').addEventListener('click', function(e) {
    if (e.target === this) closeGenerateModal();
});
<?php endif; ?>

<?php if ($rol_actual !== 'Escuela'): ?>
function closeProcessModal() {
    document.getElementById('processDocumentModal').classList.add('hidden');
}

function processDocument(documentoId, tipoDocumento, actionType) {
    document.getElementById('processDocumentoId').value = documentoId;
    document.getElementById('accionProceso').value = 'aprobar'; // Valor por defecto
    
    // Actualizar el texto según el tipo de acción
    const modalTitle = document.getElementById('processModalTitle');
    const processMessage = document.getElementById('processMessage');
    const processButton = document.getElementById('processButton');
    
    if (actionType === 'firma') {
        modalTitle.textContent = 'Firmar Documento';
        processMessage.innerHTML = 'Está a punto de <strong>firmar</strong> este ' + tipoDocumento + ' como <strong><?php echo $rol_actual; ?></strong>';
        processButton.innerHTML = '<i class="fas fa-signature mr-2"></i>Firmar Documento';
        processButton.className = 'bg-green-600 hover:bg-green-700 px-6 py-2 text-white rounded-lg';
    } else {
        modalTitle.textContent = 'Revisar Documento';
        processMessage.innerHTML = 'Está a punto de <strong>revisar</strong> este ' + tipoDocumento + ' como <strong><?php echo $rol_actual; ?></strong>';
        processButton.innerHTML = '<i class="fas fa-eye mr-2"></i>Revisar Documento';
        processButton.className = 'bg-blue-600 hover:bg-blue-700 px-6 py-2 text-white rounded-lg';
    }
    
    document.getElementById('processDocumentModal').classList.remove('hidden');
    
    <!-- Modificar la sección de JavaScript para manejar los radio buttons para todos los roles que pueden rechazar -->

<?php if (in_array($rol_actual, ['Educación DNBC', 'Dirección Nacional', 'Coordinador', 'Director de Escuela'])): ?>
// Configurar los radio buttons
const radioAprobar = document.getElementById('aprobar');
const radioRechazar = document.getElementById('rechazar');
const rechazoInfo = document.getElementById('rechazoInfo');

radioAprobar.checked = true;
rechazoInfo.classList.add('hidden');

// Añadir event listeners para los radio buttons
radioAprobar.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('accionProceso').value = 'aprobar';
        rechazoInfo.classList.add('hidden');
        if (actionType === 'firma') {
            processButton.innerHTML = '<i class="fas fa-signature mr-2"></i>Firmar Documento';
            processButton.className = 'bg-green-600 hover:bg-green-700 px-6 py-2 text-white rounded-lg';
        } else {
            processButton.innerHTML = '<i class="fas fa-eye mr-2"></i>Revisar Documento';
            processButton.className = 'bg-blue-600 hover:bg-blue-700 px-6 py-2 text-white rounded-lg';
        }
    }
});

radioRechazar.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('accionProceso').value = 'rechazar';
        rechazoInfo.classList.remove('hidden');
        processButton.innerHTML = '<i class="fas fa-undo mr-2"></i>Devolver para Corrección';
        processButton.className = 'bg-red-600 hover:bg-red-700 px-6 py-2 text-white rounded-lg';
    }
});
<?php endif; ?>
}

// Cerrar modal al hacer click fuera
document.getElementById('processDocumentModal').addEventListener('click', function(e) {
    if (e.target === this) closeProcessModal();
});
<?php endif; ?>

// Funciones para reiniciar flujo de documento
function restartDocumentFlow(documentoId, documentoCodigo) {
    document.getElementById('restartDocumentoId').value = documentoId;
    document.getElementById('restartDocumentoCodigo').textContent = documentoCodigo;
    document.getElementById('restartFlowModal').classList.remove('hidden');
}

function closeRestartFlowModal() {
    document.getElementById('restartFlowModal').classList.add('hidden');
}

// Cerrar modal al hacer click fuera
document.getElementById('restartFlowModal').addEventListener('click', function(e) {
    if (e.target === this) closeRestartFlowModal();
});

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}

function showDocumentHistory(documentoId) {
    // Aquí podrías hacer una petición AJAX para cargar el historial
    document.getElementById('historyContent').innerHTML = '<p class="text-center py-4">Cargando historial...</p>';
    document.getElementById('historyModal').classList.remove('hidden');
    
    // Simulación de carga de historial
    setTimeout(() => {
        document.getElementById('historyContent').innerHTML = `
            <div class="space-y-4">
                <div class="border-l-4 border-blue-500 pl-4 py-2">
                    <div class="font-semibold">Documento Generado</div>
                    <div class="text-sm text-gray-600">Por: Escuela • ${new Date().toLocaleDateString()}</div>
                </div>
                <div class="text-center text-gray-500 py-4">
                    <i class="fas fa-clock text-2xl mb-2"></i>
                    <p>Pendiente de procesamiento</p>
                </div>
            </div>
        `;
    }, 1000);
}

// Cerrar modal al hacer click fuera
document.getElementById('historyModal').addEventListener('click', function(e) {
    if (e.target === this) closeHistoryModal();
});

// Filtros de documentos
document.getElementById('filterDocumentType').addEventListener('change', function() {
    filterDocuments();
});

document.getElementById('filterDocumentStatus').addEventListener('change', function() {
    filterDocuments();
});

function filterDocuments() {
    const typeFilter = document.getElementById('filterDocumentType').value;
    const statusFilter = document.getElementById('filterDocumentStatus').value;
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const tipo = row.getAttribute('data-tipo');
        const estado = row.getAttribute('data-estado');
        
        let showRow = true;
        
        if (typeFilter && tipo !== typeFilter) {
            showRow = false;
        }
        
        if (statusFilter && estado !== statusFilter) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

// Cargar participantes cuando se selecciona un curso
document.getElementById('cursoDocumento').addEventListener('change', function() {
    const cursoId = this.value;
    const participanteSelect = document.getElementById('participanteDocumento');
    
    // Limpiar opciones existentes
    participanteSelect.innerHTML = '<option value="">Cargando participantes...</option>';
    
    if (cursoId) {
        // Hacer petición AJAX para obtener participantes
        fetch(`get_participants_by_course.php?curso_id=${cursoId}`)
            .then(response => response.json())
            .then(data => {
                participanteSelect.innerHTML = '<option value="">Seleccionar participante (opcional)</option>';
                
                if (data.success && data.participantes.length > 0) {
                    data.participantes.forEach(participante => {
                        const option = document.createElement('option');
                        option.value = participante.id;
                        option.textContent = `${participante.nombres} ${participante.apellidos} - ${participante.cedula}`;
                        participanteSelect.appendChild(option);
                    });
                } else {
                    participanteSelect.innerHTML = '<option value="">No hay participantes matriculados en este curso</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                participanteSelect.innerHTML = '<option value="">Error al cargar participantes</option>';
            });
    } else {
        participanteSelect.innerHTML = '<option value="">Seleccionar participante (opcional)</option>';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
