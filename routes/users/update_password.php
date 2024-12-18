<?php

function handle_password_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {
        case 'POST':
            update_password($db);
            break;
        default:
            sendMethodNotAllowedResponse();
            break;
    }
}

function update_password($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    $currentPassword = $input['currentPassword'] ?? null;
    $newPassword = $input['newPassword'] ?? null;
    $uid = $input['uid'] ?? null;

    if (!$currentPassword || !$newPassword) {
        sendBadRequestResponse('Both current password and new password are required');
    }

    if (strlen($newPassword) < 6) {
        sendBadRequestResponse('New password must be at least 6 characters long');
    }

    try {
        $check_user_query = "SELECT password FROM users WHERE uid = :uid";
        $stmt = $db->prepare($check_user_query);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            sendResponse(404, 'User not found');
        }

        // Verify MD5 hash directly
        if ($currentPassword !== $user['password']) {
            sendResponse(401, 'Current password is incorrect');
        }

        // Hash new password with password_hash
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $update_query = "UPDATE users SET password = :newPassword WHERE uid = :uid";
        $stmt = $db->prepare($update_query);
        $stmt->bindValue(':newPassword', $newPassword, PDO::PARAM_STR);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
        $stmt->execute();

        sendResponse(200, 'Password updated successfully');
    } catch (PDOException $e) {
        sendDatabaseErrorResponse($e->getMessage());
    }
}
?>
