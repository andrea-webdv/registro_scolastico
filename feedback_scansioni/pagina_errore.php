<?php
// 1. Recupero il parametro 'error' dall'URL (es: pagina_errore.php?error=nessuna_lezione)
$tipo_errore = isset($_GET['error']) ? $_GET['error'] : 'generico';

// 2. Variabili di default
$titolo_pagina = "Errore";
$titolo_card = "ERRORE";
$icona = "⚠️";
$messaggio_errore = "Si è verificato un errore imprevisto.";
$messaggio_footer = "";
$testo_bottone = "TORNA INDIETRO";
$azione_bottone = "transizioneIndietro(event)";
$href_bottone = "#";
$mostra_video = false;

// 3. Imposto i contenuti in base al codice di errore passato in GET
switch ($tipo_errore) {
    case 'checkout_multiplo':
        $titolo_pagina = "Errore - Check-in Non Consentito";
        $titolo_card = "Check-in Non Consentito";
        $icona = "🚫";
        $messaggio_errore = "<strong>Operazione non valida:</strong><br>Hai già effettuato il check-out da questa lezione.<br>Non è possibile registrarsi nuovamente in entrata.";
        $messaggio_footer = "Presenza già conclusa per la sessione odierna.<br>Per ulteriori informazioni, rivolgersi agli amministratori.";
        $testo_bottone = "Torna allo Scanner";
        $azione_bottone = "transizioneScanner(event, this.href)";
        $href_bottone = "./../leggi_qrcode/lettura_qr.php";
        $mostra_video = true; // Attiva il video di sfondo solo per questo errore
        break;

    case 'nessuna_lezione':
        $titolo_pagina = "Errore - Check-in Non Consentito";
        $titolo_card = "Check-in Non Consentito";
        $icona = "🚫";
        $messaggio_errore = "<strong>Operazione non valida:</strong><br>Nessuna lezione attualmente in corso, consulta il calendario";
        $messaggio_footer = "Verifca il calendario o ritenta tra qualche minuto.<br>Per ulteriori informazioni, rivolgersi agli amministratori.";
        $testo_bottone = "Torna allo Scanner";
        $azione_bottone = "transizioneScanner(event, this.href)";
        $href_bottone = "./../leggi_qrcode/lettura_qr.php";
        $mostra_video = true; // Attiva il video di sfondo solo per questo errore
        break;

    case 'anomalie_lettura':
        $titolo_pagina = "Errore - Lettura Presenze";
        $titolo_card = "ATTENZIONE";
        $icona = "🤦‍♂️";
        $messaggio_errore = "<strong>Troppe anomalie nella lettura presenze.</strong><br>Si prega di verificare i dati o contattare l'amministratore di sistema.";
        $messaggio_footer = ""; // Nessun footer per questo errore
        $testo_bottone = "TORNA INDIETRO";
        $azione_bottone = "transizioneIndietro(event)";
        $href_bottone = "./../leggi_qrcode/lettura_qr.php";
        $mostra_video = true; // Attiva il video di sfondo solo per questo errore
        break;
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titolo_pagina ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./../css/agenform.css">

    <style>
        /* Stili base per la pagina (es. sfondo grigio se non c'è video) */
        body {
            transition: opacity 0.4s ease-out, transform 0.4s ease-out;
            opacity: 1;
            transform: scale(1);
            <?php if (!$mostra_video): ?>background-color: #86878b;
            /* Riprende il colore della pagina anomalie */
            <?php endif; ?>
        }

        body.uscita-animata {
            opacity: 0;
            transform: scale(0.97);
        }

        <?php if ($mostra_video): ?>

        /* --- CSS APPLICATO SOLO SE IL VIDEO È ATTIVO --- */
        html,
        body,
        .container {
            background: transparent !important;
            background-color: transparent !important;
            margin: 0;
            overflow: hidden;
        }

        .video-sfondo {
            position: fixed;
            left: 64vw;
            width: 25vw;
            height: 54vh;
            object-fit: cover;
            z-index: 0;
            transform: translateX(10vw);
        }

        .overlay-scuro {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.65);
            z-index: -1;
        }

        <?php endif; ?>

        /* Personalizzazione specifica per la Card d'Errore */
        .card-errore {
            text-align: center;
            max-width: 480px;
            width: 90%;
            flex: 0 0 auto;
            border-top: 6px solid var(--rosso_brand, #951d3b);
            animation: popIn 0.3s ease-out;
            background-color: #ffffff;
            position: relative;
            z-index: 1;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: #333333;
        }

        .icona-divieto {
            font-size: 55px;
            margin-bottom: 15px;
            line-height: 1;
        }

        .titolo-errore {
            color: var(--rosso_brand, #951d3b);
            margin-bottom: 10px;
            font-size: 2.2rem;
            text-transform: uppercase;
        }

        .testo-errore {
            color: #333333;
            margin-bottom: 25px;
            font-size: 1.15rem;
            line-height: 1.4;
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.92);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body>

    <?php if ($mostra_video): ?>
        <div class="overlay-scuro"></div>
    <?php endif; ?>

    <div class="container" style="justify-content: center; min-height: 100vh; display: flex; align-items: center;">
        <div class="card card-errore">

            <div class="icona-divieto"><?= $icona ?></div>

            <h1 class="titolo-errore"><?= $titolo_card ?></h1>

            <p class="testo-errore">
                <?= $messaggio_errore ?>
            </p>

            <?php if (!empty($messaggio_footer)): ?>
                <div class="data-list" style="margin-bottom: 25px; font-weight: 600; color: #555555;">
                    <?= $messaggio_footer ?>
                </div>
            <?php endif; ?>

            <div class="button-container" style="justify-content: center;">
                <a href="<?= $href_bottone ?>" class="btn" onclick="<?= $azione_bottone ?>">
                    <?= $testo_bottone ?>
                </a>
            </div>

        </div>
    </div>
    <?php /* 
    <script src="js/gestore_suoni.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof riproduciSuono === "function") {
                // riproduciSuono('errore');
            }
        });

        // Funzione per il pulsante "Torna allo scanner"
        function transizioneScanner(event, url) {
            event.preventDefault();
            document.body.classList.add('uscita-animata');
            setTimeout(function() {
                window.location.href = url;
            }, 400);
        }

        // Funzione per il pulsante "Torna indietro"
        function transizioneIndietro(event) {
            event.preventDefault();
            document.body.classList.add('uscita-animata');
            setTimeout(function() {
                window.history.back();
            }, 400);
        }
    </script>
    */ ?>

</body>

</html>