<?php
// vm_monitor.php
session_start();  // Rozpoczynamy sesję

// Sprawdzanie, czy użytkownik jest zalogowany
if (!isset($_SESSION['username'])) {
    header("Location: login.php");  // Przekierowanie na stronę logowania, jeśli brak sesji
    exit();
}

$username = $_SESSION['username'];  // Pobranie nazwy użytkownika z sesji
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';  // Sprawdzanie, czy użytkownik jest administratorem
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Nieznany użytkownik';  // Sprawdzenie nazwy użytkownika
$hostname = gethostname();  // Pobieranie nazwy hosta

if (isset($_GET['data'])) {
    $cpu = shell_exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2 + $4}'");
    $memory_usage = shell_exec("free -m | awk '/Mem:/ {print $3/$2 * 100.0}'");
    $disk_usage = shell_exec("df / | tail -1 | awk '{print $5}' | sed 's/%//'");

    echo json_encode([
        'cpu' => round(floatval($cpu), 2),
        'memory' => round(floatval($memory_usage), 2),
        'disk' => round(floatval($disk_usage), 2),
        'uptime' => shell_exec('uptime'),
        'memoryDetails' => shell_exec('free -m'),
        'diskDetails' => shell_exec('df -h')
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring - <?php echo $hostname; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let cpuChart, memoryChart, diskChart;

        async function fetchData() {
            const response = await fetch("?data=1");
            const data = await response.json();

            document.getElementById('uptime').innerText = data.uptime;
            document.getElementById('memoryDetails').innerText = data.memoryDetails;
            document.getElementById('diskDetails').innerText = data.diskDetails;
            document.getElementById('cpuPercentage').innerText = data.cpu + ' %';

            cpuChart.data.datasets[0].data = [data.cpu, 100 - data.cpu];
            memoryChart.data.datasets[0].data = [data.memory, 100 - data.memory];
            diskChart.data.datasets[0].data = [data.disk, 100 - data.disk];

            cpuChart.update();
            memoryChart.update();
            diskChart.update();
            requestAnimationFrame(fetchData);
        }

        function initCharts() {
            const chartOptions = { responsive: true, maintainAspectRatio: false };
            const ctxCpu = document.getElementById('cpuChart').getContext('2d');
            const ctxMemory = document.getElementById('memoryChart').getContext('2d');
            const ctxDisk = document.getElementById('diskChart').getContext('2d');

            cpuChart = new Chart(ctxCpu, {
                type: 'doughnut',
                data: { labels: ['CPU Usage', 'Idle'], datasets: [{ data: [0, 100], backgroundColor: ['#f39c12', '#ecf0f1'] }] },
                options: chartOptions
            });
            memoryChart = new Chart(ctxMemory, {
                type: 'doughnut',
                data: { labels: ['Memory Usage', 'Free'], datasets: [{ data: [0, 100], backgroundColor: ['#e74c3c', '#2ecc71'] }] },
                options: chartOptions
            });
            diskChart = new Chart(ctxDisk, {
                type: 'doughnut',
                data: { labels: ['Disk Usage', 'Free'], datasets: [{ data: [0, 100], backgroundColor: ['#3498db', '#95a5a6'] }] },
                options: chartOptions
            });
            fetchData();
        }

        window.onload = initCharts;
    </script>
</head>
<body class="bg-light">
    <div class="container my-4">
        <!-- Nagłówek z przyciskiem wylogowania -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Zarządzanie urządzeniami</h2>
            </div>
            <div class="col-md-4 text-md-end text-center">
                <div class="dropdown">
                    <button class="btn btn-danger dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        Wyloguj się
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <!-- Nazwa użytkownika -->
                        <li class="dropdown-item text-center"><strong><?php echo htmlspecialchars($username); ?></strong></li>
                        <li><hr class="dropdown-divider"></li>
                        <!-- Opcje dostępne dla administratora -->
                        <?php if ($isAdmin): ?>
                            <li><a class="dropdown-item" href="admin_panel.php">Panel Admina</a></li>
                        <?php endif; ?>
                        <!-- Link do dokumentacji -->
                        <li><a class="dropdown-item" href="testowa.php">Dokumentacja</a></li>
                        <!-- Link do wylogowania -->
                        <li><a class="dropdown-item" href="logout.php">Wyloguj się</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Monitoring maszyn -->
        <h1 class="mb-4">Monitoring maszyny: <?php echo $hostname; ?></h1>    

        <div class="container mt-5">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Uptime</h5>
                    <pre id="uptime"></pre>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Zużycie CPU</h5>
                    <div style="text-align: center;"><strong id="cpuPercentage">0 %</strong></div>
                    <div style="width: 200px; height: 200px; margin: auto;"><canvas id="cpuChart"></canvas></div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pamięć RAM</h5>
                    <pre id="memoryDetails"></pre>
                    <div style="width: 200px; height: 200px; margin: auto;"><canvas id="memoryChart"></canvas></div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Dysk</h5>
                    <pre id="diskDetails"></pre>
                    <div style="width: 200px; height: 200px; margin: auto;"><canvas id="diskChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JavaScript oraz Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
