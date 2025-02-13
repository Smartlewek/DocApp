<?php
// Połączenie z bazą danych
include 'db_connection.php';

// Sprawdź, czy ID urządzenia zostało przekazane
$deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : 0;

if ($deviceId <= 0) {
    // Jeśli ID urządzenia jest nieprawidłowe, zwróć błąd
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidłowe ID urządzenia.']);
    exit;
}

// Pobierz opis urządzenia z bazy danych
$result = $conn->query("SELECT description FROM devices WHERE id = $deviceId");
if (!$result) {
    // Jeśli zapytanie nie uda się, zwróć błąd
    http_response_code(500);
    echo json_encode(['error' => 'Błąd zapytania do bazy danych.']);
    exit;
}

// Pobierz dane z zapytania
$row = $result->fetch_assoc();
$description = $row['description'] ?? '';

// Ścieżka do katalogu plików urządzenia
$uploadDir = "uploads/$deviceId";

// Sprawdź, czy katalog istnieje
$files = [];
if (is_dir($uploadDir)) {
    // Jeśli katalog istnieje, pobierz pliki
    $scannedFiles = scandir($uploadDir);
    // Filtruj wyniki, aby usunąć "." i ".."
    $files = array_diff($scannedFiles, ['.', '..']);
}

// Zwróć dane w formacie JSON
echo json_encode([
    'description' => $description,  // Opis urządzenia
    'files' => array_values($files), // Lista plików
]);
?>
