<?php

function handle_timetable_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {
        case 'GET':
            get_timetable_records($db);
            break;
        case 'POST':
            create_timetable_record($db);
            break;
        case 'PUT':
            update_timetable_record($db);
            break;
        case 'DELETE':
            delete_timetable_record($db);
            break;
        default:
            sendMethodNotAllowedResponse();
            break;
    }
}

function get_timetable_records($db) {
    $query = "SELECT * FROM timetable_create";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

function create_timetable_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Sanitize and trim input fields to prevent HTML/JS injection
    $did = htmlspecialchars(trim($input['did'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));
    $sid = htmlspecialchars(trim($input['sid'] ?? ''));
    $semid = htmlspecialchars(trim($input['semid'] ?? ''));
    $gap = htmlspecialchars(trim($input['gap'] ?? ''));
    $start_time = htmlspecialchars(trim($input['start_time'] ?? ''));
    $end_time = htmlspecialchars(trim($input['end_time'] ?? ''));

    // Validate input
    if (empty($did) || empty($pid) || empty($yid) || empty($sid) || empty($semid) || empty($start_time) || empty($end_time)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

    // Check if the timetable already exists
    $query = "SELECT COUNT(*) FROM timetable_create WHERE did = :did AND pid = :pid AND yid = :yid AND sid = :sid AND semid = :semid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':did', $did);
    $stmt->bindValue(':pid', $pid);
    $stmt->bindValue(':yid', $yid);
    $stmt->bindValue(':sid', $sid);
    $stmt->bindValue(':semid', $semid);

    $stmt->execute();
    $timetableExists = $stmt->fetchColumn();

    if ($timetableExists > 0) {
        sendBadRequestResponse('Timetable already exists');
        return;
    }

    // Prepare SQL query to insert timetable data
    $query = "INSERT INTO timetable_create (did, pid, yid, sid, semid, gap, start_time, end_time) 
              VALUES (:did, :pid, :yid, :sid, :semid, :gap, :start_time, :end_time)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':did', $did);
    $stmt->bindValue(':pid', $pid);
    $stmt->bindValue(':yid', $yid);
    $stmt->bindValue(':sid', $sid);
    $stmt->bindValue(':semid', $semid);
    $stmt->bindValue(':gap', $gap);
    $stmt->bindValue(':start_time', $start_time);
    $stmt->bindValue(':end_time', $end_time);

    // Execute the query and return the appropriate response
    if ($stmt->execute()) {
        // Success: Timetable record created
        echo json_encode(['success' => true, 'message' => 'Timetable created successfully']);
    } else {
        // Error: Failed to create timetable record
        sendDatabaseErrorResponse();
    }
}


function update_timetable_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);
    $tid = $input['tid'] ?? null;
    // Sanitize input fields
   
    $tid = htmlspecialchars(trim($input['tid'] ?? ''));

    $did = htmlspecialchars(trim($input['did'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));
    $sid = htmlspecialchars(trim($input['sid'] ?? ''));
    $semid = htmlspecialchars(trim($input['semid'] ?? ''));
    $gap = htmlspecialchars(trim($input['gap'] ?? ''));
    $start_time = htmlspecialchars(trim($input['start_time'] ?? ''));
    $end_time = htmlspecialchars(trim($input['end_time'] ?? ''));


    // Validate sanitized input
    if (!$tid ||empty($did) || empty($pid) || empty($yid) || empty($sid)  || empty($semid) || empty($gap) || empty($start_time)|| empty($end_time)) {
        sendBadRequestResponse('Invalid input or tid');
    }


    // Check if the course record with the given did exists
    $query = "SELECT * FROM timetable_create WHERE tid = :tid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':tid', $tid);
    $stmt->execute();
    $Tabletable = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$Tabletable) {
        sendBadRequestResponse('No Tabletable found with the given tid');
    }

    // Check if any field has changed
    $changes = false;

    if ($Tabletable['did'] !== $did) {
        $changes = true;
    }
    if ($Tabletable['pid'] !== $pid) {
        $changes = true;
    }
    if ($Tabletable['sid'] !== $sid) {
        $changes = true;
    }
    if ($Tabletable['semid'] !== $semid) {
        $changes = true;
    }
    if ($Tabletable['yid'] !== $yid) {
        $changes = true;
    }
    if ($Tabletable['gap'] !== $gap) {
        $changes = true;
    }

    if ($Tabletable['start_time'] !== $start_time) {
        $changes = true;
    }

    if ($Tabletable['end_time'] !== $end_time) {
        $changes = true;
    }

    // If no changes, send response and exit
    if (!$changes) {
        sendResponse(200, 'Timetable already up to date');
        return;
    }

    // Prepare SQL query to update course data
    $query = "UPDATE timetable_create SET did = :did, pid = :pid, yid = :yid, sid = :sid,  semid = :semid, gap = :gap , start_time = :start_time , end_time = :end_time WHERE tid = :tid";
    try {
        $stmt = $db->prepare($query);

        $stmt->bindValue(':did', $did);
        $stmt->bindValue(':pid', $pid);
        $stmt->bindValue(':yid', $yid);
        $stmt->bindValue(':sid', $sid);
        $stmt->bindValue(':semid', $semid);
        $stmt->bindValue(':gap', $gap);
        $stmt->bindValue(':start_time', $start_time);
        $stmt->bindValue(':end_time', $end_time);
        $stmt->bindValue(':tid', $tid);


        // Execute the update query
        if ($stmt->execute()) {
            sendResponse(200, 'Timetable updated successfully');
        } else {
            sendResponse(200, 'Timetable already up to date');
        }
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}




function delete_timetable_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);
    $tid = $input['tid'] ?? null;

    // Validate if the course ID is provided
    if (!$tid) {
        sendBadRequestResponse('Timetable ID (tid) is required');
    }

    // Check if the course exists before attempting to delete
    $query = "SELECT COUNT(*) FROM timetable_create WHERE tid = :tid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':tid', $tid);
    $stmt->execute();
    $courseExists = $stmt->fetchColumn();

    if ($courseExists == 0) {
        // No record found with the given tid
        sendResponse(404, 'No record found to delete');
    }

    // Prepare the DELETE SQL query
    $query = "DELETE FROM timetable_create WHERE tid = :tid";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':tid', $tid);
        
        // Attempt to execute the delete query
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                sendResponse(200, 'Timetable deleted successfully');
            } else {
                // If no rows are affected, meaning the record was not deleted for some reason
                sendResponse(404, 'No record found to delete');
            }
        } else {
            // If execution failed without errors
            sendDatabaseErrorResponse();
        }
    } catch (PDOException $e) {
        // Handle any database errors
        sendDatabaseErrorResponse($e->getMessage());
    }
}

?>
