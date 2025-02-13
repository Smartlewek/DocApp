<?php
session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Inicjalizacja zmiennych dla nieudanych prób 2FA
if (!isset($_SESSION['2fa_attempts'])) {
    $_SESSION['2fa_attempts'] = 0;
}
if (!isset($_SESSION['2fa_block_time'])) {
    $_SESSION['2fa_block_time'] = 0;
}

// Zmienna do przechowywania komunikatów o błędach
$error = '';
$remaining_time = 0;

// Sprawdzenie, czy użytkownik nie jest zablokowany przy 2FA
if ($_SESSION['2fa_attempts'] >= 3 && time() < $_SESSION['2fa_block_time']) {
    $remaining_time = $_SESSION['2fa_block_time'] - time();
    $error = "Zbyt wiele nieudanych prób. Konto zablokowane na 1 minutę. Spróbuj ponownie za <span id='remaining_time'>$remaining_time</span> sekund.";
}

// Wczytanie biblioteki Google Authenticator
require_once 'GoogleAuthenticator.php';
$g = new PHPGangsta_GoogleAuthenticator();

// Połączenie z bazą danych
$servername = "localhost";
$db_username = "php";
$db_password = "TK600btk";
$dbname = "mylogin";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Połączenie nieudane: " . $conn->connect_error);
}

// Pobranie danych użytkownika z bazy
$uname = $conn->real_escape_string($_SESSION['username']);
$sql = "SELECT secret, role FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $uname);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $secret = $row['secret'];
    $role = $row['role'];
} else {
    die("Nie można znaleźć danych użytkownika.");
}

// Sprawdzenie kodu 2FA
if ($_SERVER["REQUEST_METHOD"] == "POST" && $remaining_time <= 0) {
    $userCode = $_POST['totp_code'] ?? '';

    // Weryfikacja kodu TOTP
    if ($g->verifyCode($secret, $userCode, 2)) {
        // Kod poprawny, resetowanie prób
        $_SESSION['2fa_attempts'] = 0;
        $_SESSION['2fa_block_time'] = 0;

        $_SESSION['2fa_verified'] = true;

        // Przekierowanie w zależności od roli użytkownika
        if ($role === 'admin') {
            header("Location: admin_panel.php");
        } else {
            header("Location: testowa.php");
        }
        exit();
    } else {
        // Kod niepoprawny - zwiększenie liczby prób
        $_SESSION['2fa_attempts']++;
        if ($_SESSION['2fa_attempts'] >= 3) {
            $_SESSION['2fa_block_time'] = time() + 60;  // Blokada na 60 sekund
            $remaining_time = 60;
            $error = "Zbyt wiele nieudanych prób. Konto zablokowane na 1 minutę. Spróbuj ponownie za <span id='remaining_time'>$remaining_time</span> sekund.";
        } else {
            $error = "Nieprawidłowy kod 2FA. Próba " . $_SESSION['2fa_attempts'] . " z 3.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Weryfikacja 2FA</title>
    <script>
        function startCountdown(remainingTime) {
            if (remainingTime > 0) {
                var interval = setInterval(function() {
                    remainingTime--;
                    document.getElementById('remaining_time').innerText = remainingTime;

                    if (remainingTime <= 0) {
                        clearInterval(interval);
                        document.getElementById('submit_button').disabled = false;
                    }
                }, 1000);
            }
        }
    </script>
</head>
<body onload="startCountdown(<?php echo $remaining_time; ?>)">
    <form method="post" action="">
        <h2>Wprowadź kod 2FA</h2>

        <!-- Wyświetlanie komunikatu o błędach -->
        <?php if (!empty($error)) { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <label>Kod 2FA:</label>
        <input type="text" name="totp_code" placeholder="Wprowadź kod z aplikacji" required><br>
	<div class="center-button">
        <button type="submit" id="submit_button" <?php if ($remaining_time > 0) echo 'disabled'; ?>>Zweryfikuj</button>
    </form>
</body>
</html>
