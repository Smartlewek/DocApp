<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Po ^b ^eczenie z baz ^e danych
require_once 'db_connection.php';


session_start();

// Sprawdzenie, czy u  ytkownik jest adminem
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];

// Konfiguracja
$repoOwner = "Smartlewek";
$repoName = "DocApp";
$branch = "main";  // Możesz zmienić na inną gałąź, jeśli potrzeba
$localPath = "/var/www/html/DocApp/";

// Pobieranie listy commitów z repozytorium GitHub
$apiUrl = "https://api.github.com/repos/$repoOwner/$repoName/commits?sha=$branch";
$options = [
    "http" => [
        "header" => "User-Agent: PHP"
    ]
];
$context = stream_context_create($options);
$response = file_get_contents($apiUrl, false, $context);

if ($response === false) {
    die("<div class='alert alert-danger'>❌ Błąd podczas pobierania danych z GitHub.</div>");
}

$commits = json_decode($response, true);

if (!is_array($commits) || empty($commits)) {
    die("<div class='alert alert-danger'>❌ Nie można pobrać commitów z GitHub.</div>");
}

// Pobranie plików zmienionych w najnowszym commicie
$latestCommitSha = $commits[0]['sha'];
$commitUrl = "https://api.github.com/repos/$repoOwner/$repoName/commits/$latestCommitSha";
$response = file_get_contents($commitUrl, false, $context);
$commitData = json_decode($response, true);

if (!$commitData || empty($commitData['files'])) {
    die("<div class='alert alert-success'>✅ Brak zmian do aktualizacji.</div>");
}

$filesToUpdate = [];

// Zapisywanie informacji o wszystkich plikach zmienionych w commitcie
foreach ($commitData['files'] as $file) {
    // Dodajemy zmieniony plik do tablicy
    $filesToUpdate[] = [
        "name" => $file['filename'],
        "download_url" => "https://raw.githubusercontent.com/$repoOwner/$repoName/$branch/" . $file['filename']
    ];
}

// Rozpoczynamy generowanie strony
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktualizacja systemu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container my-4">

<h2> Aktualizacja systemu</h2>

<?php if (empty($filesToUpdate)): ?>
    <div class="alert alert-success">✅ System jest aktualny. Nie ma nowych plików do pobrania.</div>
<?php else: ?>
    <div class="alert alert-warning">⚠️ Znaleziono zmienione pliki:</div>
    <ul class="list-group mb-3">
        <?php foreach ($filesToUpdate as $file): ?>
            <li class="list-group-item"><?= htmlspecialchars($file['name']) ?></li>
        <?php endforeach; ?>
    </ul>
    <form method="post">
        <button type="submit" name="update" class="btn btn-primary"> Aktualizuj teraz</button>
        <button class="btn btn-info" onclick="location.href='admin_panel'">Aktualizuj</button>
    </form>
<?php endif; ?>

<?php
// Pobieranie zmienionych plików po kliknięciu "Aktualizuj"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($filesToUpdate as $file) {
        $fileContent = file_get_contents($file['download_url']);
        if ($fileContent !== false) {
            $localFilePath = $localPath . $file['name'];
            $dirPath = dirname($localFilePath);
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0777, true);  // Tworzymy brakujące katalogi
            }
            file_put_contents($localFilePath, $fileContent);
            echo "<div class='alert alert-success'>✅ Pobrano: " . htmlspecialchars($file['name']) . "</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Błąd pobierania: " . htmlspecialchars($file['name']) . "</div>";
        }
    }
    echo "<meta http-equiv='refresh' content='2'>"; // Odśwież stronę po zakończeniu
}
?>

</body>
</html>
