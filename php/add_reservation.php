<?php
// add_reservation.php - Add a new reservation via POST
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$classroom_id = isset($data['classroom_id']) ? (int)$data['classroom_id'] : 0;
$reserved_at = isset($data['reserved_at']) ? $data['reserved_at'] : '';
$duration_minutes = isset($data['duration_minutes']) ? (int)$data['duration_minutes'] : 0;

if (!$user_id || !$classroom_id || !$reserved_at || !$duration_minutes) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

try {
    // Check for overlapping reservation
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE classroom_id = ? AND (
        (? < DATE_ADD(reserved_at, INTERVAL duration_minutes MINUTE)) AND (DATE_ADD(?, INTERVAL ? MINUTE) > reserved_at)
    )');
    $stmt->execute([$classroom_id, $reserved_at, $reserved_at, $duration_minutes]);
    $overlap = $stmt->fetchColumn();
    if ($overlap > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'This classroom is already reserved for the selected time.']);
        exit;
    }
    // Insert reservation if no overlap
    $stmt = $pdo->prepare('INSERT INTO reservations (user_id, classroom_id, reserved_at, duration_minutes) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user_id, $classroom_id, $reserved_at, $duration_minutes]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add reservation: ' . $e->getMessage()]);
}
