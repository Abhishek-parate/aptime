<?php
// Database connection

function handle_roommaster_requests($request_method, $db) {
    header("Content-Type: application/json");
    switch ($request_method) {
        case 'GET':
            get_roommaster_records($db);
            break;
        case 'POST':
            create_roommaster_records($db);
            break;
        case 'PUT':
            update_roommaster_records($db);
            break;
        case 'DELETE':
            delete_roommaster_records($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_roommaster_records($db) { 
    $query = "SELECT * FROM roommaster";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

function create_roommaster_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $rid = htmlspecialchars(trim($input['rid'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));
    $sid = htmlspecialchars(trim($input['sid'] ?? ''));
    $time = htmlspecialchars(trim($input['time'] ?? ''));

    if (empty($rid) || empty($pid) || empty($yid) || empty($sid) || empty($time)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

       // Check if roommaster_code already exists
       $query = "SELECT COUNT(*) FROM roommaster WHERE rid = :rid";
       $stmt = $db->prepare($query);
       $stmt->bindValue(':rid', $rid);
       $stmt->execute();
   
       $roommasterExists = $stmt->fetchColumn();
   
       if ($roommasterExists > 0) {
           sendBadRequestResponse('roommaster already exists');
           return;
       }


         // Prepare SQL query to insert roommaster data
    $query = "INSERT INTO roommaster (rid, pid, yid, sid, time) VALUES (:rid, :pid, :yid, :sid, :time)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':rid', $rid);
    $stmt->bindValue(':pid', $pid);
    $stmt->bindValue(':yid', $yid);
    $stmt->bindValue(':sid', $sid);
    $stmt->bindValue(':time', $time);

    // Execute the query and return the appropriate response
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'roommaster Added successfully']);
    } else {
        sendDatabaseErrorResponse();
    }
}

function update_roommaster_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $rmid = $input['rmid'] ?? null;
    $rid = htmlspecialchars(trim($input['rid'] ?? ''));
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));
    $sid = htmlspecialchars(trim($input['sid'] ?? ''));
    $time = htmlspecialchars(trim($input['time'] ?? ''));

    // Validate sanitized input
    if (!$rmid || empty($rid) || empty($pid) || empty($yid) || empty($sid) || empty($time)) {
        sendBadRequestResponse('Invalid input or rmid');
        return;
    }

    $rid = html_entity_decode($rid);

    // Check if the roommaster record with the given rmid exists
    $query = "SELECT * FROM roommaster WHERE rmid = :rmid";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':rmid', $rmid);
    $stmt->execute();
    $roommaster = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$roommaster) {
        sendBadRequestResponse('No roommaster found with the given rmid');
        return;
    }

    // Check if any field has changed
    $changes = false;
    if ($roommaster['rid'] !== $rid) $changes = true;
    if ($roommaster['pid'] !== $pid) $changes = true;
    if ($roommaster['yid'] !== $yid) $changes = true;
    if ($roommaster['sid'] !== $sid) $changes = true;
    if ($roommaster['time'] !== $time) $changes = true;

    // If no changes, exit
    if (!$changes) {
        sendResponse(200, 'roommaster already up to date');
        return;
    }

    // Prepare SQL query to update roommaster data
    $query = "UPDATE roommaster SET rid = :rid, pid = :pid, yid = :yid, sid = :sid, time = :time WHERE rmid = :rmid";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':rid', $rid);
        $stmt->bindValue(':pid', $pid);
        $stmt->bindValue(':yid', $yid);
        $stmt->bindValue(':sid', $sid);
        $stmt->bindValue(':time', $time);
        $stmt->bindValue(':rmid', $rmid); // Missing binding for rmid

        // Execute the update query
        if ($stmt->execute()) {
            sendResponse(200, 'roommaster updated successfully');
        } else {
            sendResponse(500, 'Failed to update roommaster');
        }
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}


function delete_roommaster_records($db) {


  // Get input from the request body
  $input = json_decode(file_get_contents('php://input'), true);
  $rmid = $input['rmid'] ?? null;

  
  // Validate if the roommaster ID is provided
    if (!$rmid) {
    sendBadRequestResponse('roommaster ID (rmid) is required');
    }

      // Check if the roommaster exists before attempting to delete
  $query = "SELECT COUNT(*) FROM roommaster WHERE rmid = :rmid";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':rmid', $rmid);
  $stmt->execute();
  $roommasterExists = $stmt->fetchColumn();

  if ($roommasterExists == 0) {
      // No record found with the given rmid
      sendResponse(404, 'No record found to delete');
  }




   // Prepare the DELETE SQL query
   $query = "DELETE FROM roommaster WHERE rmid = :rmid";
   try {
       $stmt = $db->prepare($query);
       $stmt->bindValue(':rmid', $rmid);
       
       // Attempt to execute the delete query
       if ($stmt->execute()) {
           if ($stmt->rowCount() > 0) {
               sendResponse(200, 'roommaster deleted successfully');
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