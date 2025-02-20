#!/bin/bash

# Zaktualizowanie systemu
echo "🔄 Aktualizowanie systemu..."
apt update -y && apt upgrade -y

# Ustawienie strefy czasowej na aktualną
echo "⚙️ Ustawianie strefy czasowej..."
timedatectl set-timezone Europe/Warsaw

# Instalacja wymaganych pakietów
echo "📦 Instalowanie wymaganych pakietów..."
apt install -y mariadb-client mariadb-server apache2 php php-cli php-mbstring php-xml unzip php-curl php-mysqli qrencode git nmap speedtest-cli

# Usunięcie domyślnego pliku index.html Apache2
echo "❌ Usuwanie domyślnego pliku index.html Apache2..."
rm -f /var/www/html/index.html

# Sklonowanie repozytoriów jeśli ich nie ma
echo "🔄 Sprawdzanie repozytoriów..."
cd /var/www/html

[ ! -d "GoogleAuthenticator" ] && git clone https://github.com/PHPGangsta/GoogleAuthenticator.git
[ ! -d "TCPDF" ] && git clone https://github.com/tecnickcom/TCPDF.git

if [ ! -d "DocApp" ]; then
    git clone https://github.com/Smartlewek/DocApp.git
    mv /var/www/html/DocApp/* /var/www/html/
    rm -rf /var/www/html/DocApp
else
    echo "✅ Repozytorium DocApp już istnieje, sprawdzanie plików..."
    cd /var/www/html/DocApp
    git fetch origin
    for file in $(git ls-tree -r HEAD --name-only); do
        if [ ! -f "/var/www/html/$file" ]; then
            echo "📂 Brakujący plik: $file, kopiowanie..."
            cp "DocApp/$file" "/var/www/html/$file"
        fi
    done
fi

# Tworzenie bazy danych MySQL
echo "📌 Konfiguracja MySQL/MariaDB..."
read -p "Podaj nazwę nowej bazy danych: " dbname
read -p "Podaj nazwę użytkownika MySQL: " dbuser
read -sp "Podaj hasło dla użytkownika MySQL: " dbpass
echo ""

echo "🛠 Tworzenie bazy danych i użytkownika MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS $dbname;"
mysql -e "CREATE USER IF NOT EXISTS '$dbuser'@'localhost' IDENTIFIED BY '$dbpass';"
mysql -e "GRANT ALL PRIVILEGES ON $dbname.* TO '$dbuser'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Tworzenie tabel w bazie danych
echo "📄 Tworzenie tabel w bazie danych $dbname..."
mysql -u $dbuser -p$dbpass $dbname << EOF
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    secret VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'admin', 'view') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS devices (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    device_type ENUM('VM', 'CT', 'Physical') NOT NULL,
    name VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    netmask VARCHAR(45) NOT NULL,
    gateway VARCHAR(45) NOT NULL,
    status ENUM('Aktywne', 'Nieaktywne', 'W konfiguracji') NOT NULL DEFAULT 'Aktywne',
    description TEXT
);
EOF

# Tworzenie użytkownika admin z 2FA
echo "👤 Tworzenie użytkownika admin..."

echo "🔑 Podaj hasło dla administratora:"
read -s admin_pass

# Generowanie 2FA dla użytkownika admin
echo "📸 Generowanie kodu QR dla 2FA użytkownika admin..."

php -r '
    require_once "/var/www/html/GoogleAuthenticator/PHPGangsta/GoogleAuthenticator.php";
    $ga = new PHPGangsta_GoogleAuthenticator();
    $secret = $ga->createSecret();
    echo "Twój klucz sekretny 2FA: $secret\n";
    echo "otpauth://totp/MyServer:admin?secret=$secret&issuer=MyServer\n";
' > /tmp/admin_secret.txt

admin_secret=$(grep -oP '(?<=secret=)[A-Z0-9]+' /tmp/admin_secret.txt)
otpAuthUrl="otpauth://totp/MyServer:admin?secret=$admin_secret&issuer=MyServer"

# Generowanie QR w terminalu
echo "📸 Kod QR do zeskanowania:"
echo "$otpAuthUrl" | qrencode -t ANSIUTF8

# Zapisywanie użytkownika admin do bazy danych
hashed_pass=$(php -r "echo password_hash('$admin_pass', PASSWORD_BCRYPT);")
mysql -u "$dbuser" -p"$dbpass" "$dbname" << EOF
INSERT INTO users (username, password, secret, role) VALUES ('admin', '$hashed_pass', '$admin_secret', 'admin')
ON DUPLICATE KEY UPDATE password='$hashed_pass', secret='$admin_secret';
EOF

# Tworzenie folderu uploads
echo "📂 Tworzenie folderu 'uploads'..."
mkdir -p /var/www/html/uploads
chmod 775 /var/www/html/uploads
chown www-data:www-data /var/www/html/uploads

# Tworzenie pliku db_connection.php
echo "📄 Tworzenie pliku db_connection.php..."
cat > /var/www/html/db_connection.php <<EOF
<?php
\$servername = "localhost";
\$username = "$dbuser";
\$password = "$dbpass";
\$dbname = "$dbname";

\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);

if (\$conn->connect_error) {
    die("Connection failed: " . \$conn->connect_error);
}
?>
EOF

# Ustawienie uprawnień dla logs.txt
echo "🔒 Ustawianie uprawnień dla logs.txt..."
touch /var/www/html/logs.txt
chmod 777 /var/www/html/logs.txt

# Restartowanie Apache2 i MariaDB
echo "🔄 Restartowanie Apache2 i MariaDB..."
systemctl restart apache2
systemctl restart mariadb

# Wyświetlenie adresu IP serwera
echo "🌐 Adres IP serwera:"
ip_address=$(hostname -I | awk '{print $1}')
echo "Wpisz ten adres w aplikacji: $ip_address"

# Instalacja zakończona
echo "✅ Instalacja i konfiguracja zakończona pomyślnie!"
