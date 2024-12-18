<?php

function handle_login_requests($request_method, $db) {
    header("Content-Type: application/json"); // Set content type
    switch ($request_method) {
        case 'POST':
            user_login($db);
            break;
        default:
            sendMethodNotAllowedResponse();
            break;
    }
}

function user_login($db) {
    // Get input from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    $username = $input['username'] ?? null;
    $password = $input['password'] ?? null;

    // Validate input
    if (!$username || !$password) {
        sendBadRequestResponse('Username and password are required');
    }

    try {
        // Step 1: Check if the user is registered
        $check_user_query = "SELECT uid, username FROM users WHERE username = :username";
        $stmt = $db->prepare($check_user_query);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            sendResponse(404, 'User is not registered');
        }

        // Step 2: Verify the password
        
        $login_query = "SELECT uid, name, email, username, role FROM users WHERE username = :username AND password = :password";
        $stmt = $db->prepare($login_query);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':password', $password, PDO::PARAM_STR);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_data) {
            // Generate a response for a successful login
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => $user_data
            ]);
        } else {
            // Incorrect password
            sendResponse(401, 'Incorrect password');
        }
    } catch (PDOException $e) {
        // Handle any database error
        sendDatabaseErrorResponse($e->getMessage());
    }
}

?>
