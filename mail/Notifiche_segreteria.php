<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../collegamento_db/Letture_incrociate.php";
require_once __DIR__ . "/../collegamento_db/Collegamento.php";

require_once __DIR__ . "/../materiali/libreria_debug.php";


use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function Notifica_errore_scansioni( ?array $presenze_aperte = []){
        console_log("presenze da inviare via mail: " . json_encode($presenze_aperte));

    if (! isset($presenze_aperte)) {
        echo "problema di ricezione dei dati da Verifica_presenze.php";
    }
    
    require_once __DIR__ . "/Strumenti_email.php";

    $mail = new Strumenti_email();

    $autoinvio_indirizzo = $_ENV['SMTP_FROM_EMAIL'];
    $autoinvio_nome = $_ENV['SMTP_FROM_NAME'];

    $righe_tabella = "";

    // lettura del file html che compone il corpo della tabella
    $corpo_mail = file_get_contents(__DIR__ . '/testi_mail/notifica_errore_scansioni.html');

    // genereazione delle righe di codice html adatto ad essere inserito nel corpo della mail
    /*{"ID_lezione":535,"matricola":"20260012","ora_entrata":"2026-07-30 16:48:20","ora_uscita":null,"permessi":0,"cod_aula":"114b","cod_materia":"M011"}*/
    foreach($presenze_aperte as $riga){

        $query = "SELECT lezioni.TS_inizio FROM `lezioni` where lezioni.ID_lezione = ?;";

        $richiesta_db = Collegamento();
        $query_prep   = $richiesta_db->prepare($query);
        $query_prep->execute([$riga['ID_lezione']]);
        $TS_lezione = $query_prep->fetchColumn();
        $richiesta_db = null;
        
        $data_lezione = separa_DataOra($TS_lezione);

        $righe_tabella .= "
        <tr>
            <td style='padding: 8px; border-bottom: 1px solid #ddd;'>". $riga['matricola']. "</td>
            <td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . $riga['cod_materia'] . "</td>
            <td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . $riga['cod_aula'] . "</td>
            <td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . $data_lezione['data'] . "</td>
            <td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . $riga['ora_entrata'] . "</td>
        </tr>
        ";
    }


    // inserisce le righe generate al posto del placeholder nel corpo della mail
    $corpo_mail = str_replace('{{RIGHE_TABELLA}}', $righe_tabella, $corpo_mail);

    $mail->Invio_email($autoinvio_indirizzo, $autoinvio_nome, 'anomalia nel registro presenze', $corpo_mail);
    
}
?>