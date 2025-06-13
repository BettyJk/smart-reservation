<?php
// seats.php - List seats for a classroom and show availability for a given date
header('Content-Type: application/json');
require_once 'db.php';

$classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : 0;
$reservation_date = isset($_GET['reservation_date']) ? $_GET['reservation_date'] : date('Y-m-d');
$start_time = isset($_GET['start_time']) ? $_GET['start_time'] : null;
$end_time = isset($_GET['end_time']) ? $_GET['end_time'] : null;

if (!$classroom_id) {
    http_response_code(400);
    echo json_encode(['error' => 'classroom_id is required']);
    exit;
}

// If classroom_id and reservation_date are provided, return all reservations for that classroom and date
if (isset($_GET['classroom_id']) && isset($_GET['reservation_date']) && !isset($_GET['start_time']) && !isset($_GET['end_time'])) {
    $classroom_id = (int)$_GET['classroom_id'];
    $reservation_date = $_GET['reservation_date'];
    $query = 'SELECT r.seat_id, r.status, r.start_time, r.end_time, u.role FROM reservations r JOIN seats s ON r.seat_id = s.id JOIN users u ON r.user_id = u.id WHERE s.classroom_id = ? AND r.reservation_date = ?';
    $stmt = $pdo->prepare($query);
    $stmt->execute([$classroom_id, $reservation_date]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($reservations);
    return;
}

try {
    // Get all seats for the classroom
    $stmt = $pdo->prepare('SELECT id, seat_number, pos_x, pos_y FROM seats WHERE classroom_id = ?');
    $stmt->execute([$classroom_id]);
    $seats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get reservations for the date (join seats to get classroom_id)
    $query = 'SELECT r.seat_id, r.status, r.start_time, r.end_time, u.role FROM reservations r JOIN seats s ON r.seat_id = s.id JOIN users u ON r.user_id = u.id WHERE s.classroom_id = ? AND r.reservation_date = ? AND r.status IN ("pending", "approved")';
    $stmt2 = $pdo->prepare($query);
    $stmt2->execute([$classroom_id, $reservation_date]);
    $reserved = [];

    // DEBUG: Log reservations and requested times
    $debug_log = [];
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($start_time && $end_time) {
            // Use DateTime for accurate comparison
            $req_start = new DateTime($start_time);
            $req_end = new DateTime($end_time);
            $res_start = new DateTime($row['start_time']);
            $res_end = new DateTime($row['end_time']);
            $overlap = !($req_end <= $res_start || $req_start >= $res_end);
            $debug_log[] = [
                'seat_id' => $row['seat_id'],
                'role' => $row['role'],
                'res_start' => $row['start_time'],
                'res_end' => $row['end_time'],
                'req_start' => $start_time,
                'req_end' => $end_time,
                'overlap' => $overlap
            ];
            if ($overlap) {
                $reserved[$row['seat_id']] = $row['status'];
                if ($row['role'] === 'teacher') {
                    foreach ($seats as $s) {
                        $reserved[$s['id']] = $row['status'];
                    }
                }
            }
        } else {
            $reserved[$row['seat_id']] = $row['status'];
            if ($row['role'] === 'teacher') {
                foreach ($seats as $s) {
                    $reserved[$s['id']] = $row['status'];
                }
            }
        }
    }

    // Mark seat availability
    foreach ($seats as &$seat) {
        $seat['status'] = isset($reserved[$seat['id']]) ? $reserved[$seat['id']] : 'available';
    }
    // DEBUG: Output debug log with seat data
    echo json_encode(['seats' => $seats, 'debug' => $debug_log]);
    return;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch seats: ' . $e->getMessage()]);
}
