<?php
$servername = "localhost"; // Zmień na odpowiednią nazwę hosta
$username = "$DB_USER"; // Zmień na nazwę użytkownika bazy danych
$password = "$DB_PASS"; // Zmień na swoje hasło
$dbname = "$DB_NAME"; // Nazwa bazy danych

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
