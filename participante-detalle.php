<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$participante_id = $_GET['id'] ?? 0;

if (!$participante_id) {
    header("Location: participantes.php");
    exit();
}

// Obtener información del participante
$query_participante = "SELECT * FROM participantes WHERE id = ?";
$stmt_participante = $db->prepare($query_participante);
$stmt_participante->execute([$participante_id]);
$participante = $stmt_participante->fetch();

if (!$participante) {
    header("Location: participantes.php");
    exit();
}

// Obtener cursos del participante
$query_cursos = "SELECT m.*, c.nombre as curso_nombre, c.numero_registro, c.fecha_inicio, c.fecha_fin,
                        CONCAT(u.nombres, ' ', u.apellidos) as coordinador_nombre,
                        e.nombre as escuela_nombre
                 FROM matriculas m 
                 JOIN cursos c ON m.curso_id = c.id 
                 JOIN usuarios u ON c.coordinador_id = u.id 
                 JOIN escuelas e ON c.escuela_id = e.id 
                 WHERE m.participante_id = ? 
                 ORDER BY m.fecha_matricula DESC";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute([$participante_id]);
$cursos = $stmt_cursos->fetchAll();

// Calcular estadísticas
$total_cursos = count($cursos);
$cursos_aprobados = array_filter($cursos, function($c) { return $c['aprobado'] == 1; });
$total_aprobados = count($cursos_aprobados);
$cursos_calificados = array_filter($cursos, function($c) { return $c['calificacion'] !== null; });
$total_calificados = count($cursos_calificados);

$page_title = 'Detalle del Participante';
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
                            <a href="participantes.php" class="text-gray-700 hover:text-blue-600">Participantes</a>
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
                <i class="fas fa-user-graduate text-blue-600 mr-3"></i><?php echo htmlspecialchars($participante['nombres'] . ' ' . $participante['apellidos']); ?>
            </h1>
            <p class="text-gray-600">Información detallada del participante</p>
        </div>
        <a href="participantes.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
            <i class="fas fa-arrow-left mr-2"></i>Volver
        </a>
    </div>
</div>

<!-- Información del participante -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Información básica -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>Información Personal
        </h3>
        
        <div class="flex items-start space-x-6 mb-6">
            <!-- Fotografía -->
            <div class="flex-shrink-0">
                <?php if ($participante['fotografia'] && file_exists($participante['fotografia'])): ?>
                    <img src="<?php echo htmlspecialchars($participante['fotografia']); ?>" alt="Foto de <?php echo htmlspecialchars($participante['nombres']); ?>" class="w-32 h-32 rounded-full object-cover border-4 border-gray-200 shadow-lg">
                <?php else: ?>
                    <div class="w-32 h-32 bg-gradient-to-r from-green-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-4xl border-4 border-gray-200 shadow-lg">
                        <?php echo strtoupper(substr($participante['nombres'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Datos básicos -->
            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombres</label>
                    <p class="text-gray-900 font-semibold"><?php echo htmlspecialchars($participante['nombres']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos</label>
                    <p class="text-gray-900 font-semibold"><?php echo htmlspecialchars($participante['apellidos']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cédula</label>
                    <p class="text-gray-900"><?php echo htmlspecialchars($participante['cedula']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <p class="text-gray-900"><?php echo htmlspecialchars($participante['email']); ?></p>
                </div>
                <?php if ($participante['telefono']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <p class="text-gray-900"><?php echo htmlspecialchars($participante['telefono']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($participante['genero']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Género</label>
                    <p class="text-gray-900">
                        <?php 
                        switch($participante['genero']) {
                            case 'M': echo 'Masculino'; break;
                            case 'F': echo 'Femenino'; break;
                            default: echo htmlspecialchars($participante['genero']); break;
                        }
                        ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($participante['institucion']): ?>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Institución</label>
            <p class="text-gray-900"><?php echo htmlspecialchars($participante['institucion']); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Estadísticas -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-bar text-green-600 mr-2"></i>Estadísticas Académicas
            </h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                    <span class="text-blue-800 font-medium">Total Cursos</span>
                    <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full font-bold"><?php echo $total_cursos; ?></span>
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
                <?php if ($total_cursos > 0): ?>
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Progreso de Calificaciones</span>
                        <span><?php echo round(($total_calificados / $total_cursos) * 100, 1); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($total_calificados / $total_cursos) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Tasa de Aprobación</span>
                        <span><?php echo $total_calificados > 0 ? round(($total_aprobados / $total_calificados) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $total_calificados > 0 ? ($total_aprobados / $total_calificados) * 100 : 0; ?>%"></div>
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
                    <i class="fas fa-check-circle mr-2"></i>Participante Activo
                </span>
                <p class="text-sm text-gray-500 mt-2">
                    Registrado el <?php echo date('d/m/Y', strtotime($participante['created_at'])); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Lista de cursos -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-graduation-cap text-blue-600 mr-2"></i>Historial de Cursos
        </h3>
    </div>
    
    <?php if (!empty($cursos)): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escuela</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinador</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calificación</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Matrícula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($cursos as $curso): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($curso['curso_nombre']); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            <?php echo htmlspecialchars($curso['numero_registro']); ?>
                        </div>
                        <?php if ($curso['fecha_inicio']): ?>
                        <div class="text-xs text-gray-400">
                            <?php echo date('d/m/Y', strtotime($curso['fecha_inicio'])); ?>
                            <?php if ($curso['fecha_fin']): ?>
                                - <?php echo date('d/m/Y', strtotime($curso['fecha_fin'])); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($curso['escuela_nombre']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($curso['coordinador_nombre']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php if ($curso['calificacion'] !== null): ?>
                            <span class="font-semibold <?php echo $curso['calificacion'] >= 3.0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo number_format($curso['calificacion'], 1); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400">Sin calificar</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($curso['calificacion'] !== null): ?>
                            <?php if ($curso['aprobado']): ?>
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
                        <?php echo date('d/m/Y', strtotime($curso['fecha_matricula'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="curso-detalle.php?id=<?php echo $curso['curso_id']; ?>" class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-eye mr-1"></i>Ver Curso
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
        <p class="text-gray-500">Este participante aún no está matriculado en ningún curso</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
