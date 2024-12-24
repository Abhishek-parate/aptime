<?php

function handle_detaileddetailedtimetable_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {

        case 'POST':
            create_detailedtimetablebytid_record($db);
            break;

        default:
            sendMethodNotAllowedResponse();
            break;
    }
}




function create_detailedtimetablebytid_record($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input format
    if (!isset($input['timetableData']) || !is_array($input['timetableData'])) {
        sendBadRequestResponse('Invalid input data format');
        return;
    }

    foreach ($input['timetableData'] as $record) {
        // Extract and sanitize fields
        $teid = htmlspecialchars(trim($record['teid'] ?? ''));
        $tid = htmlspecialchars(trim($record['tid'] ?? ''));
        $cid = htmlspecialchars(trim($record['cid'] ?? ''));
        $fid = htmlspecialchars(trim($record['fid'] ?? ''));
        $rid = htmlspecialchars(trim($record['rid'] ?? ''));
        $day = htmlspecialchars(trim($record['day'] ?? ''));
        $start_time = htmlspecialchars(trim($record['start_time'] ?? ''));
        $end_time = htmlspecialchars(trim($record['end_time'] ?? ''));
        $elective = htmlspecialchars(trim($record['elective'] ?? ''));

        // Validate fields
        if ($tid === '' || $cid === '' || $fid === '' || $rid === '' || $day === '' || $start_time === '' || $end_time === '') {
            sendBadRequestResponse('All fields are required');
            return;
        }

          // Check if the timetable entry already exists (for update)
          if (!empty($teid)) {
            $query = "SELECT COUNT(*) FROM timetable_entries WHERE teid = :teid";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':teid', $teid);
            $stmt->execute();
            $exists = $stmt->fetchColumn();

            if ($exists > 0) {
                // Update the existing timetable entry
                $updateQuery = "UPDATE timetable_entries SET 
                                tid = :tid, 
                                cid = :cid, 
                                fid = :fid, 
                                rid = :rid, 
                                day = :day, 
                                start_time = :start_time, 
                                end_time = :end_time, 
                                elective = :elective 
                                WHERE teid = :teid";

                $stmt = $db->prepare($updateQuery);
                $stmt->bindValue(':teid', $teid);
                $stmt->bindValue(':tid', $tid);
                $stmt->bindValue(':cid', $cid);
                $stmt->bindValue(':fid', $fid);
                $stmt->bindValue(':rid', $rid);
                $stmt->bindValue(':day', $day);
                $stmt->bindValue(':start_time', $start_time);
                $stmt->bindValue(':end_time', $end_time);
                $stmt->bindValue(':elective', $elective);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Timetable entry updated successfully']);
                } else {
                    sendDatabaseErrorResponse('Failed to update timetable entry');
                }
            } else {
                sendBadRequestResponse('Timetable entry with provided teid does not exist');
            }
        } else {
            // Insert new timetable data
            $insertQuery = "INSERT INTO timetable_entries (tid, cid, fid, rid, day, start_time, end_time, elective) 
                            VALUES (:tid, :cid, :fid, :rid, :day, :start_time, :end_time, :elective)";
            $stmt = $db->prepare($insertQuery);
            $stmt->bindValue(':tid', $tid);
            $stmt->bindValue(':cid', $cid);
            $stmt->bindValue(':fid', $fid);
            $stmt->bindValue(':rid', $rid);
            $stmt->bindValue(':day', $day);
            $stmt->bindValue(':start_time', $start_time);
            $stmt->bindValue(':end_time', $end_time);
            $stmt->bindValue(':elective', $elective);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Timetable entry created successfully']);
            } else {
                sendDatabaseErrorResponse('Failed to create timetable entry');
            }
        }
    }
}







?>
