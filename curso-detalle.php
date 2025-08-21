<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$curso_id = $_GET['id'] ?? 0;

if (!$curso_id) {
    header("Location: cursos.php");
    exit();
}

// Obtener información del curso
$query_curso = "SELECT c.*, 
                       CONCAT(u.nombres, ' ', u.apellidos) as coordinador_nombre,
                       e.nombre as escuela_nombre,
                       e.codigo as escuela_codigo
                FROM cursos c 
                JOIN usuarios u ON c.coordinador_id = u.id 
                JOIN escuelas e ON c.escuela_id = e.id 
                WHERE c.id = ?";
$stmt_curso = $db->prepare($query_curso);
$stmt_curso->execute([$curso_id]);
$curso = $stmt_curso->fetch();

if (!$curso) {
    header("Location: cursos.php");
    exit();
}

// Verificar permisos para ver el curso
$puede_ver = false;
if ($_SESSION['user_role'] === 'Administrador General') {
    $puede_ver = true;
} elseif ($_SESSION['user_role'] === 'Coordinador' && $curso['coordinador_id'] == $_SESSION['user_id']) {
    $puede_ver = true;
} elseif (in_array($_SESSION['user_role'], ['Escuela', 'Director de Escuela', 'Educación DNBC', 'Dirección Nacional'])) {
    $puede_ver = true;
}

if (!$puede_ver) {
    header("Location: dashboard.php");
    exit();
}

// Obtener participantes matriculados
$query_participantes = "SELECT m.*, p.nombres, p.apellidos, p.cedula, p.email, p.institucion
                       FROM matriculas m 
                       JOIN participantes p ON m.participante_id = p.id 
                       WHERE m.curso_id = ? 
                       ORDER BY p.apellidos, p.nombres";
$stmt_participantes = $db->prepare($query_participantes);
$stmt_participantes->execute([$curso_id]);
$participantes = $stmt_participantes->fetchAll();

// Obtener instructores asignados (con manejo de errores)
$instructores = [];
try {
    $query_instructores = "SELECT ic.*, CONCAT(u.nombres, ' ', u.apellidos) as instructor_nombre
                          FROM instructores_cursos ic 
                          JOIN usuarios u ON ic.instructor_id = u.id 
                          WHERE ic.curso_id = ?";
    $stmt_instructores = $db->prepare($query_instructores);
    $stmt_instructores->execute([$curso_id]);
    $instructores = $stmt_instructores->fetchAll();
} catch (Exception $e) {
    // La tabla instructores_cursos puede no existir aún
    $instructores = [];
}

// Calcular estadísticas
$total_matriculados = count($participantes);
$aprobados = array_filter($participantes, function($p) { return $p['aprobado'] == 1; });
$total_aprobados = count($aprobados);
$calificados = array_filter($participantes, function($p) { return $p['calificacion'] !== null; });
$total_calificados = count($calificados);

$page_title = 'Detalle del Curso';
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
                            <a href="cursos.php" class="text-gray-700 hover:text-blue-600">Cursos</a>
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
                <i class="fas fa-graduation-cap text-blue-600 mr-3"></i><?php echo htmlspecialchars($curso['nombre']); ?>
            </h1>
            <p class="text-gray-600">Información detallada del curso</p>
        </div>
        <a href="cursos.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
            <i class="fas fa-arrow-left mr-2"></i>Volver
        </a>
    </div>
</div>

<!-- Información del curso -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Información básica -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>Información del Curso
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($curso['nombre']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número de Registro</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($curso['numero_registro']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Coordinador</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($curso['coordinador_nombre']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Escuela</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($curso['escuela_nombre']); ?></p>
            </div>
            <?php if ($curso['fecha_inicio']): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio</label>
                <p class="text-gray-900"><?php echo date('d/m/Y', strtotime($curso['fecha_inicio'])); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($curso['fecha_fin']): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Fin</label>
                <p class="text-gray-900"><?php echo date('d/m/Y', strtotime($curso['fecha_fin'])); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($curso['duracion_horas']): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Duración</label>
                <p class="text-gray-900"><?php echo $curso['duracion_horas']; ?> horas</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($curso['descripcion']): ?>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <p class="text-gray-900"><?php echo htmlspecialchars($curso['descripcion']); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($curso['contenido_tematico']): ?>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Contenido Temático</label>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($curso['contenido_tematico']); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Estadísticas -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-bar text-green-600 mr-2"></i>Estadísticas
            </h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                    <span class="text-blue-800 font-medium">Total Matriculados</span>
                    <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full font-bold"><?php echo $total_matriculados; ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                    <span class="text-yellow-800 font-medium">Calificados</span>
                    <span class="bg-yellow-200 text-yellow-800 px-3 py-1 rounded-full font-bold"><?php echo $total_calificados; ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                    <span class="text-green-800 font-medium">Aprobados</span>
                    <span class="bg-green-200 text-green-800 px-3 py-1 rounded-full font-bold"><?php echo $total_aprobados; ?></span>
                </div>
                
                <!-- Progreso -->
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Progreso de Calificaciones</span>
                        <span><?php echo $total_matriculados > 0 ? round(($total_calificados / $total_matriculados) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $total_matriculados > 0 ? ($total_calificados / $total_matriculados) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Tasa de Aprobación</span>
                        <span><?php echo $total_matriculados > 0 ? round(($total_aprobados / $total_matriculados) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $total_matriculados > 0 ? ($total_aprobados / $total_matriculados) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Instructores -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chalkboard-teacher text-purple-600 mr-2"></i>Instructores
            </h3>
            
            <?php if (!empty($instructores)): ?>
            <div class="space-y-2">
                <?php foreach ($instructores as $instructor): ?>
                <div class="flex items-center p-2 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-purple-600"></i>
                    </div>
                    <span class="text-gray-900"><?php echo htmlspecialchars($instructor['instructor_nombre']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-4">No hay instructores asignados</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lista de participantes -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-users text-blue-600 mr-2"></i>Participantes Matriculados
        </h3>
    </div>
    
    <?php if (!empty($participantes)): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participante</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cédula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Institución</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calificación</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Matrícula</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($participantes as $participante): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <?php 
                            // Obtener foto del participante
                            $query_foto = "SELECT fotografia FROM participantes WHERE id = ?";
                            $stmt_foto = $db->prepare($query_foto);
                            $stmt_foto->execute([$participante['participante_id']]);
                            $foto_data = $stmt_foto->fetch();
                            $fotografia = $foto_data['fotografia'] ?? null;
                            ?>
                            
                            <?php if ($fotografia && file_exists($fotografia)): ?>
                                <img src="<?php echo htmlspecialchars($fotografia); ?>" alt="Foto de <?php echo htmlspecialchars($participante['nombres']); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 shadow-sm">
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold border-2 border-gray-200 shadow-sm">
                                    <?php echo strtoupper(substr($participante['nombres'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($participante['nombres'] . ' ' . $participante['apellidos']); ?>
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php if ($participante['calificacion'] !== null): ?>
                            <span class="font-semibold <?php echo $participante['calificacion'] >= 3.0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo number_format($participante['calificacion'], 1); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400">Sin calificar</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($participante['calificacion'] !== null): ?>
                            <?php if ($participante['aprobado']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Aprobado
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i>No aprobado
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-clock mr-1"></i>Pendiente
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('d/m/Y', strtotime($participante['fecha_matricula'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-12">
        <i class="fas fa-user-graduate text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay participantes matriculados</h3>
        <p class="text-gray-500">Este curso aún no tiene participantes matriculados</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
