<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Solo administradores y directores pueden configurar
$auth->requireRole(['Administrador General', 'Director de Escuela', 'Escuela']);

$message = '';
$error = '';

// Obtener ID de escuela según el rol
$escuela_id = null;

if ($auth->hasRole('Director de Escuela')) {
    // Director solo puede configurar su escuela
    $query_escuela = "SELECT e.id FROM escuelas e 
                     JOIN usuarios u ON e.director_id = u.id 
                     WHERE u.id = ?";
    $stmt = $db->prepare($query_escuela);
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $escuela_id = $result ? $result['id'] : null;
} elseif ($auth->hasRole('Escuela')) {
    // Usuario de escuela puede configurar su escuela asignada
    $query_escuela = "SELECT escuela_id FROM usuarios WHERE id = ? AND escuela_id IS NOT NULL";
    $stmt = $db->prepare($query_escuela);
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $escuela_id = $result ? $result['escuela_id'] : null;
    
    // Si no tiene escuela_id asignada directamente, buscar si hay una escuela que coincida con su email o nombre
    if (!$escuela_id) {
        $query_escuela_match = "SELECT id FROM escuelas WHERE email = (SELECT email FROM usuarios WHERE id = ?) OR nombre LIKE CONCAT('%', (SELECT nombres FROM usuarios WHERE id = ?), '%')";
        $stmt_match = $db->prepare($query_escuela_match);
        $stmt_match->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        $result_match = $stmt_match->fetch();
        $escuela_id = $result_match ? $result_match['id'] : null;
    }
} else {
    // Administrador puede seleccionar escuela
    $escuela_id = $_GET['escuela_id'] ?? $_POST['escuela_id'] ?? null;
}

// Si aún no hay escuela_id y el usuario no es administrador, mostrar error específico
if (!$escuela_id && !$auth->hasRole('Administrador General')) {
    $error = 'Tu usuario no tiene una escuela asignada. Contacta al administrador para asignar tu escuela.';
}

if (!$escuela_id && $auth->hasRole('Administrador General')) {
    $error = 'No se ha seleccionado una escuela válida';
}

// Procesar formulario
if ($_POST && $escuela_id) {
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $nombre_estacion = trim($_POST['nombre_estacion'] ?? '');
    $codigo_formato = trim($_POST['codigo_formato'] ?? '');
    $version_formato = trim($_POST['version_formato'] ?? '1');
    $fecha_vigencia = $_POST['fecha_vigencia'] ?? '';
    $pie_pagina = trim($_POST['pie_pagina'] ?? '');
    $slogan = trim($_POST['slogan'] ?? '');
    $director = trim($_POST['director'] ?? '');
    $coordinador = trim($_POST['coordinador'] ?? '');
    $lema = trim($_POST['lema'] ?? '');
    $mision = trim($_POST['mision'] ?? '');
    $vision = trim($_POST['vision'] ?? '');
    
    if ($nombre_completo && $codigo_formato) {
        try {
            $query_update = "UPDATE escuelas SET 
                            nombre_completo = ?, 
                            nombre_estacion = ?, 
                            codigo_formato = ?, 
                            version_formato = ?, 
                            fecha_vigencia = ?, 
                            pie_pagina = ?, 
                            slogan = ?,
                            director = ?,
                            coordinador = ?,
                            lema = ?,
                            mision = ?,
                            vision = ?
                            WHERE id = ?";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->execute([
                $nombre_completo, 
                $nombre_estacion, 
                $codigo_formato, 
                $version_formato, 
                $fecha_vigencia, 
                $pie_pagina, 
                $slogan,
                $director,
                $coordinador,
                $lema,
                $mision,
                $vision,
                $escuela_id
            ]);
            
            $message = 'Configuración actualizada exitosamente';
        } catch (Exception $e) {
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    } else {
        $error = 'Los campos obligatorios deben ser completados';
    }
}

// Obtener información actual de la escuela
$escuela_info = null;
if ($escuela_id) {
    $query_info = "SELECT * FROM escuelas WHERE id = ?";
    $stmt_info = $db->prepare($query_info);
    $stmt_info->execute([$escuela_id]);
    $escuela_info = $stmt_info->fetch();
}

// Obtener lista de escuelas para administradores
$escuelas = [];
if ($auth->hasRole('Administrador General')) {
    $query_escuelas = "SELECT id, nombre FROM escuelas WHERE activa = 1 ORDER BY nombre";
    $stmt_escuelas = $db->prepare($query_escuelas);
    $stmt_escuelas->execute();
    $escuelas = $stmt_escuelas->fetchAll();
}

$page_title = 'Configuración Institucional';
include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-cog text-blue-600 mr-3"></i>Configuración Institucional
            </h1>
            <p class="text-gray-600">Configura la información que aparecerá en los documentos oficiales</p>
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

<!-- Selector de escuela para administradores -->
<?php if ($auth->hasRole('Administrador General') && !empty($escuelas)): ?>
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Seleccionar Escuela</h3>
    <form method="GET" class="flex items-center space-x-4">
        <select name="escuela_id" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Seleccionar escuela...</option>
            <?php foreach ($escuelas as $escuela): ?>
            <option value="<?php echo $escuela['id']; ?>" <?php echo ($escuela_id == $escuela['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($escuela['nombre']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary px-6 py-2 text-white rounded-lg">
            <i class="fas fa-search mr-2"></i>Seleccionar
        </button>
    </form>
</div>
<?php endif; ?>

<?php if ($escuela_info): ?>
<!-- Formulario de configuración -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Formulario -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">
            <i class="fas fa-edit text-blue-600 mr-2"></i>Información Institucional
        </h3>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="escuela_id" value="<?php echo $escuela_id; ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo de la Institución *</label>
                <input type="text" name="nombre_completo" required 
                       value="<?php echo htmlspecialchars($escuela_info['nombre_completo'] ?? ''); ?>"
                       placeholder="Ej: BOMBEROS VOLUNTARIOS LOS SANTOS"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Estación</label>
                <input type="text" name="nombre_estacion" 
                       value="<?php echo htmlspecialchars($escuela_info['nombre_estacion'] ?? ''); ?>"
                       placeholder="Ej: ESTACION DE BOMBEROS CT. JAIME DIAZ CAMARGO"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Director</label>
                    <input type="text" name="director" 
                           value="<?php echo htmlspecialchars($escuela_info['director'] ?? ''); ?>"
                           placeholder="Nombre del Director"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Coordinador</label>
                    <input type="text" name="coordinador" 
                           value="<?php echo htmlspecialchars($escuela_info['coordinador'] ?? ''); ?>"
                           placeholder="Nombre del Coordinador"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Código de Formato *</label>
                    <input type="text" name="codigo_formato" required 
                           value="<?php echo htmlspecialchars($escuela_info['codigo_formato'] ?? ''); ?>"
                           placeholder="Ej: ESIBOC-FO-03"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Versión</label>
                    <input type="text" name="version_formato" 
                           value="<?php echo htmlspecialchars($escuela_info['version_formato'] ?? '1'); ?>"
                           placeholder="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Vigencia</label>
                <input type="date" name="fecha_vigencia" 
                       value="<?php echo $escuela_info['fecha_vigencia'] ?? ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Lema Institucional</label>
                <input type="text" name="lema" 
                       value="<?php echo htmlspecialchars($escuela_info['lema'] ?? ''); ?>"
                       placeholder="Lema de la institución"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Slogan del Documento</label>
                <input type="text" name="slogan" 
                       value="<?php echo htmlspecialchars($escuela_info['slogan'] ?? ''); ?>"
                       placeholder="Ej: FORMATO DIRECTORIO FINALIZACIÓN DE CURSO"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Misión</label>
                <textarea name="mision" rows="3" 
                          placeholder="Misión de la institución"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($escuela_info['mision'] ?? ''); ?></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Visión</label>
                <textarea name="vision" rows="3" 
                          placeholder="Visión de la institución"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($escuela_info['vision'] ?? ''); ?></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pie de Página</label>
                <textarea name="pie_pagina" rows="3" 
                          placeholder="Información que aparecerá al final del documento"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($escuela_info['pie_pagina'] ?? ''); ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Usa \n para saltos de línea</p>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="submit" class="btn-primary px-6 py-2 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Guardar Configuración
                </button>
            </div>
        </form>
    </div>
    
    <!-- Vista previa -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">
            <i class="fas fa-eye text-green-600 mr-2"></i>Vista Previa del Encabezado
        </h3>
        
        <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
            <div class="text-center space-y-1">
                <div class="font-bold text-sm">
                    <?php echo htmlspecialchars($escuela_info['nombre_completo'] ?? 'NOMBRE DE LA INSTITUCIÓN'); ?>
                </div>
                <?php if ($escuela_info['nombre_estacion']): ?>
                <div class="text-sm">
                    <?php echo htmlspecialchars($escuela_info['nombre_estacion']); ?>
                </div>
                <?php endif; ?>
                <div class="font-bold text-sm">
                    <?php echo htmlspecialchars($escuela_info['nombre'] ?? 'NOMBRE DE LA ESCUELA'); ?>
                </div>
                <?php if ($escuela_info['lema']): ?>
                <div class="text-xs italic">
                    "<?php echo htmlspecialchars($escuela_info['lema']); ?>"
                </div>
                <?php endif; ?>
            </div>
            
            <div class="flex justify-between items-start mt-4 text-xs">
                <div>
                    <div><strong>Código:</strong> <?php echo htmlspecialchars($escuela_info['codigo_formato'] ?? 'CÓDIGO-FO-XX'); ?></div>
                    <div><strong><?php echo htmlspecialchars($escuela_info['slogan'] ?? 'SLOGAN DEL DOCUMENTO'); ?></strong></div>
                </div>
                <div class="text-right">
                    <div><strong>Versión:</strong> <?php echo htmlspecialchars($escuela_info['version_formato'] ?? '1'); ?></div>
                    <div><strong>Vigente Desde:</strong></div>
                    <div><?php echo $escuela_info['fecha_vigencia'] ? date('d/m/Y', strtotime($escuela_info['fecha_vigencia'])) : 'DD/MM/AAAA'; ?></div>
                </div>
            </div>
        </div>
        
        <?php if ($escuela_info['pie_pagina']): ?>
        <div class="mt-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Pie de Página:</h4>
            <div class="border border-gray-300 rounded-lg p-3 bg-gray-50 text-xs text-center">
                <?php echo nl2br(htmlspecialchars($escuela_info['pie_pagina'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-6">
            <a href="ver-documento.php?preview=directorio&escuela_id=<?php echo $escuela_id; ?>" 
               target="_blank" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-external-link-alt mr-2"></i>Ver Documento Completo
            </a>
        </div>
    </div>
</div>

<?php else: ?>
<div class="bg-white rounded-xl shadow-lg p-12 text-center">
    <i class="fas fa-school text-6xl text-gray-300 mb-4"></i>
    <h3 class="text-xl font-semibold text-gray-600 mb-2">Selecciona una Escuela</h3>
    <p class="text-gray-500">Para configurar la información institucional, primero selecciona una escuela</p>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
