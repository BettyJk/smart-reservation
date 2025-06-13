<?php
// my_reservations.php - List reservations for the logged-in user
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

try {
    $role = $_SESSION['role'] ?? 'student';
    if ($role === 'teacher') {
        // Show all (including cancelled) grouped class reservations for teacher
        $stmt = $pdo->prepare('SELECT c.name AS classroom_name, r.reservation_date, r.start_time, r.end_time, r.status, r.confirmation_code, COUNT(*) as seat_count
            FROM reservations r
            JOIN seats s ON r.seat_id = s.id
            JOIN classrooms c ON s.classroom_id = c.id
            WHERE r.user_id = ?
            GROUP BY classroom_name, r.reservation_date, r.start_time, r.end_time, r.status, r.confirmation_code
            ORDER BY r.reservation_date DESC, r.start_time DESC');
        $stmt->execute([$_SESSION['user_id']]);
        $reservations = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $reservations[] = [
                'classroom_name' => $row['classroom_name'],
                'seat_number' => 'Class Reserved',
                'reservation_date' => $row['reservation_date'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'status' => $row['status'],
                'confirmation_code' => $row['confirmation_code'],
                'is_class_reservation' => true
            ];
        }
        echo json_encode($reservations);
    } else {
        $stmt = $pdo->prepare('SELECT r.id, c.name AS classroom_name, s.seat_number, r.reservation_date, r.start_time, r.end_time, r.status, r.confirmation_code FROM reservations r JOIN seats s ON r.seat_id = s.id JOIN classrooms c ON s.classroom_id = c.id WHERE r.user_id = ? ORDER BY r.reservation_date DESC, r.start_time DESC');
        $stmt->execute([$_SESSION['user_id']]);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($reservations);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch reservations: ' . $e->getMessage()]);
}
