<?php
// add_classroom.php - Add a new classroom via POST
header('Content-Type: application/json');
require_once 'db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);
$name = isset($data['name']) ? trim($data['name']) : '';
$location = isset($data['location']) ? trim($data['location']) : '';

if ($name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Classroom name is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO classrooms (name, location) VALUES (?, ?)');
    $stmt->execute([$name, $location]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add classroom: ' . $e->getMessage()]);
}
