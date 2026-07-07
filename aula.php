<?php
require "./moduli/Comunicazione_database.php";
require "./moduli/ComponenteScannerQR.php";

session_start();
date_default_timezone_set('Europe/Rome');

// 1. ISTANZIAMO LA TUA CLASSE DATABASE (Passando il nome del DB 'scuola')
$db = new Database("scuola");

$scanner = new ComponenteScannerQR();

// Chiediamo alla classe scanner se ha intercettato una scansione QR
$dati_studente = $scanner->intercettaEAutenticaQR();

// SE IL QR È STATO LETTO E SCOMPARTITO DALLA CLASSE SCANNER:
if ($dati_studente !== false) {

    $matricola = $dati_studente['matricola'];
    $nome = $dati_studente['nome'];
    $cognome = $dati_studente['cognome'];

    $momento_attuale = time();
    $orario_formattato = date('H:i:s');

    try {
        // Estraiamo l'oggetto PDO nativo sfruttando il tuo metodo reso pubblico!
        $connNativa = $db->Collegamento();

        // 1. CERCA LEZIONE ATTIVA (Utilizziamo il metodo Query_select della tua classe)
        $query_lezione = "
            SELECT l.ID_lezione, l.ID_corso, m.nome_materia
            FROM lezioni l
            INNER JOIN materie m ON l.cod_materia = m.cod_materia
            WHERE ? BETWEEN l.TS_inizio AND l.TS_fine
        ";
        $risultato_lezione = $db->Query_select($query_lezione, [$momento_attuale]);

        if (empty($risultato_lezione)) {
            echo json_encode(['stato' => 'errore', 'messaggio' => 'Nessuna lezione attiva in questo momento.']);
            exit;
        }
        $lezione = $risultato_lezione[0];

        // 2. VERIFICA SE LO STUDENTE ESISTE DAVVERO NEL DB
        $query_db_studente = "SELECT ID_corso FROM studenti WHERE matricola = ?";
        $risultato_studente = $db->Query_select($query_db_studente, [$matricola]);

        if (empty($risultato_studente)) {
            echo json_encode(['stato' => 'errore', 'messaggio' => "Matricola $matricola non presente in anagrafica."]);
            exit;
        }
        $studente_db = $risultato_studente[0];

        // 3. CONTROLLO CORRISPONDENZA CORSI
        if ($studente_db['ID_corso'] !== $lezione['ID_corso']) {
            echo json_encode(['stato' => 'errore', 'messaggio' => "Lo studente $nome non appartiene a questo corso."]);
            exit;
        }

        // 4. CONTROLLO TIMBRATURE PREGRESSE PER LA LEZIONE ATTIVA
        $query_presenza = "SELECT ora_entrata, ora_uscita FROM presenze WHERE ID_lezione = ? AND matricola = ?";
        $risultato_presenza = $db->Query_select($query_presenza, [$lezione['ID_lezione'], $matricola]);

        if (empty($risultato_presenza)) {
            // SCENARIO 1: Nessun record -> Registriamo l'Entrata via PDO nativo bypassando il bug di ritorno di Query_insert
            $sql_inserimento = "INSERT INTO presenze (ID_lezione, matricola, ora_entrata, ora_uscita) VALUES (?, ?, ?, '00:00:00')";
            $stmt_ins = $connNativa->prepare($sql_inserimento);
            $stmt_ins->execute([$lezione['ID_lezione'], $matricola, $orario_formattato]);

            echo json_encode(['stato' => 'successo', 'messaggio' => "ENTRATA REGISTRATA: $nome $cognome"]);
            exit;
        } else {
            $presenza = $risultato_presenza[0];

            if (empty($presenza['ora_uscita']) || $presenza['ora_uscita'] === '00:00:00') {
                // SCENARIO 2: C'è l'entrata ma non l'uscita -> Utilizziamo l'oggetto PDO nativo della tua classe
                // Questo evita di bloccarci sul vincolo a 1 elemento del tuo Query_update
                $sql_diretto_update = "UPDATE presenze SET ora_uscita = ? WHERE ID_lezione = ? AND matricola = ?";
                $stmt_upd = $connNativa->prepare($sql_diretto_update);
                $stmt_upd->execute([$orario_formattato, $lezione['ID_lezione'], $matricola]);

                echo json_encode(['stato' => 'successo', 'messaggio' => "USCITA REGISTRATA: $nome $cognome"]);
                exit;
            } else {
                // SCENARIO 3: Entrata e Uscita già registrate
                echo json_encode(['stato' => 'attenzione', 'messaggio' => "Operazione rifiutata: $nome $cognome ha già timbrato l'uscita."]);
                exit;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['stato' => 'errore', 'messaggio' => 'Errore di sistema aula: ' . $e->getMessage()]);
        exit;
    }
}
