<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$page_title = 'Dashboard';

// Obtener estadísticas según el rol
$stats = [];

try {
    if ($_SESSION['user_role'] === 'Administrador General') {
        // Estadísticas para administrador
        $query = "SELECT 
                (SELECT COUNT(*) FROM usuarios WHERE activo = 1) as total_usuarios,
                (SELECT COUNT(*) FROM escuelas WHERE activa = 1) as total_escuelas,
                (SELECT COUNT(*) FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE r.nombre = 'Escuela' AND u.activo = 1) as usuarios_escuela,
                (SELECT COUNT(*) FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE r.nombre = 'Dirección Nacional' AND u.activo = 1) as usuarios_direccion";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats = $stmt->fetch();
    } elseif ($_SESSION['user_role'] === 'Coordinador') {
        // Estadísticas para coordinador
        $query = "SELECT 
                    (SELECT COUNT(*) FROM cursos WHERE coordinador_id = ? AND activo = 1) as mis_cursos,
                    (SELECT COUNT(*) FROM matriculas m JOIN cursos c ON m.curso_id = c.id WHERE c.coordinador_id = ?) as total_matriculas,
                    (SELECT COUNT(*) FROM matriculas m JOIN cursos c ON m.curso_id = c.id WHERE c.coordinador_id = ? AND m.aprobado = 1) as aprobados";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
        $stats = $stmt->fetch();
    } elseif ($_SESSION['user_role'] === 'Participante') {
        // Estadísticas para participante
        $query = "SELECT 
                    (SELECT COUNT(*) FROM matriculas WHERE participante_id = ?) as mis_cursos,
                    (SELECT COUNT(*) FROM matriculas WHERE participante_id = ? AND aprobado = 1) as cursos_aprobados,
                    (SELECT COUNT(*) FROM documentos d JOIN matriculas m ON d.participante_id = m.participante_id WHERE m.participante_id = ? AND d.tipo = 'certificado') as certificados";
        
        // Buscar participante por cédula del usuario
        $query_participante = "SELECT id FROM participantes WHERE cedula = (SELECT cedula FROM usuarios WHERE id = ?)";
        $stmt_participante = $db->prepare($query_participante);
        $stmt_participante->execute([$_SESSION['user_id']]);
        $participante = $stmt_participante->fetch();
        
        if ($participante) {
            $stmt = $db->prepare($query);
            $stmt->execute([$participante['id'], $participante['id'], $participante['id']]);
            $stats = $stmt->fetch();
        }
    }
} catch (Exception $e) {
    $stats = [];
}

include 'includes/header.php';
?>
<div class="mb-6">
    <h1 class="text-3xl font-bold text-[#1e2a4a] mb-2">
        <i class="fas fa-tachometer-alt text-[#e63946] mr-3"></i>Dashboard
    </h1>
    <p class="text-gray-600">Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
    <div class="flex items-center mt-2">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-[#1e2a4a]">
            <i class="fas fa-user-tag mr-1"></i>
            <?php echo htmlspecialchars($_SESSION['user_role']); ?>
        </span>
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <?php if ($_SESSION['user_role'] === 'Administrador General'): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#1e2a4a] hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-[#1e2a4a] text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Usuarios</p>
                    <p class="text-2xl font-bold text-[#1e2a4a]"><?php echo $stats['total_usuarios'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#e63946] hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-school text-[#e63946] text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Escuelas</p>
                    <p class="text-2xl font-bold text-[#1e2a4a]"><?php echo $stats['total_escuelas'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#f1c232] hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-tie text-[#f1c232] text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Usuarios Escuela</p>
                    <p class="text-2xl font-bold text-[#1e2a4a]"><?php echo $stats['usuarios_escuela'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#6c757d] hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-crown text-[#6c757d] text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Dirección Nacional</p>
                    <p class="text-2xl font-bold text-[#1e2a4a]"><?php echo $stats['usuarios_direccion'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    <?php elseif ($_SESSION['user_role'] === 'Coordinador'): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#1e2a4a] hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chalkboard-teacher text-[#1e2a4a] text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Mis Cursos</p>
                    <p class="text-2xl font-bold text-[#1e2a4a]"><?php echo $stats['mis_cursos'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#f1c232] hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-[#f1c232] text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Matrículas</p>
                    <p class="text-2xl font-bold text-[#1e2a4a]"><?php echo $stats['total_matriculas'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#e63946] hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-[#e63946] text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Aprobados</p>
                    <p class="text-2xl font-bold text-[#1e2a4a]"><?php echo $stats['aprobados'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    <?php elseif ($_SESSION['user_role'] === 'Participante'): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#1e2a4a] hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-book text-[#1e2a4a] text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Mis Cursos</p>
                    <p class="text-2xl font-bold text-[#1e2a4a]"><?php echo $stats['mis_cursos'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#f1c232] hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-trophy text-[#f1c232] text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Cursos Aprobados</p>
                    <p class="text-2xl font-bold text-[#1e2a4a]"><?php echo $stats['cursos_aprobados'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#e63946] hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-certificate text-[#e63946] text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Certificados</p>
                    <p class="text-2xl font-bold text-[#1e2a4a]"><?php echo $stats['certificados'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Acciones rápidas -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-[#1e2a4a] mb-4">
            <i class="fas fa-bolt text-[#f1c232] mr-2"></i>Acciones Rápidas
        </h3>
        <div class="space-y-3">
            <?php if ($_SESSION['user_role'] === 'Administrador General'): ?>
                <a href="usuarios.php?action=create" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <i class="fas fa-user-plus text-[#1e2a4a] mr-3"></i>
                    <span class="text-[#1e2a4a] font-medium">Crear Usuario</span>
                </a>
                <a href="escuelas.php?action=create" class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                    <i class="fas fa-school text-[#e63946] mr-3"></i>
                    <span class="text-[#e63946] font-medium">Crear Escuela</span>
                </a>
            <?php elseif ($_SESSION['user_role'] === 'Coordinador'): ?>
                <a href="calificaciones.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <i class="fas fa-clipboard-list text-[#1e2a4a] mr-3"></i>
                    <span class="text-[#1e2a4a] font-medium">Calificar Participantes</span>
                </a>
                <a href="documentos.php?action=generate" class="flex items-center p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                    <i class="fas fa-file-alt text-[#f1c232] mr-3"></i>
                    <span class="text-[#f1c232] font-medium">Generar Documentos</span>
                </a>
            <?php elseif ($_SESSION['user_role'] === 'Participante'): ?>
                <a href="mis-cursos.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <i class="fas fa-book text-[#1e2a4a] mr-3"></i>
                    <span class="text-[#1e2a4a] font-medium">Ver Mis Cursos</span>
                </a>
                <a href="certificados.php" class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                    <i class="fas fa-download text-[#e63946] mr-3"></i>
                    <span class="text-[#e63946] font-medium">Descargar Certificados</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-[#1e2a4a] mb-4">
            <i class="fas fa-bell text-[#e63946] mr-2"></i>Notificaciones Recientes
        </h3>
        <div class="space-y-3">
            <div class="flex items-start p-3 bg-yellow-50 rounded-lg">
                <i class="fas fa-exclamation-triangle text-[#f1c232] mr-3 mt-1"></i>
                <div>
                    <p class="text-sm font-medium text-[#1e2a4a]">Sistema en desarrollo</p>
                    <p class="text-xs text-gray-600">Algunas funcionalidades están en construcción</p>
                </div>
            </div>
            <div class="flex items-start p-3 bg-blue-50 rounded-lg">
                <i class="fas fa-info-circle text-[#1e2a4a] mr-3 mt-1"></i>
                <div>
                    <p class="text-sm font-medium text-[#1e2a4a]">Bienvenido al sistema</p>
                    <p class="text-xs text-gray-600">Explora las funcionalidades disponibles</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actividad reciente -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold text-[#1e2a4a] mb-4">
        <i class="fas fa-history text-[#6c757d] mr-2"></i>Actividad Reciente
    </h3>
    <div class="text-center py-8 text-gray-500">
        <i class="fas fa-clock text-4xl mb-4"></i>
        <p>No hay actividad reciente para mostrar</p>
        <p class="text-sm">Las actividades aparecerán aquí cuando comiences a usar el sistema</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
