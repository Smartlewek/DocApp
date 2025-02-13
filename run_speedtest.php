<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Funkcja do wysyłania danych przez SSE
function send_message($message) {
    echo "data: " . rtrim($message) . "\n\n";
    ob_flush();
    flush();
}

// Uruchomienie polecenia speedtest-cli
$command = 'speedtest-cli 2>&1'; // Użyj `speedtest` dla Ookla
$process = popen($command, 'r');

if (!$process) {
    send_message("Nie udało się otworzyć procesu speedtest.");
    exit;
}

// Przesyłanie danych linia po linii
while (!feof($process)) {
    $line = fgets($process);
    if ($line !== false) {
        send_message($line);
    }
}

$returnCode = pclose($process);

if ($returnCode !== 0) {
    send_message("Speedtest zakończył się błędem. Kod wyjścia: $returnCode");
}

send_message("Zakończono.");
