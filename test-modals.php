<?php
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-dnbc-navy mb-8">Prueba de Modales DNBC</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Pruebas de Confirmación -->
        <div class="dnbc-card p-6">
            <h3 class="text-lg font-semibold text-dnbc-navy mb-4">Modales de Confirmación</h3>
            <div class="space-y-3">
                <button onclick="showConfirmModal('¿Estás seguro?', 'Esta acción no se puede deshacer.', function() { showNotification('success', 'Confirmado', 'Acción ejecutada correctamente'); })" 
                        class="dnbc-button-primary w-full px-4 py-2 rounded-lg">
                    Confirmar Acción
                </button>
                
                <button onclick="showConfirmModal('¿Eliminar usuario?', 'El usuario será eliminado permanentemente.', function() { showNotification('success', 'Eliminado', 'Usuario eliminado correctamente'); })" 
                        class="dnbc-button-secondary w-full px-4 py-2 rounded-lg">
                    Eliminar Usuario
                </button>
            </div>
        </div>

        <!-- Pruebas de Notificaciones -->
        <div class="dnbc-card p-6">
            <h3 class="text-lg font-semibold text-dnbc-navy mb-4">Notificaciones</h3>
            <div class="space-y-3">
                <button onclick="showNotification('success', 'Éxito', 'Operación completada correctamente')" 
                        class="bg-green-500 text-white w-full px-4 py-2 rounded-lg">
                    Éxito
                </button>
                
                <button onclick="showNotification('error', 'Error', 'Ha ocurrido un error inesperado')" 
                        class="bg-red-500 text-white w-full px-4 py-2 rounded-lg">
                    Error
                </button>
                
                <button onclick="showNotification('warning', 'Advertencia', 'Revisa los datos ingresados')" 
                        class="bg-yellow-500 text-white w-full px-4 py-2 rounded-lg">
                    Advertencia
                </button>
                
                <button onclick="showNotification('info', 'Información', 'Datos actualizados correctamente')" 
                        class="bg-blue-500 text-white w-full px-4 py-2 rounded-lg">
                    Información
                </button>
            </div>
        </div>

        <!-- Pruebas de Loading -->
        <div class="dnbc-card p-6">
            <h3 class="text-lg font-semibold text-dnbc-navy mb-4">Loading</h3>
            <div class="space-y-3">
                <button onclick="showLoading('Procesando datos...'); setTimeout(() => { hideLoading(); showNotification('success', 'Completado', 'Proceso finalizado'); }, 3000)" 
                        class="dnbc-button-primary w-full px-4 py-2 rounded-lg">
                    Mostrar Loading (3s)
                </button>
                
                <button onclick="showLoading('Guardando...'); setTimeout(() => { hideLoading(); showNotification('success', 'Guardado', 'Datos guardados correctamente'); }, 2000)" 
                        class="dnbc-button-secondary w-full px-4 py-2 rounded-lg">
                    Simular Guardado
                </button>
            </div>
        </div>
    </div>

    <!-- Prueba de funciones nativas interceptadas -->
    <div class="dnbc-card p-6 mt-6">
        <h3 class="text-lg font-semibold text-dnbc-navy mb-4">Pruebas de Interceptación</h3>
        <p class="text-dnbc-gray mb-4">Estas funciones deberían mostrar modales personalizados en lugar de ventanas nativas:</p>
        <div class="space-x-3">
            <button onclick="alert('Esta es una alerta interceptada')" 
                    class="dnbc-button-primary px-4 py-2 rounded-lg">
                alert() Interceptado
            </button>
            
            <button onclick="if(confirm('¿Confirmas esta acción?')) { showNotification('info', 'Confirmado', 'Acción confirmada'); }" 
                    class="dnbc-button-secondary px-4 py-2 rounded-lg">
                confirm() Interceptado
            </button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
