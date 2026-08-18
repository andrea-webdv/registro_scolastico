<?php

require_once __DIR__ . "/../mail/Strumenti_email.php";
require_once __DIR__ . "/../crea_qrcode/generazione_qr.php";
require_once __DIR__ . "/../collegamento_db/Collegamento.php";
require_once __DIR__ . "/../collegamento_db/Scrittura_registrazione.php";

require_once __DIR__ . "/../materiali/libreria_debug.php";

$registrazione_ok = false;
$candidato = $_GET['candidato'] ?? null;
$dati_candidato = []; // Inizializziamo come array vuoto
$errore = null;

console_log("recupero l'id del candidato? " . $candidato);


// Eseguiamo la query solo se il parametro nell'URL è presente e non vuoto
if (!empty($candidato)) {
    try {
        // 2. Creazione della connessione PDO nativa
        $conn = new PDO("mysql:host=localhost;port=3306;dbname=scuola_sandbox", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 3. Preparazione della query (Nota: rimosso gli apici singoli da `candidature` e usato il segnaposto sicuro ?)
        $sql = "SELECT `nome`, `cognome`, `email`, `ID_corso` FROM `candidature` WHERE `ID_richiesta` = ?";
        $stmt = $conn->prepare($sql);

        // 4. Esecuzione passando la variabile in sicurezza per evitare problemi di tipo dato (stringa/numero)
        $stmt->execute([$candidato]);

        // 5. Recupero di tutti i record trovati
        $dati_candidato = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dati_candidato = $dati_candidato[0];
    } catch (PDOException $e) {
        // Gestione dell'errore in caso di fallimento del database
        $errore = "Errore nel recupero della candidatura";
        $dati_candidato = [];
    }
} else {
    session_start();

    if (!isset($_SESSION['staff_codice']) &&  $_SERVER["REQUEST_METHOD"] != "POST") {
        header("Location: ./../index.php?e=non_loggato");
        exit();
    }
}


// Controlliamo se l'utente ha premuto il pulsante inviando il form in POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //assegnazione da lettura del post del form
    $nome = $_POST['Nome'] ?? "";
    $cognome = $_POST['Cognome'] ?? "";
    $email = $_POST['Email'] ?? "";
    $ID_corso = $_POST['ID_corso'] ?? "";


    // Raccolta ed Inserimento dati in tabella studenti, so potrebbero ottenenre tramite GET se raccolti dentro la mail di conferma
    $dati_insert = array(
        'nome' => $nome,
        'cognome' => $cognome,
        'email' => $email,
    );

    $qr_binario = "";

    try {

        $invio_registrazione = Collegamento();

        $registrazione_matricola = Query_insert("studenti", $dati_insert);

        console_log("ricevo la matricola?" . $registrazione_matricola);


        // la funzione Query_insert rende la matricola genrata dal database
        $qr_matricola = generazione_qr($nome, $cognome, $registrazione_matricola);

        console_log("qr generato?" . json_encode($qr_matricola));

        // MANCANTE: INSERT NELLA TABELLA STUDENTI_CORSI DELLA COPPIA $registrazione_matricola - $ID_corso
        $query_corso = "INSERT INTO studenti_corsi (`matricola`,`ID_corso`) VALUES (?,?)";

        require_once __DIR__ . "/../materiali/libreria_debug.php";

        $registrazione_completa = Query_precompiled($query_corso, [$registrazione_matricola, $ID_corso]);

        // creazione ed invio della mail con codice qr;
        $qr_binario = file_get_contents($qr_matricola["url"]);


        if (!$registrazione_completa) {
            throw new Exception("Errore nell'inserimento della candidatura: ");
        }
    } catch (\Throwable $th) {
        echo ("<p>" . json_encode($th) . "</p>");
    }


    # un inserimento semplice come questo non è adatto agli standard di sicurezza delle mail
    # <img src='cid:qrcode_pre' alt='Codice QR'>
    $destinatario = $nome . " " . $cognome;
    $soggetto = 'Richiesta di registrazione inviata - Registrazione approvata';
    $testoMail = "
    <h2>Ciao $destinatario,</h2>
    <p>La tua iscrizione è stata completata con successo! Ora sei ufficialmente registrato.</p>
    <p>Qui sotto trovi il tuo <strong>QR Code identificativo personale</strong>. Questo codice è strettamente personale e ti servirà per accedere alla tua area riservata ed effettuare il login rapido tramite la webcam del PC.</p>
    
    <br>
    <div style='text-align: center; margin: 20px 0;'>
        <img src='cid:CID_placeholder' alt='QR Code Studente' style='border: 1px solid #dddddd; padding: 10px; border-radius: 8px; background-color: #ffffff;'>
    </div>
    <br>
    
    <p>Ti consigliamo di conservare questa email o di salvare l'immagine del codice sul tuo smartphone per averla sempre a portata di mano.</p>
    <br>
    <p>Benvenuto a bordo!</p>
    <p>Cordiali saluti</p>
    ";

    $mail = new Strumenti_email;
    $mail->Invio_email($email, $destinatario, $soggetto, $testoMail, $qr_binario, "QR studente");

    $registrazione_ok = true;
}

$sql_options = [];

if (empty($candidato)) {

    $sql_options = "SELECT `ID_corso`, `nome_corso`, `cod_edizione` FROM `corsi` WHERE `accetta_iscrizioni` = ? ORDER BY `nome_corso` ASC";
    $elenco_corsi = Query_precompiled($sql_options, [1]);
} else {
    $sql_options = "SELECT `ID_corso`, `nome_corso`, `cod_edizione` FROM `corsi` WHERE `ID_corso` = ?";
    $elenco_corsi = Query_precompiled($sql_options, [$dati_candidato['ID_corso']]);
}

console_log("corsi recuperati:" . json_encode($elenco_corsi));

if ($registrazione_ok) {
    echo ("
    <script>
        
        alert('Registrazione avvenuta con successo! riceverai una mail di conferma e sarai presto contattato dal nostro staff');

        // Reindirizza l'utente alla pagina di login
        window.location.href = './../index.php';
    </script>
    ");
}

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenform Registra - Registrazione</title>
    <link class="css-link" rel="stylesheet" href="./../css/agenform.css">
    <link rel="stylesheet" href="./../css/landing.css">
    <link rel="stylesheet" href="./../css/registrazione.css">
</head>

<body>
    <?php
    require_once __DIR__ . "/../navbar.php";
    navbar([
        ['<a href="./../index.php" class="btn btn-login">Torna al Login</a>']
    ], "Modulo di registrazione");
    ?>

    <?php
    if (isset($errore)) {
        echo $errore;
    }
    ?>

    <main class="zona-centrale">
        <div class="scatola-registrazione">

            <div class="lato-contenuto">
                <h1>Registrazione</h1>
                <p class="sottotitolo">Inserire i dati richiesti</p>

                <form action="./registrazione_studente.php" method="post">
                    <div class="riga-campi mezzo-campo">
                        <div>
                            <label for="nome" class="scritta-istruzione">Nome</label>
                            <input type="text" name="Nome" id="nome" class="campo-input" placeholder="Nome" required>
                        </div>
                        <div>
                            <label for="cognome" class="scritta-istruzione">Cognome</label>
                            <input type="text" name="Cognome" id="cognome" class="campo-input" placeholder="Cognome" required>
                        </div>
                    </div>
                    <input type="hidden" name='ID_corso' value=<?php echo $dati_candidato['ID_corso'] ?? "" ?>>



                    <div class="riga-campi">
                        <!-- da rimpiazzare con lettura dal db.corsi e compilazione -->
                        <label for="edizione" class="scritta-istruzione">Corso</label>
                        <select name="Corso_Edizione" id="edizione" class="campo-input">

                            <option value="">-- Seleziona un corso --</option>

                            <?php
                            if (!empty($elenco_corsi)):
                                foreach ($elenco_corsi as $corso):
                                    $valore_option = $corso['ID_corso'];
                                    $etichetta_option = $corso['nome_corso'] . " - Ed. " . $corso['cod_edizione'];
                            ?>
                                    <option value="<?php echo $valore_option ?>" <?php if (isset($dati_candidato['ID_corso'])){ if ($corso['ID_corso'] == $dati_candidato['ID_corso']) {
                                                                                        echo 'selected';
                                                                                    }} ?>>
                                        <?php echo $etichetta_option ?>
                                    </option>

                            <?php
                                endforeach;
                            endif;
                            ?>
                            <!--  campi di prova
                        <option value="c001|Aprile">Corso 1</option>
                        <option value="c002|Aprile">Corso 2</option>
                        <option value="c003|Gennaio">Corso 3</option>
                        <option value="c004|Novembre">Corso 4</option> -->
                        </select>
                    </div>

                    <div class="riga-campi">
                        <label for="email" class="scritta-istruzione">Indirizzo Email</label>
                        <input type="email" name="Email" id="email" class="campo-input" placeholder="Inserire indirizzo email" required>
                    </div>

                    <div class="riga-campi">
                        <label for="telefono" class="scritta-istruzione">Numero di telefono</label>
                        <input type="tel" id="telefono" class="campo-input" placeholder="Inserire numero di telefono">
                    </div>

                    <div class="riga-campi">
                        <label for="pass" class="scritta-istruzione">Password</label>
                        <input type="password" id="pass" class="campo-input" placeholder="Inserire password">
                    </div>

                    <button type="submit" class="bottone-rosso">REGISTRATI</button>
                </form>
            </div>

            <div class="lato-immagine"></div>

        </div>
    </main>

    <?php if (!empty($dati_candidato) && isset($dati_candidato)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Recupera la prima riga dei risultati estratti dal DB convertendola in JSON
                const candidato = <?php echo json_encode($dati_candidato); ?>;

                if (candidato) {
                    // Seleziona i campi di input nel form utilizzando l'attributo 'name'
                    const campoNome = document.querySelector('input[name="Nome"]');
                    const campoCognome = document.querySelector('input[name="Cognome"]');
                    const campoEmail = document.querySelector('input[name="Email"]');

                    // Se l'elemento esiste nella pagina e il dato è presente nel database, riempie il campo
                    if (campoNome && candidato.nome) {
                        campoNome.value = candidato.nome;
                    }
                    if (campoCognome && candidato.cognome) {
                        campoCognome.value = candidato.cognome;
                    }
                    if (campoEmail && candidato.email) {
                        campoEmail.value = candidato.email;
                    }

                    // Nota: Il campo <select> per "Corso_Edizione" è già gestito nativamente dal tuo PHP con l'attributo "selected".
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>