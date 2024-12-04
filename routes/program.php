<?php
// Database connection

function handle_program_requests($request_method, $db) {
    header("Content-Type: application/json");
    switch ($request_method) {
        case 'GET':
            get_program_records($db);
            break;
        case 'POST':
            create_program_record($db);
            break;
        case 'PUT':
            update_program_record($db);
            break;
        case 'DELETE':
            delete_program_record($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_program_records($db) { 
    $query = "SELECT * FROM program";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

function create_program_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);


    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $alias = htmlspecialchars(trim($input['alias'] ?? ''));
    $did = htmlspecialchars(trim($input['did'] ?? ''));

    if (empty($name) || empty($alias) || empty($did)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

       // Check if program_code already exists
       $query = "SELECT COUNT(*) FROM program WHERE name = :name";
       $stmt = $db->prepare($query);
       $stmt->bindValue(':name', $name);
       $stmt->execute();
   
       $programExists = $stmt->fetchColumn();
   
       if ($programExists > 0) {
           sendBadRequestResponse('program already exists');
           return;
       }


         // Prepare SQL query to insert program data
    $query = "INSERT INTO program (name, alias, did) VALUES (:name, :alias, :did)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':alias', $alias);
    $stmt->bindValue(':did', $did);

    // Execute the query and return the appropriate response
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Program Added successfully']);
    } else {
        sendDatabaseErrorResponse();
    }
}

function update_program_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $pid = $input['pid'] ?? null;
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $alias = htmlspecialchars(trim($input['alias'] ?? ''));
    $did = htmlspecialchars(trim($input['did'] ?? ''));

    // Validate sanitized input
    if (!$did || empty($name) || empty($alias) || empty($did)) {
        sendBadRequestResponse('Invalid input or DID');
    }

    $name = html_entity_decode($name);

    // Check if the program record with the given cid exists
    $query = "SELECT * FROM program WHERE pid = :pid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':pid', $pid);
    $stmt->execute();
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$program) {
        sendBadRequestResponse('No program found with the given did');
    }

    

 // Check if any field has changed
 $changes = false;

 if ($program['name'] !== $name) {
     $changes = true;
 }
 if ($program['alias'] !== $alias) {
     $changes = true;
 }
 if ($program['did'] !== $did) {
     $changes = true;
 }

 
 // If no program, send response and exit
 if (!$changes) {
    sendResponse(200, 'Program already up to date');
    return;
}

 // Prepare SQL query to update program data
 $query = "UPDATE program SET name = :name, alias = :alias, did = :did WHERE pid = :pid";
 try {
     $stmt = $db->prepare($query);
     $stmt->bindValue(':name', $name);
     $stmt->bindValue(':alias', $alias);
     $stmt->bindValue(':did', $did);
     $stmt->bindValue(':pid', $pid); // Bind the 'pid'

   

     // Execute the update query
     if ($stmt->execute()) {
         sendResponse(200, 'Program updated successfully');
     } else {
         sendResponse(200, 'Program already up to date');
     }
 } catch (PDOException $e) {
     sendDatabaseErrorResponse($e->getMessage());
 }


}


function delete_program_record($db) {


  // Get input from the request body
  $input = json_decode(file_get_contents('php://input'), true);
  $pid = $input['pid'] ?? null;

  
  // Validate if the program ID is provided
    if (!$pid) {
    sendBadRequestResponse('Program ID (pid) is required');
    }

      // Check if the program exists before attempting to delete
  $query = "SELECT COUNT(*) FROM program WHERE pid = :pid";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':pid', $pid);
  $stmt->execute();
  $programExists = $stmt->fetchColumn();

  if ($programExists == 0) {
      // No record found with the given pid
      sendResponse(404, 'No record found to delete');
  }




   // Prepare the DELETE SQL query
   $query = "DELETE FROM program WHERE pid = :pid";
   try {
       $stmt = $db->prepare($query);
       $stmt->bindValue(':pid', $pid);
       
       // Attempt to execute the delete query
       if ($stmt->execute()) {
           if ($stmt->rowCount() > 0) {
               sendResponse(200, 'Program deleted successfully');
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
