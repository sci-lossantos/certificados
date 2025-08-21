<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Solo Administrador General puede gestionar usuarios
$auth->requireRole(['Administrador General', 'Escuela']);

$page_title = 'Gestión de Usuarios';

// Determinar qué roles puede gestionar según el usuario actual
$roles_permitidos = [];
$puede_crear = false;

if ($_SESSION['user_role'] === 'Administrador General') {
    $roles_permitidos = ['Escuela', 'Dirección Nacional', 'Educación DNBC'];
    $puede_crear = true;
    $titulo_seccion = 'Administra los usuarios del sistema';
    $info_permisos = 'Puedes gestionar usuarios: Escuela, Dirección Nacional, Educación DNBC';
} elseif ($_SESSION['user_role'] === 'Escuela') {
    $roles_permitidos = ['Coordinador', 'Director de Escuela', 'Participante'];
    $puede_crear = true;
    $titulo_seccion = 'Administra los usuarios de tu escuela';
    $info_permisos = 'Puedes gestionar usuarios: Coordinador, Director de Escuela, Participante';
}

$message = '';
$error = '';

// Función para procesar la carga de fotografía
function procesarFotografia($file, $usuario_id) {
    if (!isset($file['fotografia']) || $file['fotografia']['error'] == UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['fotografia']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir la imagen: ' . $file['fotografia']['error']);
    }

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    $file_type = $file['fotografia']['type'];
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten JPG, JPEG y PNG.');
    }

    if ($file['fotografia']['size'] > 2 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande. Máximo 2MB.');
    }

    $upload_dir = 'uploads/fotos_usuarios/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_extension = pathinfo($file['fotografia']['name'], PATHINFO_EXTENSION);
    $file_name = 'usuario_' . $usuario_id . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;

    if (!move_uploaded_file($file['fotografia']['tmp_name'], $file_path)) {
        throw new Exception('Error al guardar la imagen.');
    }

    return $file_path;
}

// Procesar formulario
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol_nombre = $_POST['rol'] ?? '';
        $telefono = trim($_POST['telefono'] ?? '');
        $institucion = trim($_POST['institucion'] ?? '');
        $genero = $_POST['genero'] ?? '';
        
        if ($nombres && $apellidos && $cedula && $email && $password && $rol_nombre) {
            try {
                // Verificar que el rol sea permitido para el usuario actual
                if (!in_array($rol_nombre, $roles_permitidos)) {
                    throw new Exception('No tienes permisos para crear usuarios con este rol');
                }
                
                $query_rol = "SELECT id FROM roles WHERE nombre = ?";
                $stmt_rol = $db->prepare($query_rol);
                $stmt_rol->execute([$rol_nombre]);
                $rol = $stmt_rol->fetch();
                
                if (!$rol) {
                    throw new Exception('Rol no válido');
                }
                
                $query_check = "SELECT id FROM usuarios WHERE cedula = ? OR email = ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$cedula, $email]);
                
                if ($stmt_check->fetch()) {
                    throw new Exception('Ya existe un usuario con esa cédula o email');
                }
                
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $query_insert = "INSERT INTO usuarios (nombres, apellidos, cedula, email, password_hash, rol_id, telefono, institucion, genero) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $db->prepare($query_insert);
                $stmt_insert->execute([$nombres, $apellidos, $cedula, $email, $password_hash, $rol['id'], $telefono, $institucion, $genero]);

                $usuario_id = $db->lastInsertId();
                
                // Procesar fotografía si se subió
                try {
                    $fotografia = procesarFotografia($_FILES, $usuario_id);
                    if ($fotografia) {
                        $query_update_foto = "UPDATE usuarios SET fotografia = ? WHERE id = ?";
                        $stmt_update_foto = $db->prepare($query_update_foto);
                        $stmt_update_foto->execute([$fotografia, $usuario_id]);
                    }
                } catch (Exception $e) {
                    error_log('Error al procesar fotografía: ' . $e->getMessage());
                }
                
                $message = 'Usuario creado exitosamente';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Todos los campos obligatorios deben ser completados';
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol_nombre = $_POST['rol'] ?? '';
        $telefono = trim($_POST['telefono'] ?? '');
        $institucion = trim($_POST['institucion'] ?? '');
        $genero = $_POST['genero'] ?? '';
        
        if ($id && $nombres && $apellidos && $cedula && $email && $rol_nombre) {
            try {
                // Verificar que el rol sea permitido para el usuario actual
                if (!in_array($rol_nombre, $roles_permitidos)) {
                    throw new Exception('No tienes permisos para asignar este rol');
                }
                
                $query_rol = "SELECT id FROM roles WHERE nombre = ?";
                $stmt_rol = $db->prepare($query_rol);
                $stmt_rol->execute([$rol_nombre]);
                $rol = $stmt_rol->fetch();
                
                if (!$rol) {
                    throw new Exception('Rol no válido');
                }
                
                $query_check = "SELECT id FROM usuarios WHERE (cedula = ? OR email = ?) AND id != ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$cedula, $email, $id]);
                
                if ($stmt_check->fetch()) {
                    throw new Exception('Ya existe otro usuario con esa cédula o email');
                }
                
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $query_update = "UPDATE usuarios SET nombres = ?, apellidos = ?, cedula = ?, email = ?, password_hash = ?, rol_id = ?, telefono = ?, institucion = ?, genero = ? WHERE id = ?";
                    $stmt_update = $db->prepare($query_update);
                    $stmt_update->execute([$nombres, $apellidos, $cedula, $email, $password_hash, $rol['id'], $telefono, $institucion, $genero, $id]);
                } else {
                    $query_update = "UPDATE usuarios SET nombres = ?, apellidos = ?, cedula = ?, email = ?, rol_id = ?, telefono = ?, institucion = ?, genero = ? WHERE id = ?";
                    $stmt_update = $db->prepare($query_update);
                    $stmt_update->execute([$nombres, $apellidos, $cedula, $email, $rol['id'], $telefono, $institucion, $genero, $id]);
                }

                // Procesar fotografía si se subió
                try {
                    $fotografia = procesarFotografia($_FILES, $id);
                    if ($fotografia) {
                        $query_update_foto = "UPDATE usuarios SET fotografia = ? WHERE id = ?";
                        $stmt_update_foto = $db->prepare($query_update_foto);
                        $stmt_update_foto->execute([$fotografia, $id]);
                    }
                } catch (Exception $e) {
                    error_log('Error al procesar fotografía: ' . $e->getMessage());
                }
                
                $message = 'Usuario actualizado exitosamente';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Todos los campos obligatorios deben ser completados';
        }
    } elseif ($action === 'toggle_status') {
        // Cambiar estado activo/inactivo en lugar de eliminar
        $id = $_POST['id'] ?? '';
        $nuevo_estado = $_POST['nuevo_estado'] ?? '';
        
        if ($id && in_array($nuevo_estado, ['0', '1'])) {
            try {
                $query_update = "UPDATE usuarios SET activo = ? WHERE id = ?";
                $stmt_update = $db->prepare($query_update);
                $stmt_update->execute([$nuevo_estado, $id]);
                
                $estado_texto = $nuevo_estado == '1' ? 'activado' : 'desactivado';
                $message = "Usuario {$estado_texto} exitosamente";
            } catch (Exception $e) {
                $error = 'Error al cambiar el estado del usuario: ' . $e->getMessage();
            }
        }
    }
}

// Construir la consulta según el rol del usuario
if ($_SESSION['user_role'] === 'Administrador General') {
    $query_usuarios = "SELECT u.*, r.nombre as rol_nombre 
                       FROM usuarios u 
                       JOIN roles r ON u.rol_id = r.id 
                       WHERE r.nombre IN ('Escuela', 'Dirección Nacional', 'Educación DNBC')
                       ORDER BY u.created_at DESC";
} elseif ($_SESSION['user_role'] === 'Escuela') {
    // Los usuarios de escuela solo ven usuarios de su propia escuela
    $escuela_id = $auth->getUserEscuelaId();
    $query_usuarios = "SELECT u.*, r.nombre as rol_nombre 
                       FROM usuarios u 
                       JOIN roles r ON u.rol_id = r.id 
                       WHERE r.nombre IN ('Coordinador', 'Director de Escuela', 'Participante')
                       AND (u.escuela_id = ? OR u.escuela_id IS NULL)
                       ORDER BY u.created_at DESC";
}

$stmt_usuarios = $db->prepare($query_usuarios);
if ($_SESSION['user_role'] === 'Escuela') {
    $escuela_id = $auth->getUserEscuelaId();
    $stmt_usuarios->execute([$escuela_id]);
} else {
    $stmt_usuarios->execute();
}
$usuarios = $stmt_usuarios->fetchAll();

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary mb-2">Gestión de Usuarios</h1>
            <p class="text-text-secondary"><?php echo $titulo_seccion; ?></p>
            <p class="text-sm text-accent-red mt-1">
                <i class="fas fa-info-circle mr-1"></i>
                <?php echo $info_permisos; ?>
            </p>
        </div>
        <?php if ($puede_crear): ?>
        <button onclick="openCreateModal()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors shadow-md">
            <i class="fas fa-plus mr-2"></i>Nuevo Usuario
        </button>
        <?php endif; ?>
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

<!-- Users Table -->
<div class="card-shadow bg-card-bg rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-text-primary">
            Lista de Usuarios 
            <span class="text-sm font-normal text-text-secondary">(<?php echo implode(', ', $roles_permitidos); ?>)</span>
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($usuarios) > 0): ?>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cédula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Registro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($usuarios as $usuario): ?>
                <tr class="hover:bg-gray-50 transition-colors <?php echo $usuario['activo'] ? '' : 'opacity-60'; ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <?php if (!empty($usuario['fotografia']) && file_exists($usuario['fotografia'])): ?>
                                <div class="w-10 h-10 rounded-full overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($usuario['fotografia']); ?>" alt="Foto de perfil" class="w-full h-full object-cover">
                                </div>
                            <?php else: ?>
                                <div class="w-10 h-10 bg-accent-red rounded-full flex items-center justify-center text-white font-semibold">
                                    <?php echo strtoupper(substr($usuario['nombres'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-text-primary">
                                    <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>
                                </div>
                                <div class="text-sm text-text-secondary">
                                    <?php echo htmlspecialchars($usuario['telefono'] ?? 'Sin teléfono'); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                        <?php echo htmlspecialchars($usuario['cedula']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                        <?php echo htmlspecialchars($usuario['email']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php
                        $color_class = 'bg-blue-100 text-blue-800';
                        if ($usuario['rol_nombre'] === 'Dirección Nacional') $color_class = 'bg-purple-100 text-purple-800';
                        elseif ($usuario['rol_nombre'] === 'Educación DNBC') $color_class = 'bg-green-100 text-green-800';
                        elseif ($usuario['rol_nombre'] === 'Escuela') $color_class = 'bg-yellow-100 text-yellow-800';
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $color_class; ?>">
                            <?php echo htmlspecialchars($usuario['rol_nombre']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($usuario['activo']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Activo
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Inactivo
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                        <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="editUser(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nombres']); ?>', '<?php echo addslashes($usuario['apellidos']); ?>', '<?php echo addslashes($usuario['cedula']); ?>', '<?php echo addslashes($usuario['email']); ?>', '<?php echo addslashes($usuario['rol_nombre']); ?>', '<?php echo addslashes($usuario['telefono'] ?? ''); ?>', '<?php echo addslashes($usuario['institucion'] ?? ''); ?>', '<?php echo addslashes($usuario['genero'] ?? ''); ?>', '<?php echo addslashes($usuario['fotografia'] ?? ''); ?>')" 
                                class="text-blue-600 hover:text-blue-900 mr-3" title="Editar usuario">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <?php if ($usuario['activo']): ?>
                            <button onclick="if(confirm('¿Estás seguro de que deseas desactivar al usuario <?php echo addslashes($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>?')) { toggleUserStatus(<?php echo $usuario['id']; ?>, 0); }" 
                                    class="text-yellow-600 hover:text-yellow-700 mr-3" title="Desactivar usuario">
                                <i class="fas fa-user-slash"></i>
                            </button>
                        <?php else: ?>
                            <button onclick="if(confirm('¿Estás seguro de que deseas activar al usuario <?php echo addslashes($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>?')) { toggleUserStatus(<?php echo $usuario['id']; ?>, 1); }" 
                                    class="text-green-600 hover:text-green-900 mr-3" title="Activar usuario">
                                <i class="fas fa-user-check"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="px-6 py-12 text-center">
            <i class="fas fa-users text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-text-primary mb-2">No hay usuarios registrados</h3>
            <p class="text-text-secondary mb-4">
                Aún no has creado usuarios de tipo <?php echo implode(', ', $roles_permitidos); ?>.
            </p>
            <button onclick="openCreateModal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow-md">
                <i class="fas fa-plus mr-2"></i>Crear Primer Usuario
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para crear/editar usuario -->
<div id="createUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-semibold text-text-primary">Crear Nuevo Usuario</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4" enctype="multipart/form-data" id="userForm">
            <input type="hidden" name="action" value="create" id="formAction">
            <input type="hidden" name="id" id="editUserId" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Nombres *</label>
                    <input type="text" name="nombres" id="nombres" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-red focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Apellidos *</label>
                    <input type="text" name="apellidos" id="apellidos" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-red focus:border-transparent">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Cédula *</label>
                    <input type="text" name="cedula" id="cedula" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-red focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Email *</label>
                    <input type="email" name="email" id="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-red focus:border-transparent">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Contraseña <span id="passwordRequired">*</span></label>
                    <input type="password" name="password" id="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-red focus:border-transparent">
                    <p class="text-text-secondary text-xs mt-1" id="passwordHelp">Mínimo 6 caracteres</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Rol *</label>
                    <select name="rol" id="rol" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-red focus:border-transparent">
                        <option value="">Seleccionar rol</option>
                        <?php foreach ($roles_permitidos as $rol): ?>
                            <option value="<?php echo htmlspecialchars($rol); ?>"><?php echo htmlspecialchars($rol); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Teléfono</label>
                    <input type="text" name="telefono" id="telefono" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-red focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Género</label>
                    <select name="genero" id="genero" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-red focus:border-transparent">
                        <option value="">Seleccionar</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">Institución</label>
                <input type="text" name="institucion" id="institucion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-red focus:border-transparent">
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-text-primary mb-2">Fotografía</label>
                <div class="flex items-center">
                    <div id="foto-preview" class="mr-4 w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                        <i class="fas fa-user text-gray-400 text-3xl"></i>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="fotografia" id="fotografia" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-red focus:border-transparent">
                        <p class="text-text-secondary text-xs mt-1">Formatos permitidos: JPG, JPEG, PNG. Máximo 2MB.</p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" id="submitBtn" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg shadow-md">
                    <i class="fas fa-save mr-2"></i>Crear Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Formulario oculto para cambiar estado -->
<form id="toggleStatusForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="id" id="toggleUserId">
    <input type="hidden" name="nuevo_estado" id="toggleNewStatus">
</form>

<script>
function openCreateModal() {
    resetForm();
    document.getElementById('createUserModal').classList.remove('hidden');
}

function resetForm() {
    document.getElementById('userForm').reset();
    document.getElementById('modalTitle').textContent = 'Crear Nuevo Usuario';
    document.getElementById('formAction').value = 'create';
    document.getElementById('editUserId').value = '';
    
    // Configurar para crear usuario
    document.getElementById('password').required = true;
    document.getElementById('passwordRequired').textContent = '*';
    document.getElementById('passwordHelp').textContent = 'Mínimo 6 caracteres';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Crear Usuario';
    
    // Resetear previsualización de foto
    document.getElementById('foto-preview').innerHTML = '<i class="fas fa-user text-gray-400 text-3xl"></i>';
}

function closeCreateModal() {
    document.getElementById('createUserModal').classList.add('hidden');
}

function editUser(id, nombres, apellidos, cedula, email, rol, telefono, institucion, genero, fotografia) {
    document.getElementById('createUserModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Editar Usuario';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('editUserId').value = id;

    // Llenar campos
    document.getElementById('nombres').value = nombres || '';
    document.getElementById('apellidos').value = apellidos || '';
    document.getElementById('cedula').value = cedula || '';
    document.getElementById('email').value = email || '';
    document.getElementById('telefono').value = telefono || '';
    document.getElementById('institucion').value = institucion || '';
    document.getElementById('genero').value = genero || '';
    document.getElementById('rol').value = rol || '';

    // Configurar para editar usuario
    document.getElementById('password').required = false;
    document.getElementById('passwordRequired').textContent = '';
    document.getElementById('passwordHelp').textContent = 'Dejar en blanco para no cambiar la contraseña';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Actualizar Usuario';

    // Mostrar la fotografía si existe
    if (fotografia) {
        document.getElementById('foto-preview').innerHTML = `<img src="${fotografia}" class="w-full h-full object-cover">`;
    } else {
        document.getElementById('foto-preview').innerHTML = '<i class="fas fa-user text-gray-400 text-3xl"></i>';
    }
}

function toggleUserStatus(userId, newStatus) {
    document.getElementById('toggleUserId').value = userId;
    document.getElementById('toggleNewStatus').value = newStatus;
    document.getElementById('toggleStatusForm').submit();
}

// Previsualizar imagen antes de subir
document.getElementById('fotografia').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('foto-preview').innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
        }
        reader.readAsDataURL(file);
    } else {
        document.getElementById('foto-preview').innerHTML = '<i class="fas fa-user text-gray-400 text-3xl"></i>';
    }
});

// Cerrar modal al hacer click fuera
document.getElementById('createUserModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
