<?php
// export.php - obs ^buga eksportu CSV i SQL

require 'db_connection.php'; // Po ^b ^eczenie z baz ^e danych

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['format']) && isset($_GET['table'])) {
    $table = $_GET['table'];
    $format = $_GET['format'];

    if (!in_array($table, ['users', 'devices']) || !in_array($format, ['csv', 'sql'])) {
        die("B ^b ^ed: Nieprawid ^bowa tabela lub format.");
    }

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $table . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        ob_clean(); // Usuni ^ycie wcze ^{niejszych nag ^b  wk  w
        flush();

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // BOM dla poprawnego kodowania UTF-8

        if ($table === 'users') {
            // Nag ^b  wek CSV dla tabeli users
            fputcsv($output, ['ID', 'Username', 'Password', 'Secret', 'Role', 'Created At', 'Updated At', 'Active']);
            // Zapytanie SQL dla wszystkich kolumn w tabeli users
            $query = "SELECT id, username, password, secret, role, created_at, updated_at, active FROM users";
        } elseif ($table === 'devices') {
            // Nag ^b  wek CSV dla tabeli devices
            fputcsv($output, ['ID', 'Device Type', 'Name', 'IP Address', 'Netmask', 'Gateway', 'Status', 'Description']);
            // Zapytanie SQL dla wszystkich kolumn w tabeli devices
	   $query = "SELECT id, device_type, name, ip_address, netmask, gateway, status, description FROM devices";
        }

        $result = $conn->query($query);
        if (!$result) {
            die("B ^b ^ed SQL: " . $conn->error);
        }

        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    elseif ($format === 'sql') {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $table . '.sql"');

        ob_clean();
        flush();



        $query = "SELECT * FROM $table";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            echo "INSERT INTO $table VALUES ('" . implode("','", array_map('addslashes', $row)) . "');\n";
        }
        exit;
    }

    else {
        die("B ^b ^ed: Nieprawid ^bowy format.");
    }
}
?>
