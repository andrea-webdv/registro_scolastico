<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-out Eseguito</title>

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

        <!-- Icona Checkmark BLU -->
        <div style="margin-bottom: 15px;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#0d6efd" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>

        <h1 style="color: #333333; margin-bottom: 8px; font-size: 2.2rem; text-transform: uppercase;">
            Check-out Eseguito
        </h1>

        <p style="color: #666666; margin-bottom: 20px; font-size: 1.1rem; font-weight: 600;">
            Uscita registrata con successo!
        </p>

        <!-- Box dettagli orario -->
        <div class="data-list" style="margin-bottom: 25px; font-weight: 600;">
            Uscita registrata il <?php echo date("d/m/Y"); ?> alle <?php echo date("H:i"); ?>
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
            riproduciSuono('checkout_riuscito');

            // Oppure se vuoi usare i toni sintetici senza file:
            // riproduciTonoSintetico('checkout');
        });
    </script>
</body>

</html>