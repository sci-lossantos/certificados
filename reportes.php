<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireAuth();

$page_title = 'Reportes';

// Obtener datos para reportes según el rol
$reportes_data = [];

try {
    if ($_SESSION['user_role'] === 'Administrador General') {
        // Reportes para administrador general
        
        // Resumen general del sistema
        $query_resumen = "SELECT 
    (SELECT COUNT(*) FROM usuarios WHERE activo = 1 AND rol_id IN (
        SELECT id FROM roles WHERE nombre NOT IN ('Participante', 'Coordinador')
    )) as total_usuarios,
    (SELECT COUNT(*) FROM escuelas WHERE activa = 1) as total_escuelas,
    (SELECT COUNT(*) FROM cursos WHERE activo = 1) as total_cursos,
    (SELECT COUNT(*) FROM usuarios u JOIN roles r ON u.rol_id = r.id 
     WHERE r.nombre IN ('Escuela', 'Director de Escuela') AND u.activo = 1) as usuarios_gestion,
    (SELECT COUNT(*) FROM matriculas) as total_matriculas,
    (SELECT COUNT(*) FROM documentos) as total_documentos";
        $stmt_resumen = $db->prepare($query_resumen);
        $stmt_resumen->execute();
        $reportes_data['resumen'] = $stmt_resumen->fetch();
        
        // Usuarios por rol (solo roles administrativos y de gestión)
        $query_usuarios_rol = "SELECT r.nombre as rol, COUNT(u.id) as cantidad 
                      FROM roles r 
                      LEFT JOIN usuarios u ON r.id = u.rol_id AND u.activo = 1 
                      WHERE r.nombre IN ('Administrador General', 'Escuela', 'Director de Escuela', 'Educación DNBC', 'Dirección Nacional', 'Instructor')
                      GROUP BY r.id, r.nombre 
                      ORDER BY cantidad DESC";
        $stmt_usuarios_rol = $db->prepare($query_usuarios_rol);
        $stmt_usuarios_rol->execute();
        $reportes_data['usuarios_por_rol'] = $stmt_usuarios_rol->fetchAll();
        
        // Escuelas con más actividad
        $query_escuelas_actividad = "SELECT e.nombre, e.codigo,
                                    (SELECT COUNT(*) FROM cursos c WHERE c.escuela_id = e.id AND c.activo = 1) as total_cursos,
                                    (SELECT COUNT(*) FROM matriculas m JOIN cursos c ON m.curso_id = c.id WHERE c.escuela_id = e.id) as total_matriculas
                                    FROM escuelas e 
                                    WHERE e.activa = 1 
                                    ORDER BY total_cursos DESC, total_matriculas DESC 
                                    LIMIT 10";
        $stmt_escuelas_actividad = $db->prepare($query_escuelas_actividad);
        $stmt_escuelas_actividad->execute();
        $reportes_data['escuelas_actividad'] = $stmt_escuelas_actividad->fetchAll();
        
    } elseif (in_array($_SESSION['user_role'], ['Escuela', 'Director de Escuela'])) {
        // Reportes para escuela - obtener cursos relacionados con el usuario
        $query_cursos = "SELECT c.nombre, c.numero_registro,
                        (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id) as total_matriculas,
                        (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id AND m.aprobado = 1) as aprobados
                        FROM cursos c 
                        WHERE c.activo = 1 
                        ORDER BY c.created_at DESC";
        $stmt_cursos = $db->prepare($query_cursos);
        $stmt_cursos->execute();
        $reportes_data['cursos'] = $stmt_cursos->fetchAll();
        
    } elseif ($_SESSION['user_role'] === 'Coordinador') {
        // Reportes para coordinador
        
        // Mis cursos
        $query_mis_cursos = "SELECT c.nombre, c.numero_registro,
                            (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id) as total_matriculas,
                            (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id AND m.aprobado = 1) as aprobados,
                            (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id AND m.calificacion IS NOT NULL) as calificados
                            FROM cursos c 
                            WHERE c.coordinador_id = ? AND c.activo = 1 
                            ORDER BY c.created_at DESC";
        $stmt_mis_cursos = $db->prepare($query_mis_cursos);
        $stmt_mis_cursos->execute([$_SESSION['user_id']]);
        $reportes_data['mis_cursos'] = $stmt_mis_cursos->fetchAll();
    }
    
} catch (Exception $e) {
    $error = "Error al generar reportes: " . $e->getMessage();
    $reportes_data = [];
}

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">
        <i class="fas fa-chart-bar text-blue-600 mr-3"></i>Reportes
    </h1>
    <p class="text-gray-600">Estadísticas y reportes del sistema</p>
</div>

<?php if ($_SESSION['user_role'] === 'Administrador General'): ?>
<!-- Resumen General -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-chart-pie text-blue-600 mr-2"></i>Resumen General del Sistema
    </h3>
    
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="text-center p-4 bg-blue-50 rounded-lg">
            <div class="text-2xl font-bold text-blue-600"><?php echo $reportes_data['resumen']['total_usuarios'] ?? 0; ?></div>
            <div class="text-sm text-gray-600">Usuarios</div>
        </div>
        <div class="text-center p-4 bg-green-50 rounded-lg">
            <div class="text-2xl font-bold text-green-600"><?php echo $reportes_data['resumen']['total_escuelas'] ?? 0; ?></div>
            <div class="text-sm text-gray-600">Escuelas</div>
        </div>
        <div class="text-center p-4 bg-purple-50 rounded-lg">
            <div class="text-2xl font-bold text-purple-600"><?php echo $reportes_data['resumen']['total_cursos'] ?? 0; ?></div>
            <div class="text-sm text-gray-600">Cursos</div>
        </div>
        <div class="text-center p-4 bg-orange-50 rounded-lg">
            <div class="text-2xl font-bold text-orange-600"><?php echo $reportes_data['resumen']['usuarios_gestion'] ?? 0; ?></div>
            <div class="text-sm text-gray-600">Usuarios de Gestión</div>
        </div>
        <div class="text-center p-4 bg-indigo-50 rounded-lg">
            <div class="text-2xl font-bold text-indigo-600"><?php echo $reportes_data['resumen']['total_matriculas'] ?? 0; ?></div>
            <div class="text-sm text-gray-600">Matrículas</div>
        </div>
        <div class="text-center p-4 bg-red-50 rounded-lg">
            <div class="text-2xl font-bold text-red-600"><?php echo $reportes_data['resumen']['total_documentos'] ?? 0; ?></div>
            <div class="text-sm text-gray-600">Documentos</div>
        </div>
    </div>
</div>

<!-- Usuarios por Rol -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-users text-green-600 mr-2"></i>Usuarios por Rol
        </h3>
        
        <div class="space-y-3">
            <?php foreach ($reportes_data['usuarios_por_rol'] as $rol): ?>
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="font-medium text-gray-700"><?php echo htmlspecialchars($rol['rol']); ?></span>
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                    <?php echo $rol['cantidad']; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Escuelas con más actividad -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-school text-orange-600 mr-2"></i>Escuelas con Mayor Actividad
        </h3>
        
        <div class="space-y-3">
            <?php foreach ($reportes_data['escuelas_actividad'] as $escuela): ?>
            <div class="p-3 bg-gray-50 rounded-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($escuela['nombre']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($escuela['codigo']); ?></div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-blue-600"><?php echo $escuela['total_cursos']; ?> cursos</div>
                        <div class="text-sm text-green-600"><?php echo $escuela['total_matriculas']; ?> matrículas</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php elseif (in_array($_SESSION['user_role'], ['Escuela', 'Director de Escuela'])): ?>
<!-- Reportes para Escuela -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-graduation-cap text-blue-600 mr-2"></i>Reporte de Cursos
    </h3>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matrículas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aprobados</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">% Aprobación</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($reportes_data['cursos'] as $curso): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($curso['nombre']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($curso['numero_registro']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $curso['total_matriculas']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $curso['aprobados']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php 
                        $porcentaje = $curso['total_matriculas'] > 0 ? round(($curso['aprobados'] / $curso['total_matriculas']) * 100, 1) : 0;
                        echo $porcentaje . '%';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($_SESSION['user_role'] === 'Coordinador'): ?>
<!-- Reportes para Coordinador -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-chalkboard-teacher text-blue-600 mr-2"></i>Mis Cursos - Reporte de Desempeño
    </h3>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matrículas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Calificados</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aprobados</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">% Progreso</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($reportes_data['mis_cursos'] as $curso): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($curso['nombre']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($curso['numero_registro']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $curso['total_matriculas']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $curso['calificados']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $curso['aprobados']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php 
                        $progreso = $curso['total_matriculas'] > 0 ? round(($curso['calificados'] / $curso['total_matriculas']) * 100, 1) : 0;
                        echo $progreso . '%';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Botones de exportación -->
<div class="mt-6 flex justify-end space-x-3">
    <button onclick="exportToPDF()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-all">
        <i class="fas fa-file-pdf mr-2"></i>Exportar PDF
    </button>
    <button onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold transition-all">
        <i class="fas fa-file-excel mr-2"></i>Exportar Excel
    </button>
</div>

<script>
function exportToPDF() {
    alert('Funcionalidad de exportación PDF en desarrollo');
}

function exportToExcel() {
    alert('Funcionalidad de exportación Excel en desarrollo');
}
</script>

<?php include 'includes/footer.php'; ?>
