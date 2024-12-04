<?php

function handle_faculty_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {
        case 'GET':
            get_faculty_records($db);
            break;
        case 'POST':
            create_faculty_record($db);
            break;
        case 'PUT':
            update_faculty_record($db);
            break;
        case 'DELETE':
            delete_faculty_record($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_faculty_records($db) {
    $query = "SELECT * FROM faculty";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $result]);
}

function create_faculty_record($db) {


    
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (empty($input['name']) || empty($input['entrytime']) || empty($input['exittime']) || 
        empty($input['user']) || empty($input['pass']) || empty($input['role']) || 
        empty($input['day']) || empty($input['max_allowed_lecture'])) {
        header("HTTP/1.0 400 Bad Request");
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }

    // Prepare SQL query
    $query = "INSERT INTO faculty (name, entrytime, exittime, user, pass, role, day, max_allowed_lecture) 
              VALUES (:name, :entrytime, :exittime, :user, :pass, :role, :day, :max_allowed_lecture)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $input['name']);
    $stmt->bindValue(':entrytime', $input['entrytime']);
    $stmt->bindValue(':exittime', $input['exittime']);
    $stmt->bindValue(':user', $input['user']);
    $stmt->bindValue(':pass', $input['pass']);
    $stmt->bindValue(':role', $input['role']);
    $stmt->bindValue(':day', $input['day']);
    $stmt->bindValue(':max_allowed_lecture', $input['max_allowed_lecture']);

    // Execute and respond
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Time record created successfully']);
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['success' => false, 'message' => 'Time record creation failed', 'error' => $stmt->errorInfo()]);
    }
}

function update_faculty_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $fid = $input['fid'] ?? null;

    // Validate input
    if (!$fid || empty($input['name']) || empty($input['entrytime']) || empty($input['exittime']) || 
        empty($input['user']) || empty($input['pass']) || empty($input['role']) || 
        empty($input['day']) || empty($input['max_allowed_lecture'])) {
        header("HTTP/1.0 400 Bad Request");
        echo json_encode(['success' => false, 'message' => 'Invalid input or FID']);
        return;
    }

    // Prepare SQL query
    $query = "UPDATE faculty SET name = :name, entrytime = :entrytime, exittime = :exittime, 
              user = :user, pass = :pass, role = :role, day = :day, 
              max_allowed_lecture = :max_allowed_lecture WHERE fid = :fid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $input['name']);
    $stmt->bindValue(':entrytime', $input['entrytime']);
    $stmt->bindValue(':exittime', $input['exittime']);
    $stmt->bindValue(':user', $input['user']);
    $stmt->bindValue(':pass', $input['pass']);
    $stmt->bindValue(':role', $input['role']);
    $stmt->bindValue(':day', $input['day']);
    $stmt->bindValue(':max_allowed_lecture', $input['max_allowed_lecture']);
    $stmt->bindValue(':fid', $fid);

    // Execute and respond
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Time record updated successfully']);
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['success' => false, 'message' => 'Time record update failed', 'error' => $stmt->errorInfo()]);
    }
}

function delete_faculty_record($db) {
    $fid = $_GET['fid'] ?? null;

    // Validate input
    if (!$fid) {
        header("HTTP/1.0 400 Bad Request");
        echo json_encode(['success' => false, 'message' => 'Invalid FID']);
        return;
    }

    // Prepare SQL query
    $query = "DELETE FROM faculty WHERE fid = :fid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':fid', $fid);

    // Log the FID being deleted
    error_log("Attempting to delete FID: " . $fid);

    // Execute and respond
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Time record deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No record found to delete']);
        }
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode(['success' => false, 'message' => 'Time record deletion failed', 'error' => $stmt->errorInfo()]);
        error_log("Delete Error: " . json_encode($stmt->errorInfo())); // Log error info
    }
}
?>
