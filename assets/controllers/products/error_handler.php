<?php
/**
 * Global error handler for product controllers
 * 
 * This file provides centralized error handling functions for product controllers.
 * Include this file at the beginning of controller scripts to ensure consistent
 * error handling and logging.
 */

// Ensure logs directory exists
$log_dir = __DIR__ . '/../../logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Set error log file
ini_set('error_log', $log_dir . '/php_errors.log');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

/**
 * Log a debug message with optional data
 * 
 * @param string $message The message to log
 * @param mixed $data Additional data to log (optional)
 */
function debug_log($message, $data = null) {
    $log_file = __DIR__ . '/../../logs/debug.log';
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    
    if ($data !== null) {
        $log .= ' - ' . (is_array($data) || is_object($data) ? json_encode($data) : $data);
    }
    
    error_log($log);
    file_put_contents($log_file, $log . PHP_EOL, FILE_APPEND);
}

/**
 * Send a JSON response
 * 
 * @param bool $success Whether the operation was successful
 * @param string $message Message to display to the user
 * @param array $data Additional data to include in the response
 */
function send_json_response($success, $message, $data = []) {
    // Clear any previous output
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Set appropriate status code
    http_response_code($success ? 200 : 400);
    
    // Prepare response array
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    // Add additional data if provided
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    // Log the response being sent
    debug_log("Sending response", $response);
    
    // Ensure proper JSON headers are sent
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    // Send the JSON response
    $json_response = json_encode($response);
    if ($json_response === false) {
        // Handle JSON encoding errors
        $error_msg = 'JSON encoding error: ' . json_last_error_msg();
        debug_log($error_msg);
        echo json_encode([
            'success' => false,
            'message' => $error_msg
        ]);
    } else {
        echo $json_response;
    }
    exit;
}

/**
 * Custom error handler to log PHP errors
 */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Only handle certain error types
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error_type = match($errno) {
        E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR => 'FATAL ERROR',
        E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'WARNING',
        E_NOTICE, E_USER_NOTICE => 'NOTICE',
        E_STRICT => 'STRICT',
        E_DEPRECATED, E_USER_DEPRECATED => 'DEPRECATED',
        default => "UNKNOWN ERROR ($errno)"
    };
    
    // Log the error
    debug_log("PHP $error_type: $errstr in $errfile on line $errline");
    
    // If it's a fatal error, send an error response
    if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        send_json_response(false, "A server error occurred", [
            'error' => $errstr,
            'errorCode' => $errno
        ]);
    }
    
    return true; // Don't execute the PHP internal error handler
});

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        debug_log("FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']}");
        
        // Only send JSON response if headers haven't been sent yet
        if (!headers_sent()) {
            send_json_response(false, "A fatal error occurred on the server", [
                'error' => $error['message'],
                'errorCode' => $error['type']
            ]);
        }
    }
}); 