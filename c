
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sprawdzenie, czy użytkownik wysłał żądanie usunięcia urządzenia
    if (isset($_POST['delete_device_id'])) {
        $device_id = $_POST['delete_device_id'];
        $sql = "DELETE FROM devices WHERE id='$device_id'";
        if ($conn->query($sql) === TRUE) {
            header("Location: testowa.php?success=Device deleted successfully");
            exit();
        } else {
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

        // Sprawdzenie, czy edytujemy, czy dodajemy nowe urządzenie
        if (!empty($_POST['device_id'])) {
            // Aktualizacja urządzenia
            $device_id = $_POST['device_id'];
            $sql = "UPDATE devices SET device_type='$device_type', name='$name', ip_address='$ip_address', netmask='$netmask', gateway='$gateway', status='$status' WHERE id='$device_id'";
            if ($conn->query($sql) === TRUE) {
                header("Location: testowa.php?success=Device updated successfully");
                exit();
            } else {
                echo "Błąd podczas aktualizacji urządzenia: " . $conn->error;
            }
        } else {
            // Dodanie nowego urządzenia
            $sql = "INSERT INTO devices (device_type, name, ip_address, netmask, gateway, status) VALUES ('$device_type', '$name', '$ip_address', '$netmask', '$gateway', '$status')";
            if ($conn->query($sql) === TRUE) {
                header("Location: testowa.php?success=Device added successfully");
                exit();
            } else {
                echo "Błąd podczas dodawania urządzenia: " . $conn->error;
            }
        }
    }
}

$conn->close();
?>
