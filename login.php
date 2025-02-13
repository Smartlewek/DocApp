<?php
// Połączenie z bazą danych
$servername = "localhost";
$username = "php";
$password = "TK600btk";
$dbname = "mylogin";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['block_time'])) {
    $_SESSION['block_time'] = 0;
}

// Funkcja logująca zdarzenia
function logEvent($message) {
    $logfile = 'logs.txt'; // Ścieżka do pliku logów
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logfile, "[{$timestamp}] - {$message}\n", FILE_APPEND);
}

if ($_SESSION['login_attempts'] >= 3 && time() < $_SESSION['block_time']) {
    header("Location: index.php?error=Too many login attempts. Try again in 1 minute.");
    logEvent("Blokada konta dla IP: {$_SERVER['REMOTE_ADDR']}, próba logowania podczas blokady.");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uname = $conn->real_escape_string($_POST['uname']);
    $pass = $_POST['password'];

    // Pobranie danych użytkownika z bazy
    $sql = "SELECT * FROM users WHERE username='$uname'";
    $result = $conn->query($sql);

    if ($result === false) {
        logEvent("Błąd SQL: " . $conn->error);
        die("Database query error. Check logs for details.");
    }

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashed_password = $row['password'];
        $is_active = $row['active']; // Pobierz status aktywności konta

        // Debugging: logowanie danych użytkownika
        logEvent("Dane użytkownika: username={$uname}, active={$is_active}, role={$row['role']}");

        // Sprawdzenie, czy konto jest aktywne
        if ((int)$is_active === 0) { // Upewnij się, że porównanie jest poprawne
            logEvent("Nieudane logowanie dla użytkownika: {$uname} (konto nieaktywne), IP: {$_SERVER['REMOTE_ADDR']}");
            header("Location: index.php?error=Account inactive. Please contact support.");
            exit();
        }

        // Sprawdzenie hasła
        if (password_verify($pass, $hashed_password)) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['block_time'] = 0;
            $_SESSION['username'] = $uname;
            $_SESSION['role'] = $row['role']; // Ustawienie roli użytkownika

            logEvent("Udane logowanie dla użytkownika: {$uname}, IP: {$_SERVER['REMOTE_ADDR']}");

            // Przekierowanie na stronę weryfikacji 2FA dla wszystkich użytkowników
            header("Location: verify_2fa.php");
            exit();
        } else {
            $_SESSION['login_attempts']++;
            logEvent("Nieudane logowanie (błędne hasło) dla użytkownika: {$uname}, IP: {$_SERVER['REMOTE_ADDR']}, próba {$_SESSION['login_attempts']}");
            if ($_SESSION['login_attempts'] >= 3) {
                $_SESSION['block_time'] = time() + 60;
                logEvent("Blokada konta dla użytkownika: {$uname}, IP: {$_SERVER['REMOTE_ADDR']}");
                header("Location: index.php?error=Too many login attempts. Try again in 1 minute.");
            } else {
                header("Location: index.php?error=Incorrect password. Attempt " . $_SESSION['login_attempts'] . " of 3.");
            }
            exit();
        }
    } else {
        $_SESSION['login_attempts']++;
        logEvent("Nieudane logowanie (błędna nazwa użytkownika: {$uname}) IP: {$_SERVER['REMOTE_ADDR']}, próba {$_SESSION['login_attempts']}");
        if ($_SESSION['login_attempts'] >= 3) {
            $_SESSION['block_time'] = time() + 60;
            header("Location: index.php?error=Too many login attempts. Try again in 1 minute.");
        } else {
            header("Location: index.php?error=Incorrect username or password. Attempt " . $_SESSION['login_attempts'] . " of 3.");
        }
        exit();
    }
}

$conn->close();
?>
