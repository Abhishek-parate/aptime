<?php

function handle_gaps_requests($request_method, $db) {
    header("Content-Type: application/json");
    switch ($request_method) {
        case 'GET':
            get_gaps_records($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_gaps_records($db) { 
    $query = "SELECT REPLACE(SUBSTRING(COLUMN_TYPE, 6, LENGTH(COLUMN_TYPE) - 6), \"'\", \"\") AS enum_values
              FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'timetable_create' 
              AND COLUMN_NAME = 'gap'";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Transforming the result
        $enum_values = explode(',', $result['enum_values']);
        $data = array_map(function($value) {
            return ['em' => trim($value)];
        }, $enum_values);

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

?>
