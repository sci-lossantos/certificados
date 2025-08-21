<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireRole(['Escuela', 'Coordinador']);

$page_title = 'Gestión de Cursos';
$message = '';
$error = '';

// Procesar formulario
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $numero_registro = trim($_POST['numero_registro'] ?? '');
        $coordinador_id = $_POST['coordinador_id'] ?? '';
        $escuela_id = $_POST['escuela_id'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';
        $duracion_horas = $_POST['duracion_horas'] ?? '';
        $contenido_tematico = trim($_POST['contenido_tematico'] ?? '');
        
        if ($nombre && $numero_registro && $coordinador_id && $escuela_id) {
            try {
                // Verificar que no exista el número de registro
                $query_check = "SELECT id FROM cursos WHERE numero_registro = ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$numero_registro]);
                
                if ($stmt_check->fetch()) {
                    throw new Exception('Ya existe un curso con ese número de registro');
                }
                
                // Crear curso
                $query_insert = "INSERT INTO cursos (nombre, descripcion, numero_registro, coordinador_id, escuela_id, fecha_inicio, fecha_fin, duracion_horas, contenido_tematico) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $db->prepare($query_insert);
                $stmt_insert->execute([$nombre, $descripcion, $numero_registro, $coordinador_id, $escuela_id, $fecha_inicio ?: null, $fecha_fin ?: null, $duracion_horas ?: null, $contenido_tematico]);
                
                $message = 'Curso creado exitosamente';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Los campos obligatorios deben ser completados';
        }
    } elseif ($action === 'assign_instructor') {
        $curso_id = $_POST['curso_id'] ?? '';
        $instructor_id = $_POST['instructor_id'] ?? '';
        
        if ($curso_id && $instructor_id) {
            try {
                // Verificar que el curso exista
                $query_check = "SELECT id FROM cursos WHERE id = ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$curso_id]);
                
                if (!$stmt_check->fetch()) {
                    throw new Exception('El curso no existe');
                }
                
                // Crear tabla de instructores_cursos si no existe
                $db->exec("CREATE TABLE IF NOT EXISTS instructores_cursos (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    curso_id INT NOT NULL,
                    instructor_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (curso_id) REFERENCES cursos(id),
                    FOREIGN KEY (instructor_id) REFERENCES usuarios(id),
                    UNIQUE KEY unique_instructor_curso (curso_id, instructor_id)
                )");
                
                // Asignar instructor
                $query_insert = "INSERT INTO instructores_cursos (curso_id, instructor_id) VALUES (?, ?)";
                $stmt_insert = $db->prepare($query_insert);
                $stmt_insert->execute([$curso_id, $instructor_id]);
                
                $message = 'Instructor asignado exitosamente';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Debe seleccionar un curso y un instructor';
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $numero_registro = trim($_POST['numero_registro'] ?? '');
        $coordinador_id = $_POST['coordinador_id'] ?? '';
        $escuela_id = $_POST['escuela_id'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';
        $duracion_horas = $_POST['duracion_horas'] ?? '';
        $contenido_tematico = trim($_POST['contenido_tematico'] ?? '');
        
        if ($id && $nombre && $numero_registro && $coordinador_id && $escuela_id) {
            try {
                // Verificar que no exista el número de registro en otro curso
                $query_check = "SELECT id FROM cursos WHERE numero_registro = ? AND id != ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$numero_registro, $id]);
                
                if ($stmt_check->fetch()) {
                    throw new Exception('Ya existe otro curso con ese número de registro');
                }
                
                // Actualizar curso
                $query_update = "UPDATE cursos SET nombre = ?, descripcion = ?, numero_registro = ?, coordinador_id = ?, escuela_id = ?, fecha_inicio = ?, fecha_fin = ?, duracion_horas = ?, contenido_tematico = ? WHERE id = ?";
                $stmt_update = $db->prepare($query_update);
                $stmt_update->execute([$nombre, $descripcion, $numero_registro, $coordinador_id, $escuela_id, $fecha_inicio ?: null, $fecha_fin ?: null, $duracion_horas ?: null, $contenido_tematico, $id]);
                
                $message = 'Curso actualizado exitosamente';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Los campos obligatorios deben ser completados';
        }
    }
}

// Obtener coordinadores disponibles
$query_coordinadores = "SELECT u.id, u.nombres, u.apellidos 
                       FROM usuarios u 
                       JOIN roles r ON u.rol_id = r.id 
                       WHERE r.nombre = 'Coordinador' AND u.activo = 1 
                       ORDER BY u.nombres";
$stmt_coordinadores = $db->prepare($query_coordinadores);
$stmt_coordinadores->execute();
$coordinadores = $stmt_coordinadores->fetchAll();

// Obtener instructores disponibles (asumimos que los instructores son usuarios con rol Coordinador)
$query_instructores = "SELECT u.id, u.nombres, u.apellidos, r.nombre as rol_nombre
                      FROM usuarios u 
                      JOIN roles r ON u.rol_id = r.id 
                      WHERE r.nombre IN ('Coordinador', 'Instructor', 'Director de Escuela') AND u.activo = 1 
                      ORDER BY u.nombres";
$stmt_instructores = $db->prepare($query_instructores);
$stmt_instructores->execute();
$instructores = $stmt_instructores->fetchAll();

// Obtener escuelas disponibles
$query_escuelas = "SELECT id, nombre FROM escuelas WHERE activa = 1 ORDER BY nombre";
$stmt_escuelas = $db->prepare($query_escuelas);
$stmt_escuelas->execute();
$escuelas = $stmt_escuelas->fetchAll();

// Obtener lista de cursos
$query_cursos = "SELECT c.*, 
                        CONCAT(u.nombres, ' ', u.apellidos) as coordinador_nombre,
                        e.nombre as escuela_nombre,
                        (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id) as total_matriculados
                 FROM cursos c 
                 JOIN usuarios u ON c.coordinador_id = u.id 
                 JOIN escuelas e ON c.escuela_id = e.id 
                 WHERE c.activo = 1 
                 ORDER BY c.created_at DESC";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute();
$cursos = $stmt_cursos->fetchAll();

include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-graduation-cap text-blue-600 mr-3"></i>Gestión de Cursos
            </h1>
            <p class="text-gray-600">Administra los cursos de formación</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="openAssignInstructorModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
                <i class="fas fa-chalkboard-teacher mr-2"></i>Asignar Instructor
            </button>
            <button onclick="openCreateModal()" class="btn-primary text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
                <i class="fas fa-plus mr-2"></i>Nuevo Curso
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

<!-- Grid de cursos -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($cursos as $curso): ?>
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($curso['nombre']); ?></h3>
                <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($curso['descripcion'] ?? 'Sin descripción'); ?></p>
            </div>
            <div class="flex space-x-2">
                <button onclick="editCourse(<?php echo $curso['id']; ?>)" class="text-blue-600 hover:text-blue-800" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="matricularCurso(<?php echo $curso['id']; ?>, '<?php echo htmlspecialchars($curso['nombre']); ?>')" class="text-green-600 hover:text-green-800" title="Matricular">
                    <i class="fas fa-user-plus"></i>
                </button>
            </div>
        </div>
        
        <div class="space-y-2 text-sm">
            <div class="flex items-center text-gray-600">
                <i class="fas fa-hashtag w-4 mr-2"></i>
                <span>Registro: <?php echo htmlspecialchars($curso['numero_registro']); ?></span>
            </div>
            <div class="flex items-center text-gray-600">
                <i class="fas fa-user-tie w-4 mr-2"></i>
                <span>Coordinador: <?php echo htmlspecialchars($curso['coordinador_nombre']); ?></span>
            </div>
            <div class="flex items-center text-gray-600">
                <i class="fas fa-school w-4 mr-2"></i>
                <span>Escuela: <?php echo htmlspecialchars($curso['escuela_nombre']); ?></span>
            </div>
            <?php if ($curso['fecha_inicio']): ?>
            <div class="flex items-center text-gray-600">
                <i class="fas fa-calendar w-4 mr-2"></i>
                <span><?php echo date('d/m/Y', strtotime($curso['fecha_inicio'])); ?> - <?php echo $curso['fecha_fin'] ? date('d/m/Y', strtotime($curso['fecha_fin'])) : 'Sin fecha fin'; ?></span>
            </div>
            <?php endif; ?>
            <?php if ($curso['duracion_horas']): ?>
            <div class="flex items-center text-gray-600">
                <i class="fas fa-clock w-4 mr-2"></i>
                <span><?php echo $curso['duracion_horas']; ?> horas</span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <i class="fas fa-users mr-1"></i>
                    <?php echo $curso['total_matriculados']; ?> matriculados
                </span>
                <div class="flex space-x-2">
                    <button onclick="viewCourse(<?php echo $curso['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Ver detalles
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal para crear/editar curso -->
<div id="createCourseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Crear Nuevo Curso</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" id="cursoForm" class="space-y-4">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="cursoId" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Curso *</label>
                    <input type="text" name="nombre" id="nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número de Registro *</label>
                    <input type="text" name="numero_registro" id="numero_registro" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Coordinador *</label>
                    <select name="coordinador_id" id="coordinador_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar coordinador</option>
                        <?php foreach ($coordinadores as $coordinador): ?>
                        <option value="<?php echo $coordinador['id']; ?>">
                            <?php echo htmlspecialchars($coordinador['nombres'] . ' ' . $coordinador['apellidos']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Escuela *</label>
                    <select name="escuela_id" id="escuela_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar escuela</option>
                        <?php foreach ($escuelas as $escuela): ?>
                        <option value="<?php echo $escuela['id']; ?>">
                            <?php echo htmlspecialchars($escuela['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Duración (horas)</label>
                    <input type="number" name="duracion_horas" id="duracion_horas" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Contenido Temático</label>
                <textarea name="contenido_tematico" id="contenido_tematico" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Describe los temas que se cubrirán en el curso..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" id="submitButton" class="btn-primary px-6 py-2 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i><span id="submitButtonText">Crear Curso</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para asignar instructor -->
<div id="assignInstructorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Asignar Instructor a Curso</h3>
            <button onclick="closeAssignInstructorModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="assign_instructor">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Curso *</label>
                <select name="curso_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccionar curso</option>
                    <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>">
                        <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['numero_registro']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Instructor *</label>
                <select name="instructor_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccionar instructor</option>
                    <?php foreach ($instructores as $instructor): ?>
                    <option value="<?php echo $instructor['id']; ?>">
                        <?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos'] . ' (' . $instructor['rol_nombre'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeAssignInstructorModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 px-6 py-2 text-white rounded-lg">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>Asignar Instructor
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Indicador de carga -->
<div id="loadingIndicator" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-5 rounded-lg shadow-lg flex items-center">
        <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mr-3"></i>
        <span class="text-gray-700 font-medium">Cargando datos del curso...</span>
    </div>
</div>

<script>
// Función para mostrar errores en consola
function logError(message, error) {
    console.error(message, error);
    // También podríamos enviar estos errores a un endpoint para registro
}

function openCreateModal() {
    console.log('Abriendo modal para crear curso');
    resetForm();
    document.getElementById('createCourseModal').classList.remove('hidden');
}

function closeCreateModal() {
    console.log('Cerrando modal');
    document.getElementById('createCourseModal').classList.add('hidden');
}

function openAssignInstructorModal() {
    document.getElementById('assignInstructorModal').classList.remove('hidden');
}

function closeAssignInstructorModal() {
    document.getElementById('assignInstructorModal').classList.add('hidden');
}

function viewCourse(courseId) {
    window.location.href = `curso-detalle.php?id=${courseId}`;
}

function matricularCurso(cursoId, cursoNombre) {
    window.location.href = `participantes.php?curso_id=${cursoId}&curso_nombre=${encodeURIComponent(cursoNombre)}`;
}

// Cerrar modales al hacer click fuera
document.getElementById('createCourseModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateModal();
    }
});

document.getElementById('assignInstructorModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAssignInstructorModal();
    }
});

function showLoading() {
    document.getElementById('loadingIndicator').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingIndicator').classList.add('hidden');
}

function editCourse(courseId) {
    console.log('Editando curso ID:', courseId);
    
    if (!courseId || courseId <= 0) {
        alert('ID de curso inválido');
        return;
    }
    
    showLoading();
    
    // Construir URL con timestamp para evitar cache
    const url = `get_course_data.php?id=${courseId}&t=${new Date().getTime()}`;
    console.log('Fetching URL:', url);
    
    // Usar XMLHttpRequest en lugar de fetch para mejor compatibilidad
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    xhr.setRequestHeader('Pragma', 'no-cache');
    xhr.setRequestHeader('Expires', '0');
    
    xhr.onload = function() {
        console.log('Response status:', xhr.status);
        console.log('Response text:', xhr.responseText);
        
        if (xhr.status === 200) {
            try {
                const data = JSON.parse(xhr.responseText);
                console.log('Parsed data:', data);
                
                hideLoading();
                
                if (data.success && data.curso) {
                    const curso = data.curso;
                    console.log('Curso data:', curso);
                    
                    // Cambiar a modo edición
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('cursoId').value = curso.id;
                    
                    // Llenar todos los campos del formulario
                    document.getElementById('nombre').value = curso.nombre || '';
                    document.getElementById('descripcion').value = curso.descripcion || '';
                    document.getElementById('numero_registro').value = curso.numero_registro || '';
                    document.getElementById('coordinador_id').value = curso.coordinador_id || '';
                    document.getElementById('escuela_id').value = curso.escuela_id || '';
                    document.getElementById('fecha_inicio').value = curso.fecha_inicio || '';
                    document.getElementById('fecha_fin').value = curso.fecha_fin || '';
                    document.getElementById('duracion_horas').value = curso.duracion_horas || '';
                    document.getElementById('contenido_tematico').value = curso.contenido_tematico || '';
                    
                    // Cambiar título y botón del modal
                    document.getElementById('modalTitle').textContent = 'Editar Curso';
                    document.getElementById('submitButtonText').textContent = 'Actualizar Curso';
                    
                    console.log('Formulario llenado, abriendo modal');
                    
                    // Abrir modal
                    document.getElementById('createCourseModal').classList.remove('hidden');
                } else {
                    console.error('Error en respuesta:', data);
                    alert('Error al cargar los datos del curso: ' + (data.message || 'Error desconocido'));
                }
            } catch (parseError) {
                console.error('Error parsing JSON:', parseError);
                console.error('Response text was:', xhr.responseText);
                alert('Error al procesar la respuesta del servidor');
            }
        } else {
            hideLoading();
            console.error('HTTP error:', xhr.status);
            alert('Error al cargar los datos del curso. Código: ' + xhr.status);
        }
    };
    
    xhr.onerror = function() {
        hideLoading();
        console.error('Network error');
        alert('Error de red al intentar cargar los datos del curso');
    };
    
    xhr.send();
}

function resetForm() {
    console.log('Reseteando formulario');
    
    // Limpiar todos los campos del formulario
    document.getElementById('cursoForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('cursoId').value = '';
    
    // Restaurar título y botón originales
    document.getElementById('modalTitle').textContent = 'Crear Nuevo Curso';
    document.getElementById('submitButtonText').textContent = 'Crear Curso';
}

// Validación del formulario
document.getElementById('cursoForm').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const numero_registro = document.getElementById('numero_registro').value.trim();
    const coordinador_id = document.getElementById('coordinador_id').value;
    const escuela_id = document.getElementById('escuela_id').value;
    
    if (!nombre || !numero_registro || !coordinador_id || !escuela_id) {
        e.preventDefault();
        alert('Por favor, complete todos los campos obligatorios.');
        return false;
    }
    
    // Validar fechas
    const fecha_inicio = document.getElementById('fecha_inicio').value;
    const fecha_fin = document.getElementById('fecha_fin').value;
    
    if (fecha_inicio && fecha_fin && new Date(fecha_inicio) > new Date(fecha_fin)) {
        e.preventDefault();
        alert('La fecha de inicio no puede ser posterior a la fecha de fin.');
        return false;
    }
    
    return true;
});

// Verificar que los elementos existen al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    console.log('Modal exists:', !!document.getElementById('createCourseModal'));
    console.log('Form exists:', !!document.getElementById('cursoForm'));
    console.log('Loading indicator exists:', !!document.getElementById('loadingIndicator'));
});
</script>

<?php include 'includes/footer.php'; ?>
