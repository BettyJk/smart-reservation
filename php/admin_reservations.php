<?php
// admin_reservations.php - List all reservations with filters for admin
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$date = isset($_GET['date']) ? $_GET['date'] : '';
$user = isset($_GET['user']) ? trim($_GET['user']) : '';
$seat = isset($_GET['seat']) ? trim($_GET['seat']) : '';

$where = [];
$params = [];
if ($date) {
    $where[] = 'r.reservation_date = ?';
    $params[] = $date;
}
if ($user) {
    $where[] = 'u.email LIKE ?';
    $params[] = "%$user%";
}
if ($seat) {
    $where[] = 's.seat_number LIKE ?';
    $params[] = "%$seat%";
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// For the second SELECT, add role != 'teacher' to the WHERE clause
$where2 = $where;
$where2[] = "u.role != 'teacher'";
$whereSql2 = $where2 ? ('WHERE ' . implode(' AND ', $where2)) : '';

try {
    $sql = "SELECT 
        MIN(r.id) AS id, 
        u.email, 
        u.role, 
        c.name AS classroom_name, 
        'Class Reserved' AS seat_number, 
        r.reservation_date, 
        r.start_time, 
        r.end_time, 
        r.status, 
        r.confirmation_code, 
        COUNT(*) AS seat_count
    FROM reservations r 
    JOIN users u ON r.user_id = u.id 
    JOIN seats s ON r.seat_id = s.id 
    JOIN classrooms c ON s.classroom_id = c.id 
    $whereSql 
    GROUP BY 
        u.email, u.role, c.name, r.reservation_date, r.start_time, r.end_time, r.status, r.confirmation_code
    HAVING u.role = 'teacher'
    UNION ALL
    SELECT 
        r.id, 
        u.email, 
        u.role, 
        c.name AS classroom_name, 
        s.seat_number, 
        r.reservation_date, 
        r.start_time, 
        r.end_time, 
        r.status, 
        r.confirmation_code, 
        1 AS seat_count
    FROM reservations r 
    JOIN users u ON r.user_id = u.id 
    JOIN seats s ON r.seat_id = s.id 
    JOIN classrooms c ON s.classroom_id = c.id 
    $whereSql2 
    ORDER BY reservation_date DESC, start_time DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, $params));
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($reservations);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch reservations: ' . $e->getMessage()]);
}
