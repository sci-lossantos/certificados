<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Verificar si hay sesión activa
if (!$auth->isLoggedIn()) {
    echo "<h2>❌ No hay sesión activa</h2>";
    echo "<p>Por favor <a href='login.php'>inicia sesión</a> primero.</p>";
    exit();
}

// Verificar rol del usuario
$user_role = $auth->getUserRole();
$roles_permitidos = ['Administrador General', 'Director de Escuela', 'Educación DNBC', 'Dirección Nacional'];

if (!in_array($user_role, $roles_permitidos)) {
    echo "<h2>❌ Acceso Denegado</h2>";
    echo "<p>Tu rol actual: <strong>" . htmlspecialchars($user_role) . "</strong></p>";
    echo "<p>Roles permitidos: " . implode(', ', $roles_permitidos) . "</p>";
    echo "<p><a href='dashboard.php'>Volver al Dashboard</a></p>";
    exit();
}

$message = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar si existe la tabla
        $query_check_table = "SHOW TABLES LIKE 'configuracion_certificados'";
        $result = $db->query($query_check_table);
        
        if ($result->rowCount() == 0) {
            // Crear tabla si no existe
            $create_table = "
                CREATE TABLE configuracion_certificados (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    escuela_id INT NULL,
                    titulo_principal VARCHAR(255) DEFAULT 'DIRECCIÓN NACIONAL DE BOMBEROS COLOMBIA',
                    subtitulo_certificado VARCHAR(255) DEFAULT 'CERTIFICADO DE PARTICIPACIÓN',
                    texto_certifica VARCHAR(500) DEFAULT 'Certifica que:',
                    texto_aprobacion VARCHAR(500) DEFAULT 'Asistió y aprobó los requisitos del Curso:',
                    texto_intensidad VARCHAR(500) DEFAULT 'Con una duración de: {horas} horas académicas',
                    texto_realizacion VARCHAR(500) DEFAULT 'Realizado en {año}',
                    numero_registro VARCHAR(100) DEFAULT 'REG-001',
                    formato_consecutivo VARCHAR(100) DEFAULT '{registro}-{orden}',
                    mostrar_numero_consecutivo BOOLEAN DEFAULT TRUE,
                    texto_numero_consecutivo VARCHAR(255) DEFAULT 'Certificado No. {consecutivo}',
                    mostrar_numero_acta BOOLEAN DEFAULT TRUE,
                    texto_numero_acta VARCHAR(255) DEFAULT 'Acta No. {numero_acta} del {fecha_acta}',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            $db->exec($create_table);
            $message = "Tabla configuracion_certificados creada exitosamente";
        }
        
        // Obtener datos del formulario
        $escuela_id = $_POST['escuela_id'] ?? 1;
        $titulo_principal = $_POST['titulo_principal'] ?? 'DIRECCIÓN NACIONAL DE BOMBEROS COLOMBIA';
        $subtitulo_certificado = $_POST['subtitulo_certificado'] ?? 'CERTIFICADO DE PARTICIPACIÓN';
        $numero_registro = $_POST['numero_registro'] ?? 'REG-001';
        
        // Verificar si ya existe configuración para esta escuela
        $query_check = "SELECT id FROM configuracion_certificados WHERE escuela_id = ?";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->execute([$escuela_id]);
        
        if ($stmt_check->fetch()) {
            // Actualizar
            $query_update = "UPDATE configuracion_certificados SET 
                            titulo_principal = ?, 
                            subtitulo_certificado = ?, 
                            numero_registro = ?
                            WHERE escuela_id = ?";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->execute([$titulo_principal, $subtitulo_certificado, $numero_registro, $escuela_id]);
            $message = "Configuración actualizada exitosamente";
        } else {
            // Insertar nueva
            $query_insert = "INSERT INTO configuracion_certificados (escuela_id, titulo_principal, subtitulo_certificado, numero_registro) 
                            VALUES (?, ?, ?, ?)";
            $stmt_insert = $db->prepare($query_insert);
            $stmt_insert->execute([$escuela_id, $titulo_principal, $subtitulo_certificado, $numero_registro]);
            $message = "Nueva configuración creada exitosamente";
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Obtener configuración actual
$configuracion = null;
try {
    $query = "SELECT * FROM configuracion_certificados WHERE escuela_id = 1 LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $configuracion = $stmt->fetch();
} catch (Exception $e) {
    // Tabla no existe aún
}

$page_title = 'Configuración de Certificados - Simple';
include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">
        <i class="fas fa-certificate text-blue-600 mr-3"></i>Configuración de Certificados (Versión Simple)
    </h1>
    <p class="text-gray-600">Usuario: <?php echo htmlspecialchars($auth->getUserName()); ?> | Rol: <?php echo htmlspecialchars($user_role); ?></p>
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

<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-6">Configuración Básica</h3>
    
    <form method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Título Principal</label>
                <input type="text" name="titulo_principal" 
                       value="<?php echo htmlspecialchars($configuracion['titulo_principal'] ?? 'DIRECCIÓN NACIONAL DE BOMBEROS COLOMBIA'); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtítulo del Certificado</label>
                <input type="text" name="subtitulo_certificado" 
                       value="<?php echo htmlspecialchars($configuracion['subtitulo_certificado'] ?? 'CERTIFICADO DE PARTICIPACIÓN'); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Número de Registro Base</label>
                <input type="text" name="numero_registro" 
                       value="<?php echo htmlspecialchars($configuracion['numero_registro'] ?? 'REG-001'); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Escuela ID</label>
                <input type="number" name="escuela_id" 
                       value="1" readonly
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-save mr-2"></i>Guardar Configuración
            </button>
        </div>
    </form>
</div>

<div class="mt-6 bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Acciones de Prueba</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="debug-auth-config.php" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded-lg text-center">
            <i class="fas fa-bug mr-2"></i>Debug Autenticación
        </a>
        
        <a href="preview-certificate-simple.php" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg text-center">
            <i class="fas fa-eye mr-2"></i>Vista Previa Simple
        </a>
        
        <a href="configuracion-certificados.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg text-center">
            <i class="fas fa-cog mr-2"></i>Configuración Completa
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
