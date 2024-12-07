<?php

function handle_classroom_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {
        case 'GET':
            get_classroom_records($db);
            break;
        case 'POST':
            create_classroom_record($db);
            break;
        case 'PUT':
            update_classroom_record($db);
            break;
        case 'DELETE':
            delete_classroom_record($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_classroom_records($db) {
    $query = "SELECT * FROM class_room";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

function create_classroom_record($db) {
    
    $input = json_decode(file_get_contents('php://input'), true);
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $type = htmlspecialchars(trim($input['type'] ?? ''));
    $floor = htmlspecialchars(trim($input['floor'] ?? ''));
    $cap = htmlspecialchars(trim($input['cap'] ?? ''));

    if (empty($name) || empty($type) || empty($floor)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

 // Check if room_code already exists
 $query = "SELECT COUNT(*) FROM class_room WHERE name = :name ";
 $stmt = $db->prepare($query);
 $stmt->bindValue(':name', $name);


 $stmt->execute();

 $roomExists = $stmt->fetchColumn();

 if ($roomExists > 0) {
     sendBadRequestResponse('Room already exists');
     return;
 }

        // Prepare SQL query to insert room data
        $query = "INSERT INTO class_room (name, type, floor, cap) VALUES (:name, :type, :floor, :cap)";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':floor', $floor);
        $stmt->bindValue(':cap', $cap);

        // Execute the query and return the appropriate response
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Room Added successfully']);
        } else {
            sendDatabaseErrorResponse();
        }

  
}

function update_classroom_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $rid = $input['rid'] ?? null;
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $floor = htmlspecialchars(trim($input['floor'] ?? ''));
    $type = htmlspecialchars(trim($input['type'] ?? ''));
    $cap = htmlspecialchars(trim($input['cap'] ?? ''));

    // Validate sanitized input
    if (!$rid || empty($name) || empty($floor) || empty($type)) {
        sendBadRequestResponse('Invalid input or rid');
    }

    $name = html_entity_decode($name);

  // Check if the room record with the given cid exists
  $query = "SELECT * FROM class_room WHERE rid = :rid";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':rid', $rid);
  $stmt->execute();
  $room = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$room) {
      sendBadRequestResponse('No room found with the given rid');
  }

  
 // Check if any field has changed
 $changes = false;

 if ($room['name'] !== $name) {
     $changes = true;
 }
 if ($room['type'] !== $type) {
     $changes = true;
 }
 if ($room['floor'] !== $floor) {
     $changes = true;
 }

 
 // If no room, send response and exit
 if (!$changes) {
    sendResponse(200, 'room already up to date');
    return;
}



 // Prepare SQL query to update room data
 $query = "UPDATE class_room SET name = :name, floor = :floor, cap = :cap, type = :type WHERE rid = :rid";
 try {
     $stmt = $db->prepare($query);
     $stmt->bindValue(':name', $name);
     $stmt->bindValue(':floor', $floor);
     $stmt->bindValue(':cap', $cap);
     $stmt->bindValue(':type', $type);
     $stmt->bindValue(':rid', $rid);

   

     // Execute the update query
     if ($stmt->execute()) {
         sendResponse(200, 'Room updated successfully');
     } else {
         sendResponse(200, 'Room already up to date');
     }
 } catch (PDOException $e) {
     sendDatabaseErrorResponse($e->getMessage());
 }

    
   
}

function delete_classroom_record($db) {
  
  // Get input from the request body
  $input = json_decode(file_get_contents('php://input'), true);
  $rid = $input['rid'] ?? null;

  
  // Validate if the room ID is provided
    if (!$rid) {
    sendBadRequestResponse('room ID (rid) is required');
    }

      // Check if the room exists before attempting to delete
  $query = "SELECT COUNT(*) FROM class_room WHERE rid = :rid";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':rid', $rid);
  $stmt->execute();
  $roomExists = $stmt->fetchColumn();

  if ($roomExists == 0) {
      // No record found with the given rid
      sendResponse(404, 'No record found to delete');
  }




   // Prepare the DELETE SQL query
   $query = "DELETE FROM class_room WHERE rid = :rid";
   try {
       $stmt = $db->prepare($query);
       $stmt->bindValue(':rid', $rid);
       
       // Attempt to execute the delete query
       if ($stmt->execute()) {
           if ($stmt->rowCount() > 0) {
               sendResponse(200, 'room deleted successfully');
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
