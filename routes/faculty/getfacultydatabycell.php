<?php
function handle_getfacultydatabycell_requests($request_method, $db) {
    header("Content-Type: application/json"); 
    switch ($request_method) {
        case 'POST':
            get_getfacultydatabycell_records($db);
            break;
        default:
            sendMethodNotAllowedResponse();
            break;
    }
}

function get_getfacultydatabycell_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $day = htmlspecialchars(trim($input['day'] ?? ''));
    $start_time = htmlspecialchars(trim($input['startTime'] ?? ''));
    $end_time = htmlspecialchars(trim($input['endTime'] ?? ''));
    $fid = htmlspecialchars(trim($input['fid'] ?? ''));

    if (empty($day) || empty($start_time) || empty($end_time) || empty($fid)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

    $query = "SELECT DISTINCT * FROM timetable_entries WHERE day = :day AND start_time = :start_time AND end_time = :end_time AND fid = :fid";

    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':day', $day, PDO::PARAM_STR);
        $stmt->bindValue(':start_time', $start_time, PDO::PARAM_STR);
        $stmt->bindValue(':end_time', $end_time, PDO::PARAM_STR);
        $stmt->bindValue(':fid', $fid, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}


?>
