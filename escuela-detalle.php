<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireRole(['Administrador General']);

$escuela_id = $_GET['id'] ?? 0;

if (!$escuela_id) {
    header("Location: escuelas.php");
    exit();
}

// Obtener información de la escuela
$query_escuela = "SELECT e.*, CONCAT(u.nombres, ' ', u.apellidos) as director_nombre,
                         u.email as director_email, u.telefono as director_telefono
                  FROM escuelas e 
                  LEFT JOIN usuarios u ON e.director_id = u.id 
                  WHERE e.id = ?";
$stmt_escuela = $db->prepare($query_escuela);
$stmt_escuela->execute([$escuela_id]);
$escuela = $stmt_escuela->fetch();

if (!$escuela) {
    header("Location: escuelas.php");
    exit();
}

// Obtener cursos de la escuela
$query_cursos = "SELECT c.*, 
                        CONCAT(u.nombres, ' ', u.apellidos) as coordinador_nombre,
                        (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id) as total_matriculados,
                        (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id AND m.aprobado = 1) as total_aprobados
                 FROM cursos c 
                 JOIN usuarios u ON c.coordinador_id = u.id 
                 WHERE c.escuela_id = ? AND c.activo = 1 
                 ORDER BY c.created_at DESC";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute([$escuela_id]);
$cursos = $stmt_cursos->fetchAll();

// Obtener usuarios de la escuela (coordinadores y directores)
$query_usuarios = "SELECT u.*, r.nombre as rol_nombre
                   FROM usuarios u 
                   JOIN roles r ON u.rol_id = r.id 
                   WHERE u.activo = 1 AND r.nombre IN ('Coordinador', 'Director de Escuela')
                   ORDER BY r.nombre, u.nombres";
$stmt_usuarios = $db->prepare($query_usuarios);
$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->fetchAll();

// Calcular estadísticas
$total_cursos = count($cursos);
$total_matriculados = array_sum(array_column($cursos, 'total_matriculados'));
$total_aprobados = array_sum(array_column($cursos, 'total_aprobados'));

$page_title = 'Detalle de Escuela';
include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="dashboard.php" class="text-gray-700 hover:text-blue-600">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <a href="escuelas.php" class="text-gray-700 hover:text-blue-600">Escuelas</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-gray-500">Detalle</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-school text-blue-600 mr-3"></i><?php echo htmlspecialchars($escuela['nombre']); ?>
            </h1>
            <p class="text-gray-600">Información detallada de la escuela</p>
        </div>
        <a href="escuelas.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
            <i class="fas fa-arrow-left mr-2"></i>Volver
        </a>
    </div>
</div>

<!-- Información de la escuela -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Información básica -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>Información de la Escuela
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($escuela['nombre']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($escuela['codigo']); ?></p>
            </div>
            <?php if ($escuela['direccion']): ?>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($escuela['direccion']); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($escuela['telefono']): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($escuela['telefono']); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($escuela['email']): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($escuela['email']); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Información del director -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h4 class="text-md font-semibold text-gray-900 mb-3">
                <i class="fas fa-user-tie text-purple-600 mr-2"></i>Director de Escuela
            </h4>
            <?php if ($escuela['director_nombre']): ?>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($escuela['director_nombre']); ?></p>
                        <?php if ($escuela['director_email']): ?>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($escuela['director_email']); ?></p>
                        <?php endif; ?>
                        <?php if ($escuela['director_telefono']): ?>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($escuela['director_telefono']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-gray-50 p-4 rounded-lg text-center">
                <i class="fas fa-user-slash text-gray-400 text-2xl mb-2"></i>
                <p class="text-gray-500">No hay director asignado</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Estadísticas -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-bar text-green-600 mr-2"></i>Estadísticas
            </h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                    <span class="text-blue-800 font-medium">Total Cursos</span>
                    <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full font-bold"><?php echo $total_cursos; ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                    <span class="text-green-800 font-medium">Total Matriculados</span>
                    <span class="bg-green-200 text-green-800 px-3 py-1 rounded-full font-bold"><?php echo $total_matriculados; ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                    <span class="text-purple-800 font-medium">Total Aprobados</span>
                    <span class="bg-purple-200 text-purple-800 px-3 py-1 rounded-full font-bold"><?php echo $total_aprobados; ?></span>
                </div>
                
                <!-- Tasa de aprobación -->
                <?php if ($total_matriculados > 0): ?>
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Tasa de Aprobación</span>
                        <span><?php echo round(($total_aprobados / $total_matriculados) * 100, 1); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($total_aprobados / $total_matriculados) * 100; ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Estado -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-info text-blue-600 mr-2"></i>Estado
            </h3>
            
            <div class="text-center">
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>Escuela Activa
                </span>
                <p class="text-sm text-gray-500 mt-2">
                    Registrada el <?php echo date('d/m/Y', strtotime($escuela['created_at'])); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Lista de cursos -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-graduation-cap text-blue-600 mr-2"></i>Cursos de la Escuela
        </h3>
    </div>
    
    <?php if (!empty($cursos)): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinador</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matriculados</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aprobados</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fechas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($cursos as $curso): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($curso['nombre']); ?>
                        </div>
                        <?php if ($curso['descripcion']): ?>
                        <div class="text-sm text-gray-500">
                            <?php echo htmlspecialchars(substr($curso['descripcion'], 0, 50)) . (strlen($curso['descripcion']) > 50 ? '...' : ''); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($curso['numero_registro']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($curso['coordinador_nombre']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo $curso['total_matriculados']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <?php echo $curso['total_aprobados']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php if ($curso['fecha_inicio']): ?>
                            <?php echo date('d/m/Y', strtotime($curso['fecha_inicio'])); ?>
                            <?php if ($curso['fecha_fin']): ?>
                                <br>al <?php echo date('d/m/Y', strtotime($curso['fecha_fin'])); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            Sin fechas
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="curso-detalle.php?id=<?php echo $curso['id']; ?>" class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-eye mr-1"></i>Ver
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-12">
        <i class="fas fa-graduation-cap text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay cursos registrados</h3>
        <p class="text-gray-500">Esta escuela aún no tiene cursos asignados</p>
    </div>
    <?php endif; ?>
</div>

<!-- Personal de la escuela -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-users text-purple-600 mr-2"></i>Personal de la Escuela
        </h3>
    </div>
    
    <?php if (!empty($usuarios)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
        <?php foreach ($usuarios as $usuario): ?>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                    <?php echo strtoupper(substr($usuario['nombres'], 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>
                    </div>
                    <div class="text-xs text-gray-500">
                        <?php echo htmlspecialchars($usuario['rol_nombre']); ?>
                    </div>
                    <?php if ($usuario['email']): ?>
                    <div class="text-xs text-gray-500">
                        <?php echo htmlspecialchars($usuario['email']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-12">
        <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay personal asignado</h3>
        <p class="text-gray-500">Esta escuela aún no tiene coordinadores o directores asignados</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
