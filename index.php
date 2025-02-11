<?php
require_once __DIR__ . '/vendor/autoload.php';

$columns = [['Time', 17], ['Callsign', 40], ['Loc/Ref', 27], ['Rs', 14], ['Rr', 14], ['Comment', 0]];


class Speller {
    const EN = ['a' => 'Alpha', 'b' => 'Bravo', 'c' => 'Charlie', 'd' => 'Delta', 'e' => 'Echo', 'f' => 'Foxtrot', 'g' => 'Golf', 'h' => 'Hotel', 'i' => 'India', 'j' => 'Juliet', 'k' => 'Kilo', 'l' => 'Lima', 'm' => 'Mike', 'n' => 'November', 'o' => 'Oscar', 'p' => 'Papa', 'q' => 'Quebec', 'r' => 'Romeo', 's' => 'Sierra', 't' => 'Tango', 'u' => 'Uniform', 'v' => 'Victor', 'w' => 'Whiskey', 'x' => 'X-ray', 'y' => 'Yankee', 'z' => 'Zulu', '/' => 'stroke', '0' => 'Zero', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',];
    const SK = ['a' => 'Adam', 'b' => 'Božena', 'c' => 'Cyril', 'd' => 'Dušan', 'e' => 'Emil', 'f' => 'František', 'g' => 'Gustáv', 'h' => 'Helena', 'i' => 'Ivan', 'j' => 'Jozef', 'k' => 'Karol', 'l' => 'Ladislav', 'm' => 'Mária', 'n' => 'Norbert', 'o' => 'Oto', 'p' => 'Peter', 'q' => 'Quido', 'r' => 'Rudolf', 's' => 'Samuel', 't' => 'Tibor', 'u' => 'Urban', 'v' => 'Viktor', 'w' => 'Wilhelm', 'x' => 'Xénia', 'y' => 'Ypsilon', 'z' => 'Zuzana', '/' => 'lomítko', '0' => 'Nula', '1' => 'Jeden', '2' => 'Dva', '3' => 'Tri', '4' => 'Štyri', '5' => 'Päť', '6' => 'Šesť', '7' => 'Sedem', '8' => 'Osem', '9' => 'Deväť',];

    public static function spell(string $input, array $table) : string {
        $input = strtolower(trim($input));
        $output = '';
        for ($i = 0, $iMax = strlen($input); $i < $iMax; $i++) {
            $output .= $table[$input[$i]] ?? '';
            $output .= ' ';
        }
        return trim($output);
    }
}

class QTH {
    public static function WGS84ToMLS(float $latitude, float $longitude) : string {
        $latitude += 90;
        $longitude += 180;

        $maidenhead = chr(floor($longitude / 20) + 65); // Field longitude (A-R)
        $maidenhead .= chr(floor($latitude / 10) + 65);  // Field latitude (A-R)
        $maidenhead .= floor(($longitude % 20) / 2.0);             // Square longitude (0-9)
        $maidenhead .= floor($latitude % 10 / 1.0);                // Square latitude (0-9)
        $maidenhead .= chr(floor((($longitude - floor($longitude / 2) * 2) * 60) / 5) + 65);  // Subsquare longitude (a-x)
        $maidenhead .= chr(floor((($latitude - floor($latitude / 1) * 1) * 60) / 2.5) + 65); // Subsquare latitude (a-x)

        return $maidenhead;
    }
}

class SOTAPDF extends \TCPDF {
    private string $callsign;
    private string $summitRef;
    private string $summitName;
    private string $summitHeight;
    private string $summitPoints;
    private string $summitLocator;

    public function setCallsign(string $callsign): self {
        $this->callsign = $callsign;
        return $this;
    }

    public function setSummit(string $summitRef, string $summitName, string $summitHeight, string $summitPoints, string $summmitLocator): self {
        $this->summitRef = $summitRef;
        $this->summitName = $summitName;
        $this->summitHeight = $summitHeight;
        $this->summitPoints = $summitPoints;
        $this->summitLocator = $summmitLocator;
        return $this;
    }

    public function Header() {
        $this->SetFont('helvetica', '', 13);
        $this->writeHTMLCell(0, 10, 12, 12, "CQ SOTA CQ SOTA CQ SUMMITS ON THE AIR FROM <b>{$this->callsign}</b> CQ SOTA");
//        $this->Ln();
        $this->writeHTMLCell(0, 10, 12, 20, "<span style=\"color:gray;\">SOTA REF ID </span><b style=\"font-family: freesans;\">{$this->summitRef}, {$this->summitName} - {$this->summitHeight}m, {$this->summitPoints} points, {$this->summitLocator}</b>");
        $this->writeHTMLCell(0, 10, 12, 25, '<b style="font-family: freesans;">' . Speller::spell($this->summitRef, Speller::EN) . '</b>');
        $this->writeHTMLCell(0, 10, 12, 30, '<b style="font-family: freesans;color:gray;">' . Speller::spell($this->summitRef, Speller::SK) . '</b>');

        $this->writeHTMLCell(0, 10, 12, 38, "<b>Date: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/</b>");
    }

    public function drawTable(array $columns, int $rows) {
        $this->SetLineWidth(0.3);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 14);

        foreach ($columns as $column) {
            $this->Cell($column[1], 9, $column[0], 1, 0, 'L', 0);
        }
        for ($i = 0; $i < $rows; $i++) {
            $this->Ln(9);
            foreach ($columns as $column) {
                $this->Cell($column[1], 9, '', 1);
            }
        }
    }
}

if (!isset($_GET['summitRef'])) {
    die('No summit reference.');
}

$summitRef = (string)$_GET['summitRef'];

$json = file_get_contents('https://sotl.as/api/summits/' . $summitRef);
try {
    $data = json_decode($json, true, 10, JSON_THROW_ON_ERROR);
}
catch (JsonException $e) {
    die('Unable to download valid summit data.');
}

$pdf = new \SOTAPDF();
$pdf->setFont('freesans');
$pdf->setCallsign('OM6RT/P');
$pdf->setSummit($data['code'], $data['name'], (string)$data['altitude'], (string)$data['points'], QTH::WGS84ToMLS($data['coordinates']['latitude'], $data['coordinates']['longitude']));
$pdf->SetMargins(12, 44, 12);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setPrintFooter(false);


$pdf->AddPage(); // Add a new page
$pdf->drawTable($columns, 25);

$pdf->AddPage(); // Add a new page
$pdf->drawTable($columns, 25);

// Output the PDF
$pdf->Output('table.pdf', 'I');