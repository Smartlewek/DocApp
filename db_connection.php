<?php
$servername = "localhost"; // Zmień na odpowiednią nazwę hosta
$username = "php"; // Zmień na nazwę użytkownika bazy danych
$password = "TK600btk"; // Zmień na swoje hasło
$dbname = "mylogin"; // Nazwa bazy danych

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
