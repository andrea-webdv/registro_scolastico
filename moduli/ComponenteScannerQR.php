<?php
// moduli/ComponenteScannerQR.php

class ComponenteScannerQR
{

    /**
     * 1. LOGICA DI PARSING
     * Intercetta la richiesta POST, pulisce i dati del QR e li restituisce alla pagina.
     * Se non è una richiesta POST valida per il QR, restituisce false.
     */
    public function intercettaEAutenticaQR()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testo_qr'])) {
            header('Content-Type: application/json');

            $testo_qr = trim($_POST['testo_qr']);

            $matricola = null;
            $nome = null;
            $cognome = null;

            // Estrazione dati tramite Espressioni Regolari basate sul tuo formato QR
            if (preg_match('/Matricola:\s*([^\n\r]+)/i', $testo_qr, $matches)) {
                $matricola = trim($matches[1]);
            }
            if (preg_match('/Nome:\s*([^\n\r]+)/i', $testo_qr, $matches)) {
                $nome = trim($matches[1]);
            }
            if (preg_match('/Cognome:\s*([^\n\r]+)/i', $testo_qr, $matches)) {
                $cognome = trim($matches[1]);
            }

            // Se mancano i dati fondamentali, rispondiamo subito con un errore generico di lettura
            if (!$matricola || !$nome || !$cognome) {
                echo json_encode([
                    'stato' => 'errore',
                    'messaggio' => 'QR Code non riconosciuto o danneggiato.'
                ]);
                exit;
            }

            // Restituiamo un array associativo con i dati puliti alla pagina che ha invocato il metodo
            return [
                'matricola' => $matricola,
                'nome'      => $nome,
                'cognome'   => $cognome
            ];
        }

        return false;
    }

    /**
     * 2. RENDER HTML DEL BANNER
     */
    public function renderizzaBannerHtml()
    {
?>
        <div class="pannello-lettore" style="background:#fff; padding:25px; border-radius:6px; border-top:6px solid #dc3545; box-shadow:0 6px 15px rgba(0,0,0,0.1); max-width:450px; margin:20px auto; text-align:center;">
            <h3 style="margin-top:0; color:#dc3545; text-transform:uppercase; font-size:18px;">Scanner QR Badge</h3>
            <p style="color:#666; font-size:13px; margin-bottom:15px;">Inquadra il QR Code davanti alla telecamera</p>

            <div id="zona-scanner-classe" style="width:100%; border:2px solid #ddd; border-radius:4px; overflow:hidden; background:#fafafa;"></div>

            <div id="box-messaggi-classe" style="display:none; margin-top:15px; padding:10px; border-radius:4px; font-weight:bold; font-size:13px;"></div>
        </div>
    <?php
    }

    /**
     * 3. RENDER JAVASCRIPT
     */
    public function renderizzaScriptJavascript()
    {
        $url_corrente = htmlspecialchars($_SERVER['PHP_SELF']);
    ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                function mostraMessaggioClasse(testo, tipo) {
                    const box = document.getElementById('box-messaggi-classe');
                    box.innerText = testo;

                    if (tipo === 'successo') {
                        box.style.background = '#d4edda';
                        box.style.color = '#155724';
                        box.style.border = '1px solid #c3e6cb';
                    } else if (tipo === 'attenzione') {
                        box.style.background = '#fff3cd';
                        box.style.color = '#856404';
                        box.style.border = '1px solid #ffeeba';
                    } else {
                        box.style.background = '#f8d7da';
                        box.style.color = '#721c24';
                        box.style.border = '1px solid #f5c6cb';
                    }

                    box.style.display = 'block';
                    setTimeout(() => {
                        box.style.display = 'none';
                    }, 4000);
                }

                function onScanSuccess(decodedText, decodedResult) {
                    scannerComponente.pause();

                    const formData = new FormData();
                    formData.append('testo_qr', decodedText);

                    fetch('<?= $url_corrente ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            mostraMessaggioClasse(data.messaggio, data.stato);
                            setTimeout(() => {
                                scannerComponente.resume();
                            }, 2500);
                        })
                        .catch(error => {
                            mostraMessaggioClasse("Errore di comunicazione con il server.", "errore");
                            setTimeout(() => {
                                scannerComponente.resume();
                            }, 2500);
                        });
                }

                const scannerComponente = new Html5QrcodeScanner(
                    "zona-scanner-classe", {
                        fps: 10,
                        qrbox: {
                            width: 220,
                            height: 220
                        }
                    },
                    false
                );
                scannerComponente.render(onScanSuccess);
            });
        </script>
<?php
    }
}
