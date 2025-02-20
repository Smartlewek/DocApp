<?php
session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
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
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sprawdzenie dostępności IP</title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ping-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 10px;
        }
        .ping-active {
            background-color: green;
            animation: ping-animation 1s infinite alternate;
        }
        .ping-inactive {
            background-color: red;
        }
        @keyframes ping-animation {
            0% { opacity: 0.2; }
            100% { opacity: 1; }
        }

        .table-container {
            max-height: 60vh;
            overflow-y: auto;
        }

        /* Nowe style dla urządzeń mobilnych */
        #ping-result {
            max-height: 40vh; /* Maksymalna wysokość dla wyników pingowania */
            overflow-y: auto; /* Umożliwienie przewijania w razie potrzeby */
            white-space: pre-wrap; /* Umożliwia zawijanie długich linii tekstu */
            font-family: monospace; /* Użycie czcionki monospace dla efektu terminala */
            background-color: #f8f9fa; /* Tło dla wyników */
            padding: 10px; /* Padding dla lepszego wyglądu */
            border: 1px solid #dee2e6; /* Granica dla wyników */
            border-radius: 0.25rem; /* Zaokrąglone rogi */
            margin-top: 10px; /* Odstęp od formularza */
            display: none; /* Ukryj domyślnie */
        }

        @media (max-width: 768px) {
            .device-item {
                display: flex;
                flex-direction: column;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 0.5rem;
                margin-bottom: 1rem;
                background-color: #fff;
            }

            .device-label {
                font-weight: bold;
            }

            .device-value {
                text-align: right;
                margin-bottom: 5px;
            }

            .table-responsive {
                display: none; /* Ukrycie standardowej tabeli na urządzeniach mobilnych */
            }
        }

        /* Ukrycie formularza pingowania domyślnie */
        #ping-form-container {
            display: none;
        }
     </style>
</head>
<body >
<body class="bg-light">

<div class="container my-4">
        <!-- Nagłówek z przyciskiem wylogowania -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Zarządzanie urządzeniami</h2>
            </div>
            <div class="col-md-4 text-md-end text-center">
                <form action="logout.php" method="POST">
                    <button type="submit" class="btn btn-danger">Wyloguj się</button>
                </form>
            </div>
        </div>

        <!-- Wyszukiwanie -->
<div class="mb-3">
    <form action="pingowanie.php" method="POST" class="row g-2">
        <div class="col-md-3">
            <select name="searchField" class="form-select" required>
                <option value="name">Nazwa</option>
                <option value="ip_address">Adres IP</option>
            </select>
        </div>
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Wyszukaj" value="<?php echo htmlspecialchars($searchQuery); ?>">
        </div>
        <div class="col-md-3 text-md-end text-center">
            <button type="submit" class="btn btn-primary w-100">Szukaj</button>
        </div>
    </form>
</div>



<!-- Przycisk do wyświetlenia formularza pingowania oraz powrotu -->
<div class="d-flex justify-content-center justify-content-md-end">
    <button id="show-ping-form" class="btn btn-success mb-4 me-2">Ping</button>
    <a href="skaner.php" class="btn btn-info mb-4 me-2">Skaner IP</a>
    <a href="testowa.php" class="btn btn-secondary mb-4">Dokumentacja</a>
</div>


   
     
    <!-- Formularz do pinga -->
    <div id="ping-form-container" class="mb-4">
        <h4>Wykonaj ping do urządzenia</h4>
        <form id="ping-form">
           <div class="form-group">
                <label for="ip-address">Adres IP:</label>
                <input type="text" id="ip-address" class="form-control" placeholder="Wpisz adres IP" required>
            </div>
	<div class="mt-3">
            <button type="submit" class="btn btn-success">Wykonaj</button>
            <button type="button" id="cancel-ping" class="btn btn-secondary ml-2">Anuluj</button>
	</div>
        </form>
        <div id="ping-result"></div> <!-- Element do wyświetlania wyników -->
    </div>

    <!-- Tabela tradycyjna dla desktopów -->
    <div class="table-responsive" id="deviceTableContainer">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                <tr>
                    <th>Nazwa urządzenia</th>
                    <th>Adres IP</th>
                    <th>Status</th>
                    <th>Ping</th>
                </tr>
            </thead>
            <tbody>

<?php
// Ładujemy konfigurację połączenia z bazą danych
require_once 'db_connection.php';

// Sprawdzanie, czy użytkownik przesłał formularz wyszukiwania
$searchField = isset($_POST['searchField']) ? $_POST['searchField'] : '';
$searchQuery = isset($_POST['search']) ? $_POST['search'] : '';

// Budowanie zapytania w zależności od wybranego pola wyszukiwania
$sql = "SELECT name, ip_address FROM devices";

if (!empty($searchField) && !empty($searchQuery)) {
    // Zabezpieczenie przed SQL Injection poprzez przygotowanie zapytania
    $sql .= " WHERE $searchField LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchParam = "%" . $searchQuery . "%"; // Dodajemy % do zapytania
    $stmt->bind_param("s", $searchParam); // "s" oznacza, że szukamy w stringu
    $stmt->execute();
} else {
    // Jeśli nie ma filtra, pobierz wszystkie urządzenia
    $stmt = $conn->query($sql);
}

// Pobieranie wyników
$devices = [];
if ($stmt) {
    while ($device = $stmt->fetch_assoc()) {
        $devices[] = $device;
    }
}

// Sprawdzanie dostępności urządzeń za pomocą ping
foreach ($devices as $device) {
    $name = $device['name'];
    $ip = $device['ip_address'];

    // Komenda ping (1 ping, tylko pod Linuxem, na Windowsie `-n 1`)
    $pingresult = shell_exec("ping -c 1 -W 1 $ip");

    // Przetwarzanie wyniku pinga
    if (preg_match('/bytes from (.*): icmp_seq=(\d+) ttl=(\d+) time=([\d.]+) ms/', $pingresult, $matches)) {
        $status = '<span class="badge bg-success">Dostępny</span>';
        $ping_class = 'ping-active';
    } else {
        $status = '<span class="badge bg-danger">Niedostępny</span>';
        $ping_class = 'ping-inactive';
    }

    // Wyświetlenie danych w tabeli
    echo "<tr>
            <td>$name</td>
            <td>$ip</td>
            <td>$status</td>
            <td><div class='ping-indicator $ping_class'></div></td>
          </tr>";
}

// Zamknięcie połączenia z bazą danych
$conn->close();
?>

 

            </tbody>
        </table>
    </div>

    <!-- Wyświetlanie urządzeń dla urządzeń mobilnych -->
    <div id="device-mobile-container" class="d-md-none">
        <?php
        foreach ($devices as $device) {
            // Dla każdego urządzenia zdefiniuj status i klasę pingu
            $pingresult = shell_exec("ping -c 1 -W 1 {$device['ip_address']}");
            if (preg_match('/bytes from (.*): icmp_seq=(\d+) ttl=(\d+) time=([\d.]+) ms/', $pingresult, $matches)) {
                $status = '<span class="badge bg-success">Dostępny</span>';
                $ping_class = 'ping-active';
            } else {
                $status = '<span class="badge bg-danger">Niedostępny</span>';
                $ping_class = 'ping-inactive';
            }

            echo "<div class='device-item'>";
            echo "<div class='device-label'>Nazwa urządzenia:</div><div class='device-value'>{$device['name']}</div>";
            echo "<div class='device-label'>Adres IP:</div><div class='device-value'>{$device['ip_address']}</div>";
            echo "<div class='device-label'>Status:</div><div class='device-value'>$status</div>";
            echo "<div class='device-label'>Ping:</div><div class='device-value'><div class='ping-indicator $ping_class'></div></div>";
            echo "</div>";
        }
        ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>

<script>
    let isPingFormVisible = false; // Zmienna do zarządzania widocznością formularza ping
    let isMobileTableVisible = true; // Zmienna do zarządzania widocznością tabeli mobilnej

    // Obsługa przycisku do pokazania formularza ping
    $('#show-ping-form').on('click', function() {
        $('#ping-form-container').toggle(); // Wyświetlenie/ukrycie formularza pingowania
        isPingFormVisible = !isPingFormVisible; // Przełączenie stanu widoczności formularza

        if (isPingFormVisible) {
            // Ukryj tabelę na desktopach
            if ($(window).width() > 768) {
                $('.table-responsive').hide(); // Ukrycie tabeli desktopowej
            } else {
                // Ukryj tabelę mobilną
                $('#device-mobile-container').hide(); // Ukrycie tabeli mobilnej
                isMobileTableVisible = false; // Ustaw stan tabeli mobilnej na niewidoczny
            }
        } else {
            // Przywróć tabelę na desktopach
            if ($(window).width() > 768) {
                $('.table-responsive').show(); // Przywrócenie tabeli desktopowej
            } else {
                // Przywróć tabelę mobilną
                $('#device-mobile-container').show(); // Przywrócenie tabeli mobilnej
                isMobileTableVisible = true; // Ustaw stan tabeli mobilnej na widoczny
            }
        }
    });

    // Obsługa formularza pingowania
    $('#ping-form').on('submit', function(e) {
        e.preventDefault(); // Zablokuj domyślne działanie formularza
        let ip = $('#ip-address').val(); // Pobierz adres IP
        let successfulPings = 0; // Liczba udanych pingów
        let failedPings = 0; // Liczba nieudanych pingów
        const totalPackets = 4; // Ilość pingów
        let totalTime = 0; // Łączny czas
        let pingResults = []; // Tablica do przechowywania wyników pingów

        $('#ping-result').text(''); // Wyczyszczenie wyników pinga
        $('#ping-result').show(); // Pokazanie kontenera wyników

        function executePing(count) {
            if (count === 0) {
                // Po zakończeniu pingów, wyświetl podsumowanie
                setTimeout(function() {
                    let packetLoss = (failedPings / totalPackets) * 100;
                    let summary = `${totalPackets} packets transmitted, ${successfulPings} received, ${packetLoss.toFixed(0)}% packet loss, time ${totalTime}ms`;
                    $('#ping-result').append(summary);
                }, 1000); // Oczekiwanie na ostatni ping przed podsumowaniem
                return;
            }

            $.ajax({
                type: 'POST',
                url: 'ping_device.php',
                data: { ip: ip },
                dataType: 'json',
                success: function(data) {
                    let pingData = data.ping_data;
                    let time = data.time;

                    pingResults.push(pingData); // Dodaj wyniki pinga do tablicy
                    totalTime += time;

                    if (data.success) {
                        successfulPings++;
                    } else {
                        failedPings++;
                    }

                    $('#ping-result').append(pingData + '\n'); // Wyświetl wynik pinga
                    setTimeout(function() { // Oczekiwanie sekundy przed kolejnym pingiem
                        executePing(count - 1);
                    }, 1000); // 1 sekunda przerwy
                },
                error: function() {
                    pingResults.push('Błąd podczas pingowania.\n');
                    executePing(count - 1); // Wykonaj kolejny ping w przypadku błędu
                }
            });
        }

        executePing(totalPackets); // Wykonaj 4 pingi
    });

    // Obsługa przycisku anulowania
    $('#cancel-ping').on('click', function() {
        $('#ping-form-container').hide(); // Ukrycie formularza
        isPingFormVisible = false; // Zresetuj stan

        // Przywróć tabelę na desktopach
        if ($(window).width() > 768) {
            $('.table-responsive').show(); // Przywróć tabelę na desktopach
        } else {
            // Przywróć tabelę mobilną
            $('#device-mobile-container').show(); // Przywróć kontener mobilny na urządzeniach mobilnych
            isMobileTableVisible = true; // Ustaw stan tabeli mobilnej na widoczny
        }

    });
</script>
</body>
</html>

