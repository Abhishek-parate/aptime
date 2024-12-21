<?php

function handle_detailedtimetablebytid_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {

        case 'POST':
            get_detailedtimetable_record($db);
            break;

        default:
            sendMethodNotAllowedResponse();
            break;
    }
}



function get_detailedtimetable_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    $tid = $input['tid'] ?? null;

    // Validate if the Time ID is provided
    if (!$tid) {
        sendBadRequestResponse('Time ID (tid) is required');
    }

    // SQL query to get the timetable data based on tid
    $query = "SELECT * from timetable_entries 
            WHERE tid = :tid";  // Corrected SQL

    try {
        // Prepare the SQL statement
        $stmt = $db->prepare($query);

        // Bind the tid parameter to prevent SQL injection
        $stmt->bindValue(':tid', $tid, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch the results
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // Return the response as JSON
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        // Handle any database error
        sendDatabaseErrorResponse($e->getMessage());
    }
}








?>
