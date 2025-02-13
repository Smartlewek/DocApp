<?php
include 'db_connection.php';

$deviceId = $_POST['device_id'];
$description = $_POST['description'];

// Aktualizacja opisu
$stmt = $conn->prepare("UPDATE devices SET description = ? WHERE id = ?");
$stmt->bind_param("si", $description, $deviceId);
$stmt->execute();
$stmt->close();

// Ścieżka dla plików
$uploadDir = "uploads/$deviceId";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Zapis plików
foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
    $fileName = basename($_FILES['files']['name'][$key]);
    move_uploaded_file($tmpName, "$uploadDir/$fileName");
}

echo json_encode(['success' => true]);
?>
