<?php
// Vista previa del certificado ESIBOC
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireRole(['Administrador General', 'Escuela', 'Director de Escuela', 'Coordinador']);

// Datos de ejemplo para la vista previa
$datos_ejemplo = [
    'nombres' => 'JUAN CARLOS',
    'apellidos' => 'MARTINEZ GARCIA',
    'cedula' => '1104131029',
    'curso_nombre' => 'SISTEMA COMANDO DE INCIDENTES BÁSICO PARA BOMBEROS',
    'numero_consecutivo' => '2025-184-01',
    'numero_acta' => '021',
    'fecha_acta' => '2025-07-24',
    'duracion_horas' => '24',
    'numero_registro_curso' => '184-2025',
    'lugar_realizacion' => 'Floridablanca – Santander',
    'fecha_inicio' => '2025-07-21',
    'fecha_fin' => '2025-07-23',
    'fecha_firma' => '2025-07-24',
    'escuela_nombre' => 'ESIBOC',
    'contenido_tematico' => "1. INTRODUCCIÓN\n• Propósito\n• Objetivos de desempeño\n• Objetivos de capacitación\n• Evaluaciones\n• Método\n• Reglas para participar\n\n2. ORIENTACIÓN E IMPLEMENTACIÓN DEL SCI\n• Contribución del SCI\n• Antecedentes del SCI\n• SCI como norma ISO\n• Ruta de Implementación\n• Cómo abordar las cinco fases del SCI\n• Detalle de acciones a desarrollar\n• Documentación"
];

$page_title = 'Vista Previa - Certificado ESIBOC';
include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-eye text-blue-600 mr-3"></i>Vista Previa - Certificado ESIBOC
            </h1>
            <p class="text-gray-600">Formato exacto según ejemplos proporcionados</p>
        </div>
        <div class="space-x-3">
            <a href="configuracion-certificados.php" class="btn-secondary px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
            <button onclick="generarPDF()" class="btn-primary px-6 py-3 text-white rounded-lg">
                <i class="fas fa-download mr-2"></i>Descargar PDF
            </button>
        </div>
    </div>
</div>

<!-- Vista previa del certificado -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-certificate mr-2"></i>Certificado ESIBOC - Página 1
        </h3>
    </div>
    
    <div class="p-8">
        <!-- Simulación del certificado -->
        <div class="border-2 border-gray-300 p-8 bg-gray-50 min-h-96 relative" style="aspect-ratio: 11/8.5;">
            
            <!-- Marca de agua simulada -->
            <div class="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none">
                <div class="transform rotate-90 text-6xl font-bold text-gray-400 tracking-widest">
                    BOMBEROS DE COLOMBIA
                </div>
            </div>
            
            <!-- Encabezado -->
            <div class="text-center mb-6">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-16 h-16 bg-blue-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-fire text-blue-600 text-2xl"></i>
                    </div>
                    <div class="flex-1 mx-4">
                        <p class="text-sm">Cuerpo de Bomberos Los Santos Santander</p>
                        <p class="text-sm">Escuela Internacional de Bomberos del Oriente Colombiano</p>
                        <p class="text-lg font-bold">ESIBOC</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold"><?php echo $datos_ejemplo['numero_consecutivo']; ?></p>
                        <p class="text-xs">No consecutivo</p>
                        <p class="text-xs">Certificado</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 text-xs mb-4">
                    <div class="text-left">
                        <p class="font-semibold">CT. EN JEFE LINA MARÍA MARÍN RODRÍGUEZ</p>
                        <p>Directora Nacional DNBC</p>
                    </div>
                    <div class="text-left">
                        <p class="font-semibold">CT. MANUEL ENRIQUE SALAZAR HERNANDEZ</p>
                        <p>Comandante Cuerpo de Bomberos Los Santos Sant.</p>
                    </div>
                </div>
            </div>
            
            <!-- Cuerpo del certificado -->
            <div class="text-center space-y-3">
                <h2 class="text-2xl font-bold text-blue-900">
                    <?php echo strtoupper($datos_ejemplo['nombres'] . ' ' . $datos_ejemplo['apellidos']); ?>
                </h2>
                
                <p class="text-lg"><?php echo number_format($datos_ejemplo['cedula'], 0, '', '.'); ?></p>
                
                <p class="text-sm">
                    Bajo acta número <?php echo $datos_ejemplo['numero_acta']; ?> del 
                    <?php echo date('d', strtotime($datos_ejemplo['fecha_acta'])); ?> 
                    <?php echo date('F', strtotime($datos_ejemplo['fecha_acta'])); ?> de 
                    <?php echo date('Y', strtotime($datos_ejemplo['fecha_acta'])); ?> del 
                    Cuerpo de Bomberos Voluntarios Los Santos
                </p>
                
                <p class="text-sm">Con una duración de: <?php echo $datos_ejemplo['duracion_horas']; ?> HORAS</p>
                
                <p class="text-sm">Certifica que:</p>
                <p class="text-sm">Identificado con C.C. No.</p>
                <p class="text-sm">Asistió y aprobó los requisitos del Curso:</p>
                
                <h3 class="text-lg font-bold text-red-600 my-4">
                    <?php echo strtoupper($datos_ejemplo['curso_nombre']); ?>
                </h3>
                
                <p class="text-xs">
                    Curso autorizado bajo registro Nro. <?php echo $datos_ejemplo['numero_registro_curso']; ?> 
                    de la Dirección Nacional de Bomberos Colombia
                </p>
                
                <p class="text-xs">
                    En constancia de lo anterior, se firma a los 
                    <?php echo date('d', strtotime($datos_ejemplo['fecha_firma'])); ?> dias del mes de 
                    <?php echo date('F', strtotime($datos_ejemplo['fecha_firma'])); ?> de 
                    <?php echo date('Y', strtotime($datos_ejemplo['fecha_firma'])); ?>
                </p>
                
                <p class="text-xs">
                    Realizado en (<?php echo $datos_ejemplo['lugar_realizacion']; ?>) del 
                    (<?php echo date('d', strtotime($datos_ejemplo['fecha_inicio'])); ?>) de 
                    (<?php echo date('F', strtotime($datos_ejemplo['fecha_inicio'])); ?>) al 
                    (<?php echo date('d', strtotime($datos_ejemplo['fecha_fin'])); ?>) de 
                    (<?php echo date('F', strtotime($datos_ejemplo['fecha_fin'])); ?>) de 
                    <?php echo date('Y', strtotime($datos_ejemplo['fecha_fin'])); ?>
                </p>
            </div>
            
            <!-- Firmas simuladas -->
            <div class="absolute bottom-8 left-8 right-8">
                <div class="grid grid-cols-2 gap-8">
                    <div class="text-center">
                        <div class="border-b border-gray-400 mb-2 h-8"></div>
                        <p class="text-xs font-semibold">LINA MARÍA MARÍN RODRÍGUEZ</p>
                        <p class="text-xs">Directora Nacional DNBC</p>
                    </div>
                    <div class="text-center">
                        <div class="border-b border-gray-400 mb-2 h-8"></div>
                        <p class="text-xs font-semibold">MANUEL ENRIQUE SALAZAR HERNANDEZ</p>
                        <p class="text-xs">Comandante Cuerpo de Bomberos Los Santos Sant.</p>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-xs">ESIBOC-CURSOS</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Segunda página - Contenido Programático -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-list mr-2"></i>Certificado ESIBOC - Página 2 (Contenido Programático)
        </h3>
    </div>
    
    <div class="p-8">
        <div class="border-2 border-gray-300 p-8 bg-gray-50 min-h-96 relative" style="aspect-ratio: 11/8.5;">
            
            <!-- Marca de agua simulada -->
            <div class="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none">
                <div class="transform rotate-90 text-6xl font-bold text-gray-400 tracking-widest">
                    BOMBEROS DE COLOMBIA
                </div>
            </div>
            
            <!-- Encabezado similar a página 1 -->
            <div class="text-center mb-6">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-16 h-16 bg-blue-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-fire text-blue-600 text-2xl"></i>
                    </div>
                    <div class="flex-1 mx-4">
                        <p class="text-sm">Cuerpo de Bomberos Los Santos Santander</p>
                        <p class="text-sm">Escuela Internacional de Bomberos del Oriente Colombiano</p>
                        <p class="text-lg font-bold">ESIBOC</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 text-xs mb-4">
                    <div class="text-left">
                        <p class="font-semibold">CT. EN JEFE LINA MARÍA MARÍN RODRÍGUEZ</p>
                        <p>Directora Nacional DNBC</p>
                    </div>
                    <div class="text-left">
                        <p class="font-semibold">CT. MANUEL ENRIQUE SALAZAR HERNANDEZ</p>
                        <p>Comandante Cuerpo de Bomberos Los Santos Sant.</p>
                    </div>
                </div>
            </div>
            
            <!-- Título -->
            <h2 class="text-xl font-bold text-center mb-4">CONTENIDO PROGRAMÁTICO</h2>
            <p class="text-center text-sm mb-2">ST. JORGE E. SERRANO PRADA</p>
            <p class="text-center text-xs mb-6">Coordinador Curso</p>
            
            <!-- Contenido en dos columnas -->
            <div class="grid grid-cols-2 gap-6 text-xs">
                <div class="space-y-2">
                    <p class="font-bold">1. INTRODUCCIÓN</p>
                    <p>• Propósito</p>
                    <p>• Objetivos de desempeño</p>
                    <p>• Objetivos de capacitación</p>
                    <p>• Evaluaciones</p>
                    <p>• Método</p>
                    <p>• Reglas para participar</p>
                    
                    <p class="font-bold mt-4">2. ORIENTACIÓN E IMPLEMENTACIÓN DEL SCI</p>
                    <p>• Contribución del SCI</p>
                    <p>• Antecedentes del SCI</p>
                    <p>• SCI como norma ISO</p>
                    <p>• Ruta de Implementación</p>
                </div>
                <div class="space-y-2">
                    <p class="font-bold">3. CARACTERÍSTICAS Y PRINCIPIOS DEL SCI</p>
                    <p>• Los incidentes y el SCI</p>
                    <p>• El SCI: un marco común de atención</p>
                    <p>• Definiciones relacionadas con el SCI</p>
                    <p>• Aplicaciones del SCI</p>
                    
                    <p class="font-bold mt-4">4. FUNCIONES Y RESPONSABILIDADES</p>
                    <p>• Funciones y responsabilidades</p>
                    <p>• Organigrama del SCI</p>
                    <p>• Staff de Comando y Secciones</p>
                    <p>• Delegación de funciones</p>
                </div>
            </div>
            
            <!-- Firma del coordinador -->
            <div class="absolute bottom-8 right-8">
                <div class="text-center w-48">
                    <div class="border-b border-gray-400 mb-2 h-8"></div>
                    <p class="text-xs font-semibold">ST. JORGE E. SERRANO PRADA</p>
                    <p class="text-xs">Coordinador Curso</p>
                </div>
            </div>
            
            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2">
                <p class="text-xs">ESIBOC-CURSOS</p>
            </div>
        </div>
    </div>
</div>

<script>
function generarPDF() {
    // Aquí podrías llamar al generador de PDF real
    alert('Funcionalidad de generación de PDF implementada en tcpdf-certificate-esiboc.php');
}
</script>

<?php include 'includes/footer.php'; ?>
