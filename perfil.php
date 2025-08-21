<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$page_title = 'Mi Perfil';
$message = '';
$error = '';

// Obtener información del usuario actual
$user_id = $_SESSION['user_id'];
$query_user = "SELECT u.*, r.nombre as rol_nombre 
               FROM usuarios u 
               JOIN roles r ON u.rol_id = r.id 
               WHERE u.id = ?";
$stmt_user = $db->prepare($query_user);
$stmt_user->execute([$user_id]);
$usuario = $stmt_user->fetch();

if (!$usuario) {
    header("Location: logout.php");
    exit();
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $institucion = trim($_POST['institucion'] ?? '');
        $genero = $_POST['genero'] ?? '';
        
        if ($nombres && $apellidos) {
            try {
                $db->beginTransaction();
                
                // Manejar subida de fotografía
                $fotografia = $usuario['fotografia']; // Mantener foto actual por defecto
                
                if (isset($_FILES['fotografia']) && $_FILES['fotografia']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/fotos_usuarios/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['fotografia']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        if ($_FILES['fotografia']['size'] <= 2 * 1024 * 1024) { // 2MB máximo
                            $filename = 'usuario_' . $user_id . '_' . time() . '.' . $file_extension;
                            $upload_path = $upload_dir . $filename;
                            
                            if (move_uploaded_file($_FILES['fotografia']['tmp_name'], $upload_path)) {
                                // Eliminar foto anterior si existe
                                if ($fotografia && file_exists($fotografia)) {
                                    unlink($fotografia);
                                }
                                $fotografia = $upload_path;
                            }
                        } else {
                            throw new Exception('El archivo es demasiado grande. Máximo 2MB.');
                        }
                    } else {
                        throw new Exception('Formato de imagen no válido. Use JPG, PNG o GIF.');
                    }
                }
                
                // Actualizar perfil
                $query_update = "UPDATE usuarios SET nombres = ?, apellidos = ?, telefono = ?, institucion = ?, genero = ?, fotografia = ?, updated_at = NOW() WHERE id = ?";
                $stmt_update = $db->prepare($query_update);
                $result = $stmt_update->execute([$nombres, $apellidos, $telefono, $institucion, $genero, $fotografia, $user_id]);
                
                if ($result) {
                    // Actualizar sesión
                    $_SESSION['user_name'] = $nombres . ' ' . $apellidos;
                    
                    $db->commit();
                    $message = 'Perfil actualizado exitosamente';
                    
                    // Recargar datos del usuario
                    $stmt_user->execute([$user_id]);
                    $usuario = $stmt_user->fetch();
                } else {
                    throw new Exception('Error al actualizar el perfil');
                }
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        } else {
            $error = 'Los campos nombres y apellidos son obligatorios';
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($current_password && $new_password && $confirm_password) {
            try {
                // Verificar contraseña actual
                if (!password_verify($current_password, $usuario['password_hash'])) {
                    throw new Exception('La contraseña actual es incorrecta');
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception('Las contraseñas nuevas no coinciden');
                }
                
                if (strlen($new_password) < 6) {
                    throw new Exception('La nueva contraseña debe tener al menos 6 caracteres');
                }
                
                // Actualizar contraseña
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $query_password = "UPDATE usuarios SET password_hash = ?, updated_at = NOW() WHERE id = ?";
                $stmt_password = $db->prepare($query_password);
                $result = $stmt_password->execute([$new_password_hash, $user_id]);
                
                if ($result) {
                    $message = 'Contraseña actualizada exitosamente';
                } else {
                    throw new Exception('Error al actualizar la contraseña');
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Todos los campos de contraseña son obligatorios';
        }
    }
}

// Obtener estadísticas según el rol
$estadisticas = [];
try {
    if ($usuario['rol_nombre'] === 'Coordinador') {
        // Estadísticas para coordinador
        $query_stats = "SELECT 
                            (SELECT COUNT(*) FROM cursos WHERE coordinador_id = ?) as total_cursos,
                            (SELECT COUNT(*) FROM matriculas m JOIN cursos c ON m.curso_id = c.id WHERE c.coordinador_id = ?) as total_matriculados,
                            (SELECT COUNT(*) FROM matriculas m JOIN cursos c ON m.curso_id = c.id WHERE c.coordinador_id = ? AND m.aprobado = 1) as total_aprobados";
        $stmt_stats = $db->prepare($query_stats);
        $stmt_stats->execute([$user_id, $user_id, $user_id]);
        $estadisticas = $stmt_stats->fetch();
    } elseif ($usuario['rol_nombre'] === 'Instructor') {
        // Estadísticas para instructor
        $query_stats = "SELECT 
                            (SELECT COUNT(*) FROM instructores_cursos WHERE instructor_id = ?) as total_cursos_instructor";
        $stmt_stats = $db->prepare($query_stats);
        $stmt_stats->execute([$user_id]);
        $estadisticas = $stmt_stats->fetch();
    }
} catch (Exception $e) {
    // Si hay error en estadísticas, continuar sin ellas
    $estadisticas = [];
}

include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-user-circle text-red-600 mr-3"></i>Mi Perfil
            </h1>
            <p class="text-gray-600">Gestiona tu información personal y configuración de cuenta</p>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4" id="success-message">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
</div>
<script>
    // Auto-hide success message after 5 seconds
    setTimeout(function() {
        const successMsg = document.getElementById('success-message');
        if (successMsg) {
            successMsg.style.display = 'none';
        }
    }, 5000);
</script>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4" id="error-message">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
</div>
<script>
    // Auto-hide error message after 8 seconds
    setTimeout(function() {
        const errorMsg = document.getElementById('error-message');
        if (errorMsg) {
            errorMsg.style.display = 'none';
        }
    }, 8000);
</script>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Información del perfil -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Información personal -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-user text-red-600 mr-2"></i>Información Personal
            </h3>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4" id="profile-form">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="flex items-center space-x-6 mb-6">
                    <div class="flex-shrink-0">
                        <?php if ($usuario['fotografia'] && file_exists($usuario['fotografia'])): ?>
                            <img src="<?php echo htmlspecialchars($usuario['fotografia']); ?>" alt="Foto de perfil" class="w-24 h-24 rounded-full object-cover border-4 border-gray-200 shadow-lg" id="profile-image">
                        <?php else: ?>
                            <div class="w-24 h-24 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center text-white font-bold text-2xl border-4 border-gray-200 shadow-lg" id="profile-initials">
                                <?php echo strtoupper(substr($usuario['nombres'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cambiar Fotografía</label>
                        <input type="file" name="fotografia" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" id="photo-input">
                        <p class="text-xs text-gray-500 mt-1">Formatos permitidos: JPG, PNG, GIF. Máximo 2MB.</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombres *</label>
                        <input type="text" name="nombres" value="<?php echo htmlspecialchars($usuario['nombres']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" maxlength="100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Apellidos *</label>
                        <input type="text" name="apellidos" value="<?php echo htmlspecialchars($usuario['apellidos']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" maxlength="100">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cédula</label>
                        <input type="text" value="<?php echo htmlspecialchars($usuario['cedula']); ?>" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500">
                        <p class="text-xs text-gray-500 mt-1">La cédula no se puede modificar</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500">
                        <p class="text-xs text-gray-500 mt-1">El email no se puede modificar</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" maxlength="20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
                        <select name="genero" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="">Seleccionar</option>
                            <option value="M" <?php echo ($usuario['genero'] === 'M') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="F" <?php echo ($usuario['genero'] === 'F') ? 'selected' : ''; ?>>Femenino</option>
                            <option value="Otro" <?php echo ($usuario['genero'] === 'Otro') ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Institución</label>
                    <input type="text" name="institucion" value="<?php echo htmlspecialchars($usuario['institucion'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" maxlength="200">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rol</label>
                    <input type="text" value="<?php echo htmlspecialchars($usuario['rol_nombre']); ?>" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500">
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg font-semibold transition-all hover:transform hover:scale-105" id="save-profile-btn">
                        <i class="fas fa-save mr-2"></i>Actualizar Perfil
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Cambiar contraseña -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-lock text-red-600 mr-2"></i>Cambiar Contraseña
            </h3>
            
            <form method="POST" class="space-y-4" id="password-form">
                <input type="hidden" name="action" value="change_password">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña Actual *</label>
                    <input type="password" name="current_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" id="current-password">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña *</label>
                    <input type="password" name="new_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" minlength="6" id="new-password">
                    <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Nueva Contraseña *</label>
                    <input type="password" name="confirm_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" id="confirm-password">
                    <div id="password-match-message" class="text-xs mt-1"></div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-all hover:transform hover:scale-105" id="change-password-btn">
                        <i class="fas fa-key mr-2"></i>Cambiar Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Panel lateral -->
    <div class="space-y-6">
        <!-- Información de la cuenta -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-green-600 mr-2"></i>Información de la Cuenta
            </h3>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Estado:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Activo
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Miembro desde:</span>
                    <span class="text-gray-900"><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Última actualización:</span>
                    <span class="text-gray-900"><?php echo date('d/m/Y H:i', strtotime($usuario['updated_at'])); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas según el rol -->
        <?php if (!empty($estadisticas)): ?>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-bar text-purple-600 mr-2"></i>Mis Estadísticas
            </h3>
            
            <div class="space-y-3">
                <?php if ($usuario['rol_nombre'] === 'Coordinador'): ?>
                    <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                        <span class="text-blue-800 font-medium">Cursos Coordinados</span>
                        <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full font-bold"><?php echo $estadisticas['total_cursos'] ?? 0; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                        <span class="text-green-800 font-medium">Total Matriculados</span>
                        <span class="bg-green-200 text-green-800 px-3 py-1 rounded-full font-bold"><?php echo $estadisticas['total_matriculados'] ?? 0; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                        <span class="text-purple-800 font-medium">Total Aprobados</span>
                        <span class="bg-purple-200 text-purple-800 px-3 py-1 rounded-full font-bold"><?php echo $estadisticas['total_aprobados'] ?? 0; ?></span>
                    </div>
                <?php elseif ($usuario['rol_nombre'] === 'Instructor'): ?>
                    <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                        <span class="text-orange-800 font-medium">Cursos como Instructor</span>
                        <span class="bg-orange-200 text-orange-800 px-3 py-1 rounded-full font-bold"><?php echo $estadisticas['total_cursos_instructor'] ?? 0; ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Acciones rápidas -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-bolt text-yellow-600 mr-2"></i>Acciones Rápidas
            </h3>
            
            <div class="space-y-2">
                <?php if (in_array($usuario['rol_nombre'], ['Coordinador', 'Instructor'])): ?>
                <a href="configurar-firma.php" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-signature mr-2 text-red-600"></i>Configurar Firma Digital
                </a>
                <?php endif; ?>
                
                <?php if ($usuario['rol_nombre'] === 'Coordinador'): ?>
                <a href="cursos.php" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-graduation-cap mr-2 text-green-600"></i>Mis Cursos
                </a>
                <a href="calificaciones.php" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-star mr-2 text-yellow-600"></i>Calificaciones
                </a>
                <?php endif; ?>
                
                <a href="documentos.php" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-file-alt mr-2 text-purple-600"></i>Mis Documentos
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Validación de contraseñas en tiempo real
document.getElementById('confirm-password').addEventListener('input', function() {
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = this.value;
    const messageDiv = document.getElementById('password-match-message');
    
    if (confirmPassword.length > 0) {
        if (newPassword === confirmPassword) {
            messageDiv.textContent = '✓ Las contraseñas coinciden';
            messageDiv.className = 'text-xs mt-1 text-green-600';
        } else {
            messageDiv.textContent = '✗ Las contraseñas no coinciden';
            messageDiv.className = 'text-xs mt-1 text-red-600';
        }
    } else {
        messageDiv.textContent = '';
    }
});

// Preview de imagen antes de subir
document.getElementById('photo-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const profileImage = document.getElementById('profile-image');
            const profileInitials = document.getElementById('profile-initials');
            
            if (profileImage) {
                profileImage.src = e.target.result;
            } else if (profileInitials) {
                // Reemplazar las iniciales con la imagen
                profileInitials.outerHTML = `<img src="${e.target.result}" alt="Foto de perfil" class="w-24 h-24 rounded-full object-cover border-4 border-gray-200 shadow-lg" id="profile-image">`;
            }
        };
        reader.readAsDataURL(file);
    }
});

// Confirmación antes de enviar formularios
document.getElementById('profile-form').addEventListener('submit', function(e) {
    const btn = document.getElementById('save-profile-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
    btn.disabled = true;
});

document.getElementById('password-form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    const btn = document.getElementById('change-password-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Cambiando...';
    btn.disabled = true;
});
</script>

<?php include 'includes/footer.php'; ?>
