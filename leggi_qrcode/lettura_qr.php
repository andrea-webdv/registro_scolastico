<?php
session_start();

require_once __DIR__ . "/../collegamento_db/aule_e_lezioni.php";
require_once __DIR__ . "/../materiali/libreria_debug.php";

$nome_aula = "";
$ID_lezione_attuale = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['aula'])) {

    $nome_aula = $_GET['aula'];


    // Creazione dell'oggetto datetime relativa al momento della scelta dell'aula, da spostare sulla scansione
    $ora_corrente = date('Y-m-d H:i:s');
    $ora_scansione = new DateTime($ora_corrente);



    $ID_lezione_attuale =  recupero_lezione($nome_aula, $ora_corrente);

    //porzionie utile per console log
    $ID_lezione_controllata = "";

    if (!$ID_lezione_attuale) {
        $ID_lezione_controllata = "nessuna lezione in programma";
    } else {
        $ID_lezione_controllata = $ID_lezione_attuale;
    }
    console_log("lezione: " . $ID_lezione_controllata);
}

$presenze = [];
$sospese = [];


$elenco_lezioni = Lettura_aule();
?>
<!-- messaggio di errore per zombies -->
<?php
$zombies = $_GET['e'] ?? null;
?>



<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lettore Presenze QR - AgenForm</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/agenform.css">
    <link rel="stylesheet" href="../css/lettura_qr_old.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        .orizzontale {
            display: flex;
        }

        .porta {
            height: calc(1rem + 26px);
            background-image: url('./../materiali/door_icon.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }

        /* --- NUOVI STILI PER LA SELECT A TENDINA --- */
        .custom-select-container {
            position: relative;
            width: 100%;
        }

        .select-trigger {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-family: inherit;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #fff;
            cursor: pointer;
            box-sizing: border-box;
            color: #333;
            background-image: url('data:image/svg+xml;utf8,<svg fill="%23333" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        .select-trigger:focus {
            outline: none;
            border-color: #0056b3;
            box-shadow: 0 0 5px rgba(0, 86, 179, 0.3);
        }

        .select-tabella {
            display: none;
            /* Nascosta di default */
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            font-size: 16px;
            padding: 0;
            font-family: inherit;
            z-index: 1000;
            background: white;
            border: 1px solid #ccc;
            border-top: none;
            border-radius: 0 0 4px 4px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            max-height: 250px;
            overflow-y: auto;
        }

        .select-tabella.show {
            display: block;
            /* Mostra quando ha la classe 'show' */
        }

        .select-tabella option {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            margin: 0;
        }

        .select-tabella option:hover,
        .select-tabella option:checked {
            background-color: #f0f4f8;
            color: #000;
        }
    </style>
    <!-- messaggio di errore per zombies -->
    <style>
        .error {
            padding: .5rem 2.4rem;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: x-large;
            font-weight: 600;
            position: absolute;
            top: 59%;
            z-index: 100;
            border: 1px solid var(--rosso_brand);
            background-color: #e9e6e6;
            color: var(--rosso_brand);
        }
    </style>
</head>

<body>
    <!-- messaggio di errore per zombies -->
    <?php
    if (isset($zombies)) {
        echo "
        <p class='error'> Errore. Anomalie nella lettura presenze.<p>
        ";
    }
    ?>
    <main style="flex-direction: column;">

        <div class="button-container" style="margin-bottom: 20px;">
            <a href="../dashboard_staff.php" class="btn btn-create" onclick="transizioneHome(event, this.href)" style="font-size: 18px; padding: 16px 50px; letter-spacing: 1px;">
                Ritorna alla Dashboard
            </a>
        </div>
        <div class="pannello-lettore">


            <form action="lettura_qr.php?" id="aula_picker" method="get">
                <div class="mezzo-campo">

                    <label for="aula_trigger" style="display:block; margin-bottom: 10px; font-weight: bold; color: #333333;">Aula <?php echo $nome_aula ?></label>

                    <div class="custom-select-container">
                        <input type="text" id="aula_trigger" class="select-trigger" placeholder="Clicca qui per scegliere l'aula..." readonly>

                        <select id="aula_picker_input" name="aula" class="select-tabella" size="8">
                            <?php if (!empty($elenco_lezioni)): ?>
                                <?php foreach ($elenco_lezioni as $row): ?>
                                    <option value="<?php echo $row['cod_aula']; ?>">
                                        <?= htmlspecialchars($row['cod_aula']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Nessun dato trovato</option>
                            <?php endif; ?>
                        </select>
                    </div>

                </div>


                <button type="submit" class="btn btn-create porta" style="margin-top: 15px;"></button>
            </form>
            <hr style="height:.8rem;opacity:0%;">


            <h2 class="titolo-sezione">Lettore Badge</h2>
            <p class="sottotitolo">Inquadra il QR Code dello studente</p>

            <form action="./../gestione_presenze/check_scansione.php" id="form_scanner" method="post">
                <input type="hidden" id="param_matricola" name="matricola">
                <input type="hidden" id="param_lezione" name="ID_lezione">
                <div id="zona-scanner"></div>
            </form>


            <div id="box-messaggi" class="messaggio-schermo"></div>
            <!-- <button id="skip" class="btn btn-create" style="background-color:darkblue;" onclick="skippaQR()"> Skippa il QR🤙</button> -->
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
            }, 4000);
        }

        function recuperaDati(testoQR) {
            const [matricola, cognome, nome] = testoQR.split(';');
            let datiStudente = {
                matricola: matricola,
                cognome: cognome,
                nome: nome
            }
            return datiStudente
        }


        function invioScansione(matricola) {
            //esecuzione del submit controllato
            let form_scanner = document.querySelector("#form_scanner")
            let param_matricola = document.querySelector("#param_matricola")
            let param_lezione = document.querySelector("#param_lezione")

            param_matricola.value = matricola;
            param_lezione.value = <?php if (!$ID_lezione_attuale) {
                                        echo "'none'";
                                    } else {
                                        echo $ID_lezione_attuale;
                                    }  ?>;

            form_scanner.submit();
        }

        //-- lettura della scansione del qr
        function onScanSuccess(decodedText, decodedResult) {
            html5QrcodeScanner.pause();

            mostraMessaggio(decodedText, "successo");

            let dati_studente = recuperaDati(decodedText);

            invioScansione(dati_studente.matricola);

            setTimeout(() => {
                html5QrcodeScanner.resume();
            }, 3000);
        }
        /*
                function skippaQR() {
                    let falsaLettura = "10000403;Elena;Trevisan";

                    mostraMessaggio(falsaLettura, "successo");

                    // lettura dei record e popolamento array presenze
                    //query_presenze(falsaLettura);

                    let dati_studente = recuperaDati(falsaLettura);

                    invioScansione(dati_studente.matricola);
                }
        */
        //-- script dello scanner qr
        const html5QrcodeScanner = new Html5QrcodeScanner(
            "zona-scanner", {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                },
                supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
            },
            false
        );
        html5QrcodeScanner.render(onScanSuccess);
    </script>

    <script>
        function transizioneHome(event, url) {
            event.preventDefault();
            document.body.classList.add('uscita-animata');
            setTimeout(function() {
                window.location.href = url;
            }, 400);
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const trigger = document.getElementById('aula_trigger');
            const select = document.getElementById('aula_picker_input');

            // Toggle apertura menu al click sull'input
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                select.classList.toggle('show');
            });

            // Quando viene selezionata un'opzione, aggiorna il testo dell'input e chiudi
            select.addEventListener('change', function() {
                const selectedOption = select.options[select.selectedIndex];
                trigger.value = selectedOption.text.trim();
                select.classList.remove('show');
            });

            // Chiudi la tendina se l'utente clicca fuori
            document.addEventListener('click', function(e) {
                if (!trigger.contains(e.target) && !select.contains(e.target)) {
                    select.classList.remove('show');
                }
            });

            // Se c'è già un'aula selezionata all'avvio della pagina, mostrala nell'input
            if (select.selectedIndex !== -1 && select.value !== "") {
                trigger.value = select.options[select.selectedIndex].text.trim();
            }
        });
    </script>
</body>

</html>