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

    $query = "
        SELECT DISTINCT te.*, 
               f.name as faculty_name, 
               c.name as course_name, 
               r.name as room_name, 
               tc.*, 
               d.name as dept_name, 
               d.alias as dept_alias, 
               p.name as program_name, 
               p.alias as program_alias, 
               yd.name as yeardata_name
        FROM timetable_entries te
        JOIN faculty f ON te.fid = f.fid
        JOIN course c ON te.cid = c.cid
        JOIN class_room r ON te.rid = r.rid
        JOIN timetable_create tc ON te.tid = tc.tid
        JOIN dept d ON c.did = d.did
        JOIN program p ON d.did = p.did
        JOIN yeardata yd ON p.pid = yd.pid
        WHERE te.day = :day 
          AND te.start_time = :start_time 
          AND te.end_time = :end_time 
          AND te.fid = :fid";

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
