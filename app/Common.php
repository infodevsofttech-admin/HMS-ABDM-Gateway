<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace Banners, Terms and other bits of text used in the framework
 * throughout the application.
 *
 * This is intended to allow swapping out language and other strings
 * at the application level, while keeping the core CI4 files unchanged.
 */

// Always provide a way to check if a function exists before calling it
if (!function_exists('ddd')) {
    /**
     * Dump and die for debugging
     */
    function ddd($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die for debugging
     */
    function dd($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}

if (!function_exists('log_request')) {
    /**
     * Log request to database
     */
    function log_request($method, $endpoint, $statusCode, $responseTime, $ipAddress, $authStatus, $errorMessage = null)
    {
        try {
            $model = model('AbdmRequestLog');
            
            // Generate unique request ID
            $requestId = 'REQ-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
            
            $model->insert([
                'request_id' => $requestId,
                'method' => $method,
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'response_time_ms' => $responseTime,
                'ip_address' => $ipAddress,
                'authorization_status' => $authStatus,
                'error_message' => $errorMessage,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            return $requestId;
        } catch (\Exception $e) {
            log_message('error', 'Failed to log request: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * Get client IP address
     */
    function get_client_ip()
    {
        $request = service('request');
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle multiple IPs in X-Forwarded-For
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}

if (!function_exists('validate_bearer_token')) {
    /**
     * Validate bearer token
     */
    function validate_bearer_token($token)
    {
        $config = config('AbdmGateway');
        return $token === $config->bearerToken;
    }
}

if (!function_exists('generate_request_id')) {
    /**
     * Generate unique request ID
     */
    function generate_request_id()
    {
        return 'REQ-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    }
}

if (!function_exists('json_response')) {
    /**
     * Return JSON response
     */
    function json_response($data, $statusCode = 200, $message = null)
    {
        $response = service('response');
        
        return $response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setBody(json_encode([
                'status' => $statusCode >= 400 ? 'error' : 'success',
                'message' => $message,
                'data' => $data,
                'timestamp' => date('c'),
            ]));
    }
}

if (!function_exists('json_error')) {
    /**
     * Return JSON error response
     */
    function json_error($message, $statusCode = 400, $data = null)
    {
        $response = service('response');
        
        return $response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setBody(json_encode([
                'status' => 'error',
                'message' => $message,
                'data' => $data,
                'timestamp' => date('c'),
            ]));
    }
}
