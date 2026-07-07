<?php
// Avvia la sessione per eventuali messaggi futuri e imposta il fuso orario di Roma
session_start();
date_default_timezone_set('Europe/Rome');

// CONNESSIONE AL DATABASE
try {
    $conn = new PDO("mysql:host=localhost;dbname=scuola", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione: " . $e->getMessage());
}

// RICEZIONE DATI DAL LETTORE QR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricola'])) {
    
    $matricola_letta = trim($_POST['matricola']);
    $momento_attuale = time(); 
    $orario_formattato = date('H:i:s');

    try {
        // --- STEP 1: CERCA LEZIONE IN CORSO ---
        $query_lezione_attiva = "
            SELECT c.ID_lezione, c.ID_materia, m.cod_corso, m.nome_materia
            FROM calendari c
            INNER JOIN materie m ON c.ID_materia = m.ID_materia
            WHERE ? BETWEEN c.TS_inizio AND c.TS_fine
        ";
        $comando = $conn->prepare($query_lezione_attiva);
        $comando->execute([$momento_attuale]);
        
        $lezione_in_corso = $comando->fetch(PDO::FETCH_ASSOC);

        if (!$lezione_in_corso) {
            echo json_encode(['stato' => 'errore', 'messaggio' => 'Nessuna lezione in corso in questo orario.']);
            exit;
        }

        // --- STEP 2: VERIFICA STUDENTE ---
        $query_dati_studente = "SELECT cod_corso, Nome, Cognome FROM studenti WHERE Matricola = ?";
        $comando_studente = $conn->prepare($query_dati_studente);
        $comando_studente->execute([$matricola_letta]);
        $studente = $comando_studente->fetch(PDO::FETCH_ASSOC);

        if (!$studente) {
            echo json_encode(['stato' => 'errore', 'messaggio' => 'Matricola non presente nel database.']);
            exit;
        }

        // --- STEP 3: CONTROLLO CORRISPONDENZA CORSI ---
        if ($studente['cod_corso'] !== $lezione_in_corso['cod_corso']) {
            echo json_encode(['stato' => 'errore', 'messaggio' => 'Studente non iscritto al corso di questa lezione!']);
            exit;
        }

        // --- STEP 4: GESTIONE PRESENZE (ENTRATA/USCITA) ---
        $query_cerca_presenza = "SELECT ora_entrata, ora_uscita FROM presenze WHERE ID_lezione = ? AND Matricola = ?";
        $comando_presenza = $conn->prepare($query_cerca_presenza);
        $comando_presenza->execute([$lezione_in_corso['ID_lezione'], $matricola_letta]);
        $presenza_passata = $comando_presenza->fetch(PDO::FETCH_ASSOC);

        if (!$presenza_passata) {
            // REGISTRO ENTRATA
            $query_inserisci_entrata = "INSERT INTO presenze (ID_lezione, Matricola, ora_entrata, ora_uscita) VALUES (?, ?, ?, '00:00:00')";
            $comando_inserimento = $conn->prepare($query_inserisci_entrata);
            $comando_inserimento->execute([$lezione_in_corso['ID_lezione'], $matricola_letta, $orario_formattato]);

            echo json_encode(['stato' => 'successo', 'messaggio' => "ENTRATA: {$studente['Nome']} {$studente['Cognome']} ({$lezione_in_corso['nome_materia']})"]);
            exit;

        } else {
            // REGISTRO USCITA (se non è già stata registrata)
            if (empty($presenza_passata['ora_uscita']) || $presenza_passata['ora_uscita'] === '00:00:00') {
                
                $query_aggiorna_uscita = "UPDATE presenze SET ora_uscita = ? WHERE ID_lezione = ? AND Matricola = ?";
                $comando_aggiornamento = $conn->prepare($query_aggiorna_uscita);
                $comando_aggiornamento->execute([$orario_formattato, $lezione_in_corso['ID_lezione'], $matricola_letta]);

                echo json_encode(['stato' => 'successo', 'messaggio' => "USCITA: {$studente['Nome']} {$studente['Cognome']} ({$lezione_in_corso['nome_materia']})"]);
                exit;

            } else {
                echo json_encode(['stato' => 'attenzione', 'messaggio' => 'Uscita già registrata per questa lezione.']);
                exit;
            }
        }

    } catch (PDOException $e) {
        echo json_encode(['stato' => 'errore', 'messaggio' => 'Errore di sistema: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lettore Presenze QR - AgenForm</title>
    
    <link rel="stylesheet" href="./css/agenform.css">
    
    <script src="https://unpkg.com/html5-qrcode"></script>
    
    <style>
        /* Regole specifiche per questa pagina che si fondono con il CSS globale */
        main {
            flex: 1;
            display: flex;
            align-items: center; /* Centra verticalmente */
            justify-content: center; /* Centra orizzontalmente */
            padding: 20px;
        }

        .pannello-lettore {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 6px;
            border-top: 6px solid var(--rosso_brand);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px; /* Più stretto per sembrare un terminale */
            text-align: center;
        }

        .titolo-sezione {
            color: var(--rosso_brand);
            margin-bottom: 10px;
            font-size: 22px;
            text-transform: uppercase;
        }

        .sottotitolo {
            color: #555555;
            font-size: 14px;
            margin-bottom: 25px;
        }

        #zona-scanner {
            width: 100%;
            margin-bottom: 20px;
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid #cccccc;
        }

        /* Stili per le notifiche a schermo (ripresi dal tuo stile per gli errori) */
        .messaggio-schermo {
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 20px;
            text-align: center;
            display: none;
        }
        
        .successo { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1.5px solid #c3e6cb; 
        }
        .errore { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1.5px solid #f5c6cb; 
        }
        .attenzione { 
            background-color: #fff3cd; 
            color: #856404; 
            border: 1.5px solid #ffeeba; 
        }
    </style>
</head>
<body>

    <header class="barra-superiore">
        <div class="spazio-logo">
            <img src="./materiali/Logo_AgenForm_2.png" alt="Logo AgenForm" class="immagine-logo">
        </div>
        <nav class="gruppo-bottoni">
            <a href="#" class="bottone-menu attivo">Terminale Presenze</a>
        </nav>
    </header>

    <main>
        <div class="pannello-lettore">
            <h2 class="titolo-sezione">Lettore Badge</h2>
            <p class="sottotitolo">Inquadra il QR Code dello studente</p>
            
            <div id="zona-scanner"></div>

            <div id="box-messaggi" class="messaggio-schermo"></div>
        </div>
    </main>

    <script>
        function mostraMessaggio(testo, tipo) {
            const box = document.getElementById('box-messaggi');
            box.innerText = testo;
            box.className = 'messaggio-schermo ' + tipo; 
            box.style.display = 'block';
            
            setTimeout(() => {
                box.style.display = 'none';
            }, 4000); // Scompare dopo 4 secondi
        }

        function onScanSuccess(decodedText, decodedResult) {
            html5QrcodeScanner.pause();

            const formData = new FormData();
            formData.append('matricola', decodedText);

            fetch('lettura_qr.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                mostraMessaggio(data.messaggio, data.stato);
                
                // Riavvia lo scanner dopo 2.5 secondi
                setTimeout(() => {
                    html5QrcodeScanner.resume();
                }, 2500);
            })
            .catch(error => {
                mostraMessaggio("Errore di comunicazione col database.", "errore");
                setTimeout(() => { html5QrcodeScanner.resume(); }, 2500);
            });
        }

        const html5QrcodeScanner = new Html5QrcodeScanner(
            "zona-scanner", 
            { fps: 10, qrbox: {width: 250, height: 250} }, 
            false
        );
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>
</html>