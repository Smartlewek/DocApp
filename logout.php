0~<?php
session_start();
session_destroy(); // Zniszczenie sesji
header("Location: index.php"); // Przekierowanie po wylogowaniu
exit();
?>

