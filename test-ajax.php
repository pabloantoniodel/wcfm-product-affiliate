<?php
/**
 * Test AJAX simple - Cargar directamente
 */

require_once('../../../wp-load.php');

// Verificar que es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wp_send_json_error(array('message' => 'Solo POST permitido'));
}

// Log
error_log('TEST AJAX: Called successfully');
error_log('POST: ' . print_r($_POST, true));

// Respuesta simple
wp_send_json_success(array(
    'message' => 'Test AJAX funcionando correctamente',
    'post_data' => $_POST,
    'timestamp' => current_time('mysql')
));

