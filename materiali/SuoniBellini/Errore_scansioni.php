<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Errore - Lettura Presenze</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        /* CSS inserito inline per mantenere la struttura a singolo file */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Barlow Condensed', sans-serif;
        }

        :root {
            --grigio_brand: #86878b;
            --rosso_brand: #951d3b;
        }

        body {
            background-color: var(--grigio_brand);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #ffffff;
            transition: opacity 0.4s ease-out, transform 0.4s ease-out;
            opacity: 1;
            transform: scale(1);
        }

        body.uscita-animata {
            opacity: 0;
            transform: scale(0.97);
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            max-width: 600px;
            width: 100%;
            padding: 20px;
        }

        .card {
            background: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            flex: 1;
            min-width: 300px;
            box-sizing: border-box;
            color: #333333;
            text-align: center;
            border-top: 6px solid var(--rosso_brand);
        }

        .icona-errore {
            font-size: 60px;
            margin-bottom: 20px;
            line-height: 1;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 2rem;
            color: var(--rosso_brand);
            text-align: center;
            text-transform: uppercase;
        }

        p {
            font-size: 1.2rem;
            color: #333333;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 13px 30px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            color: #ffffff;
            background-color: var(--rosso_brand);
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background-color 0.2s ease, transform 0.1s ease, box-shadow 0.2s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            cursor: pointer;
            text-align: center;
        }

        .btn:hover {
            background-color: #7a122e;
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .btn:active {
            transform: scale(0.98);
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="card">
            <!-- Emote persona disperata / facepalm -->
            <div class="icona-errore">🤦‍♂️</div>
            <h1>ATTENZIONE</h1>
            <p><strong>Troppe anomalie nella lettura presenze.</strong><br>Si prega di verificare i dati o contattare l'amministratore di sistema.</p>

            <div class="button-container" style="justify-content: center;">
                <a href="#" class="btn" onclick="transizioneIndietro(event)">TORNA INDIETRO</a>
            </div>
        </div>
    </div>

    <script>
        // Funzione per animare l'uscita prima di tornare indietro
        function transizioneIndietro(event) {
            event.preventDefault();
            document.body.classList.add('uscita-animata');
            setTimeout(function() {
                window.history.back();
            }, 400);
        }
    </script>
    <!-- Importa il gestore suoni -->
    <script src="js/gestore_suoni.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Se usi i file MP3:
            riproduciSuono('errore');

            // Oppure se vuoi usare i toni sintetici senza file:
            // riproduciTonoSintetico('checkout');
        });
    </script>
</body>

</html>