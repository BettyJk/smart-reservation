<?php
// classrooms.php - List all classrooms as JSON
header('Content-Type: application/json');
require_once 'db.php';

try {
    $stmt = $pdo->query('SELECT id, name, location FROM classrooms');
    $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($classrooms);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch classrooms: ' . $e->getMessage()]);
}
