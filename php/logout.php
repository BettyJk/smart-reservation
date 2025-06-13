<?php
// logout.php - Destroy session and log out user
session_start();
session_unset();
session_destroy();
header('Content-Type: application/json');
echo json_encode(['success' => true]);
