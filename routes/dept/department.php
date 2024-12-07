<?php

function handle_dept_requests($request_method, $db) {
    switch ($request_method) {
        case 'GET':
            get_dept_records($db);
            break;
        case 'POST':
            create_dept_records($db);
            break;
        case 'PUT':
            update_dept_records($db);
            break;
        case 'DELETE':
            delete_dept_records($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_dept_records($db) {
    $query = "SELECT * FROM dept";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}




function create_dept_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $alias = htmlspecialchars(trim($input['alias'] ?? ''));
    $timeid = htmlspecialchars(trim($input['timeid'] ?? ''));

    if (empty($name) || empty($alias) || empty($timeid)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

    // Check if dept_code already exists
    $query = "SELECT COUNT(*) FROM dept WHERE name = :name";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $name);
    $stmt->execute();

    $departmentExists = $stmt->fetchColumn();

    if ($departmentExists > 0) {
        sendBadRequestResponse('Department Name already exists');
        return;
    }

    // Prepare SQL query to insert dept data
    $query = "INSERT INTO dept (name, alias, timeid) VALUES (:name, :alias, :timeid)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':alias', $alias);
    $stmt->bindValue(':timeid', $timeid);

    // Execute the query and return the appropriate response
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Department record created successfully']);
    } else {
        sendDatabaseErrorResponse();
    }
}


function update_dept_records($db)
 {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $did = $input['did'] ?? null;
    $name = htmlspecialchars(trim($input['name'] ?? ''));
    $alias = htmlspecialchars(trim($input['alias'] ?? ''));
    $timeid = htmlspecialchars(trim($input['timeid'] ?? ''));


    // Validate sanitized input
    if (!$did || empty($name) || empty($alias) || empty($timeid)) {
        sendBadRequestResponse('Invalid input or DID');
    }

    $name = html_entity_decode($name);

// Check if the dept record with the given cid exists
$query = "SELECT * FROM dept WHERE did = :did";
$stmt = $db->prepare($query);
$stmt->bindValue(':did', $did);
$stmt->execute();
$dept = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dept) {
    sendBadRequestResponse('No dept found with the given did');
}


 // Check if any field has changed
 $changes = false;

 if ($dept['name'] !== $name) {
     $changes = true;
 }
 if ($dept['alias'] !== $alias) {
     $changes = true;
 }
 if ($dept['timeid'] !== $timeid) {
     $changes = true;
 }
 


 // If no dept, send response and exit
 if (!$changes) {
     sendResponse(200, 'Department record already up to date');
     return;
 }

 // Prepare SQL query to update dept data
 $query = "UPDATE dept SET name = :name, alias = :alias, timeid = :timeid WHERE did = :did";
 try {
     $stmt = $db->prepare($query);
     $stmt->bindValue(':name', $name);
     $stmt->bindValue(':alias', $alias);
     $stmt->bindValue(':timeid', $timeid);
     $stmt->bindValue(':did', $did); // Bind the 'did'

   

     // Execute the update query
     if ($stmt->execute()) {
         sendResponse(200, 'Department record updated successfully');
     } else {
         sendResponse(200, 'Department record already up to date');
     }
 } catch (PDOException $e) {
     sendDatabaseErrorResponse($e->getMessage());
 }


   
}


function delete_dept_records($db) {

  // Get input from the request body
  $input = json_decode(file_get_contents('php://input'), true);
  $did = $input['did'] ?? null;

  // Validate if the dept ID is provided
  if (!$did) {
      sendBadRequestResponse('Department ID (did) is required');
  }

  // Check if the dept exists before attempting to delete
  $query = "SELECT COUNT(*) FROM dept WHERE did = :did";
  $stmt = $db->prepare($query);
  $stmt->bindValue(':did', $did);
  $stmt->execute();
  $deptExists = $stmt->fetchColumn();

  if ($deptExists == 0) {
      // No record found with the given did
      sendResponse(404, 'No record found to delete');
  }

   // Prepare the DELETE SQL query
   $query = "DELETE FROM dept WHERE did = :did";
   try {
       $stmt = $db->prepare($query);
       $stmt->bindValue(':did', $did);
       
       // Attempt to execute the delete query
       if ($stmt->execute()) {
           if ($stmt->rowCount() > 0) {
               sendResponse(200, 'Department record deleted successfully');
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
