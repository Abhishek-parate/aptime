<?php
function handle_allotment_requests($request_method, $db) {
    header("Content-Type: application/json");
    switch ($request_method) {
        case 'GET':
            get_allotment_records($db);
            break;
        case 'POST':
            create_allotment_record($db);
            break;
        case 'PUT':
            update_allotment_record($db);
            break;
        case 'DELETE':
            delete_allotment_record($db);
            break;
        default:
            header("HTTP/1.0 405 Method Not Allowed");
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function get_allotment_records($db) {
    $query = "
      SELECT a.aid, a.pid, a.cid, a.fid, a.did, 
       p.name AS program_name, 
       c.name AS course_name, 
       f.name AS Faculty_name, 
       d.name AS Dept_name
FROM allotment a
LEFT JOIN program p ON a.pid = p.pid
LEFT JOIN course c ON a.cid = c.cid
LEFT JOIN faculty f ON a.fid = f.fid
LEFT JOIN dept d ON a.did = d.did;

    ";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $result]);
}

function create_allotment_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['pid']) || empty($input['cid']) || empty($input['fid']) || empty($input['did'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }

    $query = "INSERT INTO allotment (pid, cid, fid, did) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$input['pid'], $input['cid'], $input['fid'], $input['did']]);

    echo json_encode(['success' => true, 'message' => 'Allotment added successfully']);
}

function update_allotment_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['pid']) || empty($input['cid']) || empty($input['fid']) || empty($input['did'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }

    $query = "UPDATE allotment SET pid = ?, cid = ?, fid = ?, did = ? WHERE aid = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$input['pid'], $input['cid'], $input['fid'], $input['did'], $input['aid']]);

    echo json_encode(['success' => true, 'message' => 'Allotment updated successfully']);
}

function delete_allotment_record($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    $query = "DELETE FROM allotment WHERE aid = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$input['aid']]);

    echo json_encode(['success' => true, 'message' => 'Allotment deleted successfully']);
}

?>