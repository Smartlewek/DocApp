<?php
session_start();


// Połączenie z bazą danych
require_once 'db_connection.php';



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $username = $_POST['username'];

    if ($action === 'reset_password') {
        // Resetowanie hasła
        $newPassword = password_hash('Default123!', PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password='$newPassword', password_reset_required=1 WHERE username='$username'";
        if ($conn->query($sql) === TRUE) {
            echo "Hasło zostało zresetowane dla użytkownika $username.";
        } else {
            echo "Błąd podczas resetowania hasła: " . $conn->error;
        }
    } elseif ($action === 'change_password') {
        // Ręczna zmiana hasła
        $newPassword = $_POST['new_password'];
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password='$hashedPassword', password_reset_required=0 WHERE username='$username'";
        if ($conn->query($sql) === TRUE) {
            echo "Hasło zostało zmienione dla użytkownika $username.";
        } else {
            echo "Błąd podczas zmiany hasła: " . $conn->error;
        }
    } elseif ($action === 'generate_2fa') {
        // Generowanie nowego klucza 2FA
        require_once 'GoogleAuthenticator.php';
        $g = new PHPGangsta_GoogleAuthenticator();
        $secret = $g->createSecret();
        $sql = "UPDATE users SET secret='$secret' WHERE username='$username'";
        if ($conn->query($sql) === TRUE) {
            echo "Wygenerowano nowy klucz 2FA dla użytkownika $username. Kod QR: ";
            $qrCodeUrl = $g->getQRCodeGoogleUrl($username, $secret, 'TwojaAplikacja');
            echo "<img src='$qrCodeUrl'>";
        } else {
            echo "Błąd podczas generowania klucza 2FA: " . $conn->error;
        }
    } elseif ($action === 'disable_2fa') {
        // Wyłączanie 2FA
        $sql = "UPDATE users SET secret=NULL WHERE username='$username'";
        if ($conn->query($sql) === TRUE) {
            echo "Wyłączono 2FA dla użytkownika $username.";
        } else {
            echo "Błąd podczas wyłączania 2FA: " . $conn->error;
        }
    }
}

$conn->close();
header("Location: admin_panel.php");
exit();
?>
