<?php
session_start();

// Controllo di sicurezza: se lo staff non è loggato, viene reindirizzato alla index
if (!isset($_SESSION['staff_codice'])) {
    header("Location: index.php?e=non_loggato");
    exit();
}

$nome_staff = $_SESSION['staff_codice'];# ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Staff - AgenForm</title>

    <!-- Importazione Font Barlow Condensed -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Foglio di stile AgenForm -->
    <link rel="stylesheet" href="./css/agenform.css">

    <style>
        .container-dashboard {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px 20px;
            /* Ridotto da 30px per recuperare spazio in alto */
        }

        .header-dashboard {
            text-align: center;
            margin-bottom: 10px;
            /* Ridotto da 25px */
        }

        .pulsanti-azioni,
        .pulsanti-vista {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 12px;
            /* Ridotto da 20px per avvicinare iframe e pulsanti */
            flex-wrap: wrap;
        }

        .vista-attiva {
            box-shadow: inset 0 0 0 2px #ffffff;
        }

        .riquadro-iframe {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            padding: 10px;
        }

        .riquadro-iframe iframe {
            width: 100%;
            height: 470px;
            /* Ridotto per tagliare il contenitore subito sotto la tabella e i filtri */
            border: none;
            border-radius: 4px;
            display: block;
        }
    </style>
</head>

<body>

    <div class="container-dashboard">
        <!-- TITOLO -->
        <div class="header-dashboard">
            <h1 style="margin-bottom: 0;">Dashboard Staff</h1>
            <p style="font-size: 1.3rem; color: #ffffff; margin-top: 0;">
                Benvenuto/a, <strong><?= htmlspecialchars($nome_staff) ?></strong>
            </p>
        </div>

        <!-- LOGOUT (SUPERIORE) -->
        <div class="pulsanti-azioni">
            <a href="./accesso/logout.php" class="btn btn-create btn-logout" onclick="transizioneAnimata(event, this.href)">
                Logout
            </a>
        </div>

        <!-- PULSANTI DI VISTA E AZIONI (INFERIORI) -->
        <div class="pulsanti-vista">
            <button type="button" class="btn btn-create vista-attiva" id="btn-presenze" onclick="mostraVista('./pdf/registro_presenze.php', this)">
                Registro Presenze
            </button>
            <button type="button" class="btn btn-create" id="btn-calendario" onclick="mostraVista('calendario_lezioni.php', this)">
                Calendario Lezioni
            </button>
            <a href="aggiungi.php" class="btn btn-create" onclick="transizioneAnimata(event, this.href)">
                Aggiungi Corsi/Lezioni
            </a>
            <a href="./crea_qrcode/generatore_qr.php" class="btn btn-create" onclick="transizioneAnimata(event, this.href)">
                Crea QR Code
            </a>
            <a href="leggi_qrcode/lettura_qr.php" class="btn btn-create" onclick="transizioneAnimata(event, this.href)">
                Leggi QR Code
            </a>
        </div>

        <!-- RIQUADRO CON L'IFRAME -->
        <div class="riquadro-iframe">
            <iframe id="iframe-contenuto" src="./pdf/registro_presenze.php" title="Contenuto dashboard staff"></iframe>
        </div>
    </div>

    <script>
        function mostraVista(url, bottonePremuto) {
            document.getElementById('iframe-contenuto').src = url;

            document.querySelectorAll('.pulsanti-vista .btn').forEach(function(bottone) {
                bottone.classList.remove('vista-attiva');
            });
            bottonePremuto.classList.add('vista-attiva');
        }

        // Funzione per l'animazione di uscita
        function transizioneAnimata(event, url) {
            event.preventDefault(); // Blocca il caricamento immediato della nuova pagina
            document.body.classList.add('uscita-animata'); // Avvia l'animazione CSS

            // Attende 400ms prima di reindirizzare, permettendo all'animazione di finire
            setTimeout(function() {
                window.location.href = url;
            }, 400);
        }
    </script>
</body>

</html>