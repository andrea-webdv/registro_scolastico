<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-out & Check-in Eseguito</title>

    <!-- Font Google Barlow Condensed -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Foglio di stile -->
    <link rel="stylesheet" href="css/agenform.css">
</head>

<body>

    <!-- Card compatta con bordo superiore rosso AgenForm -->
    <div class="card" style="text-align: center; max-width: 420px; width: 90%; flex: 0 0 auto; animation: popIn 0.3s ease-out; border-top: 6px solid var(--rosso_brand, #951d3b);">

        <!-- Icona Triangolo di Pericolo / Avviso -->
        <div style="margin-bottom: 15px;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#f39c12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>

        <h1 style="color: #333333; margin-bottom: 8px; font-size: 2.2rem; text-transform: uppercase;">
            Check-out & Check-in
        </h1>

        <p style="color: #666666; margin-bottom: 20px; font-size: 1.1rem; font-weight: 600;">
            Chiusa la presenza precedente e registrata la nuova entrata!
        </p>

        <!-- Box dettagli orario -->
        <div class="data-list" style="margin-bottom: 25px; font-weight: 600;">
            Nuova entrata registrata il <?php echo date("d/m/Y"); ?> alle <?php echo date("H:i"); ?>
        </div>

        <div class="button-container" style="justify-content: center;">
            <a href="./leggi_qrcode/lettura_qr.php" class="btn">
                Torna allo scanner
            </a>
        </div>
    </div>

    <!-- Animazione di ingresso -->
    <style>
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
    <!-- Importa il gestore suoni -->
    <script src="js/gestore_suoni.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Se usi i file MP3:
            riproduciSuono('checkin_checkout');

            // Oppure se vuoi usare i toni sintetici senza file:
            // riproduciTonoSintetico('checkout');
        });
    </script>
</body>

</html>