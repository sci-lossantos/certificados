<?php
// Funciones para manejo de numeración de certificados y actas
require_once 'config/database.php';

class CertificateNumbering {
    private $conn;
    
    public function __construct() {
        $this->conn = getMySQLiConnection();
    }
    
    /**
     * Genera el número consecutivo del certificado
     */
    public function generateConsecutiveNumber($documento_id, $participante_id, $curso_id, $escuela_id) {
        try {
            // Obtener configuración de la escuela
            $config_sql = "SELECT numero_registro, formato_consecutivo FROM configuracion_certificados WHERE escuela_id = ?";
            $stmt = $this->conn->prepare($config_sql);
            $stmt->bind_param("i", $escuela_id);
            $stmt->execute();
            $config = $stmt->get_result()->fetch_assoc();
            
            if (!$config) {
                $config = [
                    'numero_registro' => 'REG-001',
                    'formato_consecutivo' => '{registro}-{orden}'
                ];
            }
            
            // Obtener orden alfabético del participante en el curso
            $orden_alfabetico = $this->getOrdenAlfabetico($participante_id, $curso_id);
            
            // Generar número consecutivo
            $formato = $config['formato_consecutivo'];
            $consecutivo = str_replace(
                ['{registro}', '{orden}'],
                [$config['numero_registro'], str_pad($orden_alfabetico, 3, '0', STR_PAD_LEFT)],
                $formato
            );
            
            // Actualizar documento con el número consecutivo y orden
            $update_sql = "UPDATE documentos SET numero_consecutivo = ?, orden_alfabetico = ? WHERE id = ?";
            $stmt = $this->conn->prepare($update_sql);
            $stmt->bind_param("sii", $consecutivo, $orden_alfabetico, $documento_id);
            $stmt->execute();
            
            return $consecutivo;
            
        } catch (Exception $e) {
            error_log("Error generando número consecutivo: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtiene el orden alfabético del participante en el curso
     */
    private function getOrdenAlfabetico($participante_id, $curso_id) {
        $sql = "
            SELECT COUNT(*) + 1 as orden
            FROM matriculas m
            INNER JOIN participantes p ON m.participante_id = p.id
            INNER JOIN participantes p2 ON m.curso_id = ?
            WHERE m.curso_id = ? 
            AND m.calificacion >= 70 
            AND CONCAT(p2.nombres, ' ', p2.apellidos) < (
                SELECT CONCAT(p3.nombres, ' ', p3.apellidos) 
                FROM participantes p3 
                WHERE p3.id = ?
            )
            AND EXISTS (
                SELECT 1 FROM matriculas m2 
                WHERE m2.participante_id = p2.id 
                AND m2.curso_id = ? 
                AND m2.calificacion >= 70
            )
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiii", $curso_id, $curso_id, $participante_id, $curso_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['orden'] ?? 1;
    }
    
    /**
     * Genera o obtiene el número de acta para un curso
     */
    public function generateActaNumber($curso_id, $escuela_id) {
        try {
            // Verificar si ya existe un acta para este curso
            $check_sql = "SELECT numero_acta, fecha_acta FROM actas_certificacion WHERE curso_id = ? AND escuela_id = ?";
            $stmt = $this->conn->prepare($check_sql);
            $stmt->bind_param("ii", $curso_id, $escuela_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                return [
                    'numero_acta' => $existing['numero_acta'],
                    'fecha_acta' => $existing['fecha_acta']
                ];
            }
            
            // Obtener información del curso
            $curso_sql = "SELECT fecha_fin, nombre FROM cursos WHERE id = ?";
            $stmt = $this->conn->prepare($curso_sql);
            $stmt->bind_param("i", $curso_id);
            $stmt->execute();
            $curso = $stmt->get_result()->fetch_assoc();
            
            if (!$curso) {
                throw new Exception("Curso no encontrado");
            }
            
            // Obtener siguiente número de acta para la escuela
            $next_number_sql = "SELECT COALESCE(MAX(numero_acta), 0) + 1 as next_number FROM actas_certificacion WHERE escuela_id = ?";
            $stmt = $this->conn->prepare($next_number_sql);
            $stmt->bind_param("i", $escuela_id);
            $stmt->execute();
            $next_number = $stmt->get_result()->fetch_assoc()['next_number'];
            
            // Obtener estadísticas del curso
            $stats_sql = "
                SELECT 
                    COUNT(*) as total_participantes,
                    COUNT(CASE WHEN calificacion >= 70 THEN 1 END) as participantes_aprobados
                FROM matriculas 
                WHERE curso_id = ?
            ";
            $stmt = $this->conn->prepare($stats_sql);
            $stmt->bind_param("i", $curso_id);
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();
            
            // Crear nueva acta
            $fecha_acta = date('Y-m-d');
            $fecha_terminacion = $curso['fecha_fin'] ?? date('Y-m-d');
            
            $insert_sql = "
                INSERT INTO actas_certificacion (
                    numero_acta, curso_id, escuela_id, fecha_acta, fecha_terminacion_curso,
                    total_participantes, participantes_aprobados, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'generada')
            ";
            
            $stmt = $this->conn->prepare($insert_sql);
            $stmt->bind_param("iiissii", 
                $next_number, $curso_id, $escuela_id, $fecha_acta, $fecha_terminacion,
                $stats['total_participantes'], $stats['participantes_aprobados']
            );
            $stmt->execute();
            
            // Actualizar todos los documentos del curso con el número de acta
            $update_docs_sql = "
                UPDATE documentos 
                SET numero_acta = ?, fecha_acta = ? 
                WHERE curso_id = ? AND tipo = 'certificado'
            ";
            $stmt = $this->conn->prepare($update_docs_sql);
            $stmt->bind_param("isi", $next_number, $fecha_acta, $curso_id);
            $stmt->execute();
            
            return [
                'numero_acta' => $next_number,
                'fecha_acta' => $fecha_acta
            ];
            
        } catch (Exception $e) {
            error_log("Error generando número de acta: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Actualiza la numeración de un documento específico
     */
    public function updateDocumentNumbering($documento_id) {
        try {
            // Obtener información del documento
            $doc_sql = "
                SELECT d.*, c.escuela_id, d.participante_id, d.curso_id
                FROM documentos d
                INNER JOIN cursos c ON d.curso_id = c.id
                WHERE d.id = ?
            ";
            $stmt = $this->conn->prepare($doc_sql);
            $stmt->bind_param("i", $documento_id);
            $stmt->execute();
            $documento = $stmt->get_result()->fetch_assoc();
            
            if (!$documento) {
                return false;
            }
            
            // Generar número consecutivo
            $consecutivo = $this->generateConsecutiveNumber(
                $documento_id, 
                $documento['participante_id'], 
                $documento['curso_id'], 
                $documento['escuela_id']
            );
            
            // Generar número de acta
            $acta_info = $this->generateActaNumber($documento['curso_id'], $documento['escuela_id']);
            
            return [
                'consecutivo' => $consecutivo,
                'numero_acta' => $acta_info['numero_acta'] ?? null,
                'fecha_acta' => $acta_info['fecha_acta'] ?? null
            ];
            
        } catch (Exception $e) {
            error_log("Error actualizando numeración: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene la numeración de un documento
     */
    public function getDocumentNumbering($documento_id) {
        $sql = "
            SELECT numero_consecutivo, numero_acta, fecha_acta, orden_alfabetico
            FROM documentos 
            WHERE id = ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $documento_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
