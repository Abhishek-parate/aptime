<?php

function handle_getTid_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {
        case 'POST':
            get_getTid_record($db);
            break;
        default:
            sendMethodNotAllowedResponse();
            break;
    }
}

function get_getTid_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    $did = $input['did'] ?? null;
    $pid = $input['pid'] ?? null;
    $yid = $input['yid'] ?? null;
    $sid = $input['sid'] ?? null;
    $semid = $input['semid'] ?? null;

    // Validate input
    if (empty($did) || empty($pid) || empty($yid) || empty($sid) || empty($semid)) {
        sendBadRequestResponse('All fields are required');
        return;
    }

    $query = "SELECT tid
              FROM timetable_create
              WHERE did = :did AND pid = :pid AND sid = :sid AND semid = :semid AND yid = :yid";

    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':did', $did, PDO::PARAM_INT);
        $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
        $stmt->bindValue(':yid', $yid, PDO::PARAM_INT);
        $stmt->bindValue(':sid', $sid, PDO::PARAM_INT);
        $stmt->bindValue(':semid', $semid, PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $result, 'message' => 'TID retrieved successfully']);
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}

?>
