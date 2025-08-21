<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Solo Administrador General y Escuela pueden configurar certificados
$auth->requireRole(['Administrador General', 'Escuela']);

$page_title = 'Configuración de Certificados';
$message = '';
$error = '';

// Procesar formulario
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'edit') {
        $id = $_POST['id'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        // Textos del certificado
        $texto_certifica_que = trim($_POST['texto_certifica_que'] ?? 'Certifica que:');
        $texto_identificado_con = trim($_POST['texto_identificado_con'] ?? 'Identificado con C.C. No.');
        $texto_asistio_aprobo = trim($_POST['texto_asistio_aprobo'] ?? 'Asistió y aprobó los requisitos del Curso:');
        $texto_curso_autorizado = trim($_POST['texto_curso_autorizado'] ?? 'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia');
        $texto_bajo_acta = trim($_POST['texto_bajo_acta'] ?? 'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}');
        $texto_duracion = trim($_POST['texto_duracion'] ?? 'Con una duración de: {horas} horas académicas');
        $texto_realizado_en = trim($_POST['texto_realizado_en'] ?? 'Realizado en {lugar_realizacion} del {fecha_inicio} al {fecha_fin}');
        $texto_constancia = trim($_POST['texto_constancia'] ?? 'En constancia de lo anterior, se firma a los {fecha_firma}');
        
        // Configuración de numeración
        $mostrar_consecutivo = isset($_POST['mostrar_consecutivo']) ? 1 : 0;
        $formato_consecutivo = trim($_POST['formato_consecutivo'] ?? '{numero_registro}-{orden_alfabetico}');
        $numero_registro_base = trim($_POST['numero_registro_base'] ?? 'DNBC-2024');
        
        // Configuración de actas
        $mostrar_numero_acta = isset($_POST['mostrar_numero_acta']) ? 1 : 0;
        $formato_numero_acta = trim($_POST['formato_numero_acta'] ?? 'ACTA-{escuela_id}-{consecutivo}');
        
        // Configuración de firmas
        $mostrar_firma_director_nacional = isset($_POST['mostrar_firma_director_nacional']) ? 1 : 0;
        $mostrar_firma_director_escuela = isset($_POST['mostrar_firma_director_escuela']) ? 1 : 0;
        $mostrar_firma_coordinador = isset($_POST['mostrar_firma_coordinador']) ? 1 : 0;
        
        // Configuración de contenido programático
        $mostrar_contenido_programatico = isset($_POST['mostrar_contenido_programatico']) ? 1 : 0;
        $columnas_contenido = intval($_POST['columnas_contenido'] ?? 2);
        
        if ($nombre) {
            try {
                if ($action === 'create') {
                    $query = "INSERT INTO configuracion_certificados (
                        nombre, descripcion, 
                        texto_certifica_que, texto_identificado_con, texto_asistio_aprobo,
                        texto_curso_autorizado, texto_bajo_acta, texto_duracion,
                        texto_realizado_en, texto_constancia,
                        mostrar_consecutivo, formato_consecutivo, numero_registro_base,
                        mostrar_numero_acta, formato_numero_acta,
                        mostrar_firma_director_nacional, mostrar_firma_director_escuela, mostrar_firma_coordinador,
                        mostrar_contenido_programatico, columnas_contenido
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        $nombre, $descripcion,
                        $texto_certifica_que, $texto_identificado_con, $texto_asistio_aprobo,
                        $texto_curso_autorizado, $texto_bajo_acta, $texto_duracion,
                        $texto_realizado_en, $texto_constancia,
                        $mostrar_consecutivo, $formato_consecutivo, $numero_registro_base,
                        $mostrar_numero_acta, $formato_numero_acta,
                        $mostrar_firma_director_nacional, $mostrar_firma_director_escuela, $mostrar_firma_coordinador,
                        $mostrar_contenido_programatico, $columnas_contenido
                    ]);
                    
                    $message = 'Configuración creada exitosamente';
                } else {
                    $query = "UPDATE configuracion_certificados SET 
                        nombre = ?, descripcion = ?,
                        texto_certifica_que = ?, texto_identificado_con = ?, texto_asistio_aprobo = ?,
                        texto_curso_autorizado = ?, texto_bajo_acta = ?, texto_duracion = ?,
                        texto_realizado_en = ?, texto_constancia = ?,
                        mostrar_consecutivo = ?, formato_consecutivo = ?, numero_registro_base = ?,
                        mostrar_numero_acta = ?, formato_numero_acta = ?,
                        mostrar_firma_director_nacional = ?, mostrar_firma_director_escuela = ?, mostrar_firma_coordinador = ?,
                        mostrar_contenido_programatico = ?, columnas_contenido = ?
                        WHERE id = ?";
                    
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        $nombre, $descripcion,
                        $texto_certifica_que, $texto_identificado_con, $texto_asistio_aprobo,
                        $texto_curso_autorizado, $texto_bajo_acta, $texto_duracion,
                        $texto_realizado_en, $texto_constancia,
                        $mostrar_consecutivo, $formato_consecutivo, $numero_registro_base,
                        $mostrar_numero_acta, $formato_numero_acta,
                        $mostrar_firma_director_nacional, $mostrar_firma_director_escuela, $mostrar_firma_coordinador,
                        $mostrar_contenido_programatico, $columnas_contenido,
                        $id
                    ]);
                    
                    $message = 'Configuración actualizada exitosamente';
                }
            } catch (Exception $e) {
                $error = 'Error al guardar la configuración: ' . $e->getMessage();
            }
        } else {
            $error = 'El nombre de la configuración es obligatorio';
        }
    }
}

// Obtener configuraciones existentes
try {
    $query_configs = "SELECT * FROM configuracion_certificados WHERE activo = 1 ORDER BY created_at DESC";
    $stmt_configs = $db->prepare($query_configs);
    $stmt_configs->execute();
    $configuraciones = $stmt_configs->fetchAll();
} catch (Exception $e) {
    $configuraciones = [];
    $error = 'Error al cargar configuraciones: ' . $e->getMessage();
}

// Obtener configuración seleccionada
$config_seleccionada = null;
if (isset($_GET['config_id'])) {
    $config_id = $_GET['config_id'];
    try {
        $query_config = "SELECT * FROM configuracion_certificados WHERE id = ? AND activo = 1";
        $stmt_config = $db->prepare($query_config);
        $stmt_config->execute([$config_id]);
        $config_seleccionada = $stmt_config->fetch();
    } catch (Exception $e) {
        $error = 'Error al cargar la configuración: ' . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-certificate text-blue-600 mr-3"></i>Configuración de Certificados
            </h1>
            <p class="text-gray-600">Personaliza el formato y contenido de los certificados</p>
        </div>
        <button onclick="openConfigModal()" class="btn-primary text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i>Nueva Configuración
        </button>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Lista de configuraciones -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-list mr-2"></i>Configuraciones Disponibles
                </h3>
            </div>
            
            <div class="p-4">
                <?php if (empty($configuraciones)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4"></i>
                        <p>No hay configuraciones disponibles</p>
                        <button onclick="openConfigModal()" class="mt-4 text-blue-600 hover:text-blue-800">
                            Crear primera configuración
                        </button>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($configuraciones as $config): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-colors"
                             onclick="selectConfig(<?php echo $config['id']; ?>)">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($config['nombre'] ?? 'Sin nombre'); ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <?php echo htmlspecialchars($config['descripcion'] ?? 'Sin descripción'); ?>
                                    </p>
                                    <div class="flex items-center mt-2 text-xs text-gray-500">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?php echo date('d/m/Y', strtotime($config['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="event.stopPropagation(); editConfig(<?php echo $config['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="event.stopPropagation(); previewConfig(<?php echo $config['id']; ?>)" 
                                            class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Detalles de configuración -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-cog mr-2"></i>Detalles de la Configuración
                </h3>
            </div>
            
            <div class="p-6">
                <?php if ($config_seleccionada): ?>
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                                <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['nombre'] ?? 'Sin nombre'); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                                <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['descripcion'] ?? 'Sin descripción'); ?></p>
                            </div>
                        </div>
                        
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Textos del Certificado</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Certifica que"</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['texto_certifica_que'] ?? 'Certifica que:'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Identificado con"</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['texto_identificado_con'] ?? 'Identificado con C.C. No.'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Asistió y aprobó"</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['texto_asistio_aprobo'] ?? 'Asistió y aprobó los requisitos del Curso:'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Curso autorizado"</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['texto_curso_autorizado'] ?? 'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Bajo acta"</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['texto_bajo_acta'] ?? 'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Duración"</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['texto_duracion'] ?? 'Con una duración de: {horas} horas académicas'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Realizado en"</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['texto_realizado_en'] ?? 'Realizado en {lugar_realizacion} del {fecha_inicio} al {fecha_fin}'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Constancia"</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['texto_constancia'] ?? 'En constancia de lo anterior, se firma a los {fecha_firma}'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Configuración de Numeración</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Mostrar Consecutivo</label>
                                    <p class="text-gray-900"><?php echo $config_seleccionada['mostrar_consecutivo'] ? 'Sí' : 'No'; ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Formato Consecutivo</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['formato_consecutivo'] ?? '{numero_registro}-{orden_alfabetico}'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Número Registro Base</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($config_seleccionada['numero_registro_base'] ?? 'DNBC-2024'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Mostrar Número de Acta</label>
                                    <p class="text-gray-900"><?php echo $config_seleccionada['mostrar_numero_acta'] ? 'Sí' : 'No'; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Configuración de Firmas</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Director Nacional</label>
                                    <p class="text-gray-900"><?php echo $config_seleccionada['mostrar_firma_director_nacional'] ? 'Mostrar' : 'Ocultar'; ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Director de Escuela</label>
                                    <p class="text-gray-900"><?php echo $config_seleccionada['mostrar_firma_director_escuela'] ? 'Mostrar' : 'Ocultar'; ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Coordinador</label>
                                    <p class="text-gray-900"><?php echo $config_seleccionada['mostrar_firma_coordinador'] ? 'Mostrar' : 'Ocultar'; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-6 border-t">
                            <button onclick="editConfig(<?php echo $config_seleccionada['id']; ?>)" 
                                    class="px-4 py-2 text-blue-600 border border-blue-300 rounded-lg hover:bg-blue-50">
                                <i class="fas fa-edit mr-2"></i>Editar
                            </button>
                            <button onclick="previewConfig(<?php echo $config_seleccionada['id']; ?>)" 
                                    class="btn-primary px-6 py-2 text-white rounded-lg">
                                <i class="fas fa-eye mr-2"></i>Vista Previa
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-mouse-pointer text-4xl mb-4"></i>
                        <p class="text-lg mb-2">Selecciona una configuración</p>
                        <p>Haz clic en una configuración de la lista para ver sus detalles</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear/editar configuración -->
<div id="configModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/4 shadow-lg rounded-xl bg-white max-h-screen overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Nueva Configuración</h3>
            <button onclick="closeConfigModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-6" id="configForm">
            <input type="hidden" name="action" value="create" id="formAction">
            <input type="hidden" name="id" value="" id="configId">
            
            <!-- Información básica -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" name="nombre" id="nombre" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <input type="text" name="descripcion" id="descripcion" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <!-- Textos del certificado -->
            <div class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Textos del Certificado</h4>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Certifica que"</label>
                            <input type="text" name="texto_certifica_que" id="texto_certifica_que" 
                                   value="Certifica que:" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Identificado con"</label>
                            <input type="text" name="texto_identificado_con" id="texto_identificado_con" 
                                   value="Identificado con C.C. No." 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Asistió y aprobó"</label>
                        <input type="text" name="texto_asistio_aprobo" id="texto_asistio_aprobo" 
                               value="Asistió y aprobó los requisitos del Curso:" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Curso autorizado"</label>
                        <textarea name="texto_curso_autorizado" id="texto_curso_autorizado" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia</textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Bajo acta"</label>
                        <textarea name="texto_bajo_acta" id="texto_bajo_acta" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}</textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Duración"</label>
                            <input type="text" name="texto_duracion" id="texto_duracion" 
                                   value="Con una duración de: {horas} horas académicas" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Realizado en"</label>
                            <input type="text" name="texto_realizado_en" id="texto_realizado_en" 
                                   value="Realizado en {lugar_realizacion} del {fecha_inicio} al {fecha_fin}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Texto "Constancia"</label>
                        <input type="text" name="texto_constancia" id="texto_constancia" 
                               value="En constancia de lo anterior, se firma a los {fecha_firma}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
            
            <!-- Configuración de numeración -->
            <div class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Configuración de Numeración</h4>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="mostrar_consecutivo" id="mostrar_consecutivo" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="mostrar_consecutivo" class="ml-2 block text-sm text-gray-900">
                            Mostrar número consecutivo en el certificado
                        </label>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Formato Consecutivo</label>
                            <input type="text" name="formato_consecutivo" id="formato_consecutivo" 
                                   value="{numero_registro}-{orden_alfabetico}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Variables: {numero_registro}, {orden_alfabetico}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Número Registro Base</label>
                            <input type="text" name="numero_registro_base" id="numero_registro_base" 
                                   value="DNBC-2024" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="mostrar_numero_acta" id="mostrar_numero_acta" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="mostrar_numero_acta" class="ml-2 block text-sm text-gray-900">
                            Mostrar número de acta en el certificado
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Formato Número de Acta</label>
                        <input type="text" name="formato_numero_acta" id="formato_numero_acta" 
                               value="ACTA-{escuela_id}-{consecutivo}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Variables: {escuela_id}, {consecutivo}</p>
                    </div>
                </div>
            </div>
            
            <!-- Configuración de firmas -->
            <div class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Configuración de Firmas</h4>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" name="mostrar_firma_director_nacional" id="mostrar_firma_director_nacional" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="mostrar_firma_director_nacional" class="ml-2 block text-sm text-gray-900">
                            Mostrar firma del Director Nacional
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="mostrar_firma_director_escuela" id="mostrar_firma_director_escuela" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="mostrar_firma_director_escuela" class="ml-2 block text-sm text-gray-900">
                            Mostrar firma del Director de Escuela
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="mostrar_firma_coordinador" id="mostrar_firma_coordinador" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="mostrar_firma_coordinador" class="ml-2 block text-sm text-gray-900">
                            Mostrar firma del Coordinador (segunda página)
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Configuración de contenido programático -->
            <div class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Contenido Programático</h4>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="mostrar_contenido_programatico" id="mostrar_contenido_programatico" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="mostrar_contenido_programatico" class="ml-2 block text-sm text-gray-900">
                            Incluir contenido programático en segunda página
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Número de Columnas</label>
                        <select name="columnas_contenido" id="columnas_contenido" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1">1 Columna</option>
                            <option value="2" selected>2 Columnas</option>
                            <option value="3">3 Columnas</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-6 border-t">
                <button type="button" onclick="closeConfigModal()" 
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary px-6 py-2 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openConfigModal() {
    resetConfigForm();
    document.getElementById('configModal').classList.remove('hidden');
}

function closeConfigModal() {
    document.getElementById('configModal').classList.add('hidden');
}

function resetConfigForm() {
    document.getElementById('configForm').reset();
    document.getElementById('modalTitle').textContent = 'Nueva Configuración';
    document.getElementById('formAction').value = 'create';
    document.getElementById('configId').value = '';
    
    // Restablecer valores por defecto
    document.getElementById('texto_certifica_que').value = 'Certifica que:';
    document.getElementById('texto_identificado_con').value = 'Identificado con C.C. No.';
    document.getElementById('texto_asistio_aprobo').value = 'Asistió y aprobó los requisitos del Curso:';
    document.getElementById('texto_curso_autorizado').value = 'Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia';
    document.getElementById('texto_bajo_acta').value = 'Bajo acta número {numero_acta} del {fecha_acta} del {nombre_cuerpo_bomberos}';
    document.getElementById('texto_duracion').value = 'Con una duración de: {horas} horas académicas';
    document.getElementById('texto_realizado_en').value = 'Realizado en {lugar_realizacion} del {fecha_inicio} al {fecha_fin}';
    document.getElementById('texto_constancia').value = 'En constancia de lo anterior, se firma a los {fecha_firma}';
    document.getElementById('formato_consecutivo').value = '{numero_registro}-{orden_alfabetico}';
    document.getElementById('numero_registro_base').value = 'DNBC-2024';
    document.getElementById('formato_numero_acta').value = 'ACTA-{escuela_id}-{consecutivo}';
    
    // Marcar checkboxes por defecto
    document.getElementById('mostrar_consecutivo').checked = true;
    document.getElementById('mostrar_numero_acta').checked = true;
    document.getElementById('mostrar_firma_director_nacional').checked = true;
    document.getElementById('mostrar_firma_director_escuela').checked = true;
    document.getElementById('mostrar_firma_coordinador').checked = true;
    document.getElementById('mostrar_contenido_programatico').checked = true;
    document.getElementById('columnas_contenido').value = '2';
}

function selectConfig(configId) {
    window.location.href = '?config_id=' + configId;
}

function editConfig(configId) {
    // Aquí cargarías los datos de la configuración via AJAX
    // Por simplicidad, redirigimos a la página con el ID
    window.location.href = '?config_id=' + configId + '&edit=1';
}

function previewConfig(configId) {
    window.open('preview-certificate.php?config_id=' + configId, '_blank');
}

// Cerrar modal al hacer click fuera
document.getElementById('configModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeConfigModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
