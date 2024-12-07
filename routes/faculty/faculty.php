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
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

function create_faculty_record($db) {

    
    $input = json_decode(file_get_contents('php://input'), true);
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $entrytime = htmlspecialchars(trim($input['entrytime'] ?? ''));
    $exittime = htmlspecialchars(trim($input['exittime'] ?? ''));
    $max_allowed_lecture = htmlspecialchars(trim($input['max_allowed_lecture'] ?? ''));

    if (empty($name) || empty($entrytime) || empty($exittime) || empty($max_allowed_lecture)) {
        sendBadRequestResponse('All fields are required');
        return;
    }


     // Check if faculty_code already exists
 $query = "SELECT COUNT(*) FROM faculty WHERE name = :name ";
 $stmt = $db->prepare($query);
 $stmt->bindValue(':name', $name);


 $stmt->execute();

 $facultyExists = $stmt->fetchColumn();

 if ($facultyExists > 0) {
     sendBadRequestResponse('Faculty already exists');
     return;
 }

  // Prepare SQL query to insert faculty data
  $query = "INSERT INTO faculty (name, entrytime, exittime, max_allowed_lecture) VALUES (:name, :entrytime, :exittime, :max_allowed_lecture)";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':name', $name);
  $stmt->bindValue(':entrytime', $entrytime);
  $stmt->bindValue(':exittime', $exittime);
  $stmt->bindValue(':max_allowed_lecture', $max_allowed_lecture);

  // Execute the query and return the appropriate response
  if ($stmt->execute()) {
      echo json_encode(['success' => true, 'message' => 'Faulty Added successfully']);
  } else {
      sendDatabaseErrorResponse();
  }
}

function update_faculty_record($db) {

    $input = json_decode(file_get_contents('php://input'), true);
    
    $fid = $input['fid'] ?? null;
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $entrytime = htmlspecialchars(trim($input['entrytime'] ?? ''));
    $exittime = htmlspecialchars(trim($input['exittime'] ?? ''));
    $max_allowed_lecture = htmlspecialchars(trim($input['max_allowed_lecture'] ?? ''));

    // Validate sanitized input
    if (!$fid || empty($name) || empty($entrytime) || empty($exittime) || empty($max_allowed_lecture)) {
        sendBadRequestResponse('Invalid input or fid');
    }

    $name = html_entity_decode($name);


      // Check if the faculty record with the given cid exists
  $query = "SELECT * FROM faculty WHERE fid = :fid";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':fid', $fid);
  $stmt->execute();
  $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$faculty) {
      sendBadRequestResponse('No Faculty found with the given fid');
  }
  
 // Check if any field has changed
 $changes = false;

 if ($faculty['name'] !== $name) {
     $changes = true;
 }
 if ($faculty['entrytime'] !== $entrytime) {
     $changes = true;
 }
 if ($faculty['exittime'] !== $exittime) {
     $changes = true;
 }

 if ($faculty['max_allowed_lecture'] !== $max_allowed_lecture) {
    $changes = true;
}

 
 // If no faculty, send response and exit
 if (!$changes) {
    sendResponse(200, 'faculty already up to date');
    return;
}



 // Prepare SQL query to update room data
 $query = "UPDATE faculty SET name = :name, entrytime = :entrytime, exittime = :exittime, max_allowed_lecture = :max_allowed_lecture WHERE fid = :fid";
 try {
     $stmt = $db->prepare($query);
     $stmt->bindValue(':name', $name);
     $stmt->bindValue(':entrytime', $entrytime);
     $stmt->bindValue(':exittime', $exittime);
     $stmt->bindValue(':max_allowed_lecture', $max_allowed_lecture);
     $stmt->bindValue(':fid', $fid);

   

     // Execute the update query
     if ($stmt->execute()) {
         sendResponse(200, 'Faculty updated successfully');
     } else {
         sendResponse(200, 'Faculty already up to date');
     }
 } catch (PDOException $e) {
     sendDatabaseErrorResponse($e->getMessage());
 }

  


}

function delete_faculty_record($db) {
   
  // Get input from the request body
  $input = json_decode(file_get_contents('php://input'), true);
  $fid = $input['fid'] ?? null;

  
  // Validate if the room ID is provided
    if (!$fid) {
    sendBadRequestResponse('Faculty ID (fid) is required');
    }

      // Check if the room exists before attempting to delete
  $query = "SELECT COUNT(*) FROM Faculty WHERE fid = :fid";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':fid', $fid);
  $stmt->execute();
  $roomExists = $stmt->fetchColumn();

  if ($roomExists == 0) {
      // No record found with the given fid
      sendResponse(404, 'No record found to delete');
  }




   // Prepare the DELETE SQL query
   $query = "DELETE FROM Faculty WHERE fid = :fid";
   try {
       $stmt = $db->prepare($query);
       $stmt->bindValue(':fid', $fid);
       
       // Attempt to execute the delete query
       if ($stmt->execute()) {
           if ($stmt->rowCount() > 0) {
               sendResponse(200, 'Faculty deleted successfully');
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
