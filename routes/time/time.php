<?php



function handle_time_requests($request_method, $db) {
    switch ($request_method) {
        case 'GET':
            get_time_records($db);
            break;
        case 'POST':
            create_time_record($db);
            break;
        case 'PUT':
            update_time_record($db);
            break;
        case 'DELETE':
            delete_time_record($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}



function get_time_records($db) {
    $query = "SELECT * FROM time";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

function create_time_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $start = htmlspecialchars(trim($input['start'] ?? ''));
    $end = htmlspecialchars(trim($input['end'] ?? ''));
    $gap = htmlspecialchars(trim($input['gap'] ?? ''));
    $name = htmlspecialchars(trim($input['name'] ?? ''));


      // Validate input
    if (empty($start) || empty($end) || empty($gap) || empty($name) ) {
        sendBadRequestResponse('All fields are required');
    }
    
    // Check if time_code already exists
    $query = "SELECT COUNT(*) FROM time WHERE name = :name";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $name);
    $stmt->execute();

    $timeExists = $stmt->fetchColumn();

    if ($timeExists > 0) {
        sendBadRequestResponse('Shift already exists');
    }

       // Prepare SQL query to insert time data
       $query = "INSERT INTO time (start, end, gap, name) 
       VALUES (:start, :end, :gap, :name)";
$stmt = $db->prepare($query);
$stmt->bindValue(':start', $start);
$stmt->bindValue(':end', $end);
$stmt->bindValue(':gap', $gap);
$stmt->bindValue(':name', $name);

// Execute the query and return the appropriate response
if ($stmt->execute()) {
 // Success: time record created
 echo json_encode(['success' => true, 'message' => 'Shift Created successfully']);
} else {
 // Error: Failed to create time record
 sendDatabaseErrorResponse();
}
}

function update_time_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    $timeid = $input['timeid'] ?? null;

 // Sanitize input fields
    $start = htmlspecialchars(trim($input['start'] ?? ''));
    $end = htmlspecialchars(trim($input['end'] ?? ''));
    $gap = htmlspecialchars(trim($input['gap'] ?? ''));
    $name = htmlspecialchars(trim($input['name'] ?? ''));

    // Validate sanitized input
    if (!$timeid || empty($start) || empty($end) || empty($gap) || empty($name)) {
        sendBadRequestResponse('Invalid input or Time ID');
    }
    $name = html_entity_decode($name);

    // Check if the time record with the given timeid exists
    $query = "SELECT * FROM time WHERE timeid = :timeid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':timeid', $timeid);
    $stmt->execute();
    $time = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$time) {
        sendBadRequestResponse('No Shift found with the given Shift ID');
    }

    // Check if any field has changed
    $changes = false;

    if ($time['name'] !== $name) {
        $changes = true;
    }
    if ($time['start'] !== $start) {
        $changes = true;
    }
    if ($time['end'] !== $end) {
        $changes = true;
    }
    if ($time['gap'] !== $gap) {
        $changes = true;
    }
   

    // If no changes, send response and exit
    if (!$changes) {
        sendResponse(200, 'time record already up to date');
        return;
    }

    
    // Prepare SQL query to update time data
    $query = "UPDATE time SET name = :name, start = :start, end = :end, gap = :gap WHERE timeid = :timeid";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':gap', $gap);
       
        $stmt->bindValue(':timeid', $timeid);

        // Execute the update query
        if ($stmt->execute()) {
            sendResponse(200, 'Shift record updated successfully');
        } else {
            sendResponse(200, 'Shift record already up to date');
        }
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }

}

function delete_time_record($db) {

        // Get input from the request body
        $input = json_decode(file_get_contents('php://input'), true);

        $timeid = $input['timeid'] ?? null;

        // Validate if the Time ID is provided
        if (!$timeid) {
            sendBadRequestResponse('Time ID (timeid) is required');
        }


        
    // Check if the shift exists before attempting to delete
    $query = "SELECT COUNT(*) FROM time WHERE timeid = :timeid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':timeid', $timeid);
    $stmt->execute();
    $timeExists = $stmt->fetchColumn();

    if ($timeExists == 0) {
        // No record found with the given timeid
        sendResponse(404, 'No record found to delete');
    }


      // Prepare the DELETE SQL query
      $query = "DELETE FROM time WHERE timeid = :timeid";
      try {
          $stmt = $db->prepare($query);
          $stmt->bindValue(':timeid', $timeid);
          
          // Attempt to execute the delete query
          if ($stmt->execute()) {
              if ($stmt->rowCount() > 0) {
                  sendResponse(200, 'time record deleted successfully');
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