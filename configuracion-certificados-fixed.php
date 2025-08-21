<?php

try {
    // Verificar primero si la tabla y columnas existen
    $check_table = $pdo->query("SHOW COLUMNS FROM configuracion_certificados LIKE 'activo'");
    if ($check_table->rowCount() == 0) {
        // Si no existe la columna activo, crearla
        $pdo->exec("ALTER TABLE configuracion_certificados ADD COLUMN activo TINYINT(1) DEFAULT 1");
    }
    
    // Ahora hacer la consulta normal
    $stmt = $pdo->prepare("SELECT * FROM configuracion_certificados WHERE activo = 1 ORDER BY nombre");
    $stmt->execute();
    $configuraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Si hay error, mostrar mensaje más específico
    echo "<div class='alert alert-danger'>";
    echo "<h4>Error de Base de Datos</h4>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Por favor, ejecute el script de corrección: <code>scripts/fix-configuracion-certificados-activo.sql</code></p>";
    echo "</div>";
    $configuraciones = [];
}

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-certificate mr-2"></i>
                        Configuración de Certificados
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalNuevaConfiguracion">
                            <i class="fas fa-plus"></i> Nueva Configuración
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($configuraciones)): ?>
                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-info"></i> No hay configuraciones</h5>
                            No se encontraron configuraciones de certificados. 
                            <br>
                            <strong>Solución:</strong> Ejecute el script SQL: <code>scripts/fix-configuracion-certificados-activo.sql</code>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Escuela</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($configuraciones as $config): ?>
                                        <tr>
                                            <td><?= $config['id'] ?></td>
                                            <td><?= htmlspecialchars($config['nombre']) ?></td>
                                            <td><?= htmlspecialchars($config['descripcion'] ?? '') ?></td>
                                            <td>
                                                <?php if ($config['escuela_id']): ?>
                                                    Escuela ID: <?= $config['escuela_id'] ?>
                                                <?php else: ?>
                                                    <span class="badge badge-info">Genérica</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($config['activo']): ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="editarConfiguracion(<?= $config['id'] ?>)">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <button class="btn btn-sm btn-success" onclick="previsualizarCertificado(<?= $config['id'] ?>)">
                                                    <i class="fas fa-eye"></i> Vista Previa
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editarConfiguracion(id) {
    // Redirigir a página de edición
    window.location.href = 'editar-configuracion-certificado.php?id=' + id;
}

function previsualizarCertificado(id) {
    // Abrir vista previa en nueva ventana
    window.open('preview-certificate-esiboc.php?config_id=' + id, '_blank');
}
</script>
