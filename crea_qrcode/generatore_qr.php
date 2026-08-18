<?php
// Controlla se il form è stato inviato tramite POST, altrimenti usa valori predefiniti
$matricola = isset($_POST['matricola']) ? trim($_POST['matricola']) : "A85942";
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : "Mario";
$cognome = isset($_POST['cognome']) ? trim($_POST['cognome']) : "Rossi";

require_once __DIR__ . "/generazione_qr.php";
$qrCodeUrl = generazione_qr($matricola, $cognome , $nome);

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generatore QR Code Personalizzabile</title>
    <!-- Importazione Google Fonts per Barlow Condensed -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/agenform.css">
</head>

<body>

    <!-- Contenitore del pulsante Home con transizione funzionante -->
    <div class="button-container" style="margin-top: 20px; margin-bottom: 35px;">
        <a href="../dashboard_staff.php" class="btn btn-create" onclick="transizioneHome(event, this.href)" style="font-size: 18px; padding: 16px 50px; letter-spacing: 1px;">Home</a>
    </div>

    <div class="container">
        <div class="card">
            <h2>Inserisci Dati</h2>
            <p>Modifica i campi sottostanti per aggiornare il QR Code</p>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="matricola">Matricola</label>
                    <input type="text" id="matricola" name="matricola" value="<?php echo htmlspecialchars($matricola); ?>" required>
                </div>

                <div class="form-group">
                    <label for="cognome">Cognome</label>
                    <input type="text" id="cognome" name="cognome" value="<?php echo htmlspecialchars($cognome); ?>" required>
                </div>

                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                </div>

                <button class="btn btn-create" type="submit">Genera QR Code</button>
            </form>
        </div>

        <div class="card" style="text-align: center;">
            <h2>Badge Digitale</h2>
            <p>Scansiona il QR Code per visualizzare i dati</p>

            <div class="qr-code">
                <img src="<?php echo $qrCodeUrl["url"]; ?>" alt="QR Code" title="Dati Utente">
            </div>

            <div class="data-list">
                <p><strong>Contenuto del QR Code:</strong></p>
                <p><?php echo htmlspecialchars($qrCodeUrl["data"]); ?></p>
            </div>
            <!-- Aggiunto bottone per scaricare il QR Code -->
            <div style="margin-top: 25px;">
                <button type="button" class="btn btn-create" onclick="scaricaQRCode()">Scarica QR Code</button>
            </div>
        </div>
    </div>

    <!-- Script di transizione globale e per scaricare l'immagine -->
    <script>
        function transizioneDashboard(event, url) {
            event.preventDefault();
            document.body.classList.add('uscita-animata');
            setTimeout(function() {
                window.location.href = url;
            }, 400);
		}

        async function scaricaQRCode() {
            // Prendi l'URL dell'immagine generata in PHP
            const urlImmagine = "<?php echo $qrCodeUrl["url"]; ?>";
            // Prepara il nome del file da salvare sfruttando la matricola
            const nomeFile = "QRCode_<?php echo htmlspecialchars($matricola); ?>.png";

            try {
                // Recuperiamo l'immagine tramite fetch
                const response = await fetch(urlImmagine);
                const blob = await response.blob();
                
                // Creiamo un URL oggetto temporaneo e simuliamo il click per il download
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = nomeFile;
                document.body.appendChild(link);
                link.click();
                
                // Pulizia
                document.body.removeChild(link);
                window.URL.revokeObjectURL(link.href);
            } catch (error) {
                console.error("Errore nel download dell'immagine:", error);
                // Fallback: apriamo in una nuova scheda nel caso il fetch fallisca per blocchi CORS
                window.open(urlImmagine, '_blank');
            }        
        }
    </script>
</body>

</html>