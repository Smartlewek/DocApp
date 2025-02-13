<?php
session_start();

// Odczyt danych z POST
$data = json_decode(file_get_contents("php://input"));

// Ustawienie stanu kolumny w sesji
if (isset($data->show_id)) {
    $_SESSION['show_id'] = $data->show_id;
}
?>
