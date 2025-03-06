<?php
require 'db_connection.php'; // Połączenie z bazą

$sql = "TRUNCATE TABLE devices"; // Czyści tabelę
if (mysqli_query($conn, $sql)) {
    echo json_encode(["success" => "Tabela Devices została wyczyszczona"]);
} else {
    echo json_encode(["error" => "Błąd podczas czyszczenia tabeli"]);
}
?>
