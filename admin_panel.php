<?php
// Funkcja zapisująca logi do pliku
function logActionToFile($action) {
    $logFile = 'logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'nieznany użytkownik';
    $logEntry = "$timestamp | Użytkownik: $username | Akcja: $action\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}




// Połączenie z bazą danych
require_once 'db_connection.php';


session_start();

// Sprawdzenie, czy użytkownik jest adminem
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];

// Obsługa resetowania hasła przez admina
if (isset($_POST['reset_password'])) {
    $user_id = $_POST['user_id'];

    // Zapisanie informacji o wymuszeniu zmiany hasła w sesji
    $_SESSION['reset_user_id'] = $user_id;

    // Logowanie akcji
    logActionToFile("Reset hasła dla użytkownika ID: $user_id");

    // Przekierowanie do formularza zmiany hasła
    header("Location: password_reset_form.php");
    exit();
}

// Obsługa generowania nowego klucza 2FA
$qrCodeUrl = "";
$hideTable = false;  // Zmienna kontrolująca widoczność tabeli
if (isset($_POST['generate_2fa'])) {
    require_once 'GoogleAuthenticator.php';
    $g = new PHPGangsta_GoogleAuthenticator();

    $user_id = $_POST['user_id'];
    $new_secret = $g->createSecret();

    $sql = "UPDATE users SET secret='$new_secret' WHERE id='$user_id'";
    if ($conn->query($sql) === TRUE) {
        $qrCodeUrl = $g->getQRCodeGoogleUrl("User-$user_id", $new_secret, "MyApp");
        // Po wygenerowaniu 2FA ukrywamy tabelę użytkowników
        $hideTable = true;
        echo "<script>showMessage('Klucz 2FA został wygenerowany pomyślnie', 'success');</script>";

        // Logowanie akcji
        logActionToFile("Wygenerowano nowy klucz 2FA dla użytkownika ID: $user_id");
    } else {
        echo "<script>showMessage('Błąd generowania klucza 2FA', 'danger');</script>";
    }
}

// Obsługa dodawania użytkownika
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    require_once 'GoogleAuthenticator.php';
    $g = new PHPGangsta_GoogleAuthenticator();
    $new_secret = $g->createSecret();

    $sql = "INSERT INTO users (username, password, role, secret) VALUES ('$username', '$password', '$role', '$new_secret')";
    if ($conn->query($sql) === TRUE) {
        // Po dodaniu użytkownika, generujemy kod 2FA i ukrywamy tabelę
        $qrCodeUrl = $g->getQRCodeGoogleUrl($username, $new_secret, "MyApp");
        $hideTable = true;
        echo "<script>showMessage('Użytkownik dodany pomyślnie. Klucz 2FA został wygenerowany.', 'success');</script>";

        // Logowanie akcji
        logActionToFile("Dodano nowego użytkownika: $username");
    } else {
        echo "<script>showMessage('Błąd dodawania użytkownika', 'danger');</script>";
    }
}

// Obsługa usuwania użytkownika
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    $sql = "DELETE FROM users WHERE id='$user_id'";
    if ($conn->query($sql) === TRUE) {
        echo "<script>showMessage('Użytkownik usunięty pomyślnie.', 'success');</script>";

        // Logowanie akcji
        logActionToFile("Usunięto użytkownika ID: $user_id");
    } else {
        echo "<script>showMessage('Błąd usuwania użytkownika', 'danger');</script>";
    }
}

// Obsługa zmiany roli użytkownika
if (isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];

    $sql = "UPDATE users SET role='$new_role' WHERE id='$user_id'";
    if ($conn->query($sql) === TRUE) {
        echo "<script>showMessage('Rola użytkownika została zmieniona pomyślnie.', 'success');</script>";
        
        // Logowanie akcji
        logActionToFile("Zmiana roli użytkownika ID: $user_id na $new_role");
    } else {
        echo "<script>showMessage('Błąd zmiany roli użytkownika', 'danger');</script>";
    }
}



// Obsługa aktywacji/dezaktywacji użytkownika
if (isset($_POST['toggle_activation'])) {
    $user_id = $_POST['user_id'];
    $current_status = $_POST['current_status']; // Aktualny stan (1 - aktywny, 0 - nieaktywny)
    $new_status = $current_status ? 0 : 1; // Zmiana statusu

    $sql = "UPDATE users SET active='$new_status' WHERE id='$user_id'";
    if ($conn->query($sql) === TRUE) {
        $action = $new_status ? "Aktywacja" : "Dezaktywacja";
        echo "<script>showMessage('Użytkownik został pomyślnie $action.', 'success');</script>";

        // Logowanie akcji
        logActionToFile("$action konta użytkownika ID: $user_id");
    } else {
        echo "<script>showMessage('Błąd zmiany statusu użytkownika', 'danger');</script>";
    }
}


// Pobranie listy użytkowników
$sql = "SELECT id, username, role, created_at, updated_at, active FROM users";
$result = $conn->query($sql);





?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administracyjny</title>
    <!-- Link do Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
    /* Grupa przycisków w desktopie - wszystkie przyciski w jednej linii */
    .button-group {
        display: flex;
        gap: 10px; /* Odstęp między przyciskami */
        justify-content: flex-start; /* Wyrównanie przycisków do lewej w desktopie */
    }

    /* W widoku mobilnym */
    @media (max-width: 768px) {
        .button-group {
            justify-content: center; /* Wyśrodkowanie przycisków */
            flex-wrap: wrap; /* Pozwól przyciskom przełamać linię w razie potrzeby */
        }

        /* Przycisk "Pokaż logi" w widoku mobilnym - wyśrodkowany i poniżej innych przycisków */
        .button-group button#viewLogsBtn {
            text-align: center;
        }
    }
	.button-group {
    display: flex;
    gap: 10px; /* Odstęp między przyciskami */
    justify-content: flex-start; /* Wyrównanie przycisków do lewej w desktopie */
}

/* W widoku mobilnym */
@media (max-width: 768px) {
    .button-group {
        justify-content: center; /* Wyśrodkowanie przycisków */
        flex-wrap: wrap; /* Pozwól przyciskom przełamać linię w razie potrzeby */
    }

    /* Ukrywanie tabeli w widoku mobilnym */
    #userTableContainer {
        display: none;
    }

    /* Styl dla pojedynczych użytkowników w wersji mobilnej */
    .user-card {
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        margin-bottom: 15px;
        padding: 15px;
        border-radius: 5px;
    }

    .user-card h5 {
        margin-bottom: 10px;
    }

    .user-card p {
        margin: 5px 0;
    }

    .user-card .btn {
        margin-top: 10px;
    }
}
</style>


</head>
<body class="bg-light">

    <div class="container my-4">
        <!-- Nagłówek z przyciskiem wylogowania -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Panel Administracyjny</h2>
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
                            <li><a class="dropdown-item" href="testowa.php">dokumentacja</a></li>
                        <li><a class="dropdown-item" href="logout.php">Wyloguj się</a></li>
                    </ul>
                </div>
            </div>
        </div>
		
		
		

        <!-- Komunikat -->
        <div id="message" style="display:none;" class="alert alert-info"></div>

   <div class="container my-4">
    <!-- Grupa przycisków: "Dodaj urządzenie", "Dokumentacja" i "Pokaż logi" -->
    <div class="button-group mb-3">
        <button id="addUserBtn" class="btn btn-success" onclick="toggleAddUserForm()">Dodaj użytkownika</button>
        <a href="testowa.php" class="btn btn-secondary">Dokumentacja</a>
        <button id="viewLogsBtn" class="btn btn-secondary" onclick="fetchLogs()">Pokaż logi</button>
    </div>
</div>








<!-- Modal z logami -->
<div id="logsModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Logi aplikacji</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <!-- Formularz wyszukiwania w modal -->
                <div class="mb-3">
                    <label for="searchQuery" class="form-label">Szukaj w logach</label>
                    <input type="text" id="searchQuery" class="form-control" placeholder="Wpisz zapytanie do logów">
                    <button class="btn btn-primary mt-2" onclick="searchLogs()">Szukaj</button>
					<button class="btn btn-secondary mt-2" id="toggleFiltersBtn">Pokaż filtrowanie</button>
                </div>
				
				  <!-- Filtr według dat (początkowo ukryty) -->
				<div id="filterContainer" style="display: none;">
    <div id="dateFilter">
        <div class="mb-3">
            <label for="startDate" class="form-label">Data początkowa</label>
            <input type="date" id="startDate" class="form-control">
        </div>
        <div class="mb-3">
            <label for="endDate" class="form-label">Data końcowa</label>
            <input type="date" id="endDate" class="form-control">
        </div>
        <button class="btn btn-primary mt-2" onclick="filterLogsByDate()">Filtruj</button>
    </div>
</div>
				
                <pre id="logsContent" style="white-space: pre-wrap; background: #f8f9fa; padding: 1rem;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
                <a id="downloadLogsBtn" href="get_logs.php?download=1" class="btn btn-primary" download="logs.txt">Pobierz logi</a>
            </div>
        </div>
    </div>
</div>

		<!-- Formularz zmiany roli użytkownika -->
<div id="roleChangeForm" style="display:none;">
    <h2>Zmień rolę użytkownika</h2>
    <form method="post" class="mb-4">
        <input type="hidden" id="user_id_role" name="user_id">
        <div class="mb-3">
            <label for="role" class="form-label">Rola</label>
            <select class="form-select" id="role" name="role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
                <option value="view">View</option>
            </select>
        </div>
        <button type="submit" name="change_role" class="btn btn-primary">Zmień rolę</button>
        <button type="button" class="btn btn-secondary" onclick="toggleRoleChangeForm()">Anuluj</button>
    </form>
</div>

	    

        <!-- Formularz dodawania użytkownika -->
        <div id="addUserForm" style="display:none;">
            <h2>Dodaj użytkownika</h2>
            <form method="post" class="mb-4">
                <div class="mb-3">
                    <label for="username" class="form-label">Nazwa użytkownika</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Hasło</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Rola</label>
                    <select class="form-select" id="role" name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
						<option value="view">View</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">Dodaj użytkownika</button>
                <button type="button" class="btn btn-secondary" onclick="toggleAddUserForm()">Anuluj</button>
            </form>
        </div>

        <!-- Lista użytkowników -->
        <h2>Lista użytkowników</h2>
        <div id="userTableContainer" class="d-none d-md-block" <?php if($hideTable) echo 'style="display:none;"'; ?>>
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
    <tr>
        <th>ID</th>
        <th>Nazwa użytkownika</th>
        <th>Rola</th>
        <th>Data utworzenia</th>
        <th>Data modyfikacji</th>
		<th>Status</th>
        <th>Akcje</th>
    </tr>
</thead>
<tbody>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['username']; ?></td>
                <td><?php echo $row['role']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td><?php echo $row['updated_at']; ?></td>
				 <td><?php echo $row['active'] ? 'Aktywny' : 'Nieaktywny'; ?></td>
                <td>
                    <!-- Akcje użytkownika -->
                    <form method="post" style="display:inline;" onsubmit="hideUserTable();">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="reset_password" class="btn btn-warning btn-sm">Resetuj hasło</button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="hideUserTable();">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="generate_2fa" class="btn btn-info btn-sm">Generuj 2FA</button>
                    </form>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)">Usuń użytkownika</button>
                    </form>
					<a href="javascript:void(0)" class="btn btn-warning btn-sm" 
					onclick="toggleRoleChangeForm(<?php echo $row['id']; ?>, '<?php echo $row['role']; ?>')">Zmień rolę</a>

					<form method="post" style="display:inline;">
						<input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
						<input type="hidden" name="current_status" value="<?php echo $row['active']; ?>">
						<button type="submit" name="toggle_activation" class="btn btn-<?php echo $row['active'] ? 'secondary' : 'success'; ?> btn-sm">
						<?php echo $row['active'] ? 'Dezaktywuj' : 'Aktywuj'; ?>
						</button>
					</form>

                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="6" class="text-center">Brak użytkowników w bazie danych.</td>
        </tr>
    <?php endif; ?>
</tbody>
            </table>
        </div>
		
		
		<!-- Widok mobilny -->
<div class="d-md-none">
    <?php if ($result->num_rows > 0): ?>
        <?php mysqli_data_seek($result, 0); // Resetowanie wskaźnika wyników ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="user-card">
                <h5>ID: <?php echo $row['id']; ?> - <?php echo $row['username']; ?></h5>
                <p><strong>Rola:</strong> <?php echo $row['role']; ?></p>
                <p><strong>Data utworzenia:</strong> <?php echo $row['created_at']; ?></p>
                <p><strong>Status:</strong> <?php echo $row['active'] ? 'Aktywny' : 'Nieaktywny'; ?></p>
                <div>
                    <form method="post" style="display:inline;" onsubmit="hideUserTable();">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="reset_password" class="btn btn-warning btn-sm">Resetuj hasło</button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="hideUserTable();">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="generate_2fa" class="btn btn-info btn-sm">Generuj 2FA</button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="hideUserTable();">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)">Usuń użytkownika</button>
                    </form>
                    <a href="javascript:void(0)" class="btn btn-warning btn-sm" 
                    onclick="toggleRoleChangeForm(<?php echo $row['id']; ?>, '<?php echo $row['role']; ?>')">Zmień rolę</a>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="current_status" value="<?php echo $row['active']; ?>">
                        <button type="submit" name="toggle_activation" class="btn btn-<?php echo $row['active'] ? 'secondary' : 'success'; ?> btn-sm">
                        <?php echo $row['active'] ? 'Dezaktywuj' : 'Aktywuj'; ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-center">Brak użytkowników w bazie danych.</p>
    <?php endif; ?>
</div>


	
		

        <!-- Wyświetlanie QR kodu 2FA poniżej tabeli -->
        <?php if ($qrCodeUrl): ?>
            <h3>Wygenerowany kod 2FA dla użytkownika</h3>
            <p>Zeskanuj poniższy kod QR, aby dodać konto do aplikacji 2FA:</p>
            <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="img-fluid">
            <form method="post" class="mt-3">
                <button type="submit" name="confirm_2fa" class="btn btn-success">Zatwierdź 2FA</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Link do Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Skrypt do ukrywania formularza -->
    <script>
	
	
		function toggleRoleChangeForm(user_id, current_role) {
    var form = document.getElementById('roleChangeForm');
    var table = document.getElementById('userTableContainer');
    
    if (form.style.display === "none") {
        form.style.display = "block";
        table.style.display = "none";
        
        // Ustawienie wartości w formularzu
        document.getElementById('user_id_role').value = user_id;
        document.getElementById('role').value = current_role;
    } else {
        form.style.display = "none";
        table.style.display = "block";
    }
}
		
		function toggleDateFilter() {
    const dateFilter = document.getElementById('dateFilter');
    if (dateFilter.style.display === 'none') {
        dateFilter.style.display = 'block';
    } else {
        dateFilter.style.display = 'none';
    }
}
	
       function toggleAddUserForm() {
    var form = document.getElementById('addUserForm');
    var desktopTable = document.getElementById('userTableContainer'); // Tabela desktopowa
    var mobileView = document.querySelector('.d-md-none'); // Tabela mobilna

    if (form.style.display === "none") {
        // Pokaż formularz dodawania użytkownika, ukryj tabelę
        form.style.display = "block";
        if (desktopTable) desktopTable.style.display = "none";
        if (mobileView) mobileView.style.display = "none";
    } else {
        // Ukryj formularz dodawania użytkownika, przywróć tabelę
        form.style.display = "none";
        if (desktopTable) desktopTable.style.display = "block";
        if (mobileView) mobileView.style.display = "block";
    }
}


        function showMessage(message, type = 'danger') {
            var messageDiv = document.getElementById('message');
            messageDiv.textContent = message;
            messageDiv.className = `alert alert-${type}`;
            messageDiv.style.display = 'block';
        }

        function confirmDelete(user_id) {
            var confirmed = confirm('Czy na pewno chcesz usunąć tego użytkownika?');
            if (confirmed) {
                // Przekierowanie do usunięcia użytkownika
                var form = document.createElement('form');
                form.method = 'POST';
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_id';
                input.value = user_id;
                var submit = document.createElement('input');
                submit.type = 'hidden';
                submit.name = 'delete_user';
                form.appendChild(input);
                form.appendChild(submit);
                document.body.appendChild(form);
                form.submit();
            }
        }
		
		function fetchLogs() {
    fetch('get_logs.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP status ${response.status}`);
            }
            return response.text();
        })
        .then(data => {
            document.getElementById('logsContent').textContent = data.trim() || "Brak logów do wyświetlenia.";
            const logsModal = new bootstrap.Modal(document.getElementById('logsModal'));
            logsModal.show();
        })
        .catch(error => {
            document.getElementById('logsContent').textContent = `Nie udało się pobrać logów: ${error.message}`;
            console.error("Fetch error:", error);
        });
}

  // Pokaż/ukryj kontener z filtrowaniem
document.getElementById('toggleFiltersBtn').addEventListener('click', function () {
    const filterContainer = document.getElementById('filterContainer');
    if (filterContainer.style.display === 'none') {
        filterContainer.style.display = 'block';
        this.textContent = 'Ukryj filtrowanie';
    } else {
        filterContainer.style.display = 'none';
        this.textContent = 'Pokaż filtrowanie';
    }
});

// Ładowanie wszystkich logów
function loadAllLogs() {
    fetch('get_logs.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP status ${response.status}`);
            }
            return response.text();
        })
        .then(data => {
            document.getElementById('logsContent').textContent = data.trim() || "Brak logów do wyświetlenia.";
        })
        .catch(error => {
            document.getElementById('logsContent').textContent = `Błąd: ${error.message}`;
            console.error("Błąd w ładowaniu logów:", error);
        });
}

// Wyszukiwanie logów
function searchLogs() {
    const query = document.getElementById('searchQuery').value.trim();
    if (!query) {
        alert('Wpisz zapytanie do logów!');
        return;
    }

    fetch(`get_logs.php?searchQuery=${encodeURIComponent(query)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP status ${response.status}`);
            }
            return response.text();
        })
        .then(data => {
            document.getElementById('logsContent').textContent = data.trim() || "Brak wyników.";
        })
        .catch(error => {
            document.getElementById('logsContent').textContent = `Błąd: ${error.message}`;
            console.error("Błąd:", error);
        });
}

// Filtrowanie logów według daty
function filterLogsByDate() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    if (!startDate && !endDate) {
        alert('Proszę podać datę początkową lub końcową.');
        return;
    }

    let url = 'get_logs.php?';
    if (startDate) url += `startDate=${encodeURIComponent(startDate)}&`;
    if (endDate) url += `endDate=${encodeURIComponent(endDate)}`;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP status ${response.status}`);
            }
            return response.text();
        })
        .then(data => {
            document.getElementById('logsContent').textContent = data.trim() || "Brak wyników.";
        })
        .catch(error => {
            document.getElementById('logsContent').textContent = `Błąd: ${error.message}`;
            console.error("Błąd:", error);
        });
}



    // Funkcja do otwierania modala z logami
document.addEventListener('DOMContentLoaded', function () {
    const logsModal = new bootstrap.Modal(document.getElementById('logsModal'), {
        backdrop: false // Wyłącza przyciemnienie tła
    });

    // Wywołanie ładowania logów po otwarciu modala
    const openLogsModal = () => {
        loadAllLogs();  // Załaduj wszystkie logi
        logsModal.show();
    };

    // Przypisanie do przycisku otwierającego modal
    document.getElementById('viewLogsBtn').addEventListener('click', openLogsModal);
});

// Funkcja do ukrywania tabeli użytkowników w wersji desktopowej i mobilnej
function hideUserTable() {
    var userTable = document.getElementById('userTableContainer');
    if (userTable) {
        userTable.style.display = 'none';
    }
    var mobileView = document.querySelector('.d-md-none');
    if (mobileView) {
        mobileView.style.display = 'none';
    }
}

// Funkcja do przywracania widoczności tabeli użytkowników w wersji desktopowej i mobilnej
function showUserTable() {
    var userTable = document.getElementById('userTableContainer');
    if (userTable) {
        userTable.style.display = 'block';
    }
    var mobileView = document.querySelector('.d-md-none');
    if (mobileView) {
        mobileView.style.display = 'block';
    }
}

function toggleRoleChangeForm(user_id, current_role) {
    var form = document.getElementById('roleChangeForm');
    var desktopTable = document.getElementById('userTableContainer'); // Tabela desktopowa
    var mobileView = document.querySelector('.d-md-none'); // Tabela mobilna

    if (form.style.display === "none") {
        // Pokaż formularz zmiany roli, ukryj tabelę
        form.style.display = "block";
        if (desktopTable) desktopTable.style.display = "none";
        if (mobileView) mobileView.style.display = "none";

        // Ustaw wartości w formularzu
        document.getElementById('user_id_role').value = user_id;
        document.getElementById('role').value = current_role;
    } else {
        // Ukryj formularz zmiany roli, przywróć tabelę
        form.style.display = "none";
        if (desktopTable) desktopTable.style.display = "block";
        if (mobileView) mobileView.style.display = "block";
    }
}



    </script>
</body>
</html>
