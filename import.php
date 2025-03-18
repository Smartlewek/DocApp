<?php
// import.php - obsługa importu CSV i SQL

require 'db_connection.php'; // Połączenie z bazą danych

$response = ['success' => '', 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['table']) && isset($_POST['format'])) {
    $table = $_POST['table'];
    $file = $_FILES['file']['tmp_name'];
    $format = $_POST['format'];

    if (!in_array($table, ['users', 'devices', 'notes']) || !in_array($format, ['csv', 'sql'])) {
        $response['error'] = "Błąd: Nieprawidłowa tabela lub format.";
        echo json_encode($response);
        exit;
    }

    if ($format === 'csv') {
        $handle = fopen($file, 'r');
        if ($handle !== FALSE) {
            fgetcsv($handle); // Pominięcie nagłówka CSV
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($table === 'users') {
                    // Zbadaj czy CSV zawiera: ID, Username, Password, Secret, Role, Created At, Updated At, Active
                    $stmt = $conn->prepare("INSERT INTO users (id, username, password, secret, role, created_at, updated_at, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssssi", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7]);
                } elseif ($table === 'devices') {
                    // Zbadaj czy CSV zawiera: ID, Device Type, Name, IP Address, Netmask, Gateway, Status, Description
                    $stmt = $conn->prepare("INSERT INTO devices (id, device_type, name, ip_address, netmask, gateway, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssssss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7]);
                } elseif ($table === 'notes') {
                    // Zbadaj czy CSV zawiera: ID, Content, Created At, Title, Modified At, Important
                    $stmt = $conn->prepare("INSERT INTO notes (id, content, created_at, title, modified_at, important) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssii", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5]);
                }
                $stmt->execute();
            }
            fclose($handle);
            $response['success'] = "Import CSV zakończony sukcesem!";
        } else {
            $response['error'] = "Błąd: Nie można otworzyć pliku CSV.";
        }
    } elseif ($format === 'sql') {
        $query = file_get_contents($file);
        if ($query) {
            // Usuwanie wszelkich komentarzy, jeżeli w pliku SQL znajdują się takie
            $query = preg_replace('/^--.*$/m', '', $query);  // Usuwanie komentarzy SQL
            $query = preg_replace('/^\/\*.*?\*\/$/ms', '', $query); // Usuwanie komentarzy blokowych

            if ($conn->multi_query($query)) {
                $response['success'] = "Import SQL zakończony sukcesem!";
            } else {
                $response['error'] = "Błąd importu SQL: " . $conn->error;
            }
        } else {
            $response['error'] = "Błąd: Plik SQL jest pusty.";
        }
    }
} else {
    $response['error'] = "Błąd: Nie wybrano pliku, tabeli lub formatu.";
}

echo json_encode($response);
?>
