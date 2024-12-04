<?php

function handle_classroom_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {
        case 'GET':
            get_classroom_records($db);
            break;
        case 'POST':
            create_classroom_record($db);
            break;
        case 'PUT':
            update_classroom_record($db);
            break;
        case 'DELETE':
            delete_classroom_record($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_classroom_records($db) {
    $query = "SELECT * FROM class_room";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $result]);
}

function create_classroom_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (empty($input['name']) || empty($input['type']) || empty($input['capacity'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }

    $query = "INSERT INTO class_room (name, type, capacity) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$input['name'], $input['type'], $input['capacity']]);

    echo json_encode(['success' => true, 'message' => 'Classroom added successfully']);
}

function update_classroom_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (empty($input['name']) || empty($input['type']) || empty($input['capacity'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }

    $query = "UPDATE class_room SET name = ?, type = ?, capacity = ? WHERE rid = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$input['name'], $input['type'], $input['capacity'], $input['rid']]);

    echo json_encode(['success' => true, 'message' => 'Classroom updated successfully']);
}

function delete_classroom_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $query = "DELETE FROM class_room WHERE rid = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$input['rid']]);

    echo json_encode(['success' => true, 'message' => 'Classroom deleted successfully']);
}

?>
