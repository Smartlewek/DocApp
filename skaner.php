<?php
session_start();

function logEvent($message) {
    $logfile = 'logs.txt'; // Ścieżka do logów
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logfile, "[{$timestamp}] - {$message}\n", FILE_APPEND);
}

function hasPermission($action) {
    $role = $_SESSION['role'] ?? '';
    $permissions = [
        'admin' => ['skaner'],
        'user' => ['skaner'],
        'view' => [] // Użytkownik "view" nie ma dostępu do skaner
    ];
    return in_array($action, $permissions[$role] ?? []);
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (!hasPermission('skaner')) {
    header("Location: testowa.php"); // Możesz zmienić na inną stronę
    exit();
}

if (!isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header("Location: verify_2fa.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        /* Style dla animacji ładowania */
        .loading-spinner {
            display: none; /* Ukryj spinner domyślnie */
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .loading-text {
            display: none; /* Ukryj tekst domyślnie */
            position: absolute;
            top: 55%; /* Ustaw tekst bliżej spinnera */
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1rem; /* Zmniejszono rozmiar tekstu */
            color: black; /* Ustaw kolor tekstu na czarny */
            white-space: nowrap; /* Zatrzymaj tekst w jednej linii */
        }
        .dot {
            display: inline-block;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 0.1;
            }
            50% {
                opacity: 1;
            }
        }

        /* Wyśrodkowanie przycisków na widoku mobilnym */
        @media (max-width: 768px) {
            .btn-group-mobile-center {
                justify-content: center !important;
            }
        }

        /* Dodanie odstępu między przyciskami */
        .btn-spacing {
            margin-right: 10px;
        }

        .devicemobilecontainer {
            margin: 20px 0;
            border: 1px solid #dee2e6;
            border-radius: .25rem;
            padding: 15px;
            background-color: #f8f9fa;
        }
    </style>
    <title>Skanner Sieci</title>
</head>
<body class="bg-light">
    <div class="container my-4">
        <!-- Nagłówek z przyciskiem wylogowania -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Zarządzanie urządzeniami</h2>
            </div>
            <div class="col-md-4 text-md-right text-center">
                <form action="logout.php" method="POST" class="me-2">
                    <button type="submit" class="btn btn-danger">Wyloguj się</button>
                </form>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6 offset-md-6 text-md-right text-center">
                <div class="d-flex justify-content-end flex-wrap btn-group-mobile-center">
                    <a href="pingowanie.php" class="btn btn-warning btn-spacing">Monitoring IP</a>
                    <a href="testowa.php" class="btn btn-primary">Dokumentacja</a>
                </div>
            </div>
        </div>

        <form method="post" class="mb-4" id="scanForm">
            <div class="form-group">
                <label for="subnet">Zakres IP (np. 192.168.1.0/24):</label>
                <input type="text" class="form-control" id="subnet" name="subnet" required placeholder="Wprowadź zakres IP">
            </div>
            <button type="submit" class="btn btn-primary">Skanuj Sieć</button>
        </form>

        <div class="loading-spinner">
            <div class="spinner-border" role="status">
                <span class="sr-only">Ładowanie...</span>
            </div>
        </div>
        <div class="loading-text">Skanowanie<span class="dot">...</span></div>

        <div id="results" class="mt-4"></div>

        <!-- Modal -->
        <div class="modal fade" id="noResultsModal" tabindex="-1" role="dialog" aria-labelledby="noResultsModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="noResultsModalLabel">Brak wyników</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Brak znalezionych urządzeń w podanym zakresie IP.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Zamknij</button>
                    </div>
                </div>
            </div>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Pobierz zakres IP z formularza
            $subnet = escapeshellarg($_POST['subnet']); // Użyj escapeshellarg dla bezpieczeństwa

            // Wykonaj polecenie nmap
            $output = shell_exec("timeout 10 nmap -sn $subnet 2>&1"); // Użyj timeout na 10 sekund
            $errorOutput = null;

            // Sprawdź, czy skanowanie się powiodło
            if ($output === null) {
                $errorOutput = 'Błąd wykonania nmap. Sprawdź, czy jest zainstalowany i masz odpowiednie uprawnienia.';
            } else {
                // Przetwórz wynik
                $lines = explode("\n", $output);
                $devices = []; // Tablica do przechowywania znalezionych urządzeń

                foreach ($lines as $line) {
                    if (preg_match('/Nmap scan report for (.+)/', $line, $matches)) {
                        $host = htmlspecialchars($matches[1]);
                        $devices[] = ['host' => $host];
                    }
                    if (preg_match('/(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                        if (!empty($devices)) {
                            // Przypisz adres IP do ostatnio znalezionego hosta
                            $devices[count($devices) - 1]['ip'] = htmlspecialchars($matches[1]);
                        }
                    }
                }

                // Wyświetl wyniki lub komunikat o braku urządzeń
                if (!empty($devices)) {
                    echo '<div id="scanResults" style="display:block;">'; // Ustaw wyniki na widoczne

                    // Wyświetlanie w tabeli dla desktopów
                    echo '<div class="d-none d-md-block">'; // Ukryj dla urządzeń mobilnych
                    echo '<table class="table table-striped">';
                    echo '<thead><tr><th>Adres IP</th><th>Nazwa hosta</th></tr></thead><tbody>';
                    
                    foreach ($devices as $device) {
                        echo '<tr>';
                        echo '<td>' . (isset($device['ip']) ? $device['ip'] : 'Brak IP') . '</td>';
                        echo '<td>' . (isset($device['host']) ? $device['host'] : 'Brak hosta') . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                    echo '</div>'; // Koniec div dla desktopów

                    // Wyświetlanie w formularzach dla urządzeń mobilnych
                    echo '<div class="d-md-none">'; // Ukryj dla desktopów
                    foreach ($devices as $device) {
                        echo '<div class="devicemobilecontainer">'; // Dodaj klasę dla responsywności
                        echo '<form>'; // Rozpocznij formularz dla każdego urządzenia
                        echo '<div class="form-group">';
                        echo '<label>Adres IP:</label>';
                        echo '<input type="text" class="form-control" value="' . (isset($device['ip']) ? $device['ip'] : 'Brak IP') . '" readonly>';
                        echo '</div>';
                        echo '<div class="form-group">';
                        echo '<label>Nazwa hosta:</label>';
                        echo '<input type="text" class="form-control" value="' . (isset($device['host']) ? $device['host'] : 'Brak hosta') . '" readonly>';
                        echo '</div>';
                        echo '</form>'; // Zakończ formularz dla każdego urządzenia
                        echo '</div>'; // Koniec div dla responsywności
                    }
                    echo '</div>'; // Koniec div dla mobilnych
                    echo '</div>'; // Koniec ukrytego div
                } else {
                    echo '<script>window.foundDevices = false;</script>'; // Ustaw flagę na false
                }
            }

            // Wyświetlenie błędu, jeśli wystąpił
            if ($errorOutput) {
                echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($errorOutput) . '</div>';
            }
        }
        ?>
    </div>

    <script>
        const form = document.getElementById('scanForm');
        const spinner = document.querySelector('.loading-spinner');
        const loadingText = document.querySelector('.loading-text');
        
        form.addEventListener('submit', function() {
            // Wyświetl spinner i tekst podczas wysyłania formularza
            spinner.style.display = 'block';
            loadingText.style.display = 'block';

            // Ustaw timeout na 10 sekund
            setTimeout(() => {
                // Ukryj spinner i tekst ładowania
                spinner.style.display = 'none';
                loadingText.style.display = 'none';

                // Sprawdzanie, czy wyniki zostały znalezione
                if (window.foundDevices === false) {
                    $('#noResultsModal').modal('show'); // Wyświetlenie modala
                }
            }, 10000);
        });

        // Ustawienie odpowiedniej widoczności spinnera i tekstu po skanowaniu
        $(document).ready(function() {
            $('.loading-spinner').hide();
            $('.loading-text').hide();
        });
    </script>
</body>
</html>
