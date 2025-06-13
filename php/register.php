<?php
// register.php - Register a new user
header('Content-Type: application/json');
require_once 'db.php';
session_start();

// Only allow admin to register new users (except for first ever user)
$data = json_decode(file_get_contents('php://input'), true);
$admin_create = isset($data['admin_create']) ? $data['admin_create'] : false;

// Check if this is the first user ever (allow bootstrap)
$stmt = $pdo->query('SELECT COUNT(*) FROM users');
$user_count = $stmt->fetchColumn();
if ($user_count > 0) {
    if (!$admin_create) {
        // Only allow public registration for student/teacher
        if (!in_array($data['role'], ['student', 'teacher'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Only admin can register admin users']);
            exit;
        }
    } else {
        // Only allow admin to register any user
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Only admin can register new users']);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';
$role = isset($data['role']) && in_array($data['role'], ['student', 'teacher', 'admin']) ? $data['role'] : 'student';
$year = isset($data['year']) ? $data['year'] : null;

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

if ($role === 'student') {
    if (!in_array($year, ['3','4','5'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Year is required for students and must be 3, 4, or 5']);
        exit;
    }
}

try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($role === 'student') {
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, year) VALUES (?, ?, ?, ?)');
        $stmt->execute([$email, $hash, $role, $year]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
        $stmt->execute([$email, $hash, $role]);
    }
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['role'] = $role;
    echo json_encode(['success' => true, 'id' => $_SESSION['user_id'], 'role' => $role, 'year' => $year]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already exists']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to register: ' . $e->getMessage()]);
    }
}
