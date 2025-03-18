<?php
// export.php - obsługuje eksport CSV i SQL

require 'db_connection.php'; // Połączenie z bazą danych

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['format']) && isset($_GET['table'])) {
    $table = $_GET['table'];
    $format = $_GET['format'];

    if (!in_array($table, ['users', 'devices', 'notes']) || !in_array($format, ['csv', 'sql'])) {
        die("Błąd: Nieprawidłowa tabela lub format.");
    }

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $table . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        ob_clean(); // Usunięcie wcześniejszych nagłówków
        flush();

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // BOM dla poprawnego kodowania UTF-8

        if ($table === 'users') {
            // Nagłówek CSV dla tabeli users
            fputcsv($output, ['ID', 'Username', 'Password', 'Secret', 'Role', 'Created At', 'Updated At', 'Active']);
            // Zapytanie SQL dla tabeli users
            $query = "SELECT id, username, password, secret, role, created_at, updated_at, active FROM users";
        } elseif ($table === 'devices') {
            // Nagłówek CSV dla tabeli devices
            fputcsv($output, ['ID', 'Device Type', 'Name', 'IP Address', 'Netmask', 'Gateway', 'Status', 'Description']);
            // Zapytanie SQL dla tabeli devices
            $query = "SELECT id, device_type, name, ip_address, netmask, gateway, status, description FROM devices";
        } elseif ($table === 'notes') {
            // Nagłówek CSV dla tabeli notes
            fputcsv($output, ['ID', 'Content', 'Created At', 'Title', 'Modified At', 'Important']);
            // Zapytanie SQL dla tabeli notes
            $query = "SELECT id, content, created_at, title, modified_at, important FROM notes";
        }

        $result = $conn->query($query);
        if (!$result) {
            die("Błąd SQL: " . $conn->error);
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

        if ($table === 'users') {
            // Zapytanie SQL dla tabeli users
            $query = "SELECT id, username, password, secret, role, created_at, updated_at, active FROM users";
        } elseif ($table === 'devices') {
            // Zapytanie SQL dla tabeli devices
            $query = "SELECT id, device_type, name, ip_address, netmask, gateway, status, description FROM devices";
        } elseif ($table === 'notes') {
            // Zapytanie SQL dla tabeli notes
            $query = "SELECT id, content, created_at, title, modified_at, important FROM notes";
        }

        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            // Generowanie zapytania SQL dla każdego rekordu
            $values = array_map('addslashes', $row);
            echo "INSERT INTO $table (" . implode(", ", array_keys($row)) . ") VALUES ('" . implode("', '", $values) . "');\n";
        }
        exit;
    }

    else {
        die("Błąd: Nieprawidłowy format.");
    }
}
?>
