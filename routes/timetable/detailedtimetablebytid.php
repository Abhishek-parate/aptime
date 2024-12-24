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
    $query = "SELECT teid, tid, cid, fid, rid, day, start_time, end_time, elective
              FROM timetable_entries
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

        // Prepare grouped data
        $groupedData = [];
        $separateElectiveZero = [];

        // Loop through all rows and process them
        foreach ($result as $row) {
            $elective = $row['elective'] ?? null;

            // Check if the elective is 0, and add to a separate array
            if ($elective == '0') {
                $separateElectiveZero[] = [
                    'cid' => $row['cid'],
                    'fid' => $row['fid'],
                    'rid' => $row['rid'],
                    'day' => $row['day'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'elective' => '0'
                ];
            } else {
                // Group data by elective
                if (!isset($groupedData[$elective])) {
                    $groupedData[$elective] = [
                        'elective' => $elective,
                        'courses'  => []
                    ];
                }

                $groupedData[$elective]['courses'][] = [
                    'cid' => $row['cid'],
                    'fid' => $row['fid'],
                    'rid' => $row['rid'],
                    'day' => $row['day'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time']
                ];
            }
        }

        // Return the final grouped data
        $finalData = array_values($groupedData); // Convert to a simple array

        // Merge the separate entries with elective = 0 into the final response
        if (!empty($separateElectiveZero)) {
            $finalData = array_merge($finalData, $separateElectiveZero);
        }

        // Return the response as JSON
        echo json_encode(['success' => true, 'data' => $finalData]);
    } catch (PDOException $e) {
        // Handle any database error
        sendDatabaseErrorResponse($e->getMessage());
    }
}



?>
