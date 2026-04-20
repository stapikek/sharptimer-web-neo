<?php

if (!defined('IN_LR')) {
    define('IN_LR', true);
}

if (!defined('STORAGE')) {
    $rootPath = dirname(dirname(dirname(dirname(__DIR__))));
    define('STORAGE', $rootPath . '/storage/');
}

header('Content-Type: application/json; charset=utf-8');

header('Cache-Control: public, max-age=300'); 
header('Expires: ' . gmdate('D, d M Y H:i:s T', time() + 300));

$moduleDir = dirname(__DIR__);

$dataFile = $moduleDir . '/forward/data.php';
if (!file_exists($dataFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Data class not found',
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once($dataFile);

try {
    $SurfRecords = new SurfRecordsModule();
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to initialize module: ' . $e->getMessage(),
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!$SurfRecords->isConnected()) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection unavailable',
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$endpoint = $_GET['endpoint'] ?? 'stats';

if (!is_string($endpoint)) {
    logSecurityEvent('INVALID_ENDPOINT_TYPE', 'endpoint is not string');
    sendError('Invalid endpoint type', 400);
}

$endpoint = trim($endpoint);
$allowed_endpoints = ['stats', 'maps', 'records', 'map_info'];

if (!in_array($endpoint, $allowed_endpoints, true)) {
    logSecurityEvent('INVALID_ENDPOINT', 'endpoint=' . htmlspecialchars($endpoint));
    sendError('Invalid endpoint', 400);
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function logSecurityEvent($event_type, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    error_log('SurfAPI [' . $event_type . '] IP: ' . htmlspecialchars($ip) . ' | ' . htmlspecialchars($details));
}

function validateMapName($map) {
    if (empty($map) || !is_string($map)) {
        return false;
    }
    
    if (strlen($map) > 64 || strlen($map) < 1) {
        return false;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $map)) {
        return false;
    }
    
    $dangerous_patterns = [
        '/union/i', '/select/i', '/insert/i', '/update/i', '/delete/i',
        '/drop/i', '/create/i', '/alter/i', '/exec/i', '/script/i',
        '/<script/i', '/javascript:/i', '/vbscript:/i', '/onload/i',
        '/onerror/i', '/onclick/i'
    ];
    
    foreach ($dangerous_patterns as $pattern) {
        if (preg_match($pattern, $map)) {
            return false;
        }
    }
    
    return true;
}

try {
    switch ($endpoint) {
        
        case 'stats':
            $stats = $SurfRecords->getStatistics();
            sendResponse([
                'success' => true,
                'data' => $stats,
                'timestamp' => time()
            ]);
            break;
        
        case 'maps':
            $category = $_GET['category'] ?? null;

            if ($category !== null) {
                if (!is_string($category)) {
                    sendError('Invalid category type');
                }
                $category = trim($category);
                if (!in_array($category, ['surf', 'kz', 'bhop', 'other'], true)) {
                    sendError('Invalid category');
                }
            }
            
            $maps = $SurfRecords->getMaps($category);
            sendResponse([
                'success' => true,
                'data' => $maps,
                'timestamp' => time()
            ]);
            break;
        
        case 'records':
            $map = $_GET['map'] ?? null;
            
            if ($map === null || $map === '') {
                sendError('Map parameter is required');
            }
            
            if (!is_string($map)) {
                logSecurityEvent('INVALID_MAP_TYPE', 'map is not string');
                sendError('Invalid map parameter type');
            }
            
            $map = trim($map);
            
            if (!validateMapName($map)) {
                logSecurityEvent('INVALID_MAP_NAME', 'map=' . htmlspecialchars($map));
                sendError('Invalid map name format');
            }
            
            $records = $SurfRecords->getMapRecords($map);
            sendResponse([
                'success' => true,
                'data' => [
                    'map' => $map,
                    'records' => $records,
                    'count' => count($records)
                ],
                'timestamp' => time()
            ]);
            break;
        
        case 'map_info':
            $map = $_GET['map'] ?? null;
            
            if ($map === null || $map === '') {
                sendError('Map parameter is required');
            }
            
            if (!is_string($map)) {
                sendError('Invalid map parameter type');
            }
            
            $map = trim($map);
            
            if (!validateMapName($map)) {
                sendError('Invalid map name format');
            }
            
            $records = $SurfRecords->getMapRecords($map);
            $top_record = !empty($records) ? $records[0] : null;
            
            sendResponse([
                'success' => true,
                'data' => [
                    'map_name' => $map,
                    'top_record' => $top_record,
                    'has_records' => !empty($records),
                    'total_records' => count($records)
                ],
                'timestamp' => time()
            ]);
            break;
        
        default:
            sendError('Unknown API endpoint: ' . $endpoint, 404);
    }
    
} catch (Exception $e) {
    error_log("Surf API Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}