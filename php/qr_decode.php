<?php
// qr_decode.php - Accepts an uploaded image, decodes QR code, returns seat or desk info
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}
if (!isset($_FILES['qr_image']) || $_FILES['qr_image']['error'] !== UPLOAD_ERR_OK) {
    error_log('Upload error: ' . ($_FILES['qr_image']['error'] ?? 'no file'));
    http_response_code(400);
    echo json_encode(['error' => 'No image uploaded or upload error']);
    exit;
}
$tmp = $_FILES['qr_image']['tmp_name'];
if (!file_exists($tmp)) {
    error_log('Temp file does not exist: ' . $tmp);
}
require_once __DIR__ . '/vendor/autoload.php';
use Zxing\QrReader;
$qr = new QrReader($tmp);
$text = $qr->text();
if (!$text) {
    error_log('QR decode failed for file: ' . $tmp);
    http_response_code(400);
    echo json_encode(['error' => 'No QR code found in image']);
    exit;
}
// QR code should encode: seat:<seat_id> or desk:teacher
if (strpos($text, 'seat:') === 0) {
    $seat_id = (int)substr($text, 5);
    // Check if seat exists
    $stmt = $pdo->prepare('SELECT * FROM seats WHERE id = ?');
    $stmt->execute([$seat_id]);
    $seat = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$seat) {
        http_response_code(404);
        echo json_encode(['error' => 'Seat not found']);
        exit;
    }
    echo json_encode(['type' => 'seat', 'seat_id' => $seat_id, 'seat_number' => $seat['seat_number']]);
    exit;
} elseif ($text === 'desk:teacher') {
    echo json_encode(['type' => 'desk', 'desk' => 'teacher']);
    exit;
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid QR code']);
    exit;
}
