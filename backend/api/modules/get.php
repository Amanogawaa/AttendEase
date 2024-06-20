<?php

require_once 'global.php';

class Get extends GlobalMethods
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function get_records($table, $conditions = null, $columns = '*')
    {
        $sqlStr = "SELECT $columns FROM $table";
        if ($conditions != null) {
            $sqlStr .= " WHERE " . $conditions;
        }
        $result = $this->executeQuery($sqlStr);

        if ($result['code'] == 200) {
            return $this->sendPayload($result['data'], 'success', "Successfully retrieved data.", $result['code']);
        }
        return $this->sendPayload(null, 'failed', "Failed to retrieve data.", $result['code']);
    }

    //nageexecute ng query
    private function executeQuery($sql)
    {
        $data = array();
        $errmsg = "";
        $code = 0;

        try {
            $statement = $this->pdo->query($sql);
            if ($statement) {
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($result as $record) {
                    // Handle BLOB data
                    if (isset($record['file_data'])) {

                        $record['file_data'] = base64_encode($record['file_data']);
                    }
                    array_push($data, $record);
                }
                $code = 200;
                return array("code" => $code, "data" => $data);
            } else {
                $errmsg = "No data found.";
                $code = 404;
            }
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 403;
        }
        return array("code" => $code, "errmsg" => $errmsg);
    }

    public function get_user_events($user_id)
    {
        $columns = "
            events.event_id, event_name, event_description, event_location,
            event_start_date, event_end_date, event_registration_start, event_registration_end, session, max_attendees, categories, organizer_name,
            CASE
                WHEN events.event_end_date < CURDATE() THEN 'done'
                WHEN events.event_start_date <= CURDATE() THEN 'ongoing'
                ELSE 'upcoming'
            END AS event_state
        ";

        $sql = "
            SELECT 
                $columns
            FROM events
            INNER JOIN event_registration ON events.event_id = event_registration.event_id
            WHERE event_registration.user_id = :user_id
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rowCount = $stmt->rowCount();

            if ($rowCount > 0) {
                return $this->sendPayload($data, 'success', "Successfully retrieved user's events.", 200);
            } else {
                return $this->sendPayload(null, 'failed', "User has not registered for any events.", 404);
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return $this->sendPayload(null, 'failed', "Database error.", 500);
        }
    }



    public function getByEmail(string $email = null): array|false
    {
        $conditions = ($email !== null) ? "email = '$email'" : null;
        $result = $this->get_records('user', $conditions);

        if ($result['status']['remarks'] === 'success' && !empty($result['payload'])) {
            return $result['payload'][0];
        } else {
            return false;
        }
    }

    public function get_users($user_id = null)
    {
        $columns = "user_id, first_name, last_name, year_level, block, course, email, role_id";
        $condition = ($user_id !== null) ? "user_id = $user_id" : null;
        return $this->get_records('user', $condition, $columns);
    }

    public function get_roles($id = null)
    {
        $condition = $id ? "role_id=$id" : null;
        return $this->get_records('roles', $condition);
    }

    public function get_events($event_id = null)
    {
        $columns = "event_id, event_name, event_description, event_location, event_start_date, event_end_date, event_registration_start, event_registration_end, session, max_attendees, categories, organizer_name";
        $condition = ($event_id !== null) ? "event_id = $event_id" : null;
        return $this->get_records('events', $condition, $columns);
    }

    public function get_student($user_id = null)
    {
        $columns = "user_id, first_name, last_name, year_level, block, course, email";
        $condition = ($user_id !== null) ? "user_id = $user_id" : null;
        $result = $this->get_records('user', $condition, $columns);

        if ($result['status']['remarks'] === 'success') {

            $payloadData = $result['payload'];


            if (is_array($payloadData)) {
                return $payloadData;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    public function get_registered_users_for_event($event_id)
    {
        $sql = "SELECT u.first_name, u.last_name, u.email FROM user u
        INNER JOIN event_registration er ON u.user_id = er.user_id 
        WHERE er.event_id = :event_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = $stmt->rowCount();

        if ($rowCount > 0) {
            return $this->sendPayload($data, 'success', "Successfully retrieved users registered for the event.", 200);
        } else {
            return $this->sendPayload(null, 'failed', "No users registered for the event.", 404);
        }
    }

    public function getUsersByEventAttendance($event_id)
    {

        $sql = "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.year_level, u.block, u.course
        FROM `attendance` a
        INNER JOIN `user` u ON a.user_id = u.user_id
        WHERE a.event_id = :event_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = $stmt->rowCount();

        if ($rowCount > 0) {
            return $this->sendPayload($data, 'success', "Successfully retrieved users registered for the event.", 200);
        } else {
            return $this->sendPayload(null, 'failed', "No users registered for the event.", 404);
        }
    }

    public function getAttendancebyUser($userId, $eventId)
    {
        try {
            $sql = "SELECT attendance_id, remarks, created_at FROM attendance WHERE user_id = :user_id AND event_id = :event_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($data)) {
                return $this->sendPayload($data, 'success', "Successfully retrieved users registered for the event.", 200);
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }



    public function getEventById($eventId)
    {
        try {
            $sql = "SELECT event_id FROM events WHERE event_id = :event_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmt->execute();

            $eventData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($eventData) {
                return $eventData['event_id'];
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    public function get_avatar($user_id)
    {
        $fileInfo = $this->get_imageData($user_id);

        // Check if file info exists
        if ($fileInfo) {
            $fileData = $fileInfo['avatar'];

            // Set headers for file download
            header('Content-Type: image/png');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            echo $fileData;
            exit();
        } else {
            echo "User has not uploaded an avatar yet.";
            http_response_code(404);
        }
    }

    public function get_imageData($user_id = null)
    {
        $columns = "avatar";
        $condition = ($user_id !== null) ? "user_id = $user_id" : null;
        $result = $this->get_records('user', $condition, $columns);

        if ($result['status']['remarks'] === 'success' && isset($result['payload'][0]['avatar'])) {
            $fileData = $result['payload'][0]['avatar'];
            return array("avatar" => $fileData);
        } else {
            return array("avatar" => null);
        }
    }


    public function getEventImage($event_id)
    {
        $fileInfo = $this->geteventImg($event_id);

        if ($fileInfo['event_image'] !== null) {
            $fileData = $fileInfo['event_image'];

            header('Content-Type: image/png');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            echo $fileData;
            exit();
        } else {
            echo "Event image not found or not uploaded.";
            http_response_code(404);
        }
    }

    public function geteventImg($event_id = null)
    {
        $columns = "event_image";
        $condition = ($event_id !== null) ? "event_id = $event_id" : null;
        $result = $this->get_records('events', $condition, $columns);

        if ($result['status']['remarks'] === 'success' && isset($result['payload'][0]['event_image'])) {
            $fileData = $result['payload'][0]['event_image'];
            return array("event_image" => $fileData);
        } else {
            return array("event_image" => null);
        }
    }

    public function getAttendanceImage($id = null)
    {
        $fileInfo = $this->getattendanceImg($id);

        if ($fileInfo) {
            $fileData = $fileInfo['image'];

            header('Content-Type: image/png');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            echo $fileData;
            exit();
        } else {
            echo "User has not uploaded an image yet.";
            http_response_code(404);
        }
    }

    public function getattendanceImg($id = null)
    {
        $columns = "image";
        $condition = ($id !== null) ? "attendance_id = $id" : null;
        $result = $this->get_records('attendance', $condition, $columns);

        if ($result['status']['remarks'] === 'success' && isset($result['payload'][0]['image'])) {
            $fileData = $result['payload'][0]['image'];
            return array("image" => $fileData);
        } else {
            return array("image" => null);
        }
    }

    public function get_attendees_total($event_id)
    {
        try {
            $sql = "SELECT COUNT(*) AS total_attendees FROM event_registration WHERE event_id = :event_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->execute();

            $total_attendees = $stmt->fetchColumn();

            if (is_numeric($total_attendees)) {
                return $this->sendPayload($total_attendees, 'success', "Successfully retrieved total attendees for the event.", 200);
            } else {
                return $this->sendPayload(null, 'failed', "Failed to retrieve total attendees.", 404);
            }
        } catch (PDOException $e) {
            return $this->sendPayload(null, 'error', $e->getMessage(), 500);
        }
    }

    //analytics

    public function get_registered_users_by_course()
    {
        $sql = "SELECT u.course, COUNT(u.user_id) AS student_count 
                FROM user u
                GROUP BY u.course 
                ORDER BY u.course";

        // Prepare and execute the query
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = $stmt->rowCount();

        if ($rowCount > 0) {
            return $this->sendPayload($data, 'success', "Successfully retrieved student counts by course.", 200);
        } else {
            return $this->sendPayload(null, 'failed', "No users found.", 404);
        }
    }




    public function get_total_registered_users()
    {
        try {
            $sql = "SELECT COUNT(DISTINCT user_id) AS total_users FROM event_registration";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            $totalUsers = $stmt->fetchColumn();

            if (is_numeric($totalUsers)) {
                return $this->sendPayload($totalUsers, 'success', "Successfully retrieved total number of registered users.", 200);
            } else {
                return $this->sendPayload(null, 'failed', "Failed to retrieve total number of registered users.", 404);
            }
        } catch (PDOException $e) {
            return $this->sendPayload(null, 'error', $e->getMessage(), 500);
        }
    }


    public function get_registered_users_by_year_level()
    {
        $sql = "SELECT u.year_level, COUNT(u.user_id) AS student_count 
                FROM user u
                GROUP BY u.year_level 
                ORDER BY u.year_level";

        // Prepare and execute the query
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = $stmt->rowCount();

        if ($rowCount > 0) {
            return $this->sendPayload($data, 'success', "Successfully retrieved student counts by year level.", 200);
        } else {
            return $this->sendPayload(null, 'failed', "No users found.", 404);
        }
    }

    public function get_registered_users_by_block()
    {
        $sql = "SELECT u.block, COUNT(u.user_id) AS student_count 
                FROM user u
                GROUP BY u.block 
                ORDER BY u.block";

        // Prepare and execute the query
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = $stmt->rowCount();

        if ($rowCount > 0) {
            return $this->sendPayload($data, 'success', "Successfully retrieved student counts by block.", 200);
        } else {
            return $this->sendPayload(null, 'failed', "No users found.", 404);
        }
    }


    public function getTotalAttendanceInAllPastEvents()
    {
        try {
            // Fetch all past events
            $sql = "SELECT event_id, event_name FROM events WHERE event_end_date < CURDATE()";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch total attendance for each event
            foreach ($events as &$event) {
                $eventId = $event['event_id'];
                $sql = "SELECT COUNT(*) AS total_attendance FROM attendance WHERE event_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$eventId]);
                $totalAttendance = $stmt->fetchColumn();
                $event['total_attendance'] = $totalAttendance;
            }

            // Return the events with their total attendance
            return $this->sendPayload($events, 'success', "Total attendance in all past events retrieved successfully.", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, 'error', $e->getMessage(), 500);
        }
    }


    public function get_all_attendee_counts()
    {
        $sql = "SELECT e.event_id, e.event_name, COUNT(er.registration_id) AS total_attendees
                FROM events e
                LEFT JOIN event_registration er ON e.event_id = er.event_id
                GROUP BY e.event_id, e.event_name
                ORDER BY e.event_name"; // Optional: Order by event name

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($data)) {
                return $this->sendPayload(null, 'failed', "No events or attendees found.", 404);
            }

            return $this->sendPayload($data, 'success', "Successfully retrieved attendee counts for all events.", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, 'error', $e->getMessage(), 500);
        }
    }

    public function get_event_feedback($event_id = null)
    {
        $columns = "feedback_id, event_id, user_id, overall_satisfaction, content_quality, speaker_effectiveness,
            venue_rating, logistics_rating, improvement_suggestions, additional_comments, feedback_date, remarks";
        $condition = ($event_id !== null) ? "event_id = $event_id" : null;
        return $this->get_records('feedback', $condition, $columns);
    }

    public function get_user_feedback($user_id = null)
    {
        $columns = "feedback_id, event_id, user_id, overall_satisfaction, content_quality, speaker_effectiveness,
            venue_rating, logistics_rating, improvement_suggestions, additional_comments, feedback_date, remarks";
        $condition = ($user_id !== null) ? "user_id = $user_id" : null;
        return $this->get_records('feedback', $condition, $columns);
    }

    public function notification($user_id)
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $sql = "
        SELECT 
            events.event_id, event_name, event_start_date, event_end_date
        FROM events
        INNER JOIN event_registration ON events.event_id = event_registration.event_id
        WHERE DATE(event_start_date) = :tomorrow
        AND event_registration.user_id = :user_id
    ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':tomorrow', $tomorrow, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->sendPayload($data, 'success', "Successfully retrieved registered events for the user.", 200);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return $this->sendPayload(null, 'error', $e->getMessage(), 500);
        }
    }

    public function getAllAttendanceRemarks($event_id)
    {
        $sql = "
        SELECT 
            a.user_id, u.first_name, u.last_name, u.year_level, u.block, u.course, a.attendance_id, a.remarks
        FROM 
            attendance a
        INNER JOIN 
            user u ON a.user_id = u.user_id
            WHERE a.event_id = :event_id
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = $stmt->rowCount();

        if ($rowCount > 0) {
            return $this->sendPayload($data, 'success', "Successfully retrieved users registered for the event.", 200);
        } else {
            return $this->sendPayload(null, 'failed', "No users registered for the event.", 404);
        }
    }

    public function getUserFeedbackByEvent($eventId, $userId = null)
    {
        $conditions = "event_id = $eventId";
        if ($userId !== null) {
            $conditions .= " AND user_id = $userId";
        }
        return $this->get_records('feedback', $conditions);
    }
}
