<?php


// Sprawdzenie, czy użytkownik wpisał coś w polu wyszukiwania
$searchQuery = "";
$searchField = "name"; // Domyślne pole do wyszukiwania
if (isset($_POST['search'])) {
    $searchQuery = $_POST['search'];
    $searchField = $_POST['searchField']; // Odczytujemy wybrane pole
}

// Obsługuje widoczność kolumny ID
if (isset($_POST['toggle_id'])) {
    $_SESSION['show_id'] = !isset($_SESSION['show_id']) || $_SESSION['show_id'] == 0 ? 1 : 0; // Przełącza widoczność
}
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
                <form action="logout.php" method="POST">
                    <button type="submit" class="btn btn-danger">Wyloguj się</button>
                </form>
            </div>
        </div>

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

        <!-- Przycisk do ukrywania/pokazywania kolumny ID oraz dodawania urządzenia -->
        <div class="row mb-3">
            <div class="col-md-6">
                <h3>Lista urządzeń</h3>
            </div>
            <div class="col-md-6 text-md-end text-center ">
		<div class="d-flex justify-content-center flex-wrap">
                 <!-- Nowy przycisk Monitoring IP -->
			<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#opisModal">Opis</button>
                 <a href="skaner.php" class="btn btn-secondary me-1 ">Skaner</a> <!-- Nowy przycisk Skaner -->
    		<a href="pingowanie.php" class="btn btn-warning me-1">Monitoring IP</a>
		
                <form action="testowa.php" method="POST" class="d-inline">
                    <input type="hidden" name="toggle_id" value="1">
                    <button type="submit" class="btn btn-primary me-1">Pokaż/Ukryj ID</button>
                </form>
                <button class="btn btn-info me-1" data-bs-toggle="modal" data-bs-target="#pdfModal">Drukuj do PDF</button>
	        <button class="btn btn-success me-1" onclick="toggleAddForm()">Dodaj urządzenie</button>
            </div>
		 <div class="mb-2"></div> <!-- Odstęp między wierszami -->
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
                        <th class="action-column">Akcje</th>
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
                        echo "<td class='action-column'>
                                <button class='btn btn-warning btn-sm' onclick=\"editDevice({$row['id']}, '{$row['device_type']}', '{$row['name']}', '{$row['ip_address']}', '{$row['netmask']}', '{$row['gateway']}', '{$row['status']}')\">Edytuj</button>
                                <form method='POST' action='device_handler.php' onsubmit='return confirmDelete();' class='d-inline'>
                                    <input type='hidden' name='delete_device_id' value='{$row['id']}'>
                                    <button type='submit' class='btn btn-danger btn-sm'>Usuń</button>
                                </form>
                              </td>";
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


<?php

// Obsługa przesyłania plików, wyświetlania, usuwania i aktualizacja opisu

// Ustawienia katalogu dla przesłanych plików
$uploadDir = "uploads/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Funkcja przesyłania plików
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $fileName = basename($_FILES['file']['name']);
    $targetFilePath = $uploadDir . $fileName;
    $description = $_POST['description'] ?? '';

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)) {
        // Zapisz informacje o pliku w bazie danych
        $stmt = $pdo->prepare("INSERT INTO files (name, path, description) VALUES (?, ?, ?)");
        $stmt->execute([$fileName, $targetFilePath, $description]);
        echo "Plik został przesłany i zapisany.";
    } else {
        echo "Wystąpił błąd podczas przesyłania pliku.";
    }
}

// Wyświetlanie listy plików
$stmt = $pdo->query("SELECT id, name, description FROM files");
$files = $stmt->fetchAll();
foreach ($files as $file) {
    echo "<div>";
    echo "<p>Nazwa pliku: " . htmlspecialchars($file['name']) . "</p>";
    echo "<p>Opis: " . htmlspecialchars($file['description']) . "</p>";
    echo "<a href='download.php?id=" . $file['id'] . "'>Pobierz</a>";
    echo "<a href='delete.php?id=" . $file['id'] . "'>Usuń</a>";
    echo "</div>";
}

// Funkcja pobierania plików
if (isset($_GET['download_id'])) {
    $stmt = $pdo->prepare("SELECT path FROM files WHERE id = ?");
    $stmt->execute([$_GET['download_id']]);
    $file = $stmt->fetch();

    if ($file && file_exists($file['path'])) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file['path']) . '"');
        readfile($file['path']);
        exit;
    } else {
        echo "Plik nie istnieje.";
    }
}

// Funkcja usuwania plików
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("SELECT path FROM files WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    $file = $stmt->fetch();

    if ($file && file_exists($file['path'])) {
        unlink($file['path']);
        $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        echo "Plik został usunięty.";
    } else {
        echo "Plik nie istnieje.";
    }
}

?>


<script>
// Funkcje JavaScript do obsługi plików

document.getElementById('opisForm').addEventListener('submit', submitOpisForm);

function submitOpisForm(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    fetch('testowa.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert('Pliki przesłane pomyślnie!');
        loadDeviceFiles(formData.get('device_id'));
    })
    .catch(error => console.error('Error:', error));
}

function loadDeviceFiles(deviceId) {
    if (!deviceId) return;

    fetch(`testowa.php?device_id=${deviceId}`)
        .then(response => response.json())
        .then(files => {
            const fileList = document.getElementById('currentFiles');
            fileList.innerHTML = '';
            files.forEach(file => {
                const li = document.createElement('li');
                li.innerHTML = `
                    ${file} 
                    <button onclick="downloadFile('${deviceId}', '${file}')">Pobierz</button> 
                    <button onclick="deleteFile('${deviceId}', '${file}')">Usuń</button>
                `;
                fileList.appendChild(li);
            });
        });
}

function downloadFile(deviceId, fileName) {
    window.location.href = `uploads/${deviceId}/${fileName}`;
}

function deleteFile(deviceId, fileName) {
    fetch('testowa.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ device_id: deviceId, file_name: fileName })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Plik został usunięty.');
            loadDeviceFiles(deviceId);
        } else {
            alert('Nie udało się usunąć pliku.');
        }
    });
}
</script>

