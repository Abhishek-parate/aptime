<?php

function handle_yeardata_requests($request_method, $db) {
    switch ($request_method) {
        case 'GET':
            get_yeardata_records($db);
            break;
        case 'POST':
            create_yeardata_records($db);
            break;
        case 'PUT':
            update_yeardata_records($db);
            break;
        case 'DELETE':
            delete_yeardata_records($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_yeardata_records($db) {
    $query = "SELECT * FROM yeardata";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }

}

function create_yeardata_records($db) {


    $input = json_decode(file_get_contents('php://input'), true);

    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));


    if (empty($name) || empty($pid)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

       // Check if year already exists
       $query = "SELECT COUNT(*) FROM yeardata WHERE name = :name && pid = :pid";
       $stmt = $db->prepare($query);
       $stmt->bindValue(':pid', $pid);
       $stmt->bindValue(':name', $name);
       $stmt->execute();
   
       $departmentExists = $stmt->fetchColumn();
   
       if ($departmentExists > 0) {
           sendBadRequestResponse('Year already exists');
           return;
       }
   
  // Prepare SQL query to insert year data
  $query = "INSERT INTO yeardata (name, pid) VALUES (:name, :pid)";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':name', $name);
  $stmt->bindValue(':pid', $pid);
  

  // Execute the query and return the appropriate response
  if ($stmt->execute()) {
      echo json_encode(['success' => true, 'message' => 'Year created successfully']);
  } else {
      sendDatabaseErrorResponse();
  }




}

function update_yeardata_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $yid = $input['yid'] ?? null;
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));


    // Validate sanitized input
    if (!$yid || empty($name) || empty($pid)) {
        sendBadRequestResponse('Invalid input or yid');
    }

    $name = html_entity_decode($name);

// Check if the year record with the given cid exists
$query = "SELECT * FROM yeardata WHERE yid = :yid";
$stmt = $db->prepare($query);
$stmt->bindValue(':yid', $yid);
$stmt->execute();
$year = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$year) {
    sendBadRequestResponse('No Year found with the given yid');
}

 // Check if any field has changed
 $changes = false;

 if ($year['name'] !== $name) {
     $changes = true;
 }
 if ($year['pid'] !== $pid) {
     $changes = true;
 }

 
 
 // If no dept, send response and exit
 if (!$changes) {
    sendResponse(200, 'Year record already up to date');
    return;
}

// Prepare SQL query to update dept data
$query = "UPDATE yeardata SET name = :name, pid = :pid WHERE yid = :yid";
try {
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':pid', $pid);
    
    $stmt->bindValue(':yid', $yid); // Bind the 'yid'

  

    // Execute the update query
    if ($stmt->execute()) {
        sendResponse(200, 'Year record updated successfully');
    } else {
        sendResponse(200, 'Year record already up to date');
    }
} catch (PDOException $e) {
    sendDatabaseErrorResponse($e->getMessage());
}

}







function delete_yeardata_records($db) {
  
      // Get input from the request body
      $input = json_decode(file_get_contents('php://input'), true);
      $yid = $input['yid'] ?? null;
    
      // Validate if the yid ID is provided
      if (!$yid) {
          sendBadRequestResponse('Year ID (yid) is required');
      }
    
       // Check if the year exists before attempting to delete
       $query = "SELECT COUNT(*) FROM yeardata WHERE yid = :yid";
       $stmt = $db->prepare($query);
       $stmt->bindValue(':yid', $yid);
       $stmt->execute();
       $deptExists = $stmt->fetchColumn();
     
       if ($deptExists == 0) {
           // No record found with the given yid
           sendResponse(404, 'No record found to delete');
       }
     
     // Prepare the DELETE SQL query
     $query = "DELETE FROM yeardata WHERE yid = :yid";
     try {
         $stmt = $db->prepare($query);
         $stmt->bindValue(':yid', $yid);
         
         // Attempt to execute the delete query
         if ($stmt->execute()) {
             if ($stmt->rowCount() > 0) {
                 sendResponse(200, 'Year record deleted successfully');
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
