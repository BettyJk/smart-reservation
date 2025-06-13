<?php
// reserve_seat.php - Reserve a seat for a user
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$seat_id = isset($data['seat_id']) ? (int)$data['seat_id'] : 0;
$reservation_date = isset($data['reservation_date']) ? $data['reservation_date'] : '';
$start_time = isset($data['start_time']) ? $data['start_time'] : '';
$end_time = isset($data['end_time']) ? $data['end_time'] : '';

if (!$seat_id || !$reservation_date || !$start_time || !$end_time) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

$min_time = "08:30";
$max_time = "23:00";
if ($start_time < $min_time || $end_time > $max_time || $start_time >= $end_time) {
    http_response_code(400);
    echo json_encode(['error' => 'Reservations must be between 08:30 and 23:00, and start time must be before end time.']);
    exit;
}

try {
    // Get user role and year
    $stmt = $pdo->prepare('SELECT role, year FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['role'] === 'student') {
        $year = $user['year'];
        $limits = ['3' => 240, '4' => 360, '5' => 480]; // minutes
        $max_minutes = isset($limits[$year]) ? $limits[$year] : 240;
        // Calculate total reserved minutes for this user on this date (excluding cancelled)
        $stmt = $pdo->prepare('SELECT start_time, end_time FROM reservations WHERE user_id = ? AND reservation_date = ? AND status IN ("pending", "approved")');
        $stmt->execute([$_SESSION['user_id'], $reservation_date]);
        $total_minutes = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $start = strtotime($row['start_time']);
            $end = strtotime($row['end_time']);
            $total_minutes += ($end - $start) / 60;
        }
        // Add the new reservation's minutes
        $new_start = strtotime($start_time);
        $new_end = strtotime($end_time);
        $new_minutes = ($new_end - $new_start) / 60;
        if ($total_minutes + $new_minutes > $max_minutes) {
            http_response_code(409);
            echo json_encode(['error' => "You have exceeded your daily reservation limit (" . ($max_minutes/60) . "h for year $year students)."]);
            exit;
        }
    }
    // Check if the whole class is reserved by a teacher for this date/time
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations r JOIN users u ON r.user_id = u.id WHERE r.reservation_date = ? AND r.status IN ("pending", "approved") AND u.role = "teacher" AND NOT (? <= r.start_time OR ? >= r.end_time)');
    $stmt->execute([$reservation_date, $end_time, $start_time]);
    $class_reserved = $stmt->fetchColumn();
    if ($class_reserved > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'The classroom is reserved for a lesson by a teacher for the selected time.']);
        exit;
    }
    // Check if seat is already reserved for the requested time slot (only block if times overlap)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE seat_id = ? AND reservation_date = ? AND status IN ("pending", "approved") AND NOT (? <= start_time OR ? >= end_time)');
    $stmt->execute([$seat_id, $reservation_date, $start_time, $end_time]);
    $overlap = $stmt->fetchColumn();
    if ($overlap > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'This seat is already reserved for the selected time.']);
        exit;
    }
    // Insert reservation
    $confirmation_code = bin2hex(random_bytes(6));
    $stmt = $pdo->prepare('INSERT INTO reservations (user_id, seat_id, reservation_date, start_time, end_time, status, confirmation_code) VALUES (?, ?, ?, ?, ?, "pending", ?)');
    $stmt->execute([$_SESSION['user_id'], $seat_id, $reservation_date, $start_time, $end_time, $confirmation_code]);
    echo json_encode(['success' => true, 'confirmation_code' => $confirmation_code]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to reserve seat: ' . $e->getMessage()]);
}
