<?php
session_start();
// Includi la libreria che hai scaricato al punto 1
require __DIR__ . '/../vendor/autoload.php';

use FPDF\FPDF;

// 1. CONTROLLO ACCESSI
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'staff') {
    die("Accesso negato.");
}



$matricola = $_GET['matricola_studente'];

// 2. CONNESSIONE AL DATABASE
try {
    $conn = new PDO("mysql:host=localhost;dbname=scuola", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione: " . $e->getMessage());
}

// 3. RECUPERO DATI DELLO STUDENTE
$stmt_studente = $conn->prepare("SELECT Nome, Cognome, cod_corso FROM studenti WHERE Matricola = ?");
$stmt_studente->execute([$matricola]);
$studente = $stmt_studente->fetch(PDO::FETCH_ASSOC);

if (!$studente) {
    die("Errore: Studente non trovato nel database.");
}

// 4. RECUPERO STORICO LEZIONI E PRESENZE DI UNO STUDENTE / REPORT COMPLETO
// Controlliamo se è stato richiesto un singolo studente o il report generale del corso
if (isset($_GET['matricola_studente'])) {

    $matricola = $_GET['matricola_studente'];

    // Recupero dati dello studente
    $stmt_studente = $conn->prepare("SELECT nome, cognome, ID_corso FROM studenti WHERE matricola = ?");
    $stmt_studente->execute([$matricola]);
    $studente = $stmt_studente->fetch(PDO::FETCH_ASSOC);

    if (!$studente) {
        die("Errore: Studente non trovato nel database.");
    }

    // Query specifica per lo studente (Visualizza le SUE presenze/assenze)
    $query = "
        SELECT 
            c.TS_inizio, 
            c.TS_fine, 
            m.nome_materia, 
            p.ora_entrata, 
            p.ora_uscita
        FROM lezioni c
        INNER JOIN materie m ON c.cod_materia = m.cod_materia
        LEFT JOIN presenze p ON c.ID_lezione = p.ID_lezione AND p.matricola = ?
        WHERE c.ID_corso = ?
        ORDER BY c.TS_inizio ASC
    ";
    $stmt_presenze = $conn->prepare($query);
    $stmt_presenze->execute([$matricola, $studente['ID_corso']]);
    $storico_lezioni = $stmt_presenze->fetchAll(PDO::FETCH_ASSOC);
} else if (isset($_POST['ID_corso'])) { // REPORT COMPLETO DEL CORSO

    $id_corso = $_POST['ID_corso'];

    // Query per il report generale: mostra le lezioni e CONTA quanti presenti e assenti ci sono stati
    $query = "
        SELECT 
            c.TS_inizio, 
            c.TS_fine, 
            m.nome_materia,
            COUNT(p.matricola) AS totale_presenti
        FROM lezioni c
        INNER JOIN materie m ON c.cod_materia = m.cod_materia
        LEFT JOIN presenze p ON c.ID_lezione = p.ID_lezione
        WHERE c.ID_corso = ?
        GROUP BY c.ID_lezione
        ORDER BY c.TS_inizio ASC
    ";

    $stmt_presenze = $conn->prepare($query);
    $stmt_presenze->execute([$id_corso]);
    $storico_lezioni = $stmt_presenze->fetchAll(PDO::FETCH_ASSOC);

    // Nota: Nel ciclo di stampa PDF dovrai adattare le celle per mostrare il contatore generale anziché l'orario del singolo studente
} else {
    die("Errore: Dati insufficienti per generare il report.");
}


// 5. CREAZIONE FISICA DEL PDF
$pdf = new FPDF();
$pdf->AddPage();

// Titolo del documento
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Report Presenze e Assenze - AgenForm', 0, 1, 'C');
$pdf->Ln(5);

// Informazioni dello studente
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Studente: ' . utf8_decode($studente['Cognome'] . ' ' . $studente['Nome']), 0, 1);
$pdf->Cell(0, 8, 'Matricola: ' . $matricola, 0, 1);
$pdf->Cell(0, 8, 'Codice Corso: ' . ($studente['cod_corso'] ? $studente['cod_corso'] : 'N/A'), 0, 1);
$pdf->Ln(10);

// Intestazione della Tabella
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(35, 10, 'Data', 1);
$pdf->Cell(65, 10, 'Materia', 1);
$pdf->Cell(25, 10, 'Orario', 1);
$pdf->Cell(40, 10, 'Entrata / Uscita', 1);
$pdf->Cell(25, 10, 'Stato', 1);
$pdf->Ln();

// Riempimento dei Dati
$pdf->SetFont('Arial', '', 10);

if (count($storico_lezioni) > 0) {
    foreach ($storico_lezioni as $riga) {
        $data_lezione = date('d/m/Y', $riga['TS_inizio']);
        $orario_lezione = date('H:i', $riga['TS_inizio']) . '-' . date('H:i', $riga['TS_fine']);

        // Determiniamo se è Assente o Presente
        if ($riga['ora_entrata']) {
            $stato = 'Presente';
            $uscita = ($riga['ora_uscita'] === '00:00:00') ? 'In corso' : date('H:i', strtotime($riga['ora_uscita']));
            $orario_effettivo = date('H:i', strtotime($riga['ora_entrata'])) . ' / ' . $uscita;
        } else {
            $stato = 'Assente';
            $orario_effettivo = '---';
        }

        // Stampiamo le celle
        $pdf->Cell(35, 10, $data_lezione, 1);
        $pdf->Cell(65, 10, utf8_decode($riga['nome_materia']), 1);
        $pdf->Cell(25, 10, $orario_lezione, 1);

        // --- INIZIO MODIFICA CONDIZIONALE ---
        if (isset($matricola) && $matricola !== null) {
            // Se c'è una matricola, stampi l'orario di ingresso/uscita del singolo studente
            $pdf->Cell(40, 10, $orario_effettivo, 1, 0, 'C');
        } else {
            // Se è il report completo del corso, stampi il totale dei presenti
            $pdf->Cell(40, 10, "Presenti: " . ($riga['totale_presenti'] ?? 0), 1, 0, 'C');
        }
        // --- FINE MODIFICA CONDIZIONALE ---

        // Cambiamo colore al testo in base allo stato (funziona solo se stai visualizzando lo studente)
        if (isset($matricola) && $stato == 'Assente') {
            $pdf->SetTextColor(200, 0, 0); // Rosso
        } else {
            $pdf->SetTextColor(0, 150, 0); // Verde
        }

        $pdf->Cell(25, 10, $stato, 1, 0, 'C');
        $pdf->SetTextColor(0, 0, 0); // Riportiamo il colore al nero
        $pdf->Ln(); // Andiamo a capo
    }
} else {
    // Se la query non restituisce risultati
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(190, 10, 'Nessuna lezione trovata o studente non assegnato a un corso valido.', 1, 1, 'C');
}

// Spinge il file al browser per la visualizzazione/stampa (la "I" significa Inline)
$pdf->Output('I', 'Presenze_' . $matricola . '.pdf');
