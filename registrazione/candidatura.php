<?php
require_once __DIR__ . "/../mail/Strumenti_email.php";
require_once __DIR__ . "/../collegamento_db/Collegamento.php";
require_once __DIR__ . "/../collegamento_db/Scrittura_registrazione.php";

require_once __DIR__ . "/../materiali/libreria_debug.php";

/** 
 * Questa pagina utilizza metodi e classi provenienti da un'altro progetto.
 * Per tal motivol, la struttura del codice che comunica con il database è diversa dal reso del progetto
 */


$candidatura_ok = false;
$nome_corso = $_GET['corso'] ?? "";
$ID_corso = $_GET['id'] ?? "";

// Controlliamo se l'utente ha premuto il pulsante inviando il form in POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = $_POST['Nome'] ?? "";
    $cognome = $_POST['Cognome'] ?? "";
    $email = $_POST['Email'] ?? "";
    $telefono = $_POST['Telefono'] ?? "";


    $timestamp = date('Y-m-d H:i:s');


    // Raccolta ed Inserimento dati in tabella pre_iscrizioni
    $dati = array(
        'nome' => $nome,
        'cognome' => $cognome,
        'email' => $email,
        'data_iscrizione' => $timestamp,
        'stato_iscrizione' => "elaborazione",
        'ID_corso' => (int)$ID_corso
    );

    // la funzione Query_insert rende l'id dell'ultiam candidatura genrata con autoincrement dal database
    $id_candidatura = Query_insert("candidature", $dati) ?? "";
    console_log("id_candidaturarecuperato: ". json_encode($id_candidatura));
    
    // scrittura ed invio mail per studente
    $destinatario = $nome . " " . $cognome;
    $soggetto = 'Richiesta di registrazione inviata - Registrazione approvata';
    $testoMail = "
        <h2>Ciao $destinatario,</h2>
        <p>Abbiamo ricevuto la tua richiesta di registrazione <br>
        al corso $nome_corso. in data <strong>$timestamp</strong>.</p><br><br>
        <br>
        <p>La contatteremo presto per organizzare un collquio conoscitivo</p><br>
        <p>Cordiali saluti</p>
        --------------------------------------------------------------------
        <h2>Ciao $destinatario,</h2>
        <p>Siamo felici di comunicarti che la tua richiesta di iscrizione al corso <strong>$nome_corso</strong>, è stata <strong>approvata</strong>!</p>
        <p>Per completare il processo e registrarti ufficialmente come studente, clicca sul link qui sotto:</p>
        <p><a href='http://localhost/sandbox_php/registrazione/registrazione_studente.php?candidato=$id_candidatura' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; border-radius: 4px;'>Completa la Registrazione</a></p>
        <br> 
        <p>Se il pulsante non funziona, copia e incolla questo link nel tuo browser:<br>
        http://localhost/sandbox_php/registrazione/registrazione_studente.php?candidato=$id_candidatura</p>
        <br>
        <p>Benvenuto a bordo!</p>
        <p>Cordiali saluti</p>
    ";
    /*      
    - Aggiornamento sulla tua richiesta(rifiutata)   
        <h2>Ciao $destinatario,</h2>
        <p>Ti ringraziamo per l'interesse dimostrato verso il corso <strong>$corso</strong>.</p>
        <p>Purtroppo, a seguito delle nostre valutazioni (o del raggiungimento del numero massimo di partecipanti),<br> 
        non ci è possibile approvare la tua richiesta di iscrizione per questa sessione.</p>
        <br>
        <p>Ti auguriamo il meglio per il tuo percorso professionale e di studi.</p>
        <p>Cordiali saluti</p> 
    */

    

    // scrittura ed invio mail per segreteria
    $soggetto_Segr = 'CANDIDATURA AL CORSO' . $nome_corso;
    $testoMail_Segr =  '
        <h2>Nuova preiscrizione ricevuta</h2>
        <p>Si comunica che in data <strong>' . $timestamp . '</strong> è stata registrata una nuova richiesta di preiscrizione.</p>
        <table id="dati-preiscrizione" style="border-collapse: collapse; width: 100%; max-width: 500px;">
          <tr data-field="ID_richiesta">
            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Data:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;" class="value">' . $id_candidatura . '</td>
          </tr>
          <tr data-field="nominativo">
            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Richiedente:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;" class="value">' . $nome . " " . $cognome . '</td>
          </tr>
          <tr data-field="corso">
            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Corso:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;" class="value">' . $nome_corso . '</td>
          </tr>
          <tr data-field="email">
            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Contatti:</strong></td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;" class="value"><strong>' . $email . '</strong></td>
          </tr>
        </table>
        <br>
        <p>Il sistema ha già provveduto ad inviare la mail di conferma allo studente.</p>
        <hr style="border: 0; border-top: 1px solid #ccc; margin: 20px 0;">
        <p style="font-size: 12px; color: #666;">Mail automatica generata dal Sistema di Preiscrizioni.</p>
    ';

    $mail = new Strumenti_email;
    if (isset($id_candidatura)) {
        $mail->Invio_email($email, $destinatario, $soggetto, $testoMail);
        $mail->Invio_email('notifiche.agenform@gmail.com', "segreteria Agenform", $soggetto_Segr, $testoMail_Segr);
    }
    
    // Appare il pop-up di conferma a fine procedura
    if ($mail) {
        echo ("
        <script>
            
            alert('Registrazione avvenuta con successo! riceverai una mail di conferma e sarai presto contattato dal nostro staff');

            // Reindirizza l'utente alla pagina di login
            window.location.href = './../index.php';
        </script>
        ");
    }
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

    <style>
        .lato-contenuto .campo-input {
            margin-bottom: 24px;
            /* Aumentato il margine inferiore rispetto all'originale */
            padding: 13px 14px;
            /* Campi leggermente più alti per dare respiro */
        }

        .lato-contenuto .bottone-rosso {
            margin-top: 20px;
            /* Spinge il bottone un po' più in giù */
            padding: 15px;
            /* Ingrandisce leggermente l'area cliccabile */
        }

        .lato-contenuto .sottotitolo {
            margin-bottom: 35px;
            /* Distanzia maggiormente il titolo dai campi */
        }
    </style>
</head>

<body>
    <?php
    require_once __DIR__ . "/../navbar.php";
    navbar([
        ['<a href="./../index.php" class=" btn btn-login">Torna al Login</a>'],
        ['<a href="./landing.php" class=" btn bottone-menu">Corsi</a>']
    ],"Modulo di candidatura");
    ?>


    <main class="zona-centrale">
        <div class="scatola-registrazione">

            <div class="lato-contenuto">
                <h1>Modulo di candidatura al corso <br> <?php echo $nome_corso; ?></h1>
                <p class="sottotitolo">Inserire i dati richiesti</p>

                <form action='' method="post">
                    <div class="riga-campi">
                        <div class="mezzo-campo">
                            <label class="scritta-istruzione">Nome</label>
                            <input type="text" name="Nome" class="campo-input" placeholder="Nome" required>
                        </div>
                        <div class="mezzo-campo">
                            <label class="scritta-istruzione">Cognome</label>
                            <input type="text" name="Cognome" class="campo-input" placeholder="Cognome" required>
                        </div>
                    </div>

                    <label class="scritta-istruzione">Indirizzo Email</label>
                    <input type="email" name="Email" class="campo-input" placeholder="Inserire indirizzo email" required>

                    <label class="scritta-istruzione">Numero di telefono</label>
                    <input type="tel" name="Telefono" class="campo-input" placeholder="Inserire numero di telefono">

                    <label class="scritta-istruzione">Password</label>
                    <input type="password" class="campo-input" placeholder="Inserire password">

                    <button type="submit" class="bottone-rosso">CANDIDATI</button>
                </form>
            </div>

            <div class="lato-immagine"></div>

        </div>
    </main>


</body>

</html>