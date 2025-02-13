<?php
session_start();
if (!isset($_SESSION['reset_user_id'])) {
    die("Brak dostępu.");
}

// Połączenie z bazą danych
$servername = "localhost";
$db_username = "php";
$db_password = "TK600btk";
$dbname = "mylogin";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Hasła się nie zgadzają!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $user_id = $_SESSION['reset_user_id'];

        $sql = "UPDATE users SET password='$hashed_password' WHERE id='$user_id'";
        if ($conn->query($sql) === TRUE) {
            unset($_SESSION['reset_user_id']);
            
            // Przekierowanie na panel administracyjny po zmianie hasła
            header("Location: admin_panel.php");
            exit();
        } else {
            $error = "Błąd aktualizacji hasła: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zmień hasło</title>
    <!-- Link do Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
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


    <div class="container mt-5">
        <h1 class="mb-4">Zmień hasło</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="mb-3">
                <label for="new_password" class="form-label">Nowe hasło</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Potwierdź hasło</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Zmień hasło</button>
			<button type="button" class="btn btn-secondary" onclick="window.location.href='admin_panel.php'">Anuluj</button>
        </form>
    </div>

    <!-- Link do Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js">
	
function toggleAddUserForm() {
    // Działanie dla przycisku Anuluj
    alert("Anulowano operację.");
    window.location.href = 'index.php'; // Przykładowa strona przekierowania
}
	</script>
</body>
</html>
