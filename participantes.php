<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireRole(['Escuela', 'Coordinador']);

$page_title = 'Gestión de Participantes';
$message = '';
$error = '';

// Procesar formulario
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $institucion = trim($_POST['institucion'] ?? '');
        $genero = $_POST['genero'] ?? '';
        
        if ($nombres && $apellidos && $cedula && $email) {
            try {
                // Verificar que no exista la cédula
                $query_check = "SELECT id FROM participantes WHERE cedula = ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$cedula]);
                
                if ($stmt_check->fetch()) {
                    throw new Exception('Ya existe un participante con esa cédula');
                }
                
                // Manejar subida de fotografía
                $fotografia = null;
                if (isset($_FILES['fotografia']) && $_FILES['fotografia']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/fotografias/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['fotografia']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $filename = 'participante_' . $cedula . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['fotografia']['tmp_name'], $upload_path)) {
                            $fotografia = $upload_path;
                        }
                    } else {
                        throw new Exception('Formato de imagen no válido. Use JPG, PNG o GIF');
                    }
                }
                
                // Crear participante
                $password_hash = password_hash($cedula, PASSWORD_DEFAULT); // Contraseña inicial = cédula
                $query_insert = "INSERT INTO participantes (nombres, apellidos, cedula, email, telefono, institucion, genero, fotografia, password_hash) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $db->prepare($query_insert);
                $stmt_insert->execute([$nombres, $apellidos, $cedula, $email, $telefono, $institucion, $genero, $fotografia, $password_hash]);
                
                $message = 'Participante registrado exitosamente. Contraseña inicial: ' . $cedula;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Los campos obligatorios deben ser completados';
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $institucion = trim($_POST['institucion'] ?? '');
        $genero = $_POST['genero'] ?? '';
        
        if ($id && $nombres && $apellidos && $cedula && $email) {
            try {
                // Verificar que no exista la cédula en otro participante
                $query_check = "SELECT id FROM participantes WHERE cedula = ? AND id != ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$cedula, $id]);
                
                if ($stmt_check->fetch()) {
                    throw new Exception('Ya existe otro participante con esa cédula');
                }
                
                // Obtener datos actuales del participante
                $query_current = "SELECT fotografia FROM participantes WHERE id = ?";
                $stmt_current = $db->prepare($query_current);
                $stmt_current->execute([$id]);
                $current_data = $stmt_current->fetch();
                
                $fotografia = $current_data['fotografia']; // Mantener foto actual por defecto
                
                // Manejar nueva fotografía si se subió
                if (isset($_FILES['fotografia']) && $_FILES['fotografia']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/fotografias/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['fotografia']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $filename = 'participante_' . $cedula . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['fotografia']['tmp_name'], $upload_path)) {
                            // Eliminar foto anterior si existe
                            if ($fotografia && file_exists($fotografia)) {
                                unlink($fotografia);
                            }
                            $fotografia = $upload_path;
                        }
                    } else {
                        throw new Exception('Formato de imagen no válido. Use JPG, PNG o GIF');
                    }
                }
                
                // Actualizar participante
                $query_update = "UPDATE participantes SET nombres = ?, apellidos = ?, cedula = ?, email = ?, telefono = ?, institucion = ?, genero = ?, fotografia = ? WHERE id = ?";
                $stmt_update = $db->prepare($query_update);
                $stmt_update->execute([$nombres, $apellidos, $cedula, $email, $telefono, $institucion, $genero, $fotografia, $id]);
                
                $message = 'Participante actualizado exitosamente';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Los campos obligatorios deben ser completados';
        }
    } elseif ($action === 'upload_massive') {
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
                
                $participantes_creados = 0;
                $errores = [];
                $linea = 0;
                
                // Saltar la primera línea si contiene encabezados
                if (isset($_POST['tiene_encabezados'])) {
                    $header = fgetcsv($handle, 1000, ',');
                    $linea++;
                }
                
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $linea++;
                    
                    if (count($data) < 4) {
                        $errores[] = "Línea $linea: Datos insuficientes (se requieren al menos 4 columnas)";
                        continue;
                    }
                    
                    // Limpiar y procesar datos con manejo de caracteres especiales
                    $nombres = trim($data[0] ?? '');
                    $apellidos = trim($data[1] ?? '');
                    $cedula = trim($data[2] ?? '');
                    $email = trim($data[3] ?? '');
                    $telefono = trim($data[4] ?? '');
                    $institucion = trim($data[5] ?? '');
                    $genero = trim($data[6] ?? '');
                    
                    // Limpiar caracteres especiales no deseados pero mantener tildes
                    $nombres = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $nombres);
                    $apellidos = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $apellidos);
                    $institucion = preg_replace('/[^\p{L}\p{N}\s\-\.\,]/u', '', $institucion);
                    
                    // Normalizar género
                    $genero_lower = mb_strtolower($genero, 'UTF-8');
                    if (in_array($genero_lower, ['m', 'masculino', 'hombre'])) {
                        $genero = 'M';
                    } elseif (in_array($genero_lower, ['f', 'femenino', 'mujer'])) {
                        $genero = 'F';
                    } elseif (!empty($genero)) {
                        $genero = 'Otro';
                    } else {
                        $genero = null;
                    }
                    
                    // Validar campos obligatorios
                    if (empty($nombres) || empty($apellidos) || empty($cedula) || empty($email)) {
                        $errores[] = "Línea $linea: Campos obligatorios faltantes (nombres: '$nombres', apellidos: '$apellidos', cédula: '$cedula', email: '$email')";
                        continue;
                    }
                    
                    // Validar formato de email
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errores[] = "Línea $linea: Email inválido '$email'";
                        continue;
                    }
                    
                    // Verificar que no exista la cédula
                    $query_check = "SELECT id FROM participantes WHERE cedula = ?";
                    $stmt_check = $db->prepare($query_check);
                    $stmt_check->execute([$cedula]);
                    
                    if ($stmt_check->fetch()) {
                        $errores[] = "Línea $linea: Ya existe participante con cédula '$cedula'";
                        continue;
                    }
                    
                    // Verificar que no exista el email
                    $query_check_email = "SELECT id FROM participantes WHERE email = ?";
                    $stmt_check_email = $db->prepare($query_check_email);
                    $stmt_check_email->execute([$email]);
                    
                    if ($stmt_check_email->fetch()) {
                        $errores[] = "Línea $linea: Ya existe participante con email '$email'";
                        continue;
                    }
                    
                    try {
                        // Crear participante
                        $password_hash = password_hash($cedula, PASSWORD_DEFAULT);
                        $query_insert = "INSERT INTO participantes (nombres, apellidos, cedula, email, telefono, institucion, genero, password_hash) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt_insert = $db->prepare($query_insert);
                        
                        if ($stmt_insert->execute([$nombres, $apellidos, $cedula, $email, $telefono, $institucion, $genero, $password_hash])) {
                            $participantes_creados++;
                        } else {
                            $errores[] = "Línea $linea: Error al crear participante '$nombres $apellidos'";
                        }
                    } catch (Exception $e) {
                        $errores[] = "Línea $linea: Error de base de datos - " . $e->getMessage();
                    }
                }
                
                fclose($handle);
                
                // Eliminar archivo temporal si se creó
                if (isset($temp_file) && file_exists($temp_file)) {
                    unlink($temp_file);
                }
                
                $message = "Carga masiva completada. $participantes_creados participantes creados exitosamente.";
                if (!empty($errores)) {
                    $total_errores = count($errores);
                    $message .= " Se encontraron $total_errores errores.";
                    
                    // Mostrar solo los primeros 3 errores para no saturar la interfaz
                    if ($total_errores <= 3) {
                        $message .= " Errores: " . implode(' | ', $errores);
                    } else {
                        $message .= " Primeros errores: " . implode(' | ', array_slice($errores, 0, 3)) . " y " . ($total_errores - 3) . " más...";
                    }
                }
                
            } catch (Exception $e) {
                $error = 'Error en carga masiva: ' . $e->getMessage();
            }
        } else {
            $error = 'Debe seleccionar un archivo CSV válido';
        }
    }
}

// Obtener cursos disponibles
$query_cursos = "SELECT c.id, c.nombre, CONCAT(u.nombres, ' ', u.apellidos) as coordinador 
                FROM cursos c 
                JOIN usuarios u ON c.coordinador_id = u.id 
                WHERE c.activo = 1 
                ORDER BY c.nombre";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute();
$cursos = $stmt_cursos->fetchAll();

// Obtener lista de participantes
$query_participantes = "SELECT p.*, 
                               (SELECT COUNT(*) FROM matriculas m WHERE m.participante_id = p.id) as total_cursos,
                               (SELECT COUNT(*) FROM matriculas m WHERE m.participante_id = p.id AND m.aprobado = 1) as cursos_aprobados
                       FROM participantes p 
                       WHERE p.activo = 1 
                       ORDER BY p.created_at DESC";
$stmt_participantes = $db->prepare($query_participantes);
$stmt_participantes->execute();
$participantes = $stmt_participantes->fetchAll();

include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-user-graduate text-blue-600 mr-3"></i>Gestión de Participantes
            </h1>
            <p class="text-gray-600">Registra participantes y gestiona sus matrículas</p>
        </div>
        <div class="flex space-x-3">
            <a href="matriculas.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
                <i class="fas fa-user-plus mr-2"></i>Gestionar Matrículas
            </a>
            <button onclick="openMassiveUploadModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
                <i class="fas fa-upload mr-2"></i>Carga Masiva
            </button>
            <button onclick="openCreateModal()" class="btn-primary text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
                <i class="fas fa-plus mr-2"></i>Nuevo Participante
            </button>
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

<!-- Tabla de participantes -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Lista de Participantes</h3>
            <div class="flex items-center space-x-2">
                <input type="text" id="searchInput" placeholder="Buscar participante..." class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participante</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cédula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Institución</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cursos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($participantes as $participante): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <?php if ($participante['fotografia'] && file_exists($participante['fotografia'])): ?>
                                <img src="<?php echo htmlspecialchars($participante['fotografia']); ?>" alt="Foto de <?php echo htmlspecialchars($participante['nombres']); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 shadow-sm">
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold border-2 border-gray-200 shadow-sm">
                                    <?php echo strtoupper(substr($participante['nombres'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($participante['nombres'] . ' ' . $participante['apellidos']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($participante['telefono'] ?? 'Sin teléfono'); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($participante['cedula']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($participante['email']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($participante['institucion'] ?? 'No especificada'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?php echo $participante['total_cursos']; ?> inscritos
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <?php echo $participante['cursos_aprobados']; ?> aprobados
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Activo
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="editParticipant(<?php echo $participante['id']; ?>, '<?php echo addslashes($participante['nombres']); ?>', '<?php echo addslashes($participante['apellidos']); ?>', '<?php echo addslashes($participante['cedula']); ?>', '<?php echo addslashes($participante['email']); ?>', '<?php echo addslashes($participante['telefono'] ?? ''); ?>', '<?php echo addslashes($participante['institucion'] ?? ''); ?>', '<?php echo addslashes($participante['genero'] ?? ''); ?>', '<?php echo addslashes($participante['fotografia'] ?? ''); ?>')" class="text-blue-600 hover:text-blue-900 mr-3" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="viewParticipant(<?php echo $participante['id']; ?>)" class="text-purple-600 hover:text-purple-900 mr-3" title="Ver cursos">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="text-red-600 hover:text-red-900" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para crear/editar participante -->
<div id="createParticipantModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Registrar Nuevo Participante</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="participantId" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombres *</label>
                    <input type="text" name="nombres" id="nombres" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Apellidos *</label>
                    <input type="text" name="apellidos" id="apellidos" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cédula *</label>
                    <input type="text" name="cedula" id="cedula" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" id="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                    <input type="text" name="telefono" id="telefono" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
                    <select name="genero" id="genero" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Institución</label>
                <input type="text" name="institucion" id="institucion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fotografía</label>
                <div class="flex items-center space-x-4">
                    <input type="file" name="fotografia" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="preview-container" class="hidden">
                        <img id="photo-preview" src="/placeholder.svg" alt="Vista previa" class="w-16 h-16 rounded-full object-cover">
                    </div>
                    <div id="current-photo-container" class="hidden">
                        <img id="current-photo" src="/placeholder.svg" alt="Foto actual" class="w-16 h-16 rounded-full object-cover">
                        <p class="text-xs text-gray-500 text-center mt-1">Foto actual</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB</p>
            </div>
            
            <div id="password-info" class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    <p class="text-sm text-blue-800">La contraseña inicial será la cédula del participante. Podrá cambiarla al iniciar sesión.</p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" id="submitButton" class="btn-primary px-6 py-2 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Registrar Participante
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para carga masiva -->
<div id="massiveUploadModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Carga Masiva de Participantes</h3>
            <button onclick="closeMassiveUploadModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="mb-6">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-semibold text-yellow-800 mb-2">Formato del archivo CSV:</h4>
                <p class="text-sm text-yellow-700 mb-2">El archivo debe contener las siguientes columnas en este orden:</p>
                <div class="bg-white p-3 rounded border text-sm font-mono">
                    Nombres, Apellidos, Cédula, Email, Teléfono, Institución, Género
                </div>
                <p class="text-xs text-yellow-600 mt-2">
                    * Los primeros 4 campos son obligatorios<br>
                    * Género: M, Masculino, F, Femenino, Otro<br>
                    * <strong>Importante:</strong> Guarde el archivo con codificación UTF-8 para preservar tildes y caracteres especiales
                </p>
            </div>
        </div>
        
        <div class="mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-semibold text-blue-800 mb-2">Manejo de caracteres especiales:</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• El sistema detecta automáticamente la codificación del archivo</li>
                    <li>• Se preservan tildes y caracteres especiales (á, é, í, ó, ú, ñ, etc.)</li>
                    <li>• Se validan emails y se eliminan caracteres no válidos</li>
                    <li>• Se normalizan automáticamente los valores de género</li>
                </ul>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="upload_massive">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Archivo CSV *</label>
                <input type="file" name="archivo_csv" accept=".csv" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Seleccione un archivo CSV con los datos de los participantes</p>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" name="tiene_encabezados" id="tiene_encabezados" checked class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <label for="tiene_encabezados" class="ml-2 text-sm text-gray-700">El archivo tiene encabezados en la primera fila</label>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-download text-green-600 mr-2 mt-1"></i>
                    <div>
                        <p class="text-sm text-green-800 font-medium">Plantilla de ejemplo</p>
                        <p class="text-xs text-green-600">Descargue una plantilla con ejemplos de nombres con tildes y caracteres especiales</p>
                        <button type="button" onclick="downloadTemplate()" class="mt-2 text-green-600 hover:text-green-800 text-sm underline">
                            Descargar plantilla CSV con ejemplos
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeMassiveUploadModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 text-white rounded-lg">
                    <i class="fas fa-upload mr-2"></i>Cargar Participantes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function resetForm() {
    document.querySelector('form').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('participantId').value = '';
    document.getElementById('modalTitle').textContent = 'Registrar Nuevo Participante';
    document.getElementById('submitButton').innerHTML = '<i class="fas fa-save mr-2"></i>Registrar Participante';
    document.getElementById('password-info').classList.remove('hidden');
    document.getElementById('preview-container').classList.add('hidden');
    document.getElementById('current-photo-container').classList.add('hidden');
}

function openCreateModal() {
    resetForm();
    document.getElementById('createParticipantModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createParticipantModal').classList.add('hidden');
}

function editParticipant(id, nombres, apellidos, cedula, email, telefono, institucion, genero, fotografia) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('participantId').value = id;
    document.getElementById('modalTitle').textContent = 'Editar Participante';
    document.getElementById('submitButton').innerHTML = '<i class="fas fa-save mr-2"></i>Actualizar Participante';
    document.getElementById('password-info').classList.add('hidden');
    
    // Llenar campos
    document.getElementById('nombres').value = nombres || '';
    document.getElementById('apellidos').value = apellidos || '';
    document.getElementById('cedula').value = cedula || '';
    document.getElementById('email').value = email || '';
    document.getElementById('telefono').value = telefono || '';
    document.getElementById('institucion').value = institucion || '';
    document.getElementById('genero').value = genero || '';
    
    // Mostrar foto actual si existe
    if (fotografia) {
        document.getElementById('current-photo').src = fotografia;
        document.getElementById('current-photo-container').classList.remove('hidden');
    } else {
        document.getElementById('current-photo-container').classList.add('hidden');
    }
    
    document.getElementById('createParticipantModal').classList.remove('hidden');
}

function openMassiveUploadModal() {
    document.getElementById('massiveUploadModal').classList.remove('hidden');
}

function closeMassiveUploadModal() {
    document.getElementById('massiveUploadModal').classList.add('hidden');
}

function viewParticipant(id) {
    window.location.href = `participante-detalle.php?id=${id}`;
}

function downloadTemplate() {
    // Crear CSV de ejemplo con caracteres especiales y tildes
    const csvContent = "Nombres,Apellidos,Cédula,Email,Teléfono,Institución,Género\n" +
                      "José María,Pérez González,12345678,jose.perez@email.com,3001234567,Bomberos Bogotá,M\n" +
                      "María José,García Rodríguez,87654321,maria.garcia@email.com,3007654321,Bomberos Medellín,F\n" +
                      "Andrés Felipe,López Martínez,11223344,andres.lopez@email.com,3009876543,Bomberos Cali,Masculino\n" +
                      "Ana Sofía,Hernández Jiménez,55667788,ana.hernandez@email.com,3005551234,Bomberos Barranquilla,Femenino\n" +
                      "Nicolás,Muñoz Sánchez,99887766,nicolas.munoz@email.com,3002223456,Bomberos Cartagena,Otro";
    
    // Crear blob con BOM para UTF-8
    const BOM = '\uFEFF';
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'plantilla_participantes_utf8.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Vista previa de imagen
document.querySelector('input[name="fotografia"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photo-preview').src = e.target.result;
            document.getElementById('preview-container').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('preview-container').classList.add('hidden');
    }
});

// Cerrar modales al hacer click fuera
document.getElementById('createParticipantModal').addEventListener('click', function(e) {
    if (e.target === this) closeCreateModal();
});

document.getElementById('massiveUploadModal').addEventListener('click', function(e) {
    if (e.target === this) closeMassiveUploadModal();
});

// Búsqueda en tiempo real
document.getElementById('searchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
