<?php
function Lettura_nomi_riferimento(array $filtri): string
{
    $chiamata = Collegamento();
    $nomi_recuperati = [];

    $tabella = '';
    $colonna_ricerca = '';
    $colonna_risultato = '';
    $valori = [];

    // 1. Scansione dell'array per isolare i parametri di interesse, ignorando la matricola
    foreach ($filtri as $filtro) {
        if (isset($filtro['materie'])) {
            $tabella = 'materie';
            $colonna_ricerca = 'cod_materia';
            $colonna_risultato = 'nome_materia';
            $valori = $filtro['materie'];
            break;
        } elseif (isset($filtro['corsi'])) {
            $tabella = 'corsi';
            $colonna_ricerca = 'ID_corso';
            $colonna_risultato = 'nome_corso'; // Assumo che la colonna si chiami così nel tuo database
            $valori = $filtro['corsi'];
            break;
        }
    }

    // Se l'array filtri non conteneva né materie né corsi, si interrompe l'operazione
    if (empty($valori)) {
        return "Nessun riferimento specifico";
    }

    // 2. Generazione dinamica dei placeholder per la clausola IN
    $placeholders = implode(',', array_fill(0, count($valori), '?'));

    // 3. Costruzione e preparazione della query
    #$query = "SELECT `$colonna_risultato` AS `nome` FROM `$tabella` WHERE `$colonna_ricerca` IN ($placeholders)";
    $query = "
        SELECT `".$colonna_risultato."` AS `nome` FROM `".$tabella."` WHERE `".$colonna_ricerca."` IN (".$placeholders.")
    ";

    $stmt = $chiamata->prepare($query);
    $stmt->execute($valori);
    $risultati = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $chiamata = null;

    // 4. Estrazione ed unione dei nomi in una singola stringa
    foreach ($risultati as $record) {
        $nomi_recuperati[] = $record['nome'];
    }

    // Restituisce i nomi separati da virgola (es: "Materia A, Materia B")
    return implode(", ", $nomi_recuperati);
}
?>

<?php
session_start();
require_once __DIR__ . "/lib/dompdf/autoload.inc.php";
require_once __DIR__ . "/../collegamento_db/Letture_incrociate.php";
require_once __DIR__ . "/../collegamento_db/Collegamento.php";

// 1. Identifichiamo il tipo di utente loggato
$is_staff = isset($_SESSION['staff_codice']);
$is_student = isset($_SESSION['studente_matricola']);

$nome=  $_SESSION['matricola_nome'] ?? $_SESSION['studente_nome'] ?? 'Studente' ;
// Recuperiamo la matricola dello studente
$matricola = $_SESSION['studente_matricola'] ?? $_SESSION['matricola_selezionata'] ?? null;


if (isset($matricola)) {
    // Usiamo la funzione di collegamento standard per garantire l'accesso al database
    $connessione = Collegamento();

    // Query SQL per recuperare il nome del corso
    $sql = "SELECT corsi.nome_corso 
            FROM studenti 
            JOIN corsi ON studenti.ID_corso = corsi.ID_corso 
            WHERE studenti.matricola = :matricola";

    try {
        $query = $connessione->prepare($sql);
        $query->execute(['matricola' => $matricola]);
        $risultato = $query->fetch(PDO::FETCH_ASSOC);

        if ($risultato) {
            $corso = $risultato['nome_corso'];
        }
    } catch (Exception $errore) {
        $corso = "Errore nel recupero del corso";
    }
}

use Dompdf\Dompdf;
$dompdf = new Dompdf();


$filtri = isset($_GET['filtri']) ? json_decode($_GET['filtri'], true) : [];
$totale = isset($_GET['totale']) ? json_decode($_GET['totale'], true) : null;


$presenze_su_db = lettura_presenze($filtri);

// Questa variabile conterrà il nome del corso. Partiamo con un valore di default.
$corso = Lettura_nomi_riferimento($filtri);


// 2. Creiamo il titolo del PDF in modo unificato
if (isset($matricola)) {
    // Usiamo sempre "Studente:" per entrambi i casi
    $titolo ="Studente: " . $nome . ", " . $matricola . ". <br> Presenze relative a: " . $corso ?? "Non specificato";
} else {
    // Fallback di sicurezza
    $titolo = "Nessuna presenza trovata";
}

// Inseriamo la variabile $titolo direttamente nell'HTML del PDF
$html = '
<h1 style="text-align: center;">Registro presenze</h1>
<h2">' . $titolo . '</h2>
<table border="1" style="width:100%; border-collapse:collapse;">   
	<thead>
        <tr style="background:#f4f4f4;">
            <th>Data</th><th>Inizio</th><th>Fine</th><th>Materia</th><th>Entrata</th><th>Uscita</th>
        </tr>
    </thead>
    <tbody>
';


foreach ($presenze_su_db as $record) {
    $html .= "<tr><td>".$record['data_lezione']."</td><td>".$record['ora_inizio']."</td><td>".$record['ora_fine']."</td><td>".$record['nome_materia']."</td><td>".$record['ora_entrata']."</td><td>".$record['ora_uscita']."</td></tr>";
}

$html .= '
    </tbody>
    </table>'
;

// Generazione della tabella di riepilogo ore se il parametro $totale è valido
if (!empty($totale) && is_array($totale)) {
    $durata       = htmlspecialchars($totale['durata'] ?? 0);
    $ore_presenza = htmlspecialchars($totale['ore_presenza'] ?? 0);
    $ore_assenza  = htmlspecialchars($totale['ore_assenza'] ?? 0);

    $html .= '
    <div style="margin-top: 25px;">
        <h3 style="margin-bottom: 8px; font-size: 14px; text-transform: uppercase; color: #333333;">Riepilogo Ore</h3>
        <table border="1" style="width:100%; border-collapse:collapse; text-align:center; font-size:13px;">
            <thead>
                <tr style="background:#f4f4f4;">
                    <th style="padding: 8px;">Durata Prevista</th>
                    <th style="padding: 8px;">Ore Presenza (Arrot.)</th>
                    <th style="padding: 8px;">Stima Assenze</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 8px; font-weight: bold;">' . $durata . ' h</td>
                    <td style="padding: 8px; font-weight: bold; color: #951d3b;">' . $ore_presenza . ' h</td>
                    <td style="padding: 8px; font-weight: bold;">' . $ore_assenza . ' h</td>
                </tr>
            </tbody>
        </table>
    </div>';
}

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('registro_presenze.pdf');
?>