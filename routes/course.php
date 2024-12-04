<?php

function handle_course_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {
        case 'GET':
            get_course_records($db);
            break;
        case 'POST':
            create_course_record($db);
            break;
        case 'PUT':
            update_course_record($db);
            break;
        case 'DELETE':
            delete_course_record($db);
            break;
        default:
            sendMethodNotAllowedResponse();
            break;
    }
}

function get_course_records($db) {
    $query = "SELECT * FROM course";
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
function create_course_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Sanitize and trim input fields to prevent HTML/JS injection
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $alias = htmlspecialchars(trim($input['alias'] ?? ''));
    $course_code = htmlspecialchars(trim($input['course_code'] ?? ''));
    $category = htmlspecialchars(trim($input['category'] ?? ''));
    $max_lecture = filter_var($input['max_lecture'] ?? '', FILTER_SANITIZE_NUMBER_INT); // Ensure it's a number

    // Validate input
    if (empty($name) || empty($alias) || empty($course_code) || empty($category) || empty($max_lecture)) {
        sendBadRequestResponse('All fields are required');
    }

    // Check if course_code already exists
    $query = "SELECT COUNT(*) FROM course WHERE course_code = :course_code";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':course_code', $course_code);
    $stmt->execute();

    $courseExists = $stmt->fetchColumn();

    if ($courseExists > 0) {
        sendBadRequestResponse('Course code already exists');
    }

    // Prepare SQL query to insert course data
    $query = "INSERT INTO course (name, alias, course_code, category, max_lecture) 
              VALUES (:name, :alias, :course_code, :category, :max_lecture)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':alias', $alias);
    $stmt->bindValue(':course_code', $course_code);
    $stmt->bindValue(':category', $category);
    $stmt->bindValue(':max_lecture', $max_lecture);

    // Execute the query and return the appropriate response
    if ($stmt->execute()) {
        // Success: Course record created
        echo json_encode(['success' => true, 'message' => 'Course record created successfully']);
    } else {
        // Error: Failed to create course record
        sendDatabaseErrorResponse();
    }
}


function update_course_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);
    $cid = $input['cid'] ?? null;

    // Sanitize input fields
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $alias = htmlspecialchars(trim($input['alias'] ?? ''));
    $course_code = htmlspecialchars(trim($input['course_code'] ?? ''));
    $category = htmlspecialchars(trim($input['category'] ?? ''));
    $max_lecture = filter_var($input['max_lecture'] ?? '', FILTER_SANITIZE_NUMBER_INT); // Ensure it's a number

    // Validate sanitized input
    if (!$cid || empty($name) || empty($alias) || empty($course_code) || empty($category) || empty($max_lecture)) {
        sendBadRequestResponse('Invalid input or CID');
    }

    // Decode HTML entities
    $name = html_entity_decode($name);

    // Check if the course record with the given cid exists
    $query = "SELECT * FROM course WHERE cid = :cid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':cid', $cid);
    $stmt->execute();
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        sendBadRequestResponse('No course found with the given CID');
    }

    // Check if any field has changed
    $changes = false;

    if ($course['name'] !== $name) {
        $changes = true;
    }
    if ($course['alias'] !== $alias) {
        $changes = true;
    }
    if ($course['course_code'] !== $course_code) {
        $changes = true;
    }
    if ($course['category'] !== $category) {
        $changes = true;
    }
    if ($course['max_lecture'] !== $max_lecture) {
        $changes = true;
    }

    // If no changes, send response and exit
    if (!$changes) {
        sendResponse(200, 'Course record already up to date');
        return;
    }

    // Prepare SQL query to update course data
    $query = "UPDATE course SET name = :name, alias = :alias, course_code = :course_code, category = :category, max_lecture = :max_lecture WHERE cid = :cid";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':alias', $alias);
        $stmt->bindValue(':course_code', $course_code);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':max_lecture', $max_lecture);
        $stmt->bindValue(':cid', $cid);

        // Execute the update query
        if ($stmt->execute()) {
            sendResponse(200, 'Course record updated successfully');
        } else {
            sendResponse(200, 'Course record already up to date');
        }
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}




function delete_course_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);
    $cid = $input['cid'] ?? null;

    // Validate if the course ID is provided
    if (!$cid) {
        sendBadRequestResponse('Course ID (cid) is required');
    }

    // Check if the course exists before attempting to delete
    $query = "SELECT COUNT(*) FROM course WHERE cid = :cid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':cid', $cid);
    $stmt->execute();
    $courseExists = $stmt->fetchColumn();

    if ($courseExists == 0) {
        // No record found with the given cid
        sendResponse(404, 'No record found to delete');
    }

    // Prepare the DELETE SQL query
    $query = "DELETE FROM course WHERE cid = :cid";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':cid', $cid);
        
        // Attempt to execute the delete query
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                sendResponse(200, 'Course record deleted successfully');
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
