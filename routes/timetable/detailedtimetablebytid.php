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
        return; // Early return to stop further execution
    }

    // SQL query to get the timetable data based on tid, assuming 'elective' field exists
    $query = "SELECT * FROM timetable_entries WHERE tid = :tid";  // Corrected SQL

    try {
        // Prepare the SQL statement
        $stmt = $db->prepare($query);

        // Bind the tid parameter to prevent SQL injection
        $stmt->bindValue(':tid', $tid, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch the results
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group the results by elective
        $grouped_result = groupByElective($result);

        // Return the response as JSON
        echo json_encode(['success' => true, 'data' => $grouped_result]);
    } catch (PDOException $e) {
        // Handle any database error
        sendDatabaseErrorResponse($e->getMessage());
    }
}

// Function to group the timetable entries by elective
function groupByElective($result) {
    $grouped = [];

    foreach ($result as $row) {
        $elective = $row['elective'] ?? 'No Group'; // Default to 'No Group' if elective field is missing

        // Group entries by elective
        if (!isset($grouped[$elective])) {
            $grouped[$elective] = [];
        }

        // Add the timetable entry to the respective elective group
        $grouped[$elective][] = [
            'cid' => $row['cid'],
            'fid' => $row['fid'],
            'rid' => $row['rid'],
            'day' => $row['day'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time']
        ];
    }

    // Format the result to match the desired output structure
    $formatted_result = [];
    foreach ($grouped as $elective => $entries) {
        $formatted_result[] = [
            'elective' => $elective,
            'entries' => $entries
        ];
    }

    return $formatted_result;
}


?>
