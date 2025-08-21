<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireRole(['Administrador General']);

$page_title = 'Gestión de Escuelas';
$message = '';
$error = '';

// Procesar formulario
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $director_id = $_POST['director_id'] ?? '';
        
        // Convertir cadena vacía a NULL para director_id
        $director_id = ($director_id === '' || $director_id === 'null') ? null : $director_id;
        
        if ($nombre && $codigo) {
            try {
                // Verificar que no exista el código
                $query_check = "SELECT id FROM escuelas WHERE codigo = ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$codigo]);
                
                if ($stmt_check->fetch()) {
                    throw new Exception('Ya existe una escuela con ese código');
                }
                
                // Crear escuela
                $query_insert = "INSERT INTO escuelas (nombre, codigo, direccion, telefono, email, director_id) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert = $db->prepare($query_insert);
                $stmt_insert->execute([$nombre, $codigo, $direccion, $telefono, $email, $director_id]);
                
                $message = 'Escuela creada exitosamente';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Los campos obligatorios deben ser completados';
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $director_id = $_POST['director_id'] ?? '';
        
        // Convertir cadena vacía a NULL para director_id
        $director_id = ($director_id === '' || $director_id === 'null') ? null : $director_id;
        
        if ($id && $nombre && $codigo) {
            try {
                // Verificar que no exista el código en otra escuela
                $query_check = "SELECT id FROM escuelas WHERE codigo = ? AND id != ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$codigo, $id]);
                
                if ($stmt_check->fetch()) {
                    throw new Exception('Ya existe otra escuela con ese código');
                }
                
                // Actualizar escuela
                $query_update = "UPDATE escuelas SET nombre = ?, codigo = ?, direccion = ?, telefono = ?, email = ?, director_id = ? WHERE id = ?";
                $stmt_update = $db->prepare($query_update);
                $stmt_update->execute([$nombre, $codigo, $direccion, $telefono, $email, $director_id, $id]);
                
                $message = 'Escuela actualizada exitosamente';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Los campos obligatorios deben ser completados';
        }
    }
}

// Obtener directores disponibles
$query_directores = "SELECT u.id, u.nombres, u.apellidos 
                    FROM usuarios u 
                    JOIN roles r ON u.rol_id = r.id 
                    WHERE r.nombre = 'Director de Escuela' AND u.activo = 1 
                    ORDER BY u.nombres";
$stmt_directores = $db->prepare($query_directores);
$stmt_directores->execute();
$directores = $stmt_directores->fetchAll();

// Obtener lista de escuelas
$query_escuelas = "SELECT e.*, CONCAT(u.nombres, ' ', u.apellidos) as director_nombre 
                  FROM escuelas e 
                  LEFT JOIN usuarios u ON e.director_id = u.id 
                  WHERE e.activa = 1 
                  ORDER BY e.nombre";
$stmt_escuelas = $db->prepare($query_escuelas);
$stmt_escuelas->execute();
$escuelas = $stmt_escuelas->fetchAll();

include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-school text-blue-600 mr-3"></i>Gestión de Escuelas
            </h1>
            <p class="text-gray-600">Administra las escuelas del sistema</p>
        </div>
        <button onclick="openCreateModal()" class="btn-primary text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i>Nueva Escuela
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

<!-- Grid de escuelas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($escuelas as $escuela): ?>
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($escuela['nombre']); ?></h3>
                <p class="text-sm text-gray-600 mb-2">Código: <?php echo htmlspecialchars($escuela['codigo']); ?></p>
            </div>
            
        </div>
        
        <div class="space-y-2 text-sm">
            <?php if ($escuela['direccion']): ?>
            <div class="flex items-center text-gray-600">
                <i class="fas fa-map-marker-alt w-4 mr-2"></i>
                <span><?php echo htmlspecialchars($escuela['direccion']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($escuela['telefono']): ?>
            <div class="flex items-center text-gray-600">
                <i class="fas fa-phone w-4 mr-2"></i>
                <span><?php echo htmlspecialchars($escuela['telefono']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($escuela['email']): ?>
            <div class="flex items-center text-gray-600">
                <i class="fas fa-envelope w-4 mr-2"></i>
                <span><?php echo htmlspecialchars($escuela['email']); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="flex items-center text-gray-600">
                <i class="fas fa-user-tie w-4 mr-2"></i>
                <span>Director: <?php echo $escuela['director_nombre'] ? htmlspecialchars($escuela['director_nombre']) : 'No asignado'; ?></span>
            </div>
        </div>
        
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-1"></i>Activa
                </span>
                <div class="flex space-x-2">
                    <button onclick="viewSchool(<?php echo $escuela['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Ver detalles
                    </button>
                    <button onclick="editSchool(<?php echo $escuela['id']; ?>, '<?php echo addslashes($escuela['nombre']); ?>', '<?php echo addslashes($escuela['codigo']); ?>', '<?php echo addslashes($escuela['direccion'] ?? ''); ?>', '<?php echo addslashes($escuela['telefono'] ?? ''); ?>', '<?php echo addslashes($escuela['email'] ?? ''); ?>', '<?php echo $escuela['director_id'] ?? ''; ?>')" class="text-blue-600 hover:text-blue-800 text-sm font-medium ml-2">
                        Editar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal para crear escuela -->
<div id="createSchoolModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Crear Nueva Escuela</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="id" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Escuela *</label>
                    <input type="text" name="nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                    <input type="text" name="codigo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                <input type="text" name="direccion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                    <input type="text" name="telefono" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Director</label>
                <select name="director_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccionar director</option>
                    <?php foreach ($directores as $director): ?>
                    <option value="<?php echo $director['id']; ?>">
                        <?php echo htmlspecialchars($director['nombres'] . ' ' . $director['apellidos']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Puede asignar un director más tarde si no hay disponibles</p>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary px-6 py-2 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Crear Escuela
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function resetModal() {
    document.querySelector('form').reset();
    document.querySelector('input[name="action"]').value = 'create';
    document.querySelector('input[name="id"]').value = '';
    document.querySelector('#createSchoolModal h3').textContent = 'Crear Nueva Escuela';
    document.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save mr-2"></i>Crear Escuela';
}

function openCreateModal() {
    resetModal();
    document.getElementById('createSchoolModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createSchoolModal').classList.add('hidden');
}

function viewSchool(schoolId) {
    window.location.href = `escuela-detalle.php?id=${schoolId}`;
}

// Cerrar modal al hacer click fuera
document.getElementById('createSchoolModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateModal();
    }
});

function editSchool(id, nombre, codigo, direccion, telefono, email, directorId) {
    // Limpiar formulario primero
    document.querySelector('form').reset();
    
    // IMPORTANTE: Cambiar la acción a 'edit'
    document.querySelector('input[name="action"]').value = 'edit';
    document.querySelector('input[name="id"]').value = id;
    document.querySelector('input[name="nombre"]').value = nombre || '';
    document.querySelector('input[name="codigo"]').value = codigo || '';
    document.querySelector('input[name="direccion"]').value = direccion || '';
    document.querySelector('input[name="telefono"]').value = telefono || '';
    document.querySelector('input[name="email"]').value = email || '';
    
    // Manejar director_id correctamente
    if (directorId && directorId !== '' && directorId !== 'null') {
        document.querySelector('select[name="director_id"]').value = directorId;
    } else {
        document.querySelector('select[name="director_id"]').value = '';
    }
    
    // Cambiar el título y botón del modal
    document.querySelector('#createSchoolModal h3').textContent = 'Editar Escuela';
    document.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save mr-2"></i>Actualizar Escuela';
    
    // Abrir el modal
    document.getElementById('createSchoolModal').classList.remove('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>
