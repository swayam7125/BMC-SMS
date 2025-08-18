<?php
/**
 * Common response handler for AJAX and regular requests
 */
class Response {
    /**
     * Send a JSON response for AJAX requests or redirect for regular requests
     * 
     * @param array $data Response data
     * @param int $status HTTP status code
     */
    public static function send($data, $status = 200) {
        if(is_ajax_request()) {
            header('Content-Type: application/json');
            http_response_code($status);
            echo json_encode($data);
            exit;
        } else {
            if(isset($data['redirect'])) {
                if(isset($data['message'])) {
                    $type = ($status >= 200 && $status < 300) ? 'success' : 'error';
                    $_SESSION[$type] = $data['message'];
                }
                header("Location: " . $data['redirect']);
                exit;
            }
        }
    }

    /**
     * Send a success response
     * 
     * @param string $message Success message
     * @param string $redirect Redirect URL
     * @param array $data Additional data
     */
    public static function success($message, $redirect = null, $data = []) {
        $response = array_merge([
            'success' => true,
            'message' => $message,
        ], $data);
        
        if($redirect) {
            $response['redirect'] = $redirect;
        }
        
        self::send($response, 200);
    }

    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param string $redirect Redirect URL
     * @param array $data Additional data
     */
    public static function error($message, $redirect = null, $data = []) {
        $response = array_merge([
            'success' => false,
            'message' => $message,
        ], $data);
        
        if($redirect) {
            $response['redirect'] = $redirect;
        }
        
        self::send($response, 400);
    }
}
