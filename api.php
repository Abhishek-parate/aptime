<?php

// api.php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");



include_once 'db.php';  
include_once 'routes/time/time.php';  
include_once 'routes/section/section.php';  
include_once 'routes/section/getsectionfields.php';  
include_once 'routes/course/course.php';  
include_once 'routes/course/courseallotment.php';  
include_once 'routes/faculty/faculty.php';  
include_once 'routes/faculty/masterfaculty.php';  
include_once 'routes/faculty/getfacultybysection.php';  
include_once 'routes/dept/department.php';  
include_once 'routes/class/class.php';  
include_once 'routes/class/roommaster.php';  
include_once 'routes/class/getclassrooms.php';  
include_once 'routes/course/courseduration.php';  
include_once 'routes/course/coursecategory.php';  
include_once 'routes/class/getfloor.php';  
include_once 'routes/timetable/timetable.php';  
include_once 'routes/timetable/timetable.php';  
include_once 'routes/timetable/getcourseandfaculty.php';  
include_once 'routes/timetable/getgaps.php';  
include_once 'routes/timetable/gettimetable.php';  
include_once 'routes/allotment.php';  
include_once 'routes/year/year.php';  
include_once 'routes/program/program.php';  

include_once 'routes/semester/semester.php';  
include_once 'routes/semester/getelective.php';  

require_once 'routes/utils/utils.php';

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
    case 'sectionfields':
        handle_sectionfields_requests($request_method, $db);
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
        case 'getfloor':
            handle_floor_requests($request_method, $db);
        break;
        case 'gerclassrooms':
            handle_getclassrooms_requests($request_method, $db);
        break;
    case 'allotment':
        handle_allotment_requests($request_method, $db);
        break;
    case 'year':
        handle_yeardata_requests($request_method, $db);
        break;

    case 'courseallotment':
            handle_course_allotement_requests($request_method, $db);
            break;
    case 'courseduration':
        handle_duration_requests($request_method, $db);
        break;
    case 'coursecategory':
        handle_category_requests($request_method, $db);
        break;

    case 'roommaster':
        handle_roommaster_requests($request_method, $db);
    break;

    case 'masterfaculty':
        handle_masterfaculty_requests($request_method, $db);
    break;
    case 'timetable':
        handle_timetable_requests($request_method, $db);
    break;
    case 'getgaps':
        handle_gaps_requests($request_method, $db);
    break;

    case 'getimetable':
        handle_timetabledata_requests($request_method, $db);
    break;

    case 'getcourseandfaculty':
        handle_courseandfaculty_requests($request_method, $db);
    break;

    case 'semesterdata':
        handle_semesterdata_requests($request_method, $db);
    break;

    case 'getfacultybytimetable':
        handle_getfacultybytimetable_requests($request_method, $db);
    break;

    case 'getelective':
        handle_get_elective_values_requests($request_method, $db);
    break;

    default:
        $database->sendResponse(404, 'Endpoint not found.');
}
?>
