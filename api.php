<?php

// api.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");



include_once 'db.php';  
include_once 'routes/time.php';  
include_once 'routes/section.php';  
include_once 'routes/course.php';  
include_once 'routes/faculty.php';  
include_once 'routes/department.php';  
include_once 'routes/class.php';  
include_once 'routes/allotment.php';  
include_once 'routes/year.php';  
include_once 'routes/program.php';  
    
require_once 'routes/utils.php';

$database = new Database();
$db = $database->getConnection();

$request_method = $_SERVER["REQUEST_METHOD"];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

if ($request_method == 'OPTIONS') {
    http_response_code(200);
    exit;
}

switch ($endpoint) {
    case 'time':
        handle_time_requests($request_method, $db); 
        break;
    case 'section':
        handle_section_requests($request_method, $db);
        break;
    case 'course':
        handle_course_requests($request_method, $db);
        break;
    case 'faculty':
        handle_faculty_requests($request_method, $db);
        break;
    case 'program':
        handle_program_requests($request_method, $db);
        break;
    case 'dept':
        handle_dept_requests($request_method, $db);
        break;
    case 'classroom':
        handle_classroom_requests($request_method, $db);
        break;
    case 'allotment':
        handle_allotment_requests($request_method, $db);
        break;
    case 'year':
        handle_yeardata_requests($request_method, $db);
        break;
    default:
        $database->sendResponse(404, 'Endpoint not found.');
}
?>
