<?php
// import.php - obs ^buga importu CSV i SQL

require 'db_connection.php'; // Po ^b ^eczenie z baz ^e danych

$response = ['success' => '', 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['table']) && isset($_POST['format'])) {
    $table = $_POST['table'];
    $file = $_FILES['file']['tmp_name'];
    $format = $_POST['format'];

    if (!in_array($table, ['users', 'devices']) || !in_array($format, ['csv', 'sql'])) {
        $response['error'] = "B ^b ^ed: Nieprawid ^bowa tabela lub format.";
        echo json_encode($response);
        exit;
    }

    if ($format === 'csv') {
        $handle = fopen($file, 'r');
        if ($handle !== FALSE) {
            fgetcsv($handle); // Pomini ^ycie nag ^b  wka CSV
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($table === 'users') {
                    // Zak ^badaj ^ec,   e CSV zawiera: ID, Username, Password, Secret, Role, Created At, Updated At, Active
                    $stmt = $conn->prepare("INSERT INTO users (id, username, password, secret, role, created_at, updated_at, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssssi", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7]);
                } elseif ($table === 'devices') {
                    // Zak ^badaj ^ec,   e CSV zawiera: ID, Device Type, Name, IP Address, Netmask, Gateway, Status, Description
                    $stmt = $conn->prepare("INSERT INTO devices (id, device_type, name, ip_address, netmask, gateway, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssssss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7]);
                }
                $stmt->execute();
            }
	    fclose($handle);
            $response['success'] = "Import CSV zako ^dczony sukcesem!";
        } else {
            $response['error'] = "B ^b ^ed: Nie mo  na otworzy ^g pliku CSV.";
        }
    } elseif ($format === 'sql') {
        $query = file_get_contents($file);
        if ($query) {
            // Usuwanie wszelkich komentarzy, je ^{li w pliku SQL znajduj ^e si ^y takie
            $query = preg_replace('/^--.*$/m', '', $query);  // Usuwanie komentarzy SQL
            $query = preg_replace('/^\/\*.*?\*\/$/ms', '', $query); // Usuwanie komentarzy blokowych

            if ($conn->multi_query($query)) {
                $response['success'] = "Import SQL zako ^dczony sukcesem!";
            } else {
                $response['error'] = "B ^b ^ed importu SQL: " . $conn->error;
            }
        } else {
            $response['error'] = "B ^b ^ed: Plik SQL jest pusty.";
        }
    }
} else {
    $response['error'] = "B ^b ^ed: Nie wybrano pliku, tabeli lub formatu.";
}

echo json_encode($response);
?>
