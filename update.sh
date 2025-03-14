#!/bin/bash

# Ścieżka do tymczasowego katalogu
TEMP_DIR="/tmp/temp_repo"
TARGET_DIR="/var/www/html"

# Sprawdzenie, czy katalog tymczasowy istnieje
if [ ! -d "$TEMP_DIR" ]; then
    echo "Tworzenie tymczasowego katalogu i pobieranie repozytorium..."
    git clone https://twoje.repozytorium.git "$TEMP_DIR"  # Podmień na adres swojego repozytorium

    # Sprawdzamy, czy repozytorium zostało pobrane poprawnie
    if [ $? -eq 0 ]; then
        echo "Repozytorium pobrane pomyślnie."

        # Kopiowanie plików, ale pomijanie określonych elementów
        rsync -av --exclude='db_connection.php' \
                  --exclude='uploads' \
                  --exclude='GoogleAuthenticator' \
                  --exclude='TCPDF' \
                  "$TEMP_DIR/" "$TARGET_DIR/"

        echo "Pliki zostały zaktualizowane. Pomięto: db_connection.php, uploads, GoogleAuthenticator, TCPDF."

        # Usuwanie tymczasowego katalogu
        rm -rf "$TEMP_DIR"
        echo "Tymczasowy katalog usunięty."
        
        # Nadanie uprawnień dla logs.txt
        chown www-data:www-data "$logs_file"
        chmod 664 "$logs_file"
        echo "Uprawnienia do logs.txt ustawione."
    else
        echo "Błąd podczas pobierania repozytorium."
    fi
else
    echo "Tymczasowy katalog już istnieje. Repozytorium nie będzie pobierane ponownie."
fi
