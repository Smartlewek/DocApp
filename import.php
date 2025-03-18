<?php
// import.php - obsługa importu CSV i SQL

require 'db_connection.php'; // Połączenie z bazą danych

// Funkcja do logowania błędów
function logError($message) {
    $logFile = '/var/log/php_import_error.log';
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $logFile);
}

$response = ['success' => '', 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['table']) && isset($_POST['format'])) {
    $table = $_POST['table'];
    $file = $_FILES['file']['tmp_name'];
    $format = $_POST['format'];

    if (!in_array($table, ['users', 'devices', 'notes']) || !in_array($format, ['csv', 'sql'])) {
        $error = "Błąd: Nieprawidłowa tabela lub format.";
        logError($error);
        $response['error'] = $error;
        echo json_encode($response);
        exit;
    }

    if ($format === 'csv') {
        $handle = fopen($file, 'r');
        if ($handle !== FALSE) {
            fgetcsv($handle); // Pominięcie nagłówka CSV
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($table === 'users') {
                    $stmt = $conn->prepare("INSERT INTO users (id, username, password, secret, role, created_at, updated_at, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssssi", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7]);
                } elseif ($table === 'devices') {
                    $stmt = $conn->prepare("INSERT INTO devices (id, device_type, name, ip_address, netmask, gateway, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssssss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7]);
                } elseif ($table === 'notes') {
                    // Walidacja pola 'important'
                    if (!in_array($data[5], ['0', '1', '2'])) {
                        $error = "Błędna wartość pola 'important' dla ID: $data[0]. Wartość musi być 0, 1 lub 2.";
                        logError($error);
                        continue; // Pomiń ten wiersz
                    }
                    $stmt = $conn->prepare("INSERT INTO notes (id, content, created_at, title, modified_at, important) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssi", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5]);
                }
                if (!$stmt->execute()) {
                    logError("Błąd podczas importu do tabeli $table: " . $stmt->error);
                }
            }
            fclose($handle);
            $response['success'] = "Import CSV zakończony sukcesem!";
        } else {
            $error = "Błąd: Nie można otworzyć pliku CSV.";
            logError($error);
            $response['error'] = $error;
        }
    } elseif ($format === 'sql') {
        $query = file_get_contents($file);
        if ($query) {
            $query = preg_replace('/^--.*$/m', '', $query);  // Usuwanie komentarzy SQL
            $query = preg_replace('/^\/\*.*?\*\//ms', '', $query); // Usuwanie komentarzy blokowych

            if ($conn->multi_query($query)) {
                $response['success'] = "Import SQL zakończony sukcesem!";
            } else {
                $error = "Błąd importu SQL: " . $conn->error;
                logError($error);
                $response['error'] = $error;
            }
        } else {
            $error = "Błąd: Plik SQL jest pusty.";
            logError($error);
            $response['error'] = $error;
        }
    }
} else {
    $error = "Błąd: Nie wybrano pliku, tabeli lub formatu.";
    logError($error);
    $response['error'] = $error;
}

echo json_encode($response);
?>
