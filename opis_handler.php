<?php
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pobranie danych z formularza
    $deviceId = $_POST['device_id'];
    $description = $_POST['description'];
    $files = $_FILES['files'];  // Obsługa wielu plików

    // Aktualizacja opisu urządzenia w tabeli devices
    $stmt = $conn->prepare("UPDATE devices SET description = ? WHERE id = ?");
    
    if (!$stmt) {
        echo "Błąd przygotowania zapytania do aktualizacji opisu: " . $conn->error;
        exit;
    }
    
    $stmt->bind_param("si", $description, $deviceId);
    
    if (!$stmt->execute()) {
        echo "Błąd wykonania zapytania do aktualizacji opisu: " . $stmt->error;
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($stmt->affected_rows > 0) {
        echo "Opis został zaktualizowany.<br>";
    } else {
        echo "Opis nie został zaktualizowany - brak zmian lub błąd.<br>";
    }

    $stmt->close();

?>
