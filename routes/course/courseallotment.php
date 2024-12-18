<?php

function handle_course_allotement_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {
        case 'GET':
            get_course_allotment_records($db);
            break;
        case 'POST':
            create_course_allotment_record($db);
            break;
        case 'PUT':
            update_course_allotment_record($db);
            break;
        case 'DELETE':
            delete_course_allotment_record($db);
            break;
        default:
            sendMethodNotAllowedResponse();
            break;
    }
}

function get_course_allotment_records($db) {
    $query = "SELECT * FROM courseallotment";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

// Create course record with sanitized input
function create_course_allotment_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Sanitize and trim input fields to prevent HTML/JS injection
    $cid = htmlspecialchars(trim($input['cid'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));
    $sid = htmlspecialchars(trim($input['sid'] ?? ''));
    $fid = htmlspecialchars(trim($input['fid'] ?? ''));
    $semid = htmlspecialchars(trim($input['semid'] ?? ''));

    // Validate input
    if (empty($cid) || empty($pid) || empty($yid) || empty($sid) || empty($fid) || empty($semid)) {
        sendBadRequestResponse('All fields are required');
    }

    // Check if course_code already exists
    $query = "SELECT COUNT(*) FROM courseallotment WHERE cid = :cid AND pid = :pid AND yid = :yid AND sid = :sid AND fid = :fid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':cid', $cid);
    $stmt->bindValue(':pid', $pid);
    $stmt->bindValue(':yid', $yid);
    $stmt->bindValue(':sid', $sid);
    $stmt->bindValue(':fid', $fid);
    $stmt->execute();

    $courseExists = $stmt->fetchColumn();

    if ($courseExists > 0) {
        sendBadRequestResponse('Course already Alloted exists');
    }

    // Prepare SQL query to insert course data
    $query = "INSERT INTO courseallotment (cid, pid, yid, sid, fid, semid) 
              VALUES (:cid, :pid, :yid, :sid, :fid, :semid)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':cid', $cid);
    $stmt->bindValue(':pid', $pid);
    $stmt->bindValue(':yid', $yid);
    $stmt->bindValue(':sid', $sid);
    $stmt->bindValue(':fid', $fid);
    $stmt->bindValue(':semid', $semid);

    // Execute the query and return the appropriate response
    if ($stmt->execute()) {
        // Success: Course record created
        echo json_encode(['success' => true, 'message' => 'Course Alloted successfully']);
    } else {
        // Error: Failed to create course record
        sendDatabaseErrorResponse();
    }
}

function update_course_allotment_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);
    $caid = $input['caid'] ?? null;

    // Sanitize input fields
    $cid = htmlspecialchars(trim($input['cid'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));
    $sid = htmlspecialchars(trim($input['sid'] ?? ''));
    $fid = htmlspecialchars(trim($input['fid'] ?? ''));
    $semid = htmlspecialchars(trim($input['semid'] ?? ''));

    // Validate sanitized input
    if (!$caid || empty($cid) || empty($pid) || empty($yid) || empty($sid) || empty($fid) || empty($semid)) {
        sendBadRequestResponse('Invalid input or CAid');
    }

    // Check if the course record with the given caid exists
    $query = "SELECT * FROM courseallotment WHERE caid = :caid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':caid', $caid);
    $stmt->execute();
    $CourseAllotment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$CourseAllotment) {
        sendBadRequestResponse('No CourseAllotment found with the given caid');
    }

    // Check if any field has changed
    $changes = false;

    if ($CourseAllotment['cid'] !== $cid) {
        $changes = true;
    }
    if ($CourseAllotment['pid'] !== $pid) {
        $changes = true;
    }
    if ($CourseAllotment['sid'] !== $sid) {
        $changes = true;
    }
    if ($CourseAllotment['yid'] !== $yid) {
        $changes = true;
    }
    if ($CourseAllotment['fid'] !== $fid) {
        $changes = true;
    }
    if ($CourseAllotment['semid'] !== $semid) {
        $changes = true;
    }

    // If no changes, send response and exit
    if (!$changes) {
        sendResponse(200, 'CourseAllotment record already up to date');
        return;
    }

    // Prepare SQL query to update course data
    $query = "UPDATE courseallotment SET cid = :cid, pid = :pid, yid = :yid, sid = :sid, fid = :fid, semid = :semid WHERE caid = :caid";
    try {
        $stmt = $db->prepare($query);

        $stmt->bindValue(':cid', $cid);
        $stmt->bindValue(':pid', $pid);
        $stmt->bindValue(':yid', $yid);
        $stmt->bindValue(':sid', $sid);
        $stmt->bindValue(':fid', $fid);
        $stmt->bindValue(':semid', $semid);
        $stmt->bindValue(':caid', $caid);

        // Execute the update query
        if ($stmt->execute()) {
            sendResponse(200, 'Course Allotment updated successfully');
        } else {
            sendResponse(200, 'Course Allotment already up to date');
        }
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

function delete_course_allotment_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);
    $caid = $input['caid'] ?? null;

    // Validate if the course ID is provided
    if (!$caid) {
        sendBadRequestResponse('course allotment ID (caid) is required');
    }

    // Check if the course exists before attempting to delete
    $query = "SELECT COUNT(*) FROM courseallotment WHERE caid = :caid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':caid', $caid);
    $stmt->execute();
    $courseExists = $stmt->fetchColumn();

    if ($courseExists == 0) {
        // No record found with the given caid
        sendResponse(404, 'No record found to delete');
    }

    // Prepare the DELETE SQL query
    $query = "DELETE FROM courseallotment WHERE caid = :caid";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':caid', $caid);
        
        // Attempt to execute the delete query
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                sendResponse(200, 'Course Allotment deleted successfully');
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
