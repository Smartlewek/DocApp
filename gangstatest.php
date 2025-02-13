<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'GoogleAuthenticator.php';

$g = new PHPGangsta_GoogleAuthenticator();

// Generowanie sekretu
$secret = $g->createSecret();
echo "Sekret: " . $secret . "\n";

// Tworzenie linku QR Code dla aplikacji Google Authenticator
$qrCodeUrl = $g->getQRCodeGoogleUrl('TwojaAplikacja', $secret);
echo "Link do QR Code: " . $qrCodeUrl . "\n";

// Weryfikacja kodu wprowadzonego przez użytkownika
$oneCode = $g->getCode($secret);
echo "Kod jednorazowy: " . $oneCode . "\n";

$isValid = $g->verifyCode($secret, $oneCode, 2); // Okno czasowe 2 minuty
echo $isValid ? "Kod poprawny" : "Kod niepoprawny";
?>
