<?php
/**
 * Función para enviar notificaciones al servidor WebSocket
 */

/**
 * Envía un mensaje al servidor WebSocket
 * 
 * @param array $datos Datos a enviar al servidor WebSocket
 * @return bool Éxito o fracaso de la operación
 */
function enviarNotificacionWebSocket($datos) {
    try {
        // Configuración del socket
        $host = 'localhost';
        $port = 8080;
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        if ($socket === false) {
            error_log("Error al crear socket: " . socket_strerror(socket_last_error()));
            return false;
        }
        
        // Intentar conectar al servidor WebSocket
        $result = socket_connect($socket, $host, $port);
        if ($result === false) {
            error_log("No se pudo conectar al servidor WebSocket: " . socket_strerror(socket_last_error($socket)));
            return false;
        }
        
        // Convertir datos a JSON
        $mensaje = json_encode($datos);
        
        // Enviar datos
        socket_write($socket, $mensaje, strlen($mensaje));
        
        // Cerrar socket
        socket_close($socket);
        
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar notificación WebSocket: " . $e->getMessage());
        return false;
    }
}
