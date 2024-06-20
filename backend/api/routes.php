<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: *");
header("Cache-Control: no-cache, must-revalidate");

require_once "./modules/get.php";
require_once "./modules/post.php";
require_once "./modules/delete.php";
require_once "./config/database.php";
require_once __DIR__ . '/bootstrap.php';
require_once "./src/Jwt.php";

if (isset($_REQUEST['request'])) {
    $request = explode('/', $_REQUEST['request']);
} else {
    echo "Not Found";

    http_response_code(404);
    exit();
}

$con = new Connection();
$pdo = $con->connect();
$get = new Get($pdo);
$post = new Post($pdo);
$delete = new Delete($pdo);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'OPTIONS':
        http_response_code(200);
        break;

    case 'GET':
        switch ($request[0]) {
            case 'users':
                if (isset($request[1])) {
                    echo json_encode($get->get_users($request[1]));
                } else {
                    echo json_encode($get->get_users());
                }
                break;

            case 'student':
                if (count($request) > 1) {
                    echo json_encode($get->get_student($request[1]));
                } else {
                    echo json_encode($get->get_student());
                }
                break;
            case 'roles':
                if (isset($request[1])) {
                    echo json_encode($get->get_roles($request[1]));
                } else {
                    echo json_encode($get->get_roles());
                }
                break;

            case 'events':
                if (isset($request[1])) {
                    echo json_encode($get->get_events($request[1]));
                } else {
                    echo json_encode($get->get_events());
                }
                break;

            case 'userevents':
                if (isset($request[1])) {
                    echo json_encode($get->get_user_events($request[1]));
                } else {
                    echo "User ID not provided";
                    http_response_code(400);
                }
                break;

            case 'registeredUser':
                if (isset($request[1])) {
                    echo json_encode($get->get_registered_users_for_event($request[1]));
                } else {
                    echo "Event ID not provided";
                    http_response_code(400);
                }
                break;

            case 'geteventid':
                if (isset($request[1])) {
                    $eventId = $get->getEventById($request[1]);
                    if ($eventId !== false) {
                        echo json_encode(['event_id' => $eventId]);
                    } else {
                        echo "Event not found";
                        http_response_code(404);
                    }
                } else {
                    echo "Event id not provided";
                    http_response_code(400);
                }
                break;

            case 'getavatar':
                if (isset($request[1])) {
                    $get->get_avatar($request[1]);
                } else {
                    echo "ID not provided";
                    http_response_code(400);
                }
                break;

            case 'geteventimage':
                if (isset($request[1])) {
                    $get->getEventImage($request[1]);
                } else {
                    echo "ID not provided";
                    http_response_code(400);
                }
                break;

            case 'getusersbyeventattendance':
                if (isset($request[1])) {
                    echo json_encode($get->getUsersByEventAttendance($request[1]));
                } else {
                    echo "ID not provided";
                    http_response_code(400);
                }
                break;

            case 'getattendancebyuser':
                if (isset($request[1]) && isset($request[2])) {
                    echo json_encode($get->getAttendancebyUser($request[1], $request[2]));
                } else {
                    echo "IDs not provided";
                    http_response_code(400);
                }
                break;

            case 'getattendanceimage':
                if (isset($request[1])) {
                    $get->getAttendanceImage($request[1]);
                } else {
                    echo "IDs not provided";
                    http_response_code(400);
                }
                break;

            case 'total':
                echo json_encode($get->get_attendees_total($request[1]));
                break;

            case 'totalAttendees':
                echo json_encode($get->get_all_attendee_counts());
                break;

            case 'getcoursecount':
                echo json_encode($get->get_registered_users_by_course($request[0]));
                break;

            case 'getyearlevelcount':
                echo json_encode($get->get_registered_users_by_year_level($request[0]));
                break;

            case 'getblockcount':
                echo json_encode($get->get_registered_users_by_block($request[0]));
                break;

            case 'getpasteventsattendance':
                echo json_encode($get->getTotalAttendanceInAllPastEvents($request[0]));
                break;

            case 'getAllRegisteredUser':
                echo json_encode($get->get_total_registered_users($request[0]));
                break;

            case 'getEventfeedback':
                if (isset($request[1])) {
                    echo json_encode($get->get_event_feedback($request[1]));
                } else {
                    echo json_encode($get->get_event_feedback());
                }
                break;

            case 'getUserFeedback':
                if (isset($request[1])) {
                    echo json_encode($get->get_user_feedback($request[1]));
                } else {
                    echo json_encode($get->get_user_feedback());
                }
                break;

            case 'user_registered_events_notification':
                if (isset($request[1])) {
                    echo json_encode($get->notification($request[1]));
                } else {
                    echo "User ID not provided";
                    http_response_code(400);
                }
                break;

            case 'getAllRemarks':
                echo json_encode($get->getAllAttendanceRemarks($request[1]));
                break;

            case 'getUserFeed':
                echo json_encode($get->getUserFeedbackByEvent($request[1], $request[2]));
                break;



            default:
                echo "This is forbidden";
                http_response_code(403);
                break;
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        switch ($request[0]) {
            case 'login':
                $data = json_decode(file_get_contents("php://input"), true);

                if (!isset($data['email']) || !isset($data['password'])) {
                    throw new Exception("Missing login credentials", 400);
                }

                $user = $get->getByEmail($data['email']);
                $post->login($data, $user);
                break;

            case 'adduser':
                echo json_encode($post->add_user($data));
                break;

            case 'edituser':
                echo json_encode($post->edit_user($data, $request[1]));
                break;
            case 'edituserrole':
                echo json_encode($post->edit_user_role($data, $request[1]));
                break;

            case 'addevent':
                echo json_encode($post->add_event($data));
                break;

            case 'uploadevent':
                echo json_encode($post->uploadEvent($request[1]));
                break;

            case 'editevent':
                echo json_encode($post->edit_event($data, $request[1]));
                break;

            case 'uploadimage':
                echo json_encode($post->uploadAvatar($request[1]));
                break;

            case 'register':
                echo json_encode($post->register_for_event($data->event_id, $data->user_id));
                break;

            case 'markattendance':
                echo json_encode($post->mark_attendance($data->event_id, $data->user_id));
                break;

            case 'toggleattendanceremark':
                echo json_encode($post->toggleAttendanceRemark($data->attendance_id, $data->newRemark));
                break;

            case 'uploadattendanceimage':
                echo json_encode($post->uploadAttendanceImage($request[1], $request[2]));
                break;

            case 'addfeedback':
                if (isset($request[1]) && isset($request[2])) {
                    $event_id = $request[1];
                    $user_id = $request[2];
                    $data = json_decode(file_get_contents('php://input'));
                    echo json_encode($post->add_event_feedback($event_id, $user_id, $data));
                } else {
                    echo json_encode(['status' => 'failed', 'message' => 'Event ID or User ID not provided.']);
                    http_response_code(400);
                }
                break;

            default:
                echo "This is forbidden";
                http_response_code(403);
                break;
        }
        break;

    case 'DELETE':
        switch ($request[0]) {
            case 'deleteevent':
                if (isset($request[1])) {
                    $event_id = $request[1];
                    echo json_encode($delete->delete_event($event_id));
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Event ID is required for deletion"]);
                }
                break;

            case 'unregister':
                if (isset($request[1]) && isset($request[2])) {
                    $event_id = $request[1];
                    $user_id = $request[2];
                    echo json_encode($delete->unregister_from_event($event_id, $user_id));
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Event ID and User ID are required for unregistration"]);
                }
                break;
        }
        break;

    default:
        echo "This is forbidden";
        http_response_code(403);
        break;
}
