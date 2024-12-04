<?php

function get_section_records($db) {
    $query = "SELECT * FROM section";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $result]);
}

function create_section_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['name']) || empty($input['pid']) || !isset($input['yid'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }

    // Check if record already exists with same pid, yid, and name
    $checkQuery = "SELECT * FROM section WHERE pid = :pid AND yid = :yid AND name = :name";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindValue(':pid', $input['pid']);
    $checkStmt->bindValue(':yid', $input['yid']);
    $checkStmt->bindValue(':name', $input['name']);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Record already exists']);
        return;
    }

    $columns = implode(", ", array_keys($input));
    $placeholders = ":" . implode(", :", array_keys($input));
    $query = "INSERT INTO section ($columns) VALUES ($placeholders)";
    
    $stmt = $db->prepare($query);
    foreach ($input as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }

    try {
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'section record created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Record creation failed', 'error' => $stmt->errorInfo()]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Record creation failed', 'error' => $e->getMessage()]);
    }
}

function update_section_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['yid'] ?? null;
    unset($input['yid']);

    if (!$id || empty($input['name']) || empty($input['pid']) || !isset($input['yid'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input or ID']);
        return;
    }

    // Check if record already exists with the same pid, yid, and name (excluding the current record)
    $checkQuery = "SELECT * FROM section WHERE pid = :pid AND yid = :yid AND name = :name AND yid != :yid";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindValue(':pid', $input['pid']);
    $checkStmt->bindValue(':yid', $input['yid']);
    $checkStmt->bindValue(':name', $input['name']);
    $checkStmt->bindValue(':yid', $id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Record already exists']);
        return;
    }

    $columns = "";
    foreach ($input as $key => $val) {
        $columns .= "$key = :$key, ";
    }
    $columns = rtrim($columns, ", ");
    $query = "UPDATE section SET $columns WHERE yid = :yid";
    $stmt = $db->prepare($query);
    
    foreach ($input as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }
    $stmt->bindValue(':yid', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'section record updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'section record update failed', 'error' => $stmt->errorInfo()]);
    }
}




function delete_section_records1($db) {
    if (empty($input['yid'])) {
        echo json_encode(['success' => false, 'message' => 'Missing ID for deletion']);
        return;
    }
    
    $query = "DELETE FROM section WHERE yid = :yid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':yid', $input['yid']);
    
    try {
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'section record deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Record deletion failed', 'error' => $stmt->errorInfo()]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'An error occurred', 'error' => $e->getMessage()]);
    }
    
}


function delete_section_records($db) {
    $id = $_GET['yid'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        return;
    }

   
    $query = "DELETE FROM section WHERE yid = :yid";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':yid', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'section record deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'section record deletion failed', 'error' => $stmt->errorInfo()]);
    }
}






function handle_section_requests($request_method, $db) {
    switch ($request_method) {
        case 'GET':
            get_section_records($db);
            break;
        case 'POST':
            create_section_records($db);
            break;
        case 'PUT':
            update_section_records($db);
            break;
        case 'DELETE':
            delete_section_records($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}
?>
