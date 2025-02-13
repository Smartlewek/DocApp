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
    <title>Speedtest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }
        #speedtest-output {
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            height: 300px;
            overflow-y: auto;
            display: none;
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
                <h2>Speedtest</h2>
            </div>
            <div class="col-md-4 d-flex justify-content-end">
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
<button id="start-speedtest" class="btn btn-primary mb-3">Uruchom Speedtest</button>
    <div id="speedtest-output"></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#start-speedtest').on('click', function() {
        $('#speedtest-output').show().text('Uruchamianie Speedtest...\n');
        const eventSource = new EventSource('run_speedtest.php');
        
        eventSource.onmessage = function(event) {
            $('#speedtest-output').append(event.data + '\n');
            
            // Sprawdzenie, czy transmisja została zakończona
            if (event.data.includes("Zakończono.")) {
                eventSource.close();
            }
        };

        eventSource.onerror = function() {
            $('#speedtest-output').append('Błąd podczas uruchamiania Speedtest lub transmisji danych.\n');
            eventSource.close();
        };
    });
</script>
</body>
</html>
