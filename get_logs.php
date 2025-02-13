<?php
// Ścieżka do pliku logów
$logFile = 'logs.txt';

// Sprawdź, czy plik logów istnieje
if (!file_exists($logFile)) {
    echo "Plik logów nie istnieje.";
    exit;
}

// Pobierz wszystkie logi z pliku
$logs = file_get_contents($logFile);
$lines = explode("\n", $logs); // Rozdziel logi na linie

// Sprawdzanie parametrów daty
$startDate = isset($_GET['startDate']) && !empty($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) && !empty($_GET['endDate']) ? $_GET['endDate'] : null;

// Sprawdzanie zapytania wyszukiwania
$searchQuery = isset($_GET['searchQuery']) && !empty($_GET['searchQuery']) ? $_GET['searchQuery'] : null;

// Filtracja logów
$filteredLogs = array_filter($lines, function ($line) use ($searchQuery, $startDate, $endDate) {
    // Sprawdź datę logu (zakładamy, że format daty w logach to [YYYY-MM-DD HH:MM:SS] lub YYYY-MM-DD)
    if (preg_match('/^\[?(\d{4}-\d{2}-\d{2})\]?/', $line, $matches)) {
        $logDate = $matches[1]; // Wyciągnięcie daty z logu (YYYY-MM-DD)

        // Filtruj po dacie początkowej
        if ($startDate && $logDate < $startDate) {
            return false;
        }

        // Filtruj po dacie końcowej
        if ($endDate && $logDate > $endDate) {
            return false;
        }
    }

    // Filtruj po zapytaniu wyszukiwania
    if ($searchQuery && stripos($line, $searchQuery) === false) {
        return false;
    }

    return true;
});

// Zwrócenie przefiltrowanych logów
if (empty($filteredLogs)) {
    echo "Brak wyników dla podanych filtrów.";
} else {
    echo implode("\n", $filteredLogs);
}
?>
