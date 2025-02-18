

<!DOCTYPE html>
<html>
<head>
    <title>LOGIN</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<form>

<?php
// Załadowanie biblioteki Google Authenticator (dodaj odpowiednią ścieżkę)
require_once 'GoogleAuthenticator.php';

// Tworzenie instancji klasy GoogleAuthenticator
$g = new PHPGangsta_GoogleAuthenticator();

// Generowanie klucza sekretny (secret key)
$secret = $g->createSecret();
echo "Twój klucz sekretny 2FA: " . $secret;

// Generowanie URL do kodu QR
$qrCodeUrl = $g->getQRCodeGoogleUrl('admin', $secret, 'DockAppLowA');

// Wyświetlenie kodu QR do zeskanowania w aplikacji Google Authenticator
echo '<img src="'. $qrCodeUrl .'" alt="Skanuj ten kod QR w aplikacji Authenticator" />';
?>

</form>
<p class="version">version 1.2 beta</p>
</body>
</html>
