<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireRole(['Administrador General', 'Escuela', 'Director de Escuela']);

$page_title = 'Gestión de Matrículas';
$message = '';
$error = '';

// Procesar formulario
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'matricular_individual') {
        $participante_id = $_POST['participante_id'] ?? '';
        $curso_id = $_POST['curso_id'] ?? '';
        
        if ($participante_id && $curso_id) {
            try {
                // Verificar que no esté ya matriculado
                $query_check = "SELECT id FROM matriculas WHERE participante_id = ? AND curso_id = ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$participante_id, $curso_id]);
                
                if ($stmt_check->fetch()) {
                    throw new Exception('El participante ya está matriculado en este curso');
                }
                
                // Matricular participante
                $query_insert = "INSERT INTO matriculas (participante_id, curso_id) VALUES (?, ?)";
                $stmt_insert = $db->prepare($query_insert);
                $stmt_insert->execute([$participante_id, $curso_id]);
                
                $message = 'Participante matriculado exitosamente';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'matricular_masiva') {
        $curso_id = $_POST['curso_id'] ?? '';
        $participantes_ids = $_POST['participantes_ids'] ?? [];
        
        if ($curso_id && !empty($participantes_ids)) {
            try {
                $matriculados = 0;
                $ya_matriculados = 0;
                $errores = [];
                
                $db->beginTransaction();
                
                foreach ($participantes_ids as $participante_id) {
                    // Verificar que no esté ya matriculado
                    $query_check = "SELECT id FROM matriculas WHERE participante_id = ? AND curso_id = ?";
                    $stmt_check = $db->prepare($query_check);
                    $stmt_check->execute([$participante_id, $curso_id]);
                    
                    if ($stmt_check->fetch()) {
                        $ya_matriculados++;
                        continue;
                    }
                    
                    // Matricular participante
                    $query_insert = "INSERT INTO matriculas (participante_id, curso_id) VALUES (?, ?)";
                    $stmt_insert = $db->prepare($query_insert);
                    
                    if ($stmt_insert->execute([$participante_id, $curso_id])) {
                        $matriculados++;
                    } else {
                        $errores[] = "Error al matricular participante ID: $participante_id";
                    }
                }
                
                $db->commit();
                
                $message = "Matrícula masiva completada. $matriculados participantes matriculados.";
                if ($ya_matriculados > 0) {
                    $message .= " $ya_matriculados ya estaban matriculados.";
                }
                if (!empty($errores)) {
                    $message .= " " . count($errores) . " errores encontrados.";
                }
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Error en matrícula masiva: ' . $e->getMessage();
            }
        } else {
            $error = 'Debe seleccionar un curso y al menos un participante';
        }
    } elseif ($action === 'matricular_por_filtros') {
        $curso_id = $_POST['curso_id'] ?? '';
        $filtro_institucion = $_POST['filtro_institucion'] ?? '';
        $filtro_genero = $_POST['filtro_genero'] ?? '';
        $excluir_ya_matriculados = isset($_POST['excluir_ya_matriculados']);
        
        if ($curso_id) {
            try {
                // Construir query con filtros
                $where_conditions = ["p.activo = 1"];
                $params = [];
                
                if (!empty($filtro_institucion)) {
                    $where_conditions[] = "p.institucion LIKE ?";
                    $params[] = "%$filtro_institucion%";
                }
                
                if (!empty($filtro_genero)) {
                    $where_conditions[] = "p.genero = ?";
                    $params[] = $filtro_genero;
                }
                
                if ($excluir_ya_matriculados) {
                    $where_conditions[] = "p.id NOT IN (SELECT participante_id FROM matriculas WHERE curso_id = ?)";
                    $params[] = $curso_id;
                }
                
                $query_participantes = "SELECT p.id FROM participantes p WHERE " . implode(' AND ', $where_conditions);
                $stmt_participantes = $db->prepare($query_participantes);
                $stmt_participantes->execute($params);
                $participantes_filtrados = $stmt_participantes->fetchAll();
                
                if (empty($participantes_filtrados)) {
                    throw new Exception('No se encontraron participantes que cumplan con los filtros especificados');
                }
                
                $matriculados = 0;
                $ya_matriculados = 0;
                
                $db->beginTransaction();
                
                foreach ($participantes_filtrados as $participante) {
                    // Verificar que no esté ya matriculado (doble verificación)
                    $query_check = "SELECT id FROM matriculas WHERE participante_id = ? AND curso_id = ?";
                    $stmt_check = $db->prepare($query_check);
                    $stmt_check->execute([$participante['id'], $curso_id]);
                    
                    if ($stmt_check->fetch()) {
                        $ya_matriculados++;
                        continue;
                    }
                    
                    // Matricular participante
                    $query_insert = "INSERT INTO matriculas (participante_id, curso_id) VALUES (?, ?)";
                    $stmt_insert = $db->prepare($query_insert);
                    
                    if ($stmt_insert->execute([$participante['id'], $curso_id])) {
                        $matriculados++;
                    }
                }
                
                $db->commit();
                
                $message = "Matrícula por filtros completada. $matriculados participantes matriculados.";
                if ($ya_matriculados > 0) {
                    $message .= " $ya_matriculados ya estaban matriculados.";
                }
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Error en matrícula por filtros: ' . $e->getMessage();
            }
        } else {
            $error = 'Debe seleccionar un curso';
        }
    } elseif ($action === 'upload_matriculas_csv') {
        if (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
            try {
                $file = $_FILES['archivo_csv']['tmp_name'];
                
                // Detectar la codificación del archivo
                $content = file_get_contents($file);
                $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                
                // Convertir a UTF-8 si es necesario
                if ($encoding && $encoding !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                    $temp_file = tempnam(sys_get_temp_dir(), 'csv_utf8_');
                    file_put_contents($temp_file, $content);
                    $file = $temp_file;
                }
                
                $handle = fopen($file, 'r');
                
                if ($handle === false) {
                    throw new Exception('No se pudo leer el archivo CSV');
                }
                
                $matriculados = 0;
                $errores = [];
                $linea = 0;
                
                // Saltar la primera línea si contiene encabezados
                if (isset($_POST['tiene_encabezados_matriculas'])) {
                    fgetcsv($handle);
                    $linea++;
                }
                
                $db->beginTransaction();
                
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $linea++;
                    
                    if (count($data) < 2) {
                        $errores[] = "Línea $linea: Datos insuficientes (se requieren cédula y código de curso)";
                        continue;
                    }
                    
                    $cedula = trim($data[0] ?? '');
                    $codigo_curso = trim($data[1] ?? '');
                    
                    if (empty($cedula) || empty($codigo_curso)) {
                        $errores[] = "Línea $linea: Cédula o código de curso vacío";
                        continue;
                    }
                    
                    // Buscar participante por cédula
                    $query_participante = "SELECT id FROM participantes WHERE cedula = ? AND activo = 1";
                    $stmt_participante = $db->prepare($query_participante);
                    $stmt_participante->execute([$cedula]);
                    $participante = $stmt_participante->fetch();
                    
                    if (!$participante) {
                        $errores[] = "Línea $linea: No se encontró participante con cédula '$cedula'";
                        continue;
                    }
                    
                    // Buscar curso por número de registro
                    $query_curso = "SELECT id FROM cursos WHERE numero_registro = ? AND activo = 1";
                    $stmt_curso = $db->prepare($query_curso);
                    $stmt_curso->execute([$codigo_curso]);
                    $curso = $stmt_curso->fetch();
                    
                    if (!$curso) {
                        $errores[] = "Línea $linea: No se encontró curso con código '$codigo_curso'";
                        continue;
                    }
                    
                    // Verificar que no esté ya matriculado
                    $query_check = "SELECT id FROM matriculas WHERE participante_id = ? AND curso_id = ?";
                    $stmt_check = $db->prepare($query_check);
                    $stmt_check->execute([$participante['id'], $curso['id']]);
                    
                    if ($stmt_check->fetch()) {
                        $errores[] = "Línea $linea: Participante con cédula '$cedula' ya está matriculado en curso '$codigo_curso'";
                        continue;
                    }
                    
                    // Matricular participante
                    $query_insert = "INSERT INTO matriculas (participante_id, curso_id) VALUES (?, ?)";
                    $stmt_insert = $db->prepare($query_insert);
                    
                    if ($stmt_insert->execute([$participante['id'], $curso['id']])) {
                        $matriculados++;
                    } else {
                        $errores[] = "Línea $linea: Error al matricular participante '$cedula' en curso '$codigo_curso'";
                    }
                }
                
                $db->commit();
                fclose($handle);
                
                // Eliminar archivo temporal si se creó
                if (isset($temp_file) && file_exists($temp_file)) {
                    unlink($temp_file);
                }
                
                $message = "Carga masiva de matrículas completada. $matriculados matrículas creadas.";
                if (!empty($errores)) {
                    $total_errores = count($errores);
                    $message .= " Se encontraron $total_errores errores.";
                    
                    if ($total_errores <= 3) {
                        $message .= " Errores: " . implode(' | ', $errores);
                    } else {
                        $message .= " Primeros errores: " . implode(' | ', array_slice($errores, 0, 3)) . " y " . ($total_errores - 3) . " más...";
                    }
                }
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Error en carga masiva de matrículas: ' . $e->getMessage();
            }
        } else {
            $error = 'Debe seleccionar un archivo CSV válido';
        }
    }
}

// Obtener cursos disponibles
$query_cursos = "SELECT c.id, c.nombre, c.numero_registro, CONCAT(u.nombres, ' ', u.apellidos) as coordinador,
                        (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id) as total_matriculados
                 FROM cursos c 
                 JOIN usuarios u ON c.coordinador_id = u.id 
                 WHERE c.activo = 1 
                 ORDER BY c.nombre";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute();
$cursos = $stmt_cursos->fetchAll();

// Obtener participantes disponibles
$query_participantes = "SELECT p.id, p.nombres, p.apellidos, p.cedula, p.email, p.institucion, p.genero, p.fotografia,
                               (SELECT COUNT(*) FROM matriculas m WHERE m.participante_id = p.id) as total_cursos
                        FROM participantes p 
                        WHERE p.activo = 1 
                        ORDER BY p.nombres, p.apellidos";
$stmt_participantes = $db->prepare($query_participantes);
$stmt_participantes->execute();
$participantes = $stmt_participantes->fetchAll();

// Obtener instituciones únicas para filtros
$query_instituciones = "SELECT DISTINCT institucion FROM participantes WHERE institucion IS NOT NULL AND institucion != '' ORDER BY institucion";
$stmt_instituciones = $db->prepare($query_instituciones);
$stmt_instituciones->execute();
$instituciones = $stmt_instituciones->fetchAll();

include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-user-plus text-blue-600 mr-3"></i>Gestión de Matrículas
            </h1>
            <p class="text-gray-600">Matricula participantes en cursos de forma individual o masiva</p>
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

<!-- Pestañas de navegación -->
<div class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button onclick="showTab('individual')" id="tab-individual" class="tab-button active py-2 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                <i class="fas fa-user mr-2"></i>Matrícula Individual
            </button>
            <button onclick="showTab('masiva')" id="tab-masiva" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                <i class="fas fa-users mr-2"></i>Matrícula Masiva
            </button>
            <button onclick="showTab('filtros')" id="tab-filtros" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                <i class="fas fa-filter mr-2"></i>Matrícula por Filtros
            </button>
            <button onclick="showTab('csv')" id="tab-csv" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                <i class="fas fa-file-csv mr-2"></i>Carga CSV
            </button>
        </nav>
    </div>
</div>

<!-- Tab: Matrícula Individual -->
<div id="content-individual" class="tab-content">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-user text-blue-600 mr-2"></i>Matrícula Individual
        </h3>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="matricular_individual">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Participante *</label>
                    <select name="participante_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar participante</option>
                        <?php foreach ($participantes as $participante): ?>
                        <option value="<?php echo $participante['id']; ?>">
                            <?php echo htmlspecialchars($participante['nombres'] . ' ' . $participante['apellidos'] . ' - ' . $participante['cedula']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Curso *</label>
                    <select name="curso_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar curso</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>">
                            <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['numero_registro']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">
                    <i class="fas fa-user-plus mr-2"></i>Matricular Participante
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: Matrícula Masiva -->
<div id="content-masiva" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-users text-green-600 mr-2"></i>Matrícula Masiva por Selección
        </h3>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="matricular_masiva">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Curso *</label>
                <select name="curso_id" id="curso_masivo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccionar curso</option>
                    <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>">
                        <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['numero_registro'] . ' (' . $curso['total_matriculados'] . ' matriculados)'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtros de búsqueda -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium text-gray-900 mb-3">Filtros de Búsqueda</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por nombre</label>
                        <input type="text" id="filtro_nombre" placeholder="Nombre o apellido..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Institución</label>
                        <select id="filtro_institucion_masiva" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todas las instituciones</option>
                            <?php foreach ($instituciones as $institucion): ?>
                            <option value="<?php echo htmlspecialchars($institucion['institucion']); ?>">
                                <?php echo htmlspecialchars($institucion['institucion']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Género</label>
                        <select id="filtro_genero_masiva" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los géneros</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex items-center space-x-4">
                    <button type="button" onclick="aplicarFiltros()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-search mr-2"></i>Aplicar Filtros
                    </button>
                    <button type="button" onclick="limpiarFiltros()" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-times mr-2"></i>Limpiar
                    </button>
                    <div class="flex items-center">
                        <input type="checkbox" id="seleccionar_todos" class="rounded border-gray-300 text-blue-600">
                        <label for="seleccionar_todos" class="ml-2 text-sm text-gray-700">Seleccionar todos los visibles</label>
                    </div>
                </div>
            </div>
            
            <!-- Lista de participantes -->
            <div class="border border-gray-200 rounded-lg max-h-96 overflow-y-auto">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-900">Seleccionar Participantes</span>
                        <span id="contador_seleccionados" class="text-sm text-gray-600">0 seleccionados</span>
                    </div>
                </div>
                <div class="p-4 space-y-2" id="lista_participantes">
                    <?php foreach ($participantes as $participante): ?>
                    <div class="participante-item flex items-center p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50" 
                         data-nombre="<?php echo strtolower($participante['nombres'] . ' ' . $participante['apellidos']); ?>"
                         data-institucion="<?php echo strtolower($participante['institucion'] ?? ''); ?>"
                         data-genero="<?php echo $participante['genero'] ?? ''; ?>">
                        <input type="checkbox" name="participantes_ids[]" value="<?php echo $participante['id']; ?>" 
                               class="participante-checkbox rounded border-gray-300 text-blue-600 mr-3">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <?php if ($participante['fotografia'] && file_exists($participante['fotografia'])): ?>
                                    <img src="<?php echo htmlspecialchars($participante['fotografia']); ?>" alt="Foto de <?php echo htmlspecialchars($participante['nombres']); ?>" class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 shadow-sm mr-3">
                                <?php else: ?>
                                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm mr-3 border-2 border-gray-200 shadow-sm">
                                        <?php echo strtoupper(substr($participante['nombres'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($participante['nombres'] . ' ' . $participante['apellidos']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($participante['cedula']); ?> • 
                                        <?php echo htmlspecialchars($participante['institucion'] ?? 'Sin institución'); ?> • 
                                        <?php echo $participante['total_cursos']; ?> cursos
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    <span id="total_participantes"><?php echo count($participantes); ?></span> participantes disponibles
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold">
                    <i class="fas fa-users mr-2"></i>Matricular Seleccionados
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: Matrícula por Filtros -->
<div id="content-filtros" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-filter text-purple-600 mr-2"></i>Matrícula Automática por Filtros
        </h3>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2 mt-1"></i>
                <div>
                    <p class="text-sm text-yellow-800 font-medium">Matrícula Automática</p>
                    <p class="text-xs text-yellow-700">Esta opción matriculará automáticamente a TODOS los participantes que cumplan con los filtros especificados.</p>
                </div>
            </div>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="matricular_por_filtros">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Curso *</label>
                <select name="curso_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccionar curso</option>
                    <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>">
                        <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['numero_registro']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Institución</label>
                    <select name="filtro_institucion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las instituciones</option>
                        <?php foreach ($instituciones as $institucion): ?>
                        <option value="<?php echo htmlspecialchars($institucion['institucion']); ?>">
                            <?php echo htmlspecialchars($institucion['institucion']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Género</label>
                    <select name="filtro_genero" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los géneros</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" name="excluir_ya_matriculados" id="excluir_ya_matriculados" checked class="rounded border-gray-300 text-blue-600">
                <label for="excluir_ya_matriculados" class="ml-2 text-sm text-gray-700">Excluir participantes ya matriculados en este curso</label>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-semibold">
                    <i class="fas fa-magic mr-2"></i>Matricular por Filtros
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tab: Carga CSV -->
<div id="content-csv" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-file-csv text-orange-600 mr-2"></i>Carga Masiva desde CSV
        </h3>
        
        <div class="mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-semibold text-blue-800 mb-2">Formato del archivo CSV:</h4>
                <p class="text-sm text-blue-700 mb-2">El archivo debe contener las siguientes columnas:</p>
                <div class="bg-white p-3 rounded border text-sm font-mono">
                    Cédula, Código_Curso
                </div>
                <p class="text-xs text-blue-600 mt-2">
                    * Cédula: Número de cédula del participante<br>
                    * Código_Curso: Número de registro del curso
                </p>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="upload_matriculas_csv">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Archivo CSV *</label>
                <input type="file" name="archivo_csv" accept=".csv" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Seleccione un archivo CSV con las matrículas a procesar</p>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" name="tiene_encabezados_matriculas" id="tiene_encabezados_matriculas" checked class="rounded border-gray-300 text-blue-600">
                <label for="tiene_encabezados_matriculas" class="ml-2 text-sm text-gray-700">El archivo tiene encabezados en la primera fila</label>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-download text-green-600 mr-2 mt-1"></i>
                    <div>
                        <p class="text-sm text-green-800 font-medium">Plantilla de ejemplo</p>
                        <p class="text-xs text-green-600">Descargue una plantilla con el formato correcto</p>
                        <button type="button" onclick="downloadMatriculasTemplate()" class="mt-2 text-green-600 hover:text-green-800 text-sm underline">
                            Descargar plantilla CSV de matrículas
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg font-semibold">
                    <i class="fas fa-upload mr-2"></i>Procesar Matrículas CSV
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Manejo de pestañas
function showTab(tabName) {
    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remover clase activa de todos los botones
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Mostrar contenido seleccionado
    document.getElementById(`content-${tabName}`).classList.remove('hidden');
    
    // Activar botón seleccionado
    const activeButton = document.getElementById(`tab-${tabName}`);
    activeButton.classList.add('active', 'border-blue-500', 'text-blue-600');
    activeButton.classList.remove('border-transparent', 'text-gray-500');
}

// Funciones para matrícula masiva
function aplicarFiltros() {
    const filtroNombre = document.getElementById('filtro_nombre').value.toLowerCase();
    const filtroInstitucion = document.getElementById('filtro_institucion_masiva').value.toLowerCase();
    const filtroGenero = document.getElementById('filtro_genero_masiva').value;
    
    const participantes = document.querySelectorAll('.participante-item');
    let visibles = 0;
    
    participantes.forEach(item => {
        const nombre = item.dataset.nombre;
        const institucion = item.dataset.institucion;
        const genero = item.dataset.genero;
        
        let mostrar = true;
        
        if (filtroNombre && !nombre.includes(filtroNombre)) {
            mostrar = false;
        }
        
        if (filtroInstitucion && !institucion.includes(filtroInstitucion)) {
            mostrar = false;
        }
        
        if (filtroGenero && genero !== filtroGenero) {
            mostrar = false;
        }
        
        if (mostrar) {
            item.style.display = 'flex';
            visibles++;
        } else {
            item.style.display = 'none';
            // Desmarcar si está oculto
            const checkbox = item.querySelector('.participante-checkbox');
            checkbox.checked = false;
        }
    });
    
    document.getElementById('total_participantes').textContent = visibles;
    actualizarContador();
}

function limpiarFiltros() {
    document.getElementById('filtro_nombre').value = '';
    document.getElementById('filtro_institucion_masiva').value = '';
    document.getElementById('filtro_genero_masiva').value = '';
    
    document.querySelectorAll('.participante-item').forEach(item => {
        item.style.display = 'flex';
    });
    
    document.getElementById('total_participantes').textContent = <?php echo count($participantes); ?>;
    
    // Desmarcar todos
    document.querySelectorAll('.participante-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('seleccionar_todos').checked = false;
    
    actualizarContador();
}

function actualizarContador() {
    const seleccionados = document.querySelectorAll('.participante-checkbox:checked').length;
    document.getElementById('contador_seleccionados').textContent = `${seleccionados} seleccionados`;
}

// Seleccionar todos los visibles
document.getElementById('seleccionar_todos').addEventListener('change', function() {
    const participantesVisibles = document.querySelectorAll('.participante-item:not([style*="display: none"]) .participante-checkbox');
    
    participantesVisibles.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    
    actualizarContador();
});

// Actualizar contador cuando se selecciona individualmente
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('participante-checkbox')) {
        actualizarContador();
        
        // Actualizar estado del checkbox "seleccionar todos"
        const visibles = document.querySelectorAll('.participante-item:not([style*="display: none"]) .participante-checkbox');
        const seleccionados = document.querySelectorAll('.participante-item:not([style*="display: none"]) .participante-checkbox:checked');
        
        document.getElementById('seleccionar_todos').checked = visibles.length > 0 && visibles.length === seleccionados.length;
    }
});

function downloadMatriculasTemplate() {
    const csvContent = "Cédula,Código_Curso\n" +
                      "12345678,CUR-001\n" +
                      "87654321,CUR-001\n" +
                      "11223344,CUR-002\n" +
                      "55667788,CUR-002";
    
    const BOM = '\uFEFF';
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'plantilla_matriculas.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Inicializar contador
document.addEventListener('DOMContentLoaded', function() {
    actualizarContador();
});
</script>

<?php include 'includes/footer.php'; ?>
