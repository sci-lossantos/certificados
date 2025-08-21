<?php
require_once 'participante-auth.php';
require_once 'config/database.php';

$auth = new ParticipanteAuth();
$auth->requireLogin();

$conn = getMySQLiConnection();
$participante_id = $_SESSION['participante_id'];

// Obtener estadísticas del participante (consulta simplificada)
$stats_sql = "
    SELECT 
        COUNT(DISTINCT m.curso_id) as total_cursos,
        COUNT(DISTINCT CASE WHEN m.calificacion >= 70 THEN m.curso_id END) as cursos_aprobados,
        COUNT(DISTINCT d.id) as certificados_generados,
        COUNT(DISTINCT CASE WHEN d.estado = 'completado' THEN d.id END) as certificados_completados
    FROM matriculas m
    LEFT JOIN documentos d ON d.participante_id = m.participante_id AND d.curso_id = m.curso_id AND d.tipo = 'certificado'
    WHERE m.participante_id = ?
";

$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $participante_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

// Si no hay estadísticas, inicializar con ceros
if (!$stats) {
    $stats = [
        'total_cursos' => 0,
        'cursos_aprobados' => 0,
        'certificados_generados' => 0,
        'certificados_completados' => 0
    ];
}

// Obtener información del participante
$sql_participante = "SELECT * FROM participantes WHERE id = ?";
$stmt = $conn->prepare($sql_participante);
$stmt->bind_param("i", $participante_id);
$stmt->execute();
$result_participante = $stmt->get_result();

if ($result_participante->num_rows > 0) {
    $participante = $result_participante->fetch_assoc();
} else {
    echo "Participante no encontrado.";
    exit();
}

// Obtener certificados del participante (usando la tabla documentos)
$certificados_sql = "
    SELECT 
        d.id,
        d.tipo,
        d.estado,
        d.codigo_unico,
        d.fecha_generacion,
        d.fecha_completado,
        c.nombre as curso_nombre,
        c.duracion_horas,
        m.calificacion,
        m.fecha_finalizacion,
        e.nombre as escuela_nombre,
        COALESCE((SELECT COUNT(*) FROM firmas_documentos fd WHERE fd.documento_id = d.id AND fd.es_rechazo = 0), 0) as firmas_completadas,
        COALESCE((
            SELECT COUNT(*) 
            FROM firmas_documentos fd 
            JOIN usuarios u ON fd.usuario_id = u.id
            JOIN roles r ON u.rol_id = r.id
            WHERE fd.documento_id = d.id AND fd.es_rechazo = 0 AND r.nombre IN ('Coordinador', 'Director de Escuela', 'Educación DNBC', 'Dirección Nacional')
        ), 0) as total_firmas_requeridas
    FROM documentos d
    INNER JOIN cursos c ON d.curso_id = c.id
    INNER JOIN matriculas m ON m.participante_id = d.participante_id AND m.curso_id = d.curso_id
    INNER JOIN escuelas e ON c.escuela_id = e.id
    WHERE d.participante_id = ? AND d.tipo = 'certificado'
    ORDER BY d.fecha_generacion DESC
";

$stmt = $conn->prepare($certificados_sql);
$stmt->bind_param("i", $participante_id);
$stmt->execute();
$result_certificados = $stmt->get_result();
$certificados = $result_certificados->fetch_all(MYSQLI_ASSOC);

// Verificar y actualizar el estado de los certificados si es necesario
foreach ($certificados as &$cert) {
    // Si tiene todas las firmas pero no está marcado como completado
    if ($cert['firmas_completadas'] >= 4 && $cert['estado'] !== 'completado') {
        // Actualizar el estado a completado
        $update_sql = "UPDATE documentos SET estado = 'completado', fecha_completado = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $cert['id']);
        $update_stmt->execute();
        
        // Actualizar el estado en el array actual
        $cert['estado'] = 'completado';
    }
}

// Obtener historial académico
$historial_sql = "
    SELECT 
        c.nombre as curso_nombre,
        c.duracion_horas,
        e.nombre as escuela_nombre,
        m.calificacion,
        m.fecha_inicio,
        m.fecha_finalizacion,
        m.estado,
        CASE 
            WHEN m.calificacion >= 70 THEN 'Aprobado'
            WHEN m.calificacion IS NULL OR m.estado = 'inscrito' THEN 'En Curso'
            ELSE 'No Aprobado'
        END as estado_texto
    FROM matriculas m
    INNER JOIN cursos c ON m.curso_id = c.id
    INNER JOIN escuelas e ON c.escuela_id = e.id
    WHERE m.participante_id = ?
    ORDER BY m.fecha_inicio DESC
";

$stmt = $conn->prepare($historial_sql);
$stmt->bind_param("i", $participante_id);
$stmt->execute();
$result = $stmt->get_result();
$historial = $result->fetch_all(MYSQLI_ASSOC);

function getEstadoBadge($estado) {
    switch($estado) {
        case 'completado':
            return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Completado</span>';
        case 'en_proceso':
        case 'firmado_coordinador':
        case 'firmado_director_escuela':
        case 'revisado_educacion_dnbc':
        case 'firmado_director_nacional':
            return '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>En Proceso</span>';
        case 'pendiente':
        case 'generado':
            return '<span class="badge bg-secondary"><i class="fas fa-hourglass-half me-1"></i>Pendiente</span>';
        default:
            return '<span class="badge bg-light text-dark">' . ucfirst($estado) . '</span>';
    }
}

function getProgresoFirmas($firmas_completadas, $total_firmas) {
    if ($total_firmas == 0) return 0;
    return round(($firmas_completadas / $total_firmas) * 100);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Dashboard - Portal del Participante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Navbar */
        .navbar {
            background: #1e293b !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand img {
            height: 35px;
        }

        .navbar-brand {
            font-weight: 600;
            color: white !important;
        }

        /* Header */
        .page-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .page-header p {
            opacity: 0.9;
            margin: 0.5rem 0 0 0;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-card.primary { border-left-color: #3b82f6; }
        .stat-card.success { border-left-color: #10b981; }
        .stat-card.warning { border-left-color: #f59e0b; }
        .stat-card.danger { border-left-color: #ef4444; }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-card.primary .stat-number { color: #3b82f6; }
        .stat-card.success .stat-number { color: #10b981; }
        .stat-card.warning .stat-number { color: #f59e0b; }
        .stat-card.danger .stat-number { color: #ef4444; }

        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-icon {
            float: right;
            font-size: 2rem;
            opacity: 0.3;
            margin-top: -0.5rem;
        }

        /* Cards */
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header-custom {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
        }

        .card-header-custom h5 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }

        .card-body-custom {
            padding: 1.5rem;
        }

        /* Table */
        .table-custom {
            margin: 0;
        }

        .table-custom thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #475569;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem;
        }

        .table-custom tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Progress */
        .progress-custom {
            height: 6px;
            background-color: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar-custom {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        /* Buttons */
        .btn-download {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: white;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-download:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            color: white;
        }

        /* Badges */
        .badge {
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h5 {
            color: #374151;
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .page-header {
                padding: 1.5rem 0;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="public/images/dnbc-logo.png" alt="DNBC Logo" class="me-2">
                Portal del Participante
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['participante_nombre']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="participante-logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-tachometer-alt me-2"></i>Mi Dashboard</h1>
                    <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['participante_nombre']); ?>. Aquí puedes ver tu progreso académico y certificados.</p>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <i class="fas fa-graduation-cap" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-number"><?php echo $stats['total_cursos']; ?></div>
                <div class="stat-label">Cursos Inscritos</div>
                <i class="fas fa-book stat-icon"></i>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['cursos_aprobados']; ?></div>
                <div class="stat-label">Cursos Aprobados</div>
                <i class="fas fa-check-circle stat-icon"></i>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $stats['certificados_generados']; ?></div>
                <div class="stat-label">Certificados Generados</div>
                <i class="fas fa-certificate stat-icon"></i>
            </div>
            <div class="stat-card danger">
                <div class="stat-number"><?php echo $stats['certificados_completados']; ?></div>
                <div class="stat-label">Certificados Listos</div>
                <i class="fas fa-download stat-icon"></i>
            </div>
        </div>

        <!-- Certificados -->
        <div class="content-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-certificate me-2"></i>Mis Certificados</h5>
            </div>
            <div class="card-body-custom">
                <?php if (empty($certificados)): ?>
                    <div class="empty-state">
                        <i class="fas fa-certificate"></i>
                        <h5>No tienes certificados generados</h5>
                        <p>Los certificados se generan automáticamente cuando apruebas un curso.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Escuela</th>
                                    <th>Calificación</th>
                                    <th>Estado</th>
                                    <th>Progreso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificados as $cert): ?>
                                    <?php 
                                    // Verificar si tiene todas las firmas necesarias (4 firmas)
                                    $todas_firmas = ($cert['firmas_completadas'] >= 4);
                                    $progreso = getProgresoFirmas($cert['firmas_completadas'], 4); // Siempre necesitamos 4 firmas
                                    ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($cert['curso_nombre']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo $cert['duracion_horas']; ?> horas</small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($cert['escuela_nombre']); ?></td>
                                        <td>
                                            <?php if ($cert['calificacion']): ?>
                                                <span class="badge bg-success"><?php echo $cert['calificacion']; ?>%</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($todas_firmas || $cert['estado'] === 'completado'): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Completado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>En Proceso</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="progress-custom mb-1">
                                                <div class="progress-bar-custom" style="width: <?php echo $progreso; ?>%"></div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo $cert['firmas_completadas']; ?>/4 firmas
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($todas_firmas || $cert['estado'] === 'completado'): ?>
                                                <a href="tcpdf-certificate-configurable.php?id=<?php echo $cert['id']; ?>" 
                                                   class="btn btn-download btn-sm" target="_blank">
                                                    <i class="fas fa-download me-1"></i>Descargar
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    <i class="fas fa-clock me-1"></i>En Proceso
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historial Académico -->
        <div class="content-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-history me-2"></i>Historial Académico</h5>
            </div>
            <div class="card-body-custom">
                <?php if (empty($historial)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book"></i>
                        <h5>No tienes cursos registrados</h5>
                        <p>Tu historial académico aparecerá aquí cuando te inscribas en cursos.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Escuela</th>
                                    <th>Duración</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th>Calificación</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historial as $curso): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($curso['curso_nombre']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($curso['escuela_nombre']); ?></td>
                                        <td><?php echo $curso['duracion_horas']; ?> horas</td>
                                        <td>
                                            <?php echo $curso['fecha_inicio'] ? date('d/m/Y', strtotime($curso['fecha_inicio'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php echo $curso['fecha_finalizacion'] ? date('d/m/Y', strtotime($curso['fecha_finalizacion'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if ($curso['calificacion']): ?>
                                                <span class="badge <?php echo $curso['calificacion'] >= 70 ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $curso['calificacion']; ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            switch($curso['estado_texto']) {
                                                case 'Aprobado':
                                                    echo '<span class="badge bg-success">Aprobado</span>';
                                                    break;
                                                case 'En Curso':
                                                    echo '<span class="badge bg-primary">En Curso</span>';
                                                    break;
                                                case 'No Aprobado':
                                                    echo '<span class="badge bg-danger">No Aprobado</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-secondary">' . $curso['estado_texto'] . '</span>';
                                            }
                                            ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
