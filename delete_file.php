<?php
include 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$fileName = $data['fileName'];
$deviceId = $data['deviceId'];
$filePath = "uploads/$deviceId/$fileName";

if (file_exists($filePath)) {
    unlink($filePath);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>

