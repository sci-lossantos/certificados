<?php
class DocumentWorkflow {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Obtiene el siguiente estado según el tipo de documento y rol actual
     */
    public function getNextState($documento_tipo, $estado_actual, $rol_usuario) {
        $flujos = [
            'acta' => [
                'generado' => ['coordinador' => 'firmado_coordinador'],
                'firmado_coordinador' => ['director_escuela' => 'firmado_director_escuela'],
                'firmado_director_escuela' => ['educacion_dnbc' => 'revisado_educacion_dnbc'],
                'revisado_educacion_dnbc' => ['director_nacional' => 'firmado_director_nacional'],
                'firmado_director_nacional' => ['completado']
            ],
            'informe' => [
                'generado' => ['coordinador' => 'firmado_coordinador'],
                'firmado_coordinador' => ['director_escuela' => 'revisado_director_escuela'],
                'revisado_director_escuela' => ['educacion_dnbc' => 'revisado_educacion_dnbc'],
                'revisado_educacion_dnbc' => ['completado']
            ],
            'certificado' => [
                'generado' => ['coordinador' => 'firmado_coordinador'],
                'firmado_coordinador' => ['director_escuela' => 'firmado_director_escuela'],
                'firmado_director_escuela' => ['educacion_dnbc' => 'revisado_educacion_dnbc'],
                'revisado_educacion_dnbc' => ['director_nacional' => 'firmado_director_nacional'],
                'firmado_director_nacional' => ['completado']
            ],
            'directorio' => [
                'generado' => ['coordinador' => 'revisado_coordinador'],
                'revisado_coordinador' => ['director_escuela' => 'revisado_director_escuela'],
                'revisado_director_escuela' => ['educacion_dnbc' => 'revisado_educacion_dnbc'],
                'revisado_educacion_dnbc' => ['completado']
            ]
        ];
        
        if (!isset($flujos[$documento_tipo][$estado_actual])) {
            return null;
        }
        
        $transiciones = $flujos[$documento_tipo][$estado_actual];
        
        // Mapear roles a nombres de flujo
        $rol_mapping = [
            'Coordinador' => 'coordinador',
            'Director de Escuela' => 'director_escuela',
            'Educación DNBC' => 'educacion_dnbc',
            'Dirección Nacional' => 'director_nacional'
        ];
        
        $rol_flujo = $rol_mapping[$rol_usuario] ?? null;
        
        if ($rol_flujo && isset($transiciones[$rol_flujo])) {
            return $transiciones[$rol_flujo];
        }
        
        return null;
    }
    
    /**
     * Determina si la acción es firma o revisión según el documento y rol
     */
    public function getActionType($documento_tipo, $rol_usuario) {
        // Definir qué roles firman qué documentos
        $acciones_firma = [
            'acta' => ['Coordinador', 'Director de Escuela', 'Dirección Nacional'],
            'informe' => ['Coordinador'],
            'certificado' => ['Coordinador', 'Director de Escuela', 'Dirección Nacional'],
            'directorio' => [] // Nadie firma directorios, solo se revisan
        ];
        
        if (isset($acciones_firma[$documento_tipo]) && 
            in_array($rol_usuario, $acciones_firma[$documento_tipo])) {
            return 'firma';
        }
        
        return 'revision';
    }
    
    /**
     * Verifica si un usuario puede procesar un documento
     */
    public function canProcessDocument($documento_id, $user_role) {
        try {
            $query = "SELECT d.tipo, d.estado FROM documentos d WHERE d.id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$documento_id]);
            $documento = $stmt->fetch();
            
            if (!$documento) {
                return false;
            }
            
            // Verificar si el documento fue rechazado recientemente
            $query_rechazo = "SELECT COUNT(*) as rechazos FROM firmas_documentos 
                             WHERE documento_id = ? AND es_rechazo = 1";
            $stmt_rechazo = $this->db->prepare($query_rechazo);
            $stmt_rechazo->execute([$documento_id]);
            $rechazo_info = $stmt_rechazo->fetch();
            
            // Si hay rechazos, el documento debe estar en un estado que permita corrección
            if ($rechazo_info['rechazos'] > 0) {
                // Solo el generador original o coordinador pueden "corregir" el documento
                if ($user_role === 'Escuela' || $user_role === 'Coordinador') {
                    return true;
                }
                return false;
            }
            
            $next_state = $this->getNextState($documento['tipo'], $documento['estado'], $user_role);
            return $next_state !== null;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verifica si un documento fue rechazado y necesita corrección
     */
    public function isDocumentRejected($documento_id) {
        try {
            $query = "SELECT COUNT(*) as rechazos FROM firmas_documentos 
                     WHERE documento_id = ? AND es_rechazo = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$documento_id]);
            $result = $stmt->fetch();
            
            return $result['rechazos'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Reinicia el flujo de un documento rechazado (para corrección)
     */
    public function restartDocumentFlow($documento_id, $user_id, $user_role) {
        try {
            $this->db->beginTransaction();
            
            // Verificar que el documento fue rechazado
            if (!$this->isDocumentRejected($documento_id)) {
                throw new Exception('Este documento no ha sido rechazado');
            }
            
            // Verificar permisos (solo Escuela o Coordinador pueden reiniciar)
            if (!in_array($user_role, ['Escuela', 'Coordinador'])) {
                throw new Exception('No tiene permisos para reiniciar este documento');
            }
            
            // Eliminar todas las firmas anteriores (incluyendo rechazos)
            $query_delete = "DELETE FROM firmas_documentos WHERE documento_id = ?";
            $stmt_delete = $this->db->prepare($query_delete);
            $stmt_delete->execute([$documento_id]);
            
            // Resetear el estado del documento a 'generado'
            $query_reset = "UPDATE documentos SET estado = 'generado' WHERE id = ?";
            $stmt_reset = $this->db->prepare($query_reset);
            $stmt_reset->execute([$documento_id]);
            
            // Registrar la corrección
            $query_correccion = "INSERT INTO firmas_documentos 
                               (documento_id, usuario_id, tipo_firma, accion, observaciones) 
                               VALUES (?, ?, 'correccion', 'correccion', 'Documento corregido y reenviado')";
            $stmt_correccion = $this->db->prepare($query_correccion);
            $stmt_correccion->execute([$documento_id, $user_id]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Documento corregido y reenviado para procesamiento'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Modificar el método processDocument para permitir que Coordinador y Director de Escuela puedan rechazar documentos

    /**
     * Procesa un documento (firma o revisión)
     */
    public function processDocument($documento_id, $user_id, $user_role, $observaciones = '', $accion = 'aprobar') {
        try {
            $this->db->beginTransaction();
            
            // Obtener información del documento
            $query = "SELECT d.tipo, d.estado FROM documentos d WHERE d.id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$documento_id]);
            $documento = $stmt->fetch();
            
            if (!$documento) {
                throw new Exception('Documento no encontrado');
            }
            
            // Verificar que el usuario puede procesar este documento
            $next_state = $this->getNextState($documento['tipo'], $documento['estado'], $user_role);
            if (!$next_state) {
                throw new Exception('No tiene permisos para procesar este documento en su estado actual');
            }
            
            // Mapear rol a tipo de firma
            $rol_mapping = [
                'Coordinador' => 'coordinador',
                'Director de Escuela' => 'director_escuela',
                'Educación DNBC' => 'educacion_dnbc',
                'Dirección Nacional' => 'director_nacional'
            ];
            
            $tipo_firma = $rol_mapping[$user_role];
            
            // Verificar que no haya firmado/revisado ya (excluyendo rechazos)
            $query_check = "SELECT id FROM firmas_documentos 
                           WHERE documento_id = ? AND usuario_id = ? AND tipo_firma = ? AND es_rechazo = 0";
            $stmt_check = $this->db->prepare($query_check);
            $stmt_check->execute([$documento_id, $user_id, $tipo_firma]);
            if ($stmt_check->fetch()) {
                throw new Exception('Ya ha procesado este documento anteriormente');
            }
            
            // Determinar el tipo de acción (firma o revisión)
            $action_type = $this->getActionType($documento['tipo'], $user_role);
            
            // Si la acción es rechazar, procesar el rechazo
            // Ahora todos los roles pueden rechazar documentos
            if ($accion === 'rechazar') {
                // Registrar el rechazo
                $query_insert = "INSERT INTO firmas_documentos 
                                (documento_id, usuario_id, tipo_firma, accion, observaciones, es_rechazo) 
                                VALUES (?, ?, ?, 'rechazo', ?, 1)";
                $stmt_insert = $this->db->prepare($query_insert);
                $stmt_insert->execute([$documento_id, $user_id, $tipo_firma, $observaciones]);
                
                // Determinar el estado anterior al que debe volver
                $estado_anterior = $this->getEstadoAnterior($documento['tipo'], $documento['estado']);
                
                // Actualizar el estado del documento para devolverlo
                $query_update = "UPDATE documentos SET estado = ? WHERE id = ?";
                $stmt_update = $this->db->prepare($query_update);
                $stmt_update->execute([$estado_anterior, $documento_id]);
                
                $this->db->commit();
                
                return [
                    'success' => true,
                    'action' => 'rechazo',
                    'new_state' => $estado_anterior,
                    'message' => 'Documento devuelto para corrección'
                ];
            }
            
            // Verificar que el usuario tiene firma configurada (solo para firmas)
            if ($action_type === 'firma') {
                $query_firma = "SELECT id FROM firmas_usuarios WHERE usuario_id = ? AND activa = TRUE";
                $stmt_firma = $this->db->prepare($query_firma);
                $stmt_firma->execute([$user_id]);
                if (!$stmt_firma->fetch()) {
                    throw new Exception('Debe configurar su firma antes de firmar documentos');
                }
            }
            
            // Si es aprobación normal, continuar con el flujo estándar
            // Registrar la firma/revisión
            $query_insert = "INSERT INTO firmas_documentos 
                            (documento_id, usuario_id, tipo_firma, accion, observaciones, es_rechazo) 
                            VALUES (?, ?, ?, ?, ?, 0)";
            $stmt_insert = $this->db->prepare($query_insert);
            $stmt_insert->execute([$documento_id, $user_id, $tipo_firma, $action_type, $observaciones]);
            
            // Actualizar estado del documento
            if ($next_state !== 'completado') {
                $query_update = "UPDATE documentos SET estado = ? WHERE id = ?";
                $stmt_update = $this->db->prepare($query_update);
                $stmt_update->execute([$next_state, $documento_id]);
            } else {
                // Marcar como completado
                $query_update = "UPDATE documentos SET estado = 'completado' WHERE id = ?";
                $stmt_update = $this->db->prepare($query_update);
                $stmt_update->execute([$documento_id]);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'action' => $action_type,
                'new_state' => $next_state === 'completado' ? 'completado' : $next_state,
                'message' => $action_type === 'firma' ? 'Documento firmado exitosamente' : 'Documento revisado exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Determina el estado anterior al que debe volver un documento rechazado
     */
    public function getEstadoAnterior($documento_tipo, $estado_actual) {
        $flujos_inversos = [
            'acta' => [
                'firmado_coordinador' => 'generado',
                'firmado_director_escuela' => 'firmado_coordinador',
                'revisado_educacion_dnbc' => 'firmado_director_escuela',
                'firmado_director_nacional' => 'revisado_educacion_dnbc'
            ],
            'informe' => [
                'firmado_coordinador' => 'generado',
                'revisado_director_escuela' => 'firmado_coordinador',
                'revisado_educacion_dnbc' => 'revisado_director_escuela'
            ],
            'certificado' => [
                'firmado_coordinador' => 'generado',
                'firmado_director_escuela' => 'firmado_coordinador',
                'revisado_educacion_dnbc' => 'firmado_director_escuela',
                'firmado_director_nacional' => 'revisado_educacion_dnbc'
            ],
            'directorio' => [
                'revisado_coordinador' => 'generado',
                'revisado_director_escuela' => 'revisado_coordinador',
                'revisado_educacion_dnbc' => 'revisado_director_escuela'
            ]
        ];
        
        // Si existe un estado anterior definido, devolverlo
        if (isset($flujos_inversos[$documento_tipo][$estado_actual])) {
            return $flujos_inversos[$documento_tipo][$estado_actual];
        }
        
        // Si no hay un estado anterior definido, devolver al estado inicial
        return 'generado';
    }
    
    /**
     * Obtiene documentos pendientes para un rol específico
     */
    public function getPendingDocuments($user_role) {
        $estados_por_rol = [
            'Coordinador' => ['generado'],
            'Director de Escuela' => ['firmado_coordinador', 'revisado_coordinador'],
            'Educación DNBC' => ['firmado_director_escuela', 'revisado_director_escuela'],
            'Dirección Nacional' => ['revisado_educacion_dnbc']
        ];
        
        if (!isset($estados_por_rol[$user_role])) {
            return [];
        }
        
        $estados = $estados_por_rol[$user_role];
        $placeholders = str_repeat('?,', count($estados) - 1) . '?';
        
        try {
            // Excluir documentos que han sido rechazados
            $query = "SELECT d.*, c.nombre as curso_nombre, c.numero_registro,
                            CONCAT(p.nombres, ' ', p.apellidos) as participante_nombre,
                            CONCAT(u.nombres, ' ', u.apellidos) as generado_por_nombre,
                            e.nombre as escuela_nombre
                     FROM documentos d 
                     JOIN cursos c ON d.curso_id = c.id 
                     JOIN escuelas e ON c.escuela_id = e.id
                     LEFT JOIN participantes p ON d.participante_id = p.id
                     LEFT JOIN usuarios u ON d.generado_por = u.id
                     WHERE d.estado IN ($placeholders)
                     AND d.id NOT IN (
                         SELECT DISTINCT documento_id 
                         FROM firmas_documentos 
                         WHERE es_rechazo = 1
                     )
                     ORDER BY d.created_at ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($estados);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtiene documentos rechazados que necesitan corrección
     */
    public function getRejectedDocuments($user_id) {
        try {
            $query = "SELECT DISTINCT d.*, c.nombre as curso_nombre, c.numero_registro,
                            CONCAT(p.nombres, ' ', p.apellidos) as participante_nombre,
                            CONCAT(u.nombres, ' ', u.apellidos) as generado_por_nombre,
                            e.nombre as escuela_nombre,
                            fd.observaciones as motivo_rechazo,
                            CONCAT(ru.nombres, ' ', ru.apellidos) as rechazado_por,
                            rr.nombre as rol_rechazo,
                            fd.fecha_firma as fecha_rechazo
                     FROM documentos d 
                     JOIN cursos c ON d.curso_id = c.id 
                     JOIN escuelas e ON c.escuela_id = e.id
                     LEFT JOIN participantes p ON d.participante_id = p.id
                     LEFT JOIN usuarios u ON d.generado_por = u.id
                     JOIN firmas_documentos fd ON d.id = fd.documento_id AND fd.es_rechazo = 1
                     JOIN usuarios ru ON fd.usuario_id = ru.id
                     JOIN roles rr ON ru.rol_id = rr.id
                     WHERE (d.generado_por = ? OR c.coordinador_id = ?)
                     AND d.id IN (
                         SELECT documento_id 
                         FROM firmas_documentos 
                         WHERE es_rechazo = 1
                     )
                     ORDER BY fd.fecha_firma DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtiene el historial de firmas de un documento
     */
    public function getDocumentHistory($documento_id) {
        try {
            $query = "SELECT fd.*, CONCAT(u.nombres, ' ', u.apellidos) as firmante_nombre,
                            r.nombre as rol_nombre
                     FROM firmas_documentos fd
                     JOIN usuarios u ON fd.usuario_id = u.id
                     JOIN roles r ON u.rol_id = r.id
                     WHERE fd.documento_id = ?
                     ORDER BY fd.fecha_firma ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$documento_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtiene información detallada del flujo para mostrar al usuario
     */
    public function getWorkflowInfo($documento_tipo) {
        $flujos_info = [
            'acta' => [
                'pasos' => [
                    ['rol' => 'Coordinador', 'accion' => 'Firma', 'descripcion' => 'Firma el acta'],
                    ['rol' => 'Director de Escuela', 'accion' => 'Firma', 'descripcion' => 'Firma el acta'],
                    ['rol' => 'Educación DNBC', 'accion' => 'Revisa', 'descripcion' => 'Revisa y aprueba'],
                    ['rol' => 'Dirección Nacional', 'accion' => 'Firma', 'descripcion' => 'Firma final']
                ]
            ],
            'informe' => [
                'pasos' => [
                    ['rol' => 'Coordinador', 'accion' => 'Firma', 'descripcion' => 'Firma el informe'],
                    ['rol' => 'Director de Escuela', 'accion' => 'Revisa', 'descripcion' => 'Revisa el informe'],
                    ['rol' => 'Educación DNBC', 'accion' => 'Revisa', 'descripcion' => 'Revisa y aprueba']
                ]
            ],
            'certificado' => [
                'pasos' => [
                    ['rol' => 'Coordinador', 'accion' => 'Firma', 'descripcion' => 'Firma el certificado'],
                    ['rol' => 'Director de Escuela', 'accion' => 'Firma', 'descripcion' => 'Firma el certificado'],
                    ['rol' => 'Educación DNBC', 'accion' => 'Revisa', 'descripcion' => 'Revisa y aprueba'],
                    ['rol' => 'Dirección Nacional', 'accion' => 'Firma', 'descripcion' => 'Firma final']
                ]
            ],
            'directorio' => [
                'pasos' => [
                    ['rol' => 'Coordinador', 'accion' => 'Revisa', 'descripcion' => 'Revisa el directorio'],
                    ['rol' => 'Director de Escuela', 'accion' => 'Revisa', 'descripcion' => 'Revisa el directorio'],
                    ['rol' => 'Educación DNBC', 'accion' => 'Revisa', 'descripcion' => 'Revisa y aprueba']
                ]
            ]
        ];
        
        return $flujos_info[$documento_tipo] ?? null;
    }
}
?>
