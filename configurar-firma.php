<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Solo roles que firman documentos pueden acceder
$auth->requireRole(['Coordinador', 'Director de Escuela', 'Educación DNBC', 'Dirección Nacional']);

$page_title = 'Configurar Firma';
$message = '';
$error = '';

// Procesar formulario
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_firma') {
        $tipo_firma = $_POST['tipo_firma'] ?? 'texto';
        $contenido_firma = '';
        
        if ($tipo_firma === 'texto') {
            $contenido_firma = trim($_POST['contenido_firma'] ?? '');
        } elseif ($tipo_firma === 'canvas') {
            $contenido_firma = $_POST['contenido_imagen'] ?? '';
        } elseif ($tipo_firma === 'upload') {
            // Manejar subida de archivo
            if (isset($_FILES['firma_upload']) && $_FILES['firma_upload']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/firmas/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['firma_upload']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    // Verificar tamaño del archivo (máximo 2MB)
                    if ($_FILES['firma_upload']['size'] <= 2 * 1024 * 1024) {
                        $file_name = 'firma_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($_FILES['firma_upload']['tmp_name'], $file_path)) {
                            $contenido_firma = $file_path;
                        } else {
                            $error = 'Error al subir el archivo';
                        }
                    } else {
                        $error = 'El archivo es demasiado grande. Máximo 2MB permitido.';
                    }
                } else {
                    $error = 'Solo se permiten archivos JPG, PNG y GIF';
                }
            } else {
                $error = 'Debe seleccionar un archivo de imagen';
            }
        }
        
        if ($contenido_firma && !$error) {
            try {
                // Iniciar transacción
                $db->beginTransaction();
                
                // Estrategia: Desactivar todas las firmas del usuario primero
                $query_deactivate = "UPDATE firmas_usuarios SET activa = FALSE WHERE usuario_id = ?";
                $stmt_deactivate = $db->prepare($query_deactivate);
                $stmt_deactivate->execute([$_SESSION['user_id']]);
                
                // Verificar si ya existe una firma inactiva con los mismos datos
                $query_check = "SELECT id FROM firmas_usuarios 
                               WHERE usuario_id = ? AND tipo_firma = ? AND activa = FALSE 
                               LIMIT 1";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$_SESSION['user_id'], $tipo_firma]);
                $existing_firma = $stmt_check->fetch();
                
                if ($existing_firma) {
                    // Actualizar la firma existente
                    $query_update = "UPDATE firmas_usuarios 
                                    SET contenido_firma = ?, activa = TRUE, created_at = NOW() 
                                    WHERE id = ?";
                    $stmt_update = $db->prepare($query_update);
                    $stmt_update->execute([$contenido_firma, $existing_firma['id']]);
                } else {
                    // Insertar nueva firma
                    $query_insert = "INSERT INTO firmas_usuarios (usuario_id, tipo_firma, contenido_firma, activa) 
                                    VALUES (?, ?, ?, TRUE)";
                    $stmt_insert = $db->prepare($query_insert);
                    $stmt_insert->execute([$_SESSION['user_id'], $tipo_firma, $contenido_firma]);
                }
                
                // Confirmar transacción
                $db->commit();
                
                $message = 'Firma configurada exitosamente';
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $db->rollback();
                $error = 'Error al guardar la firma: ' . $e->getMessage();
            }
        } elseif (!$error) {
            $error = 'Debe proporcionar el contenido de la firma';
        }
    }
}

// Obtener firma actual del usuario
$query_firma = "SELECT * FROM firmas_usuarios WHERE usuario_id = ? AND activa = TRUE";
$stmt_firma = $db->prepare($query_firma);
$stmt_firma->execute([$_SESSION['user_id']]);
$firma_actual = $stmt_firma->fetch();

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">
        <i class="fas fa-signature text-blue-600 mr-3"></i>Configurar Firma
    </h1>
    <p class="text-gray-600">Configure su firma para documentos oficiales</p>
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Configurar Firma -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-edit text-blue-600 mr-2"></i>Configurar Nueva Firma
        </h3>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4" id="firma-form">
            <input type="hidden" name="action" value="save_firma">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Firma</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="tipo_firma" value="texto" checked class="mr-2" onchange="toggleFirmaType()">
                        <span>Firma de Texto</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="tipo_firma" value="canvas" class="mr-2" onchange="toggleFirmaType()">
                        <span>Dibujar Firma</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="tipo_firma" value="upload" class="mr-2" onchange="toggleFirmaType()">
                        <span>Subir Imagen de Firma</span>
                    </label>
                </div>
            </div>
            
            <!-- Firma de Texto -->
            <div id="firma-texto">
                <label class="block text-sm font-medium text-gray-700 mb-2">Texto de la Firma</label>
                <input type="text" name="contenido_firma" id="contenido_texto" 
                       value="<?php echo $firma_actual && $firma_actual['tipo_firma'] === 'texto' ? htmlspecialchars($firma_actual['contenido_firma']) : htmlspecialchars($_SESSION['user_name']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Ej: Dr. Juan Pérez - Coordinador">
                <p class="text-sm text-gray-500 mt-1">Este texto aparecerá como su firma en los documentos</p>
            </div>
            
            <!-- Dibujar Firma -->
            <div id="firma-canvas" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Dibujar Firma</label>
                <div class="border border-gray-300 rounded-lg p-4">
                    <canvas id="signature-canvas" width="400" height="150" class="border border-gray-200 rounded cursor-crosshair bg-white w-full"></canvas>
                    <div class="mt-2 space-x-2">
                        <button type="button" onclick="clearSignature()" class="px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600">
                            <i class="fas fa-eraser mr-1"></i>Limpiar
                        </button>
                        <button type="button" onclick="saveCanvasSignature()" class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">
                            <i class="fas fa-save mr-1"></i>Preparar Firma
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Dibuje su firma usando el mouse o dedo (en dispositivos táctiles)</p>
                </div>
                <input type="hidden" name="contenido_imagen" id="contenido_canvas">
            </div>
            
            <!-- Subir Imagen -->
            <div id="firma-upload" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subir Imagen de Firma</label>
                <div class="border border-gray-300 rounded-lg p-4">
                    <input type="file" name="firma_upload" id="firma_upload" accept="image/*" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="preview-upload" class="mt-3 hidden">
                        <p class="text-sm text-gray-600 mb-2">Vista previa:</p>
                        <img id="preview-image" src="/placeholder.svg" alt="Vista previa" class="max-h-32 border rounded shadow-sm">
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Formatos: JPG, PNG, GIF | Tamaño máximo: 2MB | Recomendado: fondo transparente
                    </p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="submit" class="btn-primary px-6 py-2 text-white rounded-lg hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i>Guardar Configuración
                </button>
            </div>
        </form>
    </div>
    
    <!-- Vista Previa de Firma -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-eye text-green-600 mr-2"></i>Firma Actual
        </h3>
        
        <?php if ($firma_actual): ?>
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <div class="text-sm text-gray-600 mb-2">
                Tipo: 
                <?php 
                switch($firma_actual['tipo_firma']) {
                    case 'texto': echo 'Texto'; break;
                    case 'canvas': echo 'Dibujada'; break;
                    case 'upload': echo 'Imagen Subida'; break;
                    default: echo ucfirst($firma_actual['tipo_firma']);
                }
                ?>
            </div>
            
            <?php if ($firma_actual['tipo_firma'] === 'texto'): ?>
            <div class="font-signature text-xl text-gray-800 py-4 border-b-2 border-gray-400 inline-block">
                <?php echo htmlspecialchars($firma_actual['contenido_firma']); ?>
            </div>
            <?php else: ?>
            <div class="py-4">
                <img src="<?php echo htmlspecialchars($firma_actual['contenido_firma']); ?>" 
                     alt="Firma" class="max-h-20 border-b-2 border-gray-400" 
                     onerror="this.src='/placeholder.svg?height=60&width=200&text=Error+cargando+firma'">
            </div>
            <?php endif; ?>
            
            <div class="text-xs text-gray-500 mt-2">
                Configurada el: <?php echo date('d/m/Y H:i', strtotime($firma_actual['created_at'])); ?>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-signature text-4xl mb-4"></i>
            <p>No tienes una firma configurada</p>
            <p class="text-sm">Configure su firma para poder firmar documentos</p>
        </div>
        <?php endif; ?>
        
        <!-- Información sobre el uso de la firma -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="font-semibold text-blue-800 mb-2">
                <i class="fas fa-info-circle mr-1"></i>Información
            </h4>
            <ul class="text-sm text-blue-700 space-y-1">
                <li>• Su firma aparecerá en todos los documentos que firme</li>
                <li>• Como <?php echo $_SESSION['user_role']; ?>, puede firmar: Actas, Informes y Certificados</li>
                <li>• La firma se registra con fecha y hora automáticamente</li>
                <li>• Puede cambiar su firma en cualquier momento</li>
                <li>• Solo puede tener una firma activa a la vez</li>
            </ul>
        </div>
    </div>
</div>

<style>
.font-signature {
    font-family: 'Brush Script MT', cursive;
}
</style>

<script>
let canvas, ctx, isDrawing = false;

function toggleFirmaType() {
    const tipoTexto = document.querySelector('input[name="tipo_firma"][value="texto"]').checked;
    const tipoCanvas = document.querySelector('input[name="tipo_firma"][value="canvas"]').checked;
    const tipoUpload = document.querySelector('input[name="tipo_firma"][value="upload"]').checked;
    
    const firmaTexto = document.getElementById('firma-texto');
    const firmaCanvas = document.getElementById('firma-canvas');
    const firmaUpload = document.getElementById('firma-upload');
    
    // Ocultar todos
    firmaTexto.classList.add('hidden');
    firmaCanvas.classList.add('hidden');
    firmaUpload.classList.add('hidden');
    
    // Limpiar validaciones
    document.getElementById('contenido_texto').required = false;
    document.getElementById('contenido_canvas').required = false;
    if (document.getElementById('firma_upload')) {
        document.getElementById('firma_upload').required = false;
    }
    
    // Mostrar el seleccionado
    if (tipoTexto) {
        firmaTexto.classList.remove('hidden');
        document.getElementById('contenido_texto').required = true;
    } else if (tipoCanvas) {
        firmaCanvas.classList.remove('hidden');
        document.getElementById('contenido_canvas').required = true;
        setTimeout(initCanvas, 100); // Delay para asegurar que el canvas esté visible
    } else if (tipoUpload) {
        firmaUpload.classList.remove('hidden');
        if (document.getElementById('firma_upload')) {
            document.getElementById('firma_upload').required = true;
        }
    }
}

function initCanvas() {
    canvas = document.getElementById('signature-canvas');
    if (!canvas) return;
    
    ctx = canvas.getContext('2d');
    
    // Configurar canvas
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    // Limpiar canvas con fondo blanco
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Remover listeners anteriores
    canvas.removeEventListener('mousedown', startDrawing);
    canvas.removeEventListener('mousemove', draw);
    canvas.removeEventListener('mouseup', stopDrawing);
    canvas.removeEventListener('mouseout', stopDrawing);
    canvas.removeEventListener('touchstart', handleTouch);
    canvas.removeEventListener('touchmove', handleTouch);
    canvas.removeEventListener('touchend', stopDrawing);
    
    // Agregar listeners
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    canvas.addEventListener('touchstart', handleTouch);
    canvas.addEventListener('touchmove', handleTouch);
    canvas.addEventListener('touchend', stopDrawing);
}

function startDrawing(e) {
    isDrawing = true;
    const rect = canvas.getBoundingClientRect();
    ctx.beginPath();
    ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
}

function draw(e) {
    if (!isDrawing) return;
    const rect = canvas.getBoundingClientRect();
    ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
    ctx.stroke();
}

function stopDrawing() {
    isDrawing = false;
}

function handleTouch(e) {
    e.preventDefault();
    const touch = e.touches[0];
    const mouseEvent = new MouseEvent(e.type === 'touchstart' ? 'mousedown' : 
                                     e.type === 'touchmove' ? 'mousemove' : 'mouseup', {
        clientX: touch.clientX,
        clientY: touch.clientY
    });
    canvas.dispatchEvent(mouseEvent);
}

function clearSignature() {
    if (ctx && canvas) {
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        document.getElementById('contenido_canvas').value = '';
    }
}

function saveCanvasSignature() {
    if (canvas) {
        const dataURL = canvas.toDataURL('image/png');
        document.getElementById('contenido_canvas').value = dataURL;
        alert('Firma preparada. Haga clic en "Guardar Configuración" para confirmar.');
    }
}

// Vista previa de imagen subida
document.getElementById('firma_upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validar tamaño
        if (file.size > 2 * 1024 * 1024) {
            alert('El archivo es demasiado grande. Máximo 2MB permitido.');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('preview-upload');
            const img = document.getElementById('preview-image');
            img.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
});

// Validación del formulario
document.getElementById('firma-form').addEventListener('submit', function(e) {
    const tipoCanvas = document.querySelector('input[name="tipo_firma"][value="canvas"]').checked;
    const contenidoCanvas = document.getElementById('contenido_canvas').value;
    
    if (tipoCanvas && !contenidoCanvas) {
        e.preventDefault();
        alert('Debe dibujar su firma y hacer clic en "Preparar Firma" antes de guardar.');
        return false;
    }
});

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    toggleFirmaType();
});
</script>

<?php include 'includes/footer.php'; ?>
