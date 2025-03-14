#!/bin/bash

temp_dir="/tmp/repo_temp"
repo_url="https://github.com/Smartlewek/DocApp"
logs_file="/var/www/html/logs.txt"

# Sprawdzenie czy istnieje tymczasowy katalog
if [ ! -d "$temp_dir" ]; then
    echo "Tworzenie tymczasowego katalogu..."
    mkdir "$temp_dir"
else
    echo "Tymczasowy katalog już istnieje."
fi

# Pobranie repozytorium
if git clone "$repo_url" "$temp_dir"; then
    echo "Repozytorium pobrane pomyślnie."
    
    # Kopiowanie plików z wyjątkiem db_connection.php, uploads, GoogleAuthenticator, TCPDF, .git
    rsync -av --exclude 'db_connection.php' --exclude 'uploads' --exclude 'GoogleAuthenticator' --exclude 'TCPDF' --exclude '.git' "$temp_dir/" /var/www/html/
    echo "Pliki skopiowane pomyślnie."
    
    # Nadanie uprawnień dla logs.txt
    chown www-data:www-data "$logs_file"
    chmod 664 "$logs_file"
    echo "Uprawnienia do logs.txt ustawione."

    # Usunięcie tymczasowego katalogu
    rm -rf "$temp_dir"
    echo "Tymczasowy katalog usunięty."
else
    echo "Błąd podczas pobierania repozytorium."
fi
