<?php
// Database connection

function handle_masterfaculty_requests($request_method, $db) {
    header("Content-Type: application/json");
    switch ($request_method) {
        case 'GET':
            get_masterfaculty_records($db);
            break;
        case 'POST':
            create_masterfaculty_records($db);
            break;
        case 'PUT':
            update_masterfaculty_records($db);
            break;
        case 'DELETE':
            delete_masterfaculty_records($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_masterfaculty_records($db) { 
    $query = "SELECT * FROM masterfaculty";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

function create_masterfaculty_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $fid = htmlspecialchars(trim($input['fid'] ?? ''));
    $time = htmlspecialchars(trim($input['time'] ?? ''));
    $day = htmlspecialchars(trim($input['day'] ?? ''));

    if (empty($fid) || empty($time) || empty($day)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

       // Check if masterfaculty_code already exists
       $query = "SELECT COUNT(*) FROM masterfaculty WHERE fid = :fid";
       $stmt = $db->prepare($query);
       $stmt->bindValue(':fid', $fid);
       $stmt->execute();
   
       $masterfacultyExists = $stmt->fetchColumn();
   
       if ($masterfacultyExists > 0) {
           sendBadRequestResponse('masterfaculty already exists');
           return;
       }


         // Prepare SQL query to insert masterfaculty data
    $query = "INSERT INTO masterfaculty (fid, time, day) VALUES (:fid, :time, :day)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':fid', $fid);
    $stmt->bindValue(':time', $time);
    $stmt->bindValue(':day', $day);

    // Execute the query and return the appropriate response
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'masterfaculty Added successfully']);
    } else {
        sendDatabaseErrorResponse();
    }
}

function update_masterfaculty_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $mfid = $input['mfid'] ?? null;
    $fid = htmlspecialchars(trim($input['fid'] ?? ''));
    $time = htmlspecialchars(trim($input['time'] ?? ''));
    $day = htmlspecialchars(trim($input['day'] ?? ''));

    // Validate sanitized input
    if (!$mfid || empty($fid) || empty($time) || empty($day)) {
        sendBadRequestResponse('Invalid input or mfid');
    }

    $fid = html_entity_decode($fid);

    // Check if the masterfaculty record with the given cid exists
    $query = "SELECT * FROM masterfaculty WHERE mfid = :mfid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':mfid', $mfid);
    $stmt->execute();
    $masterfaculty = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$masterfaculty) {
        sendBadRequestResponse('No masterfaculty found with the given mfid');
    }

    

 // Check if any field has changed
 $changes = false;

 if ($masterfaculty['fid'] !== $fid) {
     $changes = true;
 }
 if ($masterfaculty['time'] !== $time) {
     $changes = true;
 }
 if ($masterfaculty['day'] !== $day) {
     $changes = true;
 }

 
 // If no masterfaculty, send response and exit
 if (!$changes) {
    sendResponse(200, 'masterfaculty already up to date');
    return;
}

 // Prepare SQL query to update masterfaculty data
 $query = "UPDATE masterfaculty SET fid = :fid, time = :time, day = :day WHERE mfid = :mfid";
 try {
     $stmt = $db->prepare($query);
     $stmt->bindValue(':fid', $fid);
     $stmt->bindValue(':time', $time);
     $stmt->bindValue(':day', $day);
     $stmt->bindValue(':mfid', $mfid);

   

     // Execute the update query
     if ($stmt->execute()) {
         sendResponse(200, 'masterfaculty updated successfully');
     } else {
         sendResponse(200, 'masterfaculty already up to date');
     }
 } catch (PDOException $e) {
     sendDatabaseErrorResponse($e->getMessage());
 }

}


function delete_masterfaculty_records($db) {


  // Get input from the request body
  $input = json_decode(file_get_contents('php://input'), true);
  $mfid = $input['mfid'] ?? null;

  
  // Validate if the masterfaculty ID is provided
    if (!$mfid) {
    sendBadRequestResponse('masterfaculty ID (mfid) is required');
    }

      // Check if the masterfaculty exists before attempting to delete
  $query = "SELECT COUNT(*) FROM masterfaculty WHERE mfid = :mfid";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':mfid', $mfid);
  $stmt->execute();
  $masterfacultyExists = $stmt->fetchColumn();

  if ($masterfacultyExists == 0) {
      // No record found with the given mfid
      sendResponse(404, 'No record found to delete');
  }




   // Prepare the DELETE SQL query
   $query = "DELETE FROM masterfaculty WHERE mfid = :mfid";
   try {
       $stmt = $db->prepare($query);
       $stmt->bindValue(':mfid', $mfid);
       
       // Attempt to execute the delete query
       if ($stmt->execute()) {
           if ($stmt->rowCount() > 0) {
               sendResponse(200, 'masterfaculty deleted successfully');
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