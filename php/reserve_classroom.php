<?php
// reserve_classroom.php - Teacher reserves all seats in a classroom for a lesson
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Only teachers can reserve the whole classroom']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
$classroom_id = 1; // Only one classroom for now
$reservation_date = isset($data['reservation_date']) ? $data['reservation_date'] : '';
$start_time = isset($data['start_time']) ? $data['start_time'] : '';
$end_time = isset($data['end_time']) ? $data['end_time'] : '';
if (!$reservation_date || !$start_time || !$end_time) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}
$min_time = "08:30";
$max_time = "23:00";
if ($start_time < $min_time || $end_time > $max_time || $start_time >= $end_time) {
    http_response_code(400);
    echo json_encode(['error' => 'Classroom reservations must be between 08:30 and 23:00, and start time must be before end time.']);
    exit;
}
try {
    // Cancel all student reservations for this classroom, date, and overlapping time
    $cancelStmt = $pdo->prepare('
        UPDATE reservations
        SET status = "cancelled"
        WHERE seat_id IN (SELECT id FROM seats WHERE classroom_id = ?)
          AND reservation_date = ?
          AND status IN ("pending", "approved")
          AND user_id IN (SELECT id FROM users WHERE role = "student")
          AND NOT (? >= end_time OR ? <= start_time)
    ');
    $cancelStmt->execute([$classroom_id, $reservation_date, $start_time, $end_time]);
    $cancelledCount = $cancelStmt->rowCount();

    // Notify affected students
    $affectedStmt = $pdo->prepare('
        SELECT DISTINCT r.user_id
        FROM reservations r
        JOIN seats s ON r.seat_id = s.id
        JOIN users u ON r.user_id = u.id
        WHERE s.classroom_id = ?
          AND r.reservation_date = ?
          AND r.status = "cancelled"
          AND u.role = "student"
          AND NOT (? >= r.end_time OR ? <= r.start_time)
    ');
    $affectedStmt->execute([$classroom_id, $reservation_date, $start_time, $end_time]);
    $affectedUsers = $affectedStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($affectedUsers as $student_id) {
        $msg = "Your reservation was cancelled because a teacher reserved the classroom for a lesson at this time.";
        $pdo->prepare('INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())')->execute([$student_id, $msg]);
    }
    // Get all seat ids for the classroom
    $stmt = $pdo->prepare('SELECT id FROM seats WHERE classroom_id = ?');
    $stmt->execute([$classroom_id]);
    $seats = $stmt->fetchAll(PDO::FETCH_COLUMN);
    // Check for conflicts
    $conflict = false;
    foreach ($seats as $seat_id) {
        $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE seat_id = ? AND reservation_date = ? AND status IN ("pending", "approved") AND NOT (? >= end_time OR ? <= start_time)');
        $stmt2->execute([$seat_id, $reservation_date, $start_time, $end_time]);
        if ($stmt2->fetchColumn() > 0) {
            $conflict = true;
            break;
        }
    }
    if ($conflict) {
        http_response_code(409);
        echo json_encode(['error' => 'Some seats are already reserved for the selected time.']);
        exit;
    }
    // Reserve all seats
    $confirmation_code = bin2hex(random_bytes(6));
    foreach ($seats as $seat_id) {
        $stmt3 = $pdo->prepare('INSERT INTO reservations (user_id, seat_id, reservation_date, start_time, end_time, status, confirmation_code) VALUES (?, ?, ?, ?, ?, "pending", ?)');
        $stmt3->execute([$_SESSION['user_id'], $seat_id, $reservation_date, $start_time, $end_time, $confirmation_code]);
    }
    echo json_encode(['success' => true, 'confirmation_code' => $confirmation_code]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to reserve classroom: ' . $e->getMessage()]);
}
