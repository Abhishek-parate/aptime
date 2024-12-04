<?php

// utils.php
function sendBadRequestResponse($message) {
    header("HTTP/1.0 400 Bad Request");
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function sendMethodNotAllowedResponse() {
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

function sendDatabaseErrorResponse($error = 'Database error') {
    header("HTTP/1.0 500 Internal Server Error");
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

function sendResponse($status_code, $message, $data = null) {
    $response = ['success' => $status_code === 200, 'message' => $message];
    
    // Only include data if it's not null
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

?>