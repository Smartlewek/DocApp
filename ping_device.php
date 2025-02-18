<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $ip = escapeshellarg($_POST['ip']);
    $pingresult = shell_exec("ping -c 1 -W 1 $ip"); // Użyj -n 1 w systemie Windows

    // Przetwarzanie wyniku pinga
    $response = [];
    $response['success'] = false; // Domyślnie uznajemy za nieudany ping
    if (preg_match('/bytes from (.*): icmp_seq=(\d+) ttl=(\d+) time=([\d.]+) ms/', $pingresult, $matches)) {
        $response['ping_data'] = "64 bytes from $matches[1]: icmp_seq=$matches[2] ttl=$matches[3] time=$matches[4] ms";
        $response['success'] = true; // Ping był udany
        $response['time'] = floatval($matches[4]); // Czas pingowania
    } elseif (preg_match('/Destination Host Unreachable/', $pingresult)) {
        $response['ping_data'] = "From $ip icmp_seq=1 Destination Host Unreachable";
        $response['time'] = 0; // Czas pingowania w przypadku nieudanej operacji
    } else {
        $response['ping_data'] = "Ping do $ip: Brak odpowiedzi.";
        $response['time'] = 0; // Czas pingowania w przypadku błędu
    }

    echo json_encode($response);
    exit;
}
