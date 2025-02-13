<?php
session_start();
require_once('tcpdf/tcpdf.php'); // Upewnij się, że ścieżka jest poprawna

// Odczytanie stanu kolumny ID z parametrów URL
$showId = isset($_GET['show_id']) ? (int)$_GET['show_id'] : 1;
$orientation = isset($_GET['orientation']) ? $_GET['orientation'] : 'P'; // Domyślnie pionowo

// Utworzenie klasy MYPDF dziedziczącej po TCPDF
class MYPDF extends TCPDF {
    // Przedefiniowanie metody Footer()
    public function Footer() {
        // Ustawienie pozycji Y na 15 mm od dołu
        $this->SetY(-15);
        // Ustawienie czcionki dla podpisu
        $this->SetFont('dejavusans', 'I', 10);
        
        // Dodanie podpisu w stopce
        $this->Cell(0, 10, 'Wygenerowane przez aplikację: IPsmartDocumentation', 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Tworzenie nowego dokumentu PDF
$pdf = new MYPDF($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Ustawienia dokumentu
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Twoja Nazwa');
$pdf->SetTitle('Lista urządzeń');

// Ustawienia nagłówka (bez logo)
$pdf->SetHeaderData('', 0, '', 'Dokumentacja sieci');

// Ustawienia nagłówka i stopki
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->SetFont('dejavusans', '', 12); // Użyj czcionki DejaVu Sans

// Dodanie strony
$pdf->AddPage();

// Ustawienia bazy danych
$servername = "localhost";
$username = "php";
$password = "TK600btk";
$dbname = "mylogin";

$conn = new mysqli($servername, $username, $password, $dbname);

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Zapytanie do pobrania wszystkich urządzeń
$sql = "SELECT * FROM devices";
$result = $conn->query($sql);

// Generowanie tabeli PDF
$html = '<h1>Lista urządzeń</h1>';
$html .= '<table border="1" cellpadding="5">';
$html .= '<thead>';
$html .= '<tr>';
if ($showId === 1) {
    $html .= '<th>ID</th>'; // Dodaj kolumnę ID tylko, jeśli ma być wyświetlana
}
$html .= '<th>Rodzaj</th>';
$html .= '<th>Nazwa</th>';
$html .= '<th>Adres IP</th>';
$html .= '<th>Maska sieciowa</th>';
$html .= '<th>Brama</th>';
$html .= '<th>Status</th>';
$html .= '</tr>';
$html .= '</thead>';
$html .= '<tbody>';

if ($result->num_rows > 0) {
    // Wyświetlanie danych urządzeń
    while($row = $result->fetch_assoc()) {
        $html .= '<tr>';
        if ($showId === 1) {
            $html .= '<td>' . $row['id'] . '</td>'; // Dodaj kolumnę ID tylko, jeśli ma być wyświetlana
        }
        $html .= '<td>' . $row['device_type'] . '</td>';
        $html .= '<td>' . $row['name'] . '</td>';
        $html .= '<td>' . $row['ip_address'] . '</td>';
        $html .= '<td>' . $row['netmask'] . '</td>';
        $html .= '<td>' . $row['gateway'] . '</td>';
        $html .= '<td>' . $row['status'] . '</td>';
        $html .= '</tr>';
    }
}

$html .= '</tbody>';
$html .= '</table>';

// Wstawianie HTML do PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Zakończenie i wygenerowanie pliku PDF
$pdf->Output('lista_urzadzen.pdf', 'I'); // 'I' - wyświetlanie w przeglądarce

$conn->close();
?>
