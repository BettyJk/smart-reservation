<?php
// reservations.php - List all reservations as JSON
header('Content-Type: application/json');
require_once 'db.php';

try {
    $stmt = $pdo->query('SELECT r.id, r.user_id, u.username, r.classroom_id, c.name AS classroom_name, r.reserved_at, r.duration_minutes FROM reservations r JOIN users u ON r.user_id = u.id JOIN classrooms c ON r.classroom_id = c.id ORDER BY r.reserved_at DESC');
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($reservations);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch reservations: ' . $e->getMessage()]);
}
