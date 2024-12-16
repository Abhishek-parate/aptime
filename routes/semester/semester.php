<?php
// Database connection



function handle_semesterdata_requests($request_method, $db) {
    header("Content-Type: application/json");
    switch ($request_method) {
        case 'GET':
            get_semesterdata_records($db);
            break;
        case 'POST':
            create_semesterdata_records($db);
            break;
        case 'PUT':
            update_semesterdata_records($db);
            break;
        case 'DELETE';
            delete_semesterdata_records($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_semesterdata_records($db) { 
    $query = "SELECT * FROM semesterdata";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

function create_semesterdata_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));

    if (empty($name) || empty($pid) || empty($yid)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

       // Check if semesterdata_code already exists
       $query = "SELECT COUNT(*) FROM semesterdata WHERE name = :name AND pid = :pid AND yid = :yid";
       $stmt = $db->prepare($query);
       $stmt->bindValue(':name', $name);
       $stmt->bindValue(':pid', $pid);
       $stmt->bindValue(':yid', $yid);
       $stmt->execute();
   
       $semesterdataExists = $stmt->fetchColumn();
   
       if ($semesterdataExists > 0) {
           sendBadRequestResponse('semesterdata already exists');
           return;
       }


         // Prepare SQL query to insert semesterdata data
    $query = "INSERT INTO semesterdata (name, pid, yid) VALUES (:name, :pid, :yid)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':pid', $pid);
    $stmt->bindValue(':yid', $yid);

    // Execute the query and return the appropriate response
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'semesterdata Added successfully']);
    } else {
        sendDatabaseErrorResponse();
    }
}

function update_semesterdata_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $semid = $input['semid'] ?? null;
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));

    // Validate sanitized input
    if (!$semid || empty($name) || empty($pid) || empty($yid)) {
        sendBadRequestResponse('Invalid input or semid');
    }

    $name = html_entity_decode($name);

    // Check if the semesterdata record with the given cid exists
    $query = "SELECT * FROM semesterdata WHERE semid = :semid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':semid', $semid);
    $stmt->execute();
    $semesterdata = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$semesterdata) {
        sendBadRequestResponse('No semesterdata found with the given semid');
    }

    

 // Check if any field has changed
 $changes = false;

 if ($semesterdata['name'] !== $name) {
     $changes = true;
 }
 if ($semesterdata['pid'] !== $pid) {
     $changes = true;
 }
 if ($semesterdata['yid'] !== $yid) {
     $changes = true;
 }

 
 // If no semesterdata, send response and exit
 if (!$changes) {
    sendResponse(200, 'semesterdata already up to date');
    return;
}

 // Prepare SQL query to update semesterdata data
 $query = "UPDATE semesterdata SET name = :name, pid = :pid, yid = :yid WHERE semid = :semid";
 try {
     $stmt = $db->prepare($query);
     $stmt->bindValue(':name', $name);
     $stmt->bindValue(':pid', $pid);
     $stmt->bindValue(':yid', $yid);
     $stmt->bindValue(':semid', $semid);

   

     // Execute the update query
     if ($stmt->execute()) {
         sendResponse(200, 'semesterdata updated successfully');
     } else {
         sendResponse(200, 'semesterdata already up to date');
     }
 } catch (PDOException $e) {
     sendDatabaseErrorResponse($e->getMessage());
 }

}


function delete_semesterdata_records($db) {


  // Get input from the request body
  $input = json_decode(file_get_contents('php://input'), true);
  $semid = $input['semid'] ?? null;

  
  // Validate if the semesterdata ID is provided
    if (!$semid) {
    sendBadRequestResponse('semesterdata ID (semid) is required');
    }

      // Check if the semesterdata exists before attempting to delete
  $query = "SELECT COUNT(*) FROM semesterdata WHERE semid = :semid";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':semid', $semid);
  $stmt->execute();
  $semesterdataExists = $stmt->fetchColumn();

  if ($semesterdataExists == 0) {
      // No record found with the given semid
      sendResponse(404, 'No record found to delete');
  }




   // Prepare the DELETE SQL query
   $query = "DELETE FROM semesterdata WHERE semid = :semid";
   try {
       $stmt = $db->prepare($query);
       $stmt->bindValue(':semid', $semid);
       
       // Attempt to execute the delete query
       if ($stmt->execute()) {
           if ($stmt->rowCount() > 0) {
               sendResponse(200, 'semesterdata deleted successfully');
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