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

    $allSuccess = true;
    $errorMessage = '';

    // Loop over the timetable data
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

        // Check for deletion condition
        if (empty($cid) && empty($rid) && !empty($teid)) {
            // Delete the timetable entry
            $deleteQuery = "DELETE FROM timetable_entries WHERE teid = :teid";
            $stmt = $db->prepare($deleteQuery);
            $stmt->bindValue(':teid', $teid);

            if (!$stmt->execute()) {
                $allSuccess = false;
                $errorMessage = 'Failed to delete timetable entry';
                break;
            }
            continue; // Skip to the next record
        }

        // Validate fields for insert/update
        if ($tid === '' || $cid === '' || $fid === '' || $rid === '' || $day === '' || $start_time === '' || $end_time === '') {
            $allSuccess = false;
            $errorMessage = 'All fields are required unless deleting';
            break;
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

                if (!$stmt->execute()) {
                    $allSuccess = false;
                    $errorMessage = 'Failed to update timetable entry';
                    break;
                }
            } else {
                $allSuccess = false;
                $errorMessage = 'Timetable entry with provided teid does not exist';
                break;
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

            if (!$stmt->execute()) {
                $allSuccess = false;
                $errorMessage = 'Failed to create timetable entry';
                break;
            }
        }
    }

    // Send a single response based on the outcome
    if ($allSuccess) {
        echo json_encode(['success' => true, 'message' => 'Timetable entries processed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }
}
?>