<?php
include 'db_connection.php';

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

// Komunikaty
$message = '';
$title = '';
$content = '';
$important = 0;

// Funkcja do czyszczenia pól formularza
function clearFormFields() {
    global $title, $content, $important;
    $title = '';
    $content = '';
    $important = 0;
}

// Dodawanie notatki
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $important = $_POST['important'];  // Pobranie wartości ważności

    if (!empty($title) && !empty($content)) {
        $sql = "INSERT INTO notes (title, content, important) VALUES ('$title', '$content', '$important')";
        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Notatka została dodana!</div>";
            clearFormFields();
        }
    }
}

// Edytowanie notatki
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $important = $_POST['important'];  // Pobranie wartości ważności

    if (!empty($title) && !empty($content)) {
        // Aktualizacja notatki
        $sql = "UPDATE notes SET title='$title', content='$content', important='$important', modified_at=CURRENT_TIMESTAMP WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Notatka została zaktualizowana!</div>";
            clearFormFields();
            header("Location: notes.php");
            exit();
        }
    }
}

// Usuwanie notatki
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $conn->query("DELETE FROM notes WHERE id=$id");
}

// Pobieranie notatek
$sql = "SELECT * FROM notes ORDER BY created_at DESC";
$result = $conn->query($sql);

// Edycja notatki
$noteToEdit = null;
if (isset($_GET['edit']) && $_GET['edit']) {
    $id = $_GET['edit'];
    $noteToEdit = $conn->query("SELECT * FROM notes WHERE id=$id")->fetch_assoc();
    $title = $noteToEdit['title'];
    $content = $noteToEdit['content'];
    $important = $noteToEdit['important'];
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <title>Notatki</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Notatki</h2>
    <?php echo $message; ?>
    <form method="POST" action="">
        <input type="hidden" name="action" value="<?php echo ($noteToEdit) ? 'edit' : 'add'; ?>">
        <input type="hidden" name="id" value="<?php echo ($noteToEdit) ? $noteToEdit['id'] : ''; ?>">
        <div class="form-group">
            <label for="title">Tytuł</label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo $title; ?>">
        </div>
        <div class="form-group">
            <label for="content">Treść</label>
            <textarea class="form-control" id="content" name="content" rows="3"><?php echo $content; ?></textarea>
        </div>
        <div class="form-group">
            <label for="important">Wybierz ważność:</label>
            <select name="important" id="important" class="form-control">
                <option value="0" <?php echo ($noteToEdit && $noteToEdit['important'] == 0) ? 'selected' : ''; ?>>Brak ważności</option>
                <option value="1" <?php echo ($noteToEdit && $noteToEdit['important'] == 1) ? 'selected' : ''; ?>>Ważna</option>
                <option value="2" <?php echo ($noteToEdit && $noteToEdit['important'] == 2) ? 'selected' : ''; ?>>Krytyczna</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo ($noteToEdit) ? 'Zaktualizuj notatkę' : 'Dodaj notatkę'; ?></button>
    </form>

    <hr>

    <!-- Kontener mobilny -->
    <div id="notesMobileContainer" class="d-block d-md-none">
        <h3>Notatki (Mobilne)</h3>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card mb-2">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $row['title']; ?></h5>
                    <p class="card-text"><?php echo substr($row['content'], 0, 50) . '...'; ?></p>

                    <!-- Dodanie informacji o ważności -->
                    <p>
                        <?php 
                        if ($row['important'] == 1) {
                            echo '<i class="fas fa-star text-warning"></i> WAŻNE';
                        } elseif ($row['important'] == 2) {
                            echo '<i class="fas fa-exclamation-circle text-danger"></i> KRYTYCZNA';
                        } else {
                            echo '<i class="fas fa-star text-muted"></i> Brak ważności';
                        }
                        ?>
                    </p>

                    <a href="notes.php?edit=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Podgląd</a>
                    <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć tę notatkę?');">Usuń</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Kontener desktopowy -->
    <div id="notesDesktopContainer" class="d-none d-md-block">
        <h3>Wszystkie notatki</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tytuł</th>
                    <th>Treść</th>
                    <th>Data utworzenia</th>
                    <th>Data modyfikacji</th>
                    <th>Ważność</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php $result = $conn->query($sql); ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><strong><?php echo $row['title']; ?></strong></td>
                        <td><?php echo substr($row['content'], 0, 50) . '...'; ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td><?php echo $row['modified_at']; ?></td>
                        <td>
                            <?php 
                            if ($row['important'] == 1) {
                                echo '<i class="fas fa-star text-warning"></i> WAŻNE';
                            } elseif ($row['important'] == 2) {
                                echo '<i class="fas fa-exclamation-circle text-danger"></i> KRYTYCZNA';
                            } else {
                                echo '<i class="fas fa-star text-muted"></i> Brak ważności';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="notes.php?edit=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Podgląd</a>
                            <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć tę notatkę?');">Usuń</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script
</body>
</html>

<?php $conn->close(); ?>
