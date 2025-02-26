<?php
session_start();

function logAction($message) {
    $logFile = __DIR__ . '/logs.txt'; 
    $timestamp = date("Y-m-d H:i:s"); 
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    error_log($logMessage, 3, $logFile); 
}

function hasPermission($action) {
    $role = $_SESSION['role'] ?? ''; // Pobieramy rolę użytkownika z sesji
    $permissions = [
        'admin' => ['add_device', 'delete_device', 'update_device', 'monitor', 'print_pdf'],
        'user' => ['add_device', 'delete_device', 'update_device', 'monitor', 'print_pdf'],
        'view' => [''] // Użytkownik z rolą "view" tylko może dodawać urządzenia
    ];
    return in_array($action, $permissions[$role] ?? []);
}

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header("Location: verify_2fa.php");
    exit();
}



// Blokowanie akcji na podstawie uprawnień
$searchQuery = "";
$searchField = "name"; 
if (isset($_POST['search'])) {
    if (!hasPermission('monitor')) {
        logAction("Nieautoryzowana próba monitorowania przez użytkownika.");
        // Dodanie zmiennej sesyjnej, aby wyświetlić komunikat
        $_SESSION['permission_error'] = true;
    } else {
        logAction("Użytkownik wyszukiwał urządzenia z zapytaniem: '{$searchQuery}' na polu: '{$searchField}'.");
        $searchQuery = $_POST['search'];
        $searchField = $_POST['searchField'];
    }
}

// Obsługuje widoczność kolumny ID
if (isset($_POST['toggle_id'])) {
    $_SESSION['show_id'] = !isset($_SESSION['show_id']) || $_SESSION['show_id'] == 0 ? 1 : 0; // Przełącza widoczność
}

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Nieznany użytkownik';

// Informacja o wersji aplikacji
$appVersion = "1.2 beta";

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie urządzeniami</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
	.modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1050; /* Zapewnia, że modal jest na wierzchu */
    max-width: 90%; /* Ogranicza szerokość modalu */
}

.modal-backdrop {
    z-index: 1040; /* Zapewnia, że tło modalu nie zakłóca interakcji z tabelą */
}
	
	.btn-custom {
    background-color: #ff5733; /* Twój własny kolor */
    border-color: #ff5733;    /* Dopasowanie koloru obramowania */
    color: #fff;              /* Kolor tekstu */
}

.btn-custom:hover {
    background-color: #e04c2e; /* Kolor po najechaniu */
    border-color: #e04c2e;
}


        .table-container {
            max-height: 60vh;
            overflow-y: auto;
        }
        .hidden {
            display: none; /* Styl dla ukrytego formularza */
        }
        .action-column {
            width: 150px; /* Dostosowanie szerokości kolumny Akcje */
        }
        /* Responsywność dla urządzeń mobilnych */
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
                text-align: right; /* Wartości po prawej stronie */
                margin-bottom: 5px; /* Odstęp między wierszami */
            }

            .action-column {
                display: flex;
                flex-direction: column;
                align-items: flex-end; /* Przyciski na prawo */
            }

            .action-button {
		 margin-left: 10px; /* Odstęp między przyciskami */
                margin-top: 0.5rem; /* Odstęp między przyciskami */
               
            }

            .table-responsive {
                display: none; /* Ukryj oryginalną tabelę na urządzeniach mobilnych */
            }

            /* Formularz mobilny */
            .mobile-form {
                display: flex;
                flex-direction: column;
            }
        }
    </style>
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
                        <li><a class="dropdown-item" href="logout.php">Wyloguj się</a></li>
                    </ul>
                </div>
            </div>
        </div>

   <!-- Bootstrap JS i popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

        <!-- Wyszukiwanie -->
        <div class="mb-3">
            <form action="testowa.php" method="POST" class="row g-2">
                <div class="col-md-3">
                    <select name="searchField" class="form-select" required>
                        <option value="name">Nazwa</option>
                        <option value="id">ID</option>
                        <option value="ip_address">Adres IP</option>
                        <option value="netmask">Maska sieciowa</option>
                        <option value="gateway">Brama</option>
                        <option value="status">Status</option>
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

		  <!-- Modal z komunikatem o braku uprawnień -->
<?php if (isset($_SESSION['permission_error']) && $_SESSION['permission_error']) : ?>
    <div class="modal fade" id="permissionErrorModal" tabindex="-1" aria-labelledby="permissionErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionErrorModalLabel">Brak uprawnień</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-danger">
                    Nie masz uprawnień do monitorowania urządzeń. Skontaktuj się z administratorem.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var permissionErrorModal = new bootstrap.Modal(document.getElementById('permissionErrorModal'));
            permissionErrorModal.show();
        });
    </script>
    <?php 
    unset($_SESSION['permission_error']); // Resetowanie flagi błędu
    endif; 
?>



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

        <div class="row mb-3">
    <div class="col-md-6">
        <h3>Lista urządzeń</h3>
    </div>
    <div class="col-md-12 text-md-end text-center">
        <!-- Kontener na przyciski -->
        <div class="d-flex justify-content-md-end justify-content-center flex-wrap gap-2">
            <!-- Przyciski -->
			<?php if (hasPermission('monitor')): ?>
	    <a href="speedtest.php" class="btn btn-primary btn-custom">Speedtest</a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#opisModal">Opis</button>
            <a href="skaner.php" class="btn btn-secondary">Skaner</a>
            <a href="pingowanie.php" class="btn btn-warning">Monitoring IP</a>
			<?php endif; ?>
			
            <form action="testowa.php" method="POST" class="d-inline">
                <input type="hidden" name="toggle_id" value="1">
                <button type="submit" class="btn btn-primary">Pokaż/Ukryj ID</button>
            </form>
			<?php if (hasPermission('print_pdf')): ?>
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#pdfModal">Drukuj do PDF</button>
			<?php endif; ?>
			<?php if (hasPermission('add_device')): ?>
            <button class="btn btn-success" onclick="toggleAddForm()">Dodaj urządzenie</button>
			<?php endif; ?>
        </div>
    </div>
</div>



        <!-- Tabela z urządzeniami (responsywna tabela) -->
        <div class="table-responsive" id="deviceTableContainer">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <?php if (!isset($_SESSION['show_id']) || $_SESSION['show_id'] == 1) : ?>
                            <th class="id-column">ID</th> <!-- Ukryta/pokazywana kolumna ID -->
                        <?php endif; ?>
                        <th>Rodzaj</th>
                        <th>Nazwa</th>
                        <th>Adres IP</th>
                        <th>Maska sieciowa</th>
                        <th>Brama</th>
                        <th>Status</th>
                         <!-- Sprawdzenie uprawnień przed wyświetleniem kolumny Akcje -->
        <?php if (hasPermission('monitor')): ?>
            <th class="action-column">Akcje</th>
        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
		    <?php
    		// Wyświetlanie wszystkich urządzeń z bazy danych
    		require_once 'db_connection.php';
	
   	       // Zapytanie SQL z filtrowaniem po nazwie, jeśli pole wyszukiwania jest wypełnione
    	       if (!empty($searchQuery)) {
               $searchQuery = $conn->real_escape_string($searchQuery);

              // Sprawdź, czy szukamy statusu
              if ($searchField === 'status') {
             // Dla statusu używamy operatora "=" dla dokładnego dopasowania z ignorowaniem wielkości liter
            $result = $conn->query("SELECT * FROM devices WHERE LOWER($searchField) = LOWER('$searchQuery')");
        } else {
            // Dla pozostałych pól używamy LIKE, również z ignorowaniem wielkości liter
            $result = $conn->query("SELECT * FROM devices WHERE LOWER($searchField) LIKE LOWER('%$searchQuery%')");
        }
    } else {
        $result = $conn->query("SELECT * FROM devices");
    }                     

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        if (!isset($_SESSION['show_id']) || $_SESSION['show_id'] == 1) {
                            echo "<td class='id-column'>{$row['id']}</td>"; // Ukryta/pokazywana kolumna ID
                        }
                        echo "<td>{$row['device_type']}</td>";
                        echo "<td>{$row['name']}</td>";
                        echo "<td>{$row['ip_address']}</td>";
                        echo "<td>{$row['netmask']}</td>";
                        echo "<td>{$row['gateway']}</td>";
                        echo "<td>{$row['status']}</td>";
                       
                                 if (hasPermission('monitor')) {
									echo "<td class='action-column'>
                    <button class='btn btn-warning btn-sm' onclick=\"editDevice({$row['id']}, '{$row['device_type']}', '{$row['name']}', '{$row['ip_address']}', '{$row['netmask']}', '{$row['gateway']}', '{$row['status']}')\">Edytuj</button>
                    <form method='POST' action='device_handler.php' onsubmit='return confirmDelete();' class='d-inline'>
                        <input type='hidden' name='delete_device_id' value='{$row['id']}'>
                        <button type='submit' class='btn btn-danger btn-sm'>Usuń</button>
                    </form>
                  </td>";
        }
        echo "</tr>";
    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Wyświetlanie urządzeń w formacie formularza dla urządzeń mobilnych -->
        <div class="d-md-none" id="mobileDeviceContainer">
            <?php
    // Użycie tego samego zapytania SQL, co w tabeli, aby uzyskać dane urządzeń
    if (!empty($searchQuery)) {
        $searchQuery = $conn->real_escape_string($searchQuery);
        // Sprawdzenie, czy szukamy statusu
        if ($searchField === 'status') {
            // Dla statusu używamy operatora LIKE dla częściowych dopasowań z ignorowaniem wielkości liter
            $result = $conn->query("SELECT * FROM devices WHERE LOWER($searchField) = LOWER('$searchQuery')");
        } else {
            // Dla pozostałych pól używamy LIKE, również z ignorowaniem wielkości liter
            $result = $conn->query("SELECT * FROM devices WHERE LOWER($searchField) LIKE LOWER('%$searchQuery%')");
        }
    } else {
        $result = $conn->query("SELECT * FROM devices");
    }

            // Wyświetlenie wyników w formacie mobilnym
            while ($row = $result->fetch_assoc()) {
                echo "<div class='device-item mb-3'>";
                if (!isset($_SESSION['show_id']) || $_SESSION['show_id'] == 1) {
                    echo "<div class='device-label'>ID:</div><div class='device-value'>{$row['id']}</div>";
                }
                echo "<div class='device-label'>Rodzaj:</div><div class='device-value'>{$row['device_type']}</div>";
                echo "<div class='device-label'>Nazwa:</div><div class='device-value'>{$row['name']}</div>";
                echo "<div class='device-label'>Adres IP:</div><div class='device-value'>{$row['ip_address']}</div>";
                echo "<div class='device-label'>Maska sieciowa:</div><div class='device-value'>{$row['netmask']}</div>";
                echo "<div class='device-label'>Brama:</div><div class='device-value'>{$row['gateway']}</div>";
                echo "<div class='device-label'>Status:</div><div class='device-value'>{$row['status']}</div>";
                echo "<div class='action-column'>
			 <div class='d-flex justify-content-end'>
                      <button class='btn btn-warning btn-sm' onclick=\"editDevice({$row['id']}, '{$row['device_type']}', '{$row['name']}', '{$row['ip_address']}', '{$row['netmask']}', '{$row['gateway']}', '{$row['status']}')\">Edytuj</button>
                        <form method='POST' action='device_handler.php' onsubmit='return confirmDelete();' class='d-inline'>
                            <input type='hidden' name='delete_device_id' value='{$row['id']}'>
                            <button type='submit' class='btn btn-danger btn-sm'>Usuń</button>
                        </form>
			</div>
                      </div>";
                echo "</div>";
            }
            ?>
        </div>

        <!-- Formularz do dodawania/aktualizacji urządzeń, początkowo ukryty -->
        <form id="deviceForm" action="device_handler.php" method="POST" class="hidden mt-4">
            <h4>Dodaj/Edytuj urządzenie</h4>
            <div class="mb-3">
                <label for="device_type" class="form-label">Rodzaj urządzenia:</label>
                <select name="device_type" id="device_type" class="form-select" required>
                    <option value="VM">VM</option>
                    <option value="CT">CT</option>
                    <option value="Physical">Sprzęt fizyczny</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Nazwa urządzenia:</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Podaj nazwę urządzenia" required>
            </div>

            <div class="mb-3">
                <label for="ip_address" class="form-label">Adres IP:</label>
                <input type="text" name="ip_address" id="ip_address" class="form-control" placeholder="Podaj adres IP" required>
            </div>

            <div class="mb-3">
                <label for="netmask" class="form-label">Maska sieciowa:</label>
                <input type="text" name="netmask" id="netmask" class="form-control" placeholder="Podaj maskę sieciową" required>
            </div>

            <div class="mb-3">
                <label for="gateway" class="form-label">Brama sieciowa:</label>
                <input type="text" name="gateway" id="gateway" class="form-control" placeholder="Podaj bramę sieciową" required>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status:</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="Aktywne">Aktywne</option>
                    <option value="Nieaktywne">Nieaktywne</option>
                    <option value="W konfiguracji">W konfiguracji</option>
                </select>
            </div>

            <!-- Ukryte pole na ID urządzenia (potrzebne tylko przy edycji) -->
            <input type="hidden" name="device_id" id="device_id" value="">

            <div class="form-buttons d-flex justify-content-between">
                <button type="reset" class="btn btn-secondary" onclick="toggleAddForm()">Anuluj</button>
                <button type="submit" name="save" class="btn btn-primary">Zapisz urządzenie</button>
            </div>
        </form>
    </div>
	
	<div class="modal fade" id="opisModal" tabindex="-1" role="dialog" aria-labelledby="opisModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="opisModalLabel">Edytuj Opis Urządzenia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="opisForm" method="POST" enctype="multipart/form-data" onsubmit="submitOpisForm(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="device_id">Wybierz urządzenie:</label>
                        <select id="device_id" name="device_id" class="form-control" required onchange="loadDeviceDescription(this.value)">
                            <option value="">-- Wybierz urządzenie --</option>
                            <?php
                            $deviceList = $conn->query("SELECT id, name FROM devices");
                            while ($device = $deviceList->fetch_assoc()) {
                                echo "<option value='{$device['id']}'>{$device['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Opis</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="file">Dodaj Pliki</label>
                        <input type="file" class="form-control-file" id="file" name="files[]" multiple>
                        <ul id="currentFiles"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Modal do wyboru orientacji PDF -->
    <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="generate_pdf.php" method="GET" target="_blank">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pdfModalLabel">Wybierz orientację PDF</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Wybierz orientację dokumentu:</p>
                        <select name="orientation" class="form-select" required>
                            <option value="P">Pionowa (A4)</option>
                            <option value="L">Pozioma (A4)</option>
                        </select>
                        <input type="hidden" name="show_id" value="<?php echo isset($_SESSION['show_id']) ? $_SESSION['show_id'] : 1; ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Drukuj do PDF</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleAddForm() {
            const form = document.getElementById('deviceForm');
            const tableContainer = document.getElementById('deviceTableContainer');
            const mobileContainer = document.getElementById('mobileDeviceContainer');

            // Zmiana widoczności formularza oraz tabeli
            form.classList.toggle('hidden');
            if (tableContainer) {
                tableContainer.classList.toggle('hidden');
            }
            if (mobileContainer) {
                mobileContainer.style.display = mobileContainer.style.display === 'none' ? 'block' : 'none';
            }
        }

        function editDevice(id, type, name, ip, netmask, gateway, status) {
            document.getElementById('device_id').value = id;
            document.getElementById('device_type').value = type;
            document.getElementById('name').value = name;
            document.getElementById('ip_address').value = ip;
            document.getElementById('netmask').value = netmask;
            document.getElementById('gateway').value = gateway;
            document.getElementById('status').value = status; // Ustawienie statusu
            toggleAddForm(); // Pokaż formularz, jeśli edytujemy
        }

        function confirmDelete() {
            return confirm('Czy na pewno chcesz usunąć to urządzenie?');
        }

        // Funkcja do ukrywania/pokazywania kolumny ID
        function toggleColumn() {
            const idColumns = document.querySelectorAll('.id-column');
            idColumns.forEach(column => {
                column.style.display = column.style.display === 'none' ? '' : 'none';
            });
        }
    </script>
    <!-- Dodajemy informację o wersji aplikacji w stopce -->
    <footer class="text-center mt-4">
        <p>Wersja aplikacji: <?php echo $appVersion; ?></p>
    </footer>
    <!-- Bootstrap JS and Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<script>

function loadDeviceDescription(deviceId) {
    // Reset pola opisu i listy plików, zanim dane zostaną wczytane
    document.getElementById('description').value = '';
    document.getElementById('currentFiles').innerHTML = '';

    // Jeśli brak wybranego urządzenia, zakończ funkcję
    if (!deviceId) {
        return;
    }

    // Pobierz dane urządzenia i jego plików
    fetch(`/get_device_description.php?device_id=${deviceId}`)
        .then(response => response.json())
        .then(data => {
            // Wypełnij pole opisu
            document.getElementById('description').value = data.description || '';

            // Wypełnij listę plików
            const fileList = data.files || [];
            const currentFilesElement = document.getElementById('currentFiles');
            currentFilesElement.innerHTML = fileList.map(file => `
                <li>
                    <a href="/uploads/${deviceId}/${file}" download>${file}</a>
                    <button type="button" onclick="deleteFile('${file}', ${deviceId})" class="btn btn-sm btn-danger">Usuń</button>
                </li>
            `).join('');
        })
        .catch(error => {
            console.error('Błąd wczytywania danych urządzenia:', error);
            alert('Nie udało się wczytać danych urządzenia.');
        });
}

function deleteFile(fileName, deviceId) {
    fetch(`/delete_file.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fileName, deviceId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadDeviceDescription(deviceId);
        } else {
            alert('Nie udało się usunąć pliku.');
        }f
    });
}

function submitOpisForm(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('opisForm'));

    fetch(`/save_device_description.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Opis i pliki zapisane pomyślnie.');
            document.getElementById('opisForm').reset();
            $('#opisModal').modal('hide');
        } else {
            alert('Błąd podczas zapisywania.');
        }
    });
}

// Po zamknięciu modalu resetuj dane
$('#opisModal').on('hidden.bs.modal', function () {
    document.getElementById('opisForm').reset();
    document.getElementById('currentFiles').innerHTML = '';
});

</script>
