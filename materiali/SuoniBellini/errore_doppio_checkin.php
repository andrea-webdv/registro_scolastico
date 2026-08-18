<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Errore - Check-in Non Consentito</title>

    <!-- Font Google Barlow Condensed -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Foglio di stile AgenForm -->
    <link rel="stylesheet" href="css/agenform.css">

    <style>
        /* Personalizzazione specifica per la Card d'Errore */
        .card-errore {
            text-align: center;
            max-width: 480px;
            width: 90%;
            flex: 0 0 auto;
            border-top: 6px solid var(--rosso_brand);
            animation: popIn 0.3s ease-out;
        }

        .icona-divieto {
            font-size: 55px;
            margin-bottom: 15px;
            line-height: 1;
        }

        .titolo-errore {
            color: var(--rosso_brand);
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

    <div class="container" style="justify-content: center;">
        <div class="card card-errore">

            <!-- Icona di Divieto / Blocco -->
            <div class="icona-divieto">🚫</div>

            <h1 class="titolo-errore">Check-in Non Consentito</h1>

            <p class="testo-errore">
                <strong>Operazione non valida:</strong><br>
                Hai già effettuato il check-out da questa lezione.<br>
                Non è possibile registrarsi nuovamente in entrata.
            </p>

            <!-- Dettaglio descrittivo nel box tipico AgenForm -->
            <div class="data-list" style="margin-bottom: 25px; font-weight: 600; color: #555555;">
                Presenza già conclusa per la sessione odierna.<br>
                Per ulteriori informazioni, rivolgersi agli amministratori.
            </div>

            <div class="button-container" style="justify-content: center;">
                <a href="./leggi_qrcode/lettura_qr.php" class="btn" onclick="transizioneScanner(event, this.href)">
                    Torna allo Scanner
                </a>
            </div>

        </div>
    </div>

    <!-- Gestione transizione di uscita e riproduzione audio -->
    <script src="js/gestore_suoni.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Riproduce il suono di errore se il modulo audio è presente
            riproduciSuono('errore');
        });

        function transizioneScanner(event, url) {
            event.preventDefault();
            document.body.classList.add('uscita-animata');
            setTimeout(function() {
                window.location.href = url;
            }, 400);
        }
    </script>

</body>

</html>