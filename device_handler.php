<?php
function logAction($message) {
    $logFile = 'logs.txt'; // Ścieżka do pliku logu
    $timestamp = date("Y-m-d H:i:s"); // Pobierz aktualny czas
    $logMessage = "[$timestamp] $message" . PHP_EOL; // Tworzymy wiadomość logu
    error_log($logMessage, 3, $logFile); // Zapisz log do pliku
}

require_once 'db_connection.php'; // Połączenie z bazą danych

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    logAction("Otrzymano żądanie POST na stronie: " . $_SERVER['REQUEST_URI']);
    // Sprawdzenie, czy użytkownik wysłał żądanie usunięcia urządzenia
    if (isset($_POST['delete_device_id'])) {
       logAction("Żądanie usunięcia urządzenia, ID: $device_id");
        $device_id = $_POST['delete_device_id'];
        $sql = "DELETE FROM devices WHERE id='$device_id'";
        if ($conn->query($sql) === TRUE) {
            logAction("Urządzenie usunięte, ID: $device_id");
            header("Location: testowa.php?success=Device deleted successfully");
            exit();
        } else {
           logAction("Błąd podczas usuwania urządzenia ID: $device_id, " . $conn->error);
            echo "Błąd podczas usuwania urządzenia: " . $conn->error;
        }
    } else {
        // Pobieranie danych z formularza
        $device_type = $_POST['device_type'];
        $name = $_POST['name'];
        $ip_address = $_POST['ip_address'];
        $netmask = $_POST['netmask'];
        $gateway = $_POST['gateway'];
        $status = $_POST['status']; // Pobranie statusu z formularza
	
	logAction("Otrzymano dane urządzenia: Typ: $device_type, Nazwa: $name, IP: $ip_address, Status: $status");	

        // Sprawdzenie, czy edytujemy, czy dodajemy nowe urządzenie
        if (!empty($_POST['device_id'])) {
	logAction("Wartość device_id w żądaniu POST: " . ($_POST['device_id'] ?? 'brak'));
            // Aktualizacja urządzenia
            $device_id = $_POST['device_id'];
	    logAction("Żądanie aktualizacji urządzenia ID: $device_id");
            $sql = "UPDATE devices SET device_type='$device_type', name='$name', ip_address='$ip_address', netmask='$netmask', gateway='$gateway', status='$status' WHERE id='$device_id'";
            if ($conn->query($sql) === TRUE) {
		logAction("Urządzenie zaktualizowane, ID: $device_id");
                header("Location: testowa.php?success=Device updated successfully");
                exit();
            } else {
                echo "Błąd podczas aktualizacji urządzenia: " . $conn->error;
		logAction("Błąd podczas aktualizacji urządzenia ID: $device_id, " . $conn->error);
            }
        } else {
	    logAction("Żądanie dodania nowego urządzenia");
            // Dodanie nowego urządzenia
            $sql = "INSERT INTO devices (device_type, name, ip_address, netmask, gateway, status) VALUES ('$device_type', '$name', '$ip_address', '$netmask', '$gateway', '$status')";
            if ($conn->query($sql) === TRUE) {
		logAction("Nowe urządzenie dodane: Typ: $device_type, Nazwa: $name, IP: $ip_address, Status: $status");
                header("Location: testowa.php?success=Device added successfully");
                exit();
            } else {
		logAction("Błąd podczas dodawania urządzenia, " . $conn->error);
                echo "Błąd podczas dodawania urządzenia: " . $conn->error;
            }
        }
    }
}

$conn->close();
?>
