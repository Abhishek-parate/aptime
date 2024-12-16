<?php
function handle_getfacultybytimetable_requests($request_method, $db) {
    header("Content-Type: application/json"); 
    switch ($request_method) {
        case 'POST':
            get_getfacultybytimetable_records($db);
            break;
       
        default:
            sendMethodNotAllowedResponse();
            break;
    }
}

function get_getfacultybytimetable_records($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $pid = htmlspecialchars(trim($input['pid'] ?? ''));
    $yid = htmlspecialchars(trim($input['yid'] ?? ''));
    $sid = htmlspecialchars(trim($input['sid'] ?? ''));

    if (empty($pid) || empty($yid) || empty($sid)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

$query = "SELECT 
    ca.caid, 
    ca.cid, 
    ca.pid, 
    ca.yid, 
    ca.sid, 
    ca.fid, 
    f.name AS faculty_name, 
    f.entrytime, 
    f.exittime, 
    f.max_allowed_lecture,
    c.name AS course_name, 
    c.alias AS course_alias, 
    c.course_code, 
    c.category, 
    c.max_lecture, 
    c.duration
FROM 
    courseallotment ca
JOIN 
    faculty f 
ON 
    ca.fid = f.fid
JOIN 
    course c 
ON 
    ca.cid = c.cid
WHERE 
    ca.pid = :pid AND 
    ca.yid = :yid AND 
    ca.sid = :sid";  

    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
        $stmt->bindValue(':yid', $yid, PDO::PARAM_INT);
        $stmt->bindValue(':sid', $sid, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($result)) {
            sendResponse(404, 'No record found for the provided ID');
        }
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}


?>
