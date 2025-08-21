<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireRole(['Coordinador']);

$page_title = 'Calificaciones';
$message = '';
$error = '';

// Verificar y crear columnas necesarias en la tabla matriculas
try {
    // Verificar si existe la columna updated_at
    $check_updated_at = $db->query("SHOW COLUMNS FROM matriculas LIKE 'updated_at'");
    if ($check_updated_at->rowCount() == 0) {
        $db->exec("ALTER TABLE matriculas ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL");
    }
    
    // Verificar si existe la columna created_at
    $check_created_at = $db->query("SHOW COLUMNS FROM matriculas LIKE 'created_at'");
    if ($check_created_at->rowCount() == 0) {
        $db->exec("ALTER TABLE matriculas ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }
    
    // Verificar si existe la columna escala_calificacion en cursos
    $check_escala = $db->query("SHOW COLUMNS FROM cursos LIKE 'escala_calificacion'");
    if ($check_escala->rowCount() == 0) {
        $db->exec("ALTER TABLE cursos ADD COLUMN escala_calificacion ENUM('0-5', '0-100') DEFAULT '0-5'");
    }
    
    // Verificar y ajustar el tipo de dato de la columna calificacion
    $check_calificacion = $db->query("SHOW COLUMNS FROM matriculas LIKE 'calificacion'");
    $calificacion_info = $check_calificacion->fetch();
    
    if ($calificacion_info && strpos($calificacion_info['Type'], 'decimal(5,2)') === false) {
        $db->exec("ALTER TABLE matriculas MODIFY COLUMN calificacion DECIMAL(5,2) NULL DEFAULT NULL");
    }
    
} catch (Exception $e) {
    error_log("Error al verificar/crear columnas en tablas: " . $e->getMessage());
}

// Obtener cursos del coordinador
$query_cursos = "SELECT c.id, c.nombre, c.numero_registro, c.escala_calificacion,
                        (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id) as total_matriculados,
                        (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id AND m.calificacion IS NOT NULL) as calificados
                 FROM cursos c 
                 WHERE c.coordinador_id = ? AND c.activo = 1 
                 ORDER BY c.created_at DESC";
$stmt_cursos = $db->prepare($query_cursos);
$stmt_cursos->execute([$_SESSION['user_id']]);
$cursos = $stmt_cursos->fetchAll();

// Procesar cambio de escala de calificación
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'cambiar_escala') {
    $curso_id = $_POST['curso_id'] ?? '';
    $escala = $_POST['escala_calificacion'] ?? '';
    
    if ($curso_id && $escala && in_array($escala, ['0-5', '0-100'])) {
        try {
            // Verificar que el curso pertenece al coordinador
            $query_verify = "SELECT id, escala_calificacion FROM cursos WHERE id = ? AND coordinador_id = ? AND activo = 1";
            $stmt_verify = $db->prepare($query_verify);
            $stmt_verify->execute([$curso_id, $_SESSION['user_id']]);
            $curso_actual = $stmt_verify->fetch();
            
            if (!$curso_actual) {
                throw new Exception('No tienes permisos para modificar este curso');
            }
            
            $escala_anterior = $curso_actual['escala_calificacion'] ?? '0-5';
            
            // Solo convertir si la escala realmente cambió
            if ($escala_anterior !== $escala) {
                $db->beginTransaction();
                
                // Actualizar escala del curso
                $query_update = "UPDATE cursos SET escala_calificacion = ? WHERE id = ?";
                $stmt_update = $db->prepare($query_update);
                $stmt_update->execute([$escala, $curso_id]);
                
                // Convertir calificaciones existentes
                if ($escala === '0-100' && $escala_anterior === '0-5') {
                    // Convertir de 0-5 a 0-100
                    $query_convert = "UPDATE matriculas SET 
                                        calificacion = ROUND(calificacion * 20, 2),
                                        aprobado = IF(calificacion * 20 >= 60, 1, 0) 
                                      WHERE curso_id = ? AND calificacion IS NOT NULL";
                    $stmt_convert = $db->prepare($query_convert);
                    $stmt_convert->execute([$curso_id]);
                    
                } elseif ($escala === '0-5' && $escala_anterior === '0-100') {
                    // Convertir de 0-100 a 0-5
                    $query_convert = "UPDATE matriculas SET 
                                        calificacion = ROUND(calificacion / 20, 2),
                                        aprobado = IF(calificacion / 20 >= 3, 1, 0) 
                                      WHERE curso_id = ? AND calificacion IS NOT NULL";
                    $stmt_convert = $db->prepare($query_convert);
                    $stmt_convert->execute([$curso_id]);
                }
                
                $db->commit();
                $message = "Escala de calificación actualizada exitosamente de $escala_anterior a $escala";
            } else {
                $message = 'La escala ya estaba configurada en ' . $escala;
            }
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = 'Error al cambiar la escala: ' . $e->getMessage();
        }
    } else {
        $error = 'Datos incompletos o inválidos';
    }
}

// Procesar calificaciones
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'calificar') {
    $calificaciones = $_POST['calificaciones'] ?? [];
    $curso_id = $_POST['curso_id'] ?? '';
    
    if ($calificaciones && $curso_id) {
        try {
            // Verificar que el curso pertenece al coordinador
            $query_verify = "SELECT id, escala_calificacion FROM cursos WHERE id = ? AND coordinador_id = ? AND activo = 1";
            $stmt_verify = $db->prepare($query_verify);
            $stmt_verify->execute([$curso_id, $_SESSION['user_id']]);
            $curso_info = $stmt_verify->fetch();
            
            if (!$curso_info) {
                throw new Exception('No tienes permisos para calificar este curso');
            }
            
            $escala = $curso_info['escala_calificacion'] ?? '0-5';
            $nota_minima = ($escala === '0-5') ? 3.0 : 60.0;
            $nota_maxima = ($escala === '0-5') ? 5.0 : 100.0;
            
            $db->beginTransaction();
            
            $calificaciones_actualizadas = 0;
            $errores_calificacion = [];
            
            foreach ($calificaciones as $matricula_id => $calificacion) {
                if ($calificacion !== '' && is_numeric($calificacion)) {
                    $calificacion = floatval($calificacion);
                    
                    // Validar rango de calificación según escala
                    if ($calificacion < 0 || $calificacion > $nota_maxima) {
                        $errores_calificacion[] = "Calificación inválida: $calificacion (debe estar entre 0 y $nota_maxima)";
                        continue;
                    }
                    
                    $aprobado = $calificacion >= $nota_minima ? 1 : 0;
                    
                    // Verificar que la matrícula existe y pertenece al curso
                    $query_check_matricula = "SELECT m.id FROM matriculas m 
                                             JOIN cursos c ON m.curso_id = c.id 
                                             WHERE m.id = ? AND c.id = ? AND c.coordinador_id = ?";
                    $stmt_check = $db->prepare($query_check_matricula);
                    $stmt_check->execute([$matricula_id, $curso_id, $_SESSION['user_id']]);
                    
                    if ($stmt_check->fetch()) {
                        $query_update = "UPDATE matriculas SET calificacion = ?, aprobado = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                        $stmt_update = $db->prepare($query_update);
                        
                        if ($stmt_update->execute([$calificacion, $aprobado, $matricula_id])) {
                            $calificaciones_actualizadas++;
                        } else {
                            $errores_calificacion[] = "Error al actualizar matrícula ID: $matricula_id";
                        }
                    } else {
                        $errores_calificacion[] = "Matrícula no válida ID: $matricula_id";
                    }
                }
            }
            
            $db->commit();
            
            if ($calificaciones_actualizadas > 0) {
                $message = "Se actualizaron $calificaciones_actualizadas calificaciones exitosamente.";
                if (!empty($errores_calificacion)) {
                    $message .= " Errores encontrados: " . implode(', ', array_slice($errores_calificacion, 0, 3));
                    if (count($errores_calificacion) > 3) {
                        $message .= " y " . (count($errores_calificacion) - 3) . " más...";
                    }
                }
            } else {
                $error = 'No se actualizó ninguna calificación. ' . implode(', ', $errores_calificacion);
            }
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = 'Error al guardar calificaciones: ' . $e->getMessage();
        }
    } else {
        $error = 'Datos de calificación incompletos';
    }
}

// Obtener participantes de un curso específico si se selecciona
$participantes = [];
$curso_seleccionado = null;
if (isset($_GET['curso_id'])) {
    $curso_id = $_GET['curso_id'];
    
    // Verificar que el curso pertenece al coordinador
    $query_verify = "SELECT * FROM cursos WHERE id = ? AND coordinador_id = ?";
    $stmt_verify = $db->prepare($query_verify);
    $stmt_verify->execute([$curso_id, $_SESSION['user_id']]);
    $curso_seleccionado = $stmt_verify->fetch();
    
    if ($curso_seleccionado) {
        // Si no tiene escala definida, establecer por defecto
        if (empty($curso_seleccionado['escala_calificacion'])) {
            $query_update = "UPDATE cursos SET escala_calificacion = '0-5' WHERE id = ?";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->execute([$curso_id]);
            $curso_seleccionado['escala_calificacion'] = '0-5';
        }
        
        $query_participantes = "SELECT m.id as matricula_id, m.calificacion, m.aprobado, m.observaciones,
                                       p.nombres, p.apellidos, p.cedula, p.email, p.institucion, p.id as participante_id
                               FROM matriculas m 
                               JOIN participantes p ON m.participante_id = p.id 
                               WHERE m.curso_id = ? 
                               ORDER BY p.apellidos, p.nombres";
        $stmt_participantes = $db->prepare($query_participantes);
        $stmt_participantes->execute([$curso_id]);
        $participantes = $stmt_participantes->fetchAll();
    }
}

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">
        <i class="fas fa-clipboard-list text-blue-600 mr-3"></i>Calificaciones
    </h1>
    <p class="text-gray-600">Califica a los participantes de tus cursos</p>
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

<?php if (!$curso_seleccionado): ?>
<!-- Lista de cursos -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($cursos as $curso): ?>
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($curso['nombre']); ?></h3>
                <p class="text-sm text-gray-600">Registro: <?php echo htmlspecialchars($curso['numero_registro']); ?></p>
            </div>
        </div>
        
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Total matriculados:</span>
                <span class="font-semibold text-blue-600"><?php echo $curso['total_matriculados']; ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Calificados:</span>
                <span class="font-semibold text-green-600"><?php echo $curso['calificados']; ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <?php 
                $porcentaje = $curso['total_matriculados'] > 0 ? ($curso['calificados'] / $curso['total_matriculados']) * 100 : 0;
                ?>
                <div class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full transition-all duration-300" style="width: <?php echo $porcentaje; ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 text-center"><?php echo round($porcentaje, 1); ?>% completado</p>
            
            <div class="flex justify-between items-center pt-2">
                <span class="text-sm text-gray-600">Escala de calificación:</span>
                <span class="font-semibold text-purple-600">
                    <?php echo ($curso['escala_calificacion'] ?? '0-5') === '0-100' ? '0 a 100' : '0 a 5'; ?>
                </span>
            </div>
        </div>
        
        <div class="mt-6">
            <a href="?curso_id=<?php echo $curso['id']; ?>" class="w-full btn-primary text-white py-2 px-4 rounded-lg font-semibold text-center block hover:shadow-lg transition-all">
                <i class="fas fa-edit mr-2"></i>Calificar Participantes
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($cursos)): ?>
<div class="text-center py-12">
    <i class="fas fa-graduation-cap text-6xl text-gray-300 mb-4"></i>
    <h3 class="text-xl font-semibold text-gray-600 mb-2">No tienes cursos asignados</h3>
    <p class="text-gray-500">Contacta al administrador para que te asigne cursos como coordinador</p>
</div>
<?php endif; ?>

<?php else: ?>
<!-- Formulario de calificaciones -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($curso_seleccionado['nombre']); ?></h2>
            <p class="text-gray-600">Registro: <?php echo htmlspecialchars($curso_seleccionado['numero_registro']); ?></p>
        </div>
        <div class="flex items-center space-x-4">
            <form method="POST" class="flex items-center space-x-2" onsubmit="return confirm('¿Está seguro de cambiar la escala de calificación? Esto convertirá automáticamente las calificaciones existentes.');">
                <input type="hidden" name="action" value="cambiar_escala">
                <input type="hidden" name="curso_id" value="<?php echo $curso_seleccionado['id']; ?>">
                <label class="text-sm font-medium text-gray-700">Escala:</label>
                <select name="escala_calificacion" class="px-2 py-1 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="0-5" <?php echo ($curso_seleccionado['escala_calificacion'] ?? '0-5') === '0-5' ? 'selected' : ''; ?>>0 a 5</option>
                    <option value="0-100" <?php echo ($curso_seleccionado['escala_calificacion'] ?? '0-5') === '0-100' ? 'selected' : ''; ?>>0 a 100</option>
                </select>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-sm">
                    Cambiar
                </button>
            </form>
            <a href="calificaciones.php" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>
    
    <?php if (!empty($participantes)): ?>
    <form method="POST" id="calificacionesForm" class="space-y-4">
        <input type="hidden" name="action" value="calificar">
        <input type="hidden" name="curso_id" value="<?php echo $curso_seleccionado['id']; ?>">
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participante</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cédula</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Institución</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Calificación 
                            <span class="text-xs font-normal normal-case text-gray-500">
                                (<?php echo ($curso_seleccionado['escala_calificacion'] ?? '0-5') === '0-100' ? '0-100' : '0-5'; ?>)
                            </span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
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
                                $stmt_foto->execute([$participante['participante_id'] ?? 0]);
                                $foto_data = $stmt_foto->fetch();
                                $fotografia = $foto_data['fotografia'] ?? null;
                                ?>
                                
                                <?php if ($fotografia && file_exists($fotografia)): ?>
                                    <img src="<?php echo htmlspecialchars($fotografia); ?>" alt="Foto de <?php echo htmlspecialchars($participante['nombres']); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 shadow-sm">
                                <?php else: ?>
                                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold border-2 border-gray-200 shadow-sm">
                                        <?php echo strtoupper(substr($participante['nombres'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($participante['nombres'] . ' ' . $participante['apellidos']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($participante['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($participante['cedula']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($participante['institucion'] ?? 'No especificada'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $escala_actual = $curso_seleccionado['escala_calificacion'] ?? '0-5';
                            $min = 0;
                            $max = $escala_actual === '0-100' ? 100 : 5;
                            $step = $escala_actual === '0-100' ? 1 : 0.1;
                            $nota_minima = $escala_actual === '0-100' ? 60 : 3;
                            
                            // Formatear el valor actual según la escala
                            $valor_actual = '';
                            if ($participante['calificacion'] !== null) {
                                if ($escala_actual === '0-100') {
                                    $valor_actual = number_format((float)$participante['calificacion'], 0);
                                } else {
                                    $valor_actual = number_format((float)$participante['calificacion'], 1);
                                }
                            }
                            ?>
                            <input 
                                type="number" 
                                name="calificaciones[<?php echo $participante['matricula_id']; ?>]" 
                                value="<?php echo $valor_actual; ?>"
                                min="<?php echo $min; ?>" 
                                max="<?php echo $max; ?>" 
                                step="<?php echo $step; ?>" 
                                class="w-20 px-2 py-1 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="<?php echo $escala_actual === '0-100' ? '0' : '0.0'; ?>"
                                data-matricula-id="<?php echo $participante['matricula_id']; ?>"
                                data-nota-minima="<?php echo $nota_minima; ?>"
                                data-nota-maxima="<?php echo $max; ?>"
                            >
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap status-cell">
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="flex justify-between items-center pt-6 border-t border-gray-200">
            <div class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-2"></i>
                Nota mínima para aprobar: 
                <?php echo ($curso_seleccionado['escala_calificacion'] ?? '0-5') === '0-100' ? '60' : '3.0'; ?>
            </div>
            <button type="submit" id="submitCalificaciones" class="btn-primary text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
                <i class="fas fa-save mr-2"></i>Guardar Calificaciones
            </button>
        </div>
    </form>
    <?php else: ?>
    <div class="text-center py-12">
        <i class="fas fa-user-graduate text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay participantes matriculados</h3>
        <p class="text-gray-500">Este curso aún no tiene participantes matriculados</p>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Indicador de guardado -->
<div id="saveIndicator" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg hidden">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span>Guardando...</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('calificacionesForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input[type="number"]');
    const submitButton = document.getElementById('submitCalificaciones');
    
    // Validación en tiempo real
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            validateInput(this);
            updateStatus(this);
        });
        
        input.addEventListener('blur', function() {
            validateInput(this);
            updateStatus(this);
        });
    });
    
    function validateInput(input) {
        const value = parseFloat(input.value);
        const min = parseFloat(input.getAttribute('min'));
        const max = parseFloat(input.getAttribute('max'));
        
        // Remover clases de error previas
        input.classList.remove('border-red-500', 'border-green-500');
        
        if (input.value === '') {
            return true; // Campo vacío es válido
        }
        
        if (isNaN(value) || value < min || value > max) {
            input.classList.add('border-red-500');
            return false;
        } else {
            input.classList.add('border-green-500');
            return true;
        }
    }
    
    function updateStatus(input) {
        const value = parseFloat(input.value);
        const notaMinima = parseFloat(input.getAttribute('data-nota-minima'));
        const matriculaId = input.getAttribute('data-matricula-id');
        const statusCell = input.closest('tr').querySelector('.status-cell');
        
        if (input.value === '' || isNaN(value)) {
            statusCell.innerHTML = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    <i class="fas fa-clock mr-1"></i>Pendiente
                </span>
            `;
        } else if (value >= notaMinima) {
            statusCell.innerHTML = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-1"></i>Aprobado
                </span>
            `;
        } else {
            statusCell.innerHTML = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <i class="fas fa-times-circle mr-1"></i>No aprobado
                </span>
            `;
        }
    }
    
    // Validación del formulario antes de enviar
    form.addEventListener('submit', function(e) {
        let hasErrors = false;
        let hasValues = false;
        
        inputs.forEach(input => {
            if (input.value !== '') {
                hasValues = true;
                if (!validateInput(input)) {
                    hasErrors = true;
                }
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            alert('Por favor, corrige los errores en las calificaciones antes de guardar.');
            return false;
        }
        
        if (!hasValues) {
            e.preventDefault();
            alert('Debe ingresar al menos una calificación.');
            return false;
        }
        
        // Mostrar indicador de guardado
        const indicator = document.getElementById('saveIndicator');
        if (indicator) {
            indicator.classList.remove('hidden');
        }
        
        // Deshabilitar botón para evitar doble envío
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
        }
        
        return true;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
