<?php

function handle_timetabledata_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {
        case 'POST':
            get_timetabledata_records($db);
            break;
       
        default:
            sendMethodNotAllowedResponse();
            break;
    }
}

function get_timetabledata_records($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    $tid = $input['tid'] ?? null;

    // Validate if the Time ID is provided
    if (!$tid) {
        sendBadRequestResponse('Time ID (tid) is required');
    }

    // SQL query to get the timetable data based on tid
    $query = "SELECT 
                timetable_create.tid,
                dept.did AS did,
                dept.name AS dept_name,
                program.pid AS pid,
                program.name AS program_name,
                yeardata.yid AS yid,
                yeardata.name AS year_name,
                section.sid AS sid,
                section.name AS section_name,
                semesterdata.semid AS semid,
                semesterdata.name AS sem_name,
                timetable_create.gap,
                timetable_create.start_time,
                timetable_create.end_time
            FROM 
                timetable_create
            JOIN 
                dept ON timetable_create.did = dept.did
            JOIN 
                program ON timetable_create.pid = program.pid
            JOIN 
                yeardata ON timetable_create.yid = yeardata.yid
            JOIN 
                section ON timetable_create.sid = section.sid
             JOIN 
                semesterdata ON timetable_create.semid = semesterdata.semid
            WHERE timetable_create.tid = :tid";  // Corrected SQL

    try {
        // Prepare the SQL statement
        $stmt = $db->prepare($query);

        // Bind the tid parameter to prevent SQL injection
        $stmt->bindValue(':tid', $tid, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch the results
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if any results were returned
        if (empty($result)) {
            sendResponse(404, 'No record found for the provided Timetable ID');
        }

        // Return the response as JSON
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        // Handle any database error
        sendDatabaseErrorResponse($e->getMessage());
    }
}


?>
