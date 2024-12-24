<?php

function handle_detailedtimetablebytid_requests($request_method, $db) {
    header("Content-Type: application/json");
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
    $input = json_decode(file_get_contents('php://input'), true);

    $tid = $input['tid'] ?? null;

    if (!$tid) {
        sendBadRequestResponse('Time ID (tid) is required');
        return;
    }

    $query = "SELECT teid, tid, cid, fid, rid, day, start_time, end_time, elective
              FROM timetable_entries
              WHERE tid = :tid";

    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':tid', $tid, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $groupedData = [];
        $separateElectiveZero = [];

        foreach ($result as $row) {
            $elective = $row['elective'] ?? null;

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

        $finalData = array_values($groupedData);

        if (!empty($separateElectiveZero)) {
            $finalData = array_merge($finalData, $separateElectiveZero);
        }

        echo json_encode(['success' => true, 'data' => $finalData]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

?>
