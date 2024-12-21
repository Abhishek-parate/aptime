<?php

function handle_detaileddetailedtimetable_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {

        case 'POST':
            create_detailedtimetable_record($db);
            break;

        default:
            sendMethodNotAllowedResponse();
            break;
    }
}




function create_detailedtimetable_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input format
    if (!isset($input['timetableData']) || !is_array($input['timetableData'])) {
        sendBadRequestResponse('Invalid input data format');
        return;
    }

    foreach ($input['timetableData'] as $record) {
        // Extract and sanitize fields
        $tid = htmlspecialchars(trim($record['tid'] ?? ''));
        $cid = htmlspecialchars(trim($record['cid'] ?? ''));
        $fid = htmlspecialchars(trim($record['fid'] ?? ''));
        $rid = htmlspecialchars(trim($record['rid'] ?? ''));
        $day = htmlspecialchars(trim($record['day'] ?? ''));
        $start_time = htmlspecialchars(trim($record['start_time'] ?? ''));
        $end_time = htmlspecialchars(trim($record['end_time'] ?? ''));
        $options = htmlspecialchars(trim($record['options'] ?? ''));
        $status = htmlspecialchars(trim($record['status'] ?? ''));

        // Validate fields
        if ($tid === '' || $cid === '' || $fid === '' || $rid === '' || $day === '' || $start_time === '' || $end_time === '' || $status === '') {
            sendBadRequestResponse('All fields are required');
            return;
        }

        if (!is_numeric($status)) {
            sendBadRequestResponse('Status should be numeric');
            return;
        }

        // Check if the timetable entry already exists
        $query = "SELECT COUNT(*) FROM timetable_entries 
                  WHERE tid = :tid AND cid = :cid AND fid = :fid AND rid = :rid 
                  AND day = :day AND start_time = :start_time AND end_time = :end_time 
                  AND options = :options AND status = :status";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':tid', $tid);
        $stmt->bindValue(':cid', $cid);
        $stmt->bindValue(':fid', $fid);
        $stmt->bindValue(':rid', $rid);
        $stmt->bindValue(':day', $day);
        $stmt->bindValue(':start_time', $start_time);
        $stmt->bindValue(':end_time', $end_time);
        $stmt->bindValue(':options', $options);
        $stmt->bindValue(':status', $status);
        $stmt->execute();
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            sendBadRequestResponse('Timetable entry already exists');
            return;
        }

        // Insert timetable data
        $query = "INSERT INTO timetable_entries (tid, cid, fid, rid, day, start_time, end_time, options, status) 
                  VALUES (:tid, :cid, :fid, :rid, :day, :start_time, :end_time, :options, :status)";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':tid', $tid);
        $stmt->bindValue(':cid', $cid);
        $stmt->bindValue(':fid', $fid);
        $stmt->bindValue(':rid', $rid);
        $stmt->bindValue(':day', $day);
        $stmt->bindValue(':start_time', $start_time);
        $stmt->bindValue(':end_time', $end_time);
        $stmt->bindValue(':options', $options);
        $stmt->bindValue(':status', $status);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Timetable entry created successfully']);
        } else {
            sendDatabaseErrorResponse('Failed to create timetable entry');
        }
    }
}







?>
