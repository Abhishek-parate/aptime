<?php
// Database connection



function handle_section_requests($request_method, $db) {
    header("Content-Type: application/json");
    switch ($request_method) {
        case 'GET':
            get_section_records($db);
            break;
        case 'POST':
            create_section_records($db);
            break;
        case 'PUT':
            update_section_records($db);
            break;
        case 'DELETE';
            delete_section_records($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_section_records($db) { 
    $query = "SELECT * FROM section";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

function create_section_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));

    if (empty($name) || empty($pid) || empty($yid)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

       // Check if section_code already exists
       $query = "SELECT COUNT(*) FROM section WHERE name = :name AND pid = :pid AND yid = :yid";
       $stmt = $db->prepare($query);
       $stmt->bindValue(':name', $name);
       $stmt->bindValue(':pid', $pid);
       $stmt->bindValue(':yid', $yid);
       $stmt->execute();
   
       $sectionExists = $stmt->fetchColumn();
   
       if ($sectionExists > 0) {
           sendBadRequestResponse('section already exists');
           return;
       }


         // Prepare SQL query to insert section data
    $query = "INSERT INTO section (name, pid, yid) VALUES (:name, :pid, :yid)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':pid', $pid);
    $stmt->bindValue(':yid', $yid);

    // Execute the query and return the appropriate response
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'section Added successfully']);
    } else {
        sendDatabaseErrorResponse();
    }
}

function update_section_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $sid = $input['sid'] ?? null;
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));

    // Validate sanitized input
    if (!$sid || empty($name) || empty($pid) || empty($yid)) {
        sendBadRequestResponse('Invalid input or sid');
    }

    $name = html_entity_decode($name);

    // Check if the section record with the given cid exists
    $query = "SELECT * FROM section WHERE sid = :sid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':sid', $sid);
    $stmt->execute();
    $section = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$section) {
        sendBadRequestResponse('No section found with the given sid');
    }

    

 // Check if any field has changed
 $changes = false;

 if ($section['name'] !== $name) {
     $changes = true;
 }
 if ($section['pid'] !== $pid) {
     $changes = true;
 }
 if ($section['yid'] !== $yid) {
     $changes = true;
 }

 
 // If no section, send response and exit
 if (!$changes) {
    sendResponse(200, 'section already up to date');
    return;
}

 // Prepare SQL query to update section data
 $query = "UPDATE section SET name = :name, pid = :pid, yid = :yid WHERE sid = :sid";
 try {
     $stmt = $db->prepare($query);
     $stmt->bindValue(':name', $name);
     $stmt->bindValue(':pid', $pid);
     $stmt->bindValue(':yid', $yid);
     $stmt->bindValue(':sid', $sid);

   

     // Execute the update query
     if ($stmt->execute()) {
         sendResponse(200, 'section updated successfully');
     } else {
         sendResponse(200, 'section already up to date');
     }
 } catch (PDOException $e) {
     sendDatabaseErrorResponse($e->getMessage());
 }

}


function delete_section_records($db) {


  // Get input from the request body
  $input = json_decode(file_get_contents('php://input'), true);
  $sid = $input['sid'] ?? null;

  
  // Validate if the section ID is provided
    if (!$sid) {
    sendBadRequestResponse('section ID (sid) is required');
    }

      // Check if the section exists before attempting to delete
  $query = "SELECT COUNT(*) FROM section WHERE sid = :sid";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':sid', $sid);
  $stmt->execute();
  $sectionExists = $stmt->fetchColumn();

  if ($sectionExists == 0) {
      // No record found with the given sid
      sendResponse(404, 'No record found to delete');
  }




   // Prepare the DELETE SQL query
   $query = "DELETE FROM section WHERE sid = :sid";
   try {
       $stmt = $db->prepare($query);
       $stmt->bindValue(':sid', $sid);
       
       // Attempt to execute the delete query
       if ($stmt->execute()) {
           if ($stmt->rowCount() > 0) {
               sendResponse(200, 'section deleted successfully');
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