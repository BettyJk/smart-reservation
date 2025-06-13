<?php
// cancel_reservation.php - Cancel a reservation (student or admin)
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
$reservation_id = isset($data['reservation_id']) ? (int)$data['reservation_id'] : 0;
$confirmation_code = isset($data['confirmation_code']) ? $data['confirmation_code'] : '';

if (!$reservation_id && !$confirmation_code) {
    http_response_code(400);
    echo json_encode(['error' => 'reservation_id or confirmation_code is required']);
    exit;
}

try {
    if ($confirmation_code) {
        // Cancel all reservations with this confirmation_code (for class reservation)
        // Allow admin to cancel any teacher's class reservation
        if ($_SESSION['role'] === 'admin') {
            // Cancel all reservations for this confirmation_code (all seats for this class reservation)
            $stmt = $pdo->prepare('UPDATE reservations SET status = "cancelled" WHERE confirmation_code = ?');
            $stmt->execute([$confirmation_code]);
        } else {
            // Only allow teacher to cancel their own class reservation
            $stmt = $pdo->prepare('UPDATE reservations SET status = "cancelled" WHERE confirmation_code = ? AND user_id = ?');
            $stmt->execute([$confirmation_code, $_SESSION['user_id']]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
    // Only allow cancel if user owns reservation or is admin
    $stmt = $pdo->prepare('SELECT user_id FROM reservations WHERE id = ?');
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['error' => 'Reservation not found']);
        exit;
    }
    if ($reservation['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized']);
        exit;
    }
    // Cancel reservation
    $stmt = $pdo->prepare('UPDATE reservations SET status = "cancelled" WHERE id = ?');
    $stmt->execute([$reservation_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to cancel reservation: ' . $e->getMessage()]);
}
