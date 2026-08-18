<?php
session_start();

// 1. Controllo di sicurezza: reindirizza se non loggato
if (!isset($_SESSION['studente_matricola'])) {
    header("Location: ../index.php?e=non_loggato");
    exit();
}

require_once __DIR__ . "/lib/dompdf/autoload.inc.php";
require_once __DIR__ . "/../collegamento_db/Collegamento.php";

use Dompdf\Dompdf;

$matricola = $_SESSION['studente_matricola'];
$nome_studente = $_SESSION['studente_nome'] ?? 'Studente';


# è necessario pulire il json ricevuto, per via di modifiche effettuate in automatico da xampp. esempio di lezione ricevuta:
#{\"giorno\":\"Martedi\",\"data\":\"27-07-2026\",\"materia\":\"Crittografia\",\"orario\":\"08:30 - 12:30\"}

$raw_json = $_POST["calendario"] ?? '[]';
$html = '';

// Pulizia: rimozione dei backslash di escape aggiunti dal server
if (is_string($raw_json)) {
    $raw_json = stripslashes($raw_json);
}

$calendario_settimana = json_decode($raw_json, true);

$html = '
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
';

//  intercetta dati diversi da un array o null derivanti dalla decodifica di json corrotti
if (!is_array($calendario_settimana) || empty($calendario_settimana)) {

    $html = '
    <style>
        .errore-box { 
            border: 2px solid #a30000; 
            padding: 20px; 
            background-color: #fff8f8; 
            border-radius: 5px; 
        }
        h1{
            color: #a30000; 
            margin-top: 0;
        }
    </style>
    </head>
    <body>
        <h1>Errore nella ricezione del calendario</h1>
    ';

    $html .= '
        </body>
        </html>
    ';


} else {

    $html = '
        <style>
            .header {
                text-align: center;
                margin-bottom: 25px;
            }
            .header h1 {
                color: #a30000;
                margin-bottom: 5px;
            }
            .header p {
                font-size: 14px;
                color: #555555;
                margin: 3px 0;
            }
            .modale-contenuto {
                background-color: #ffffff;
                color: #333333;
                padding: 30px;
                border-radius: 8px;
                min-width: 300px;
                max-width: 80%;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            }
            #tabella-calendario {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                margin-bottom: 25px;
                font-family: inherit;
            }

            #tabella-calendario th,
            #tabella-calendario td {
                padding: 12px 15px;
                border-bottom: 1px solid #eeeeee;
                text-align: left;
            }

            #tabella-calendario th {
                background-color: #a30000;
                color: #ffffff;
                font-weight: 600;
            }
            #tabella-calendario tr:last-child td {
                border-bottom: none;
            }

            .cella-vuota {
                color: #999999;
                font-style: italic;
            }
        </style>
    </head>
    <body>
    <div class="header">
        <h1>Calendario Settimanale Lezioni</h1>
        <p>Studente: <strong>' . htmlspecialchars($nome_studente) . '</strong> (Matricola: ' . htmlspecialchars($matricola) . ')</p>
        <p>Programmazione dal Lunedì al Sabato</p>
    </div>
    ';



    $html .= '
        <table id="tabella-calendario">
            <thead>
                <tr>
                    <th>Giorno</th>
                    <th>Materia</th>
                    <th>Orario</th>
                    <th>Aula</th>
                </tr>
            </thead>
            <tbody>
            <tbody class="modale-contenuto">
    ';

                    
    $giorni_settimana = ["Lunedi", "Martedi", "Mercoledi", "Giovedi", "Venerdi", "Sabato"];
    $g_idx = 0; // Indice del giorno corrente nell'array $giorni_settimana
    $primo_elemento_giorno = true;

    foreach ($calendario_settimana as $lezione){
        // Se si passa a un nuovo giorno rispetto a quello trattato precedentemente
        if (!$primo_elemento_giorno && $giorni_settimana[$g_idx] !== $lezione['giorno']) {
            $g_idx++;
            $primo_elemento_giorno = true;
        }

        // Fallback per giorni mancanti/senza lezioni precedenti al giorno della lezione attuale
        while ($g_idx < count($giorni_settimana) && $giorni_settimana[$g_idx] !== $lezione['giorno']) {

            $html .='
            <tr>
                <td><strong>'. htmlspecialchars($giorni_settimana[$g_idx]) . '</strong></td>
                <td class="cella-vuota">Nessuna lezione</td>
                <td class="cella-vuota">-</td>
                <td class="cella-vuota">-</td>
            </tr>';
        
            $g_idx++;
            $primo_elemento_giorno = true;
        }

        // Rendering del giorno e della lezione corrispondente
        if ($g_idx < count($giorni_settimana) && $giorni_settimana[$g_idx] === $lezione['giorno']){

            $html .= '<tr><td>';
            if ($primo_elemento_giorno){
                
                $html .= '<strong>' . htmlspecialchars($lezione['giorno']) . '</strong>';

                if (isset($lezione['data'])){
                    $html .='<br><small>'. htmlspecialchars($lezione['data']) . '</small>';
                };
            }
            $html .= '</td>';

            $html .= '
                <td>'. htmlspecialchars($lezione['materia']) .'</td>
                <td>'. htmlspecialchars($lezione['orario']) . '</td>
                <td>'. htmlspecialchars($lezione['aula']) . '</td>
                </tr>
            ';
    
            $primo_elemento_giorno = false;
        };
    }  

    // Incremento dell'indice se l'ultimo giorno processato ha contenuto lezioni
    if (!$primo_elemento_giorno) {
        $g_idx++;
    };
    

    // Completamento con placeholder per eventuali giorni rimanenti della settimana
    while ($g_idx < count($giorni_settimana)) {
    $html .='
        <tr>
            <td><strong>'. htmlspecialchars($giorni_settimana[$g_idx]) .'</strong></td>
            <td class="cella-vuota">Nessuna lezione</td>
            <td class="cella-vuota">-</td>
        </tr>
        ';

        $g_idx++;
    };

    $html.= '
        </tbody>
        </tbody>
        </table>
        </body>
        </html>
    '; 
}           



// 5. Generazione PDF con Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('calendario_settimanale.pdf', ['Attachment' => true]);
