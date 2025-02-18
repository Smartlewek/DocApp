<!DOCTYPE html>
<html>
<head>
    <title>LOGIN</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <?php
    session_start();
    function logAction($message) {
    // Możesz zmienić poniższą część na logowanie do pliku lub bazy danych
    $logfile ='logs.txt';  // Zmień ścieżkę na odpowiednią
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logfile, "[{$timestamp}] - {$message}\n", FILE_APPEND);
}

    // Dołączenie pliku z funkcją sprawdzania sieci
    include 'check_network.php';

    // Pobranie informacji o IP użytkownika i źródle połączenia
    $userData = getUserIP();
	
	logAction("Logowanie próba z IP: {$userData['ip']} (Źródło: {$userData['source']})");

    // Inicjalizacja zmiennych
    $remaining_time = 0;
    $error = "";

    // Sprawdzenie, czy użytkownik jest zablokowany i ile czasu pozostało do końca blokady
    if (isset($_SESSION['block_time']) && time() < $_SESSION['block_time']) {
        $remaining_time = $_SESSION['block_time'] - time(); // Czas pozostały do końca blokady
        $error = "Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za <span id='remaining_time'>" . $remaining_time . "</span> sekund.";

	logAction("Konto zablokowane dla IP: {$userData['ip']} - Pozostały czas blokady: {$remaining_time} sekund.");

    }

    // Logowanie informacji o próbach logowania
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['uname'];
        $password = $_POST['password'];

        // Przykład: Sprawdzenie danych logowania
        // Jeśli użytkownik poda błędne dane, zapisujemy błąd w logach
        if (!isValidLogin($username, $password)) {
            logAction("Błąd logowania dla użytkownika: {$username} z IP: {$userData['ip']}");
            $error = "Nieprawidłowy login lub hasło!";
        } else {
            // Jeżeli logowanie jest poprawne, zapisujemy sukces logowania
            logAction("Udane logowanie dla użytkownika: {$username} z IP: {$userData['ip']}");
            // Możesz tutaj przekierować użytkownika na stronę po udanym logowaniu
        }
    }
    ?>

    <div class="clock" id="clock"></div>
    <form action="login.php" method="post">
        <h2>LOGIN</h2>

        <!-- Wyświetlanie informacji o IP i źródle połączenia pod tytułem LOGIN -->
        <div class="network-info">
            Logowanie z IP: <?php echo $userData['ip']; ?> (Źródło: <?php echo $userData['source']; ?>)
        </div>

        <!-- Wyświetlanie komunikatu o błędach lub blokadzie -->
        <?php if (!empty($error)) { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } elseif (isset($_GET['error'])) { ?>
            <p class="error"><?php echo $_GET['error']; ?></p>
        <?php } ?>

        <label>User Name</label>
        <input type="text" name="uname" placeholder="User Name" required><br>

        <label>Password</label>
        <input type="password" name="password" placeholder="Password" required><br>
        <div class="center-button">
        <button type="submit" id="submit_button" <?php if ($remaining_time > 0) echo 'disabled'; ?>>Login</button>
        </div>
    </form>

    <p class="version">version 1.4 beta</p>

    <script>
        // Funkcja JavaScript do odliczania czasu blokady
        function startCountdown(remainingTime) {
            if (remainingTime > 0) {
                var interval = setInterval(function() {
                    remainingTime--;
                    document.getElementById('remaining_time').innerText = remainingTime;

                    // Gdy czas blokady się skończy, odblokuj przycisk logowania
                    if (remainingTime <= 0) {
                        clearInterval(interval);
                        document.getElementById('submit_button').disabled = false;
                        document.getElementById('remaining_time').innerText = "0"; // Ustaw ostatnią wartość na 0
                    }
                }, 1000);
            }
        }

        // Rozpoczęcie odliczania przy ładowaniu strony
        window.onload = function() {
            startCountdown(<?php echo isset($remaining_time) ? $remaining_time : 0; ?>);
        };

        function updateClock() {
            const now = new Date();
            const options = { 
                year: 'numeric', month: '2-digit', day: '2-digit', 
                hour: '2-digit', minute: '2-digit', second: '2-digit', 
                hour12: false 
            };
            document.getElementById('clock').textContent = now.toLocaleString('pl-PL', options);
        }

        // Aktualizuj zegar co sekundę
        setInterval(updateClock, 1000);
        updateClock(); // Ustawienie początkowego czasu
    </script>
</body>
</html>
