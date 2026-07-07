<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

class Strumenti_email {

    /**
     * Inizializza il componente e controlla in automatico se il form è stato inviato.
     */
    public function __construct() {
        // Se la richiesta è di tipo POST, elabora automaticamente l'invio
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione_invio_mail'])) {
            $this->elaboraEInvia();
        }
    }

    
/**
 * Invia una mail allo studente leggendo la configurazione dal file .env
 */
public function Invio_email(mixed $destiantario, string $nome_destiantario, string $soggetto, string $corpo, ?string $dati_immagine=null, ?string $nome_immagine=null, ?string $indirizzo_file=null) {
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$mail = new PHPMailer(true);

    try {
        // --- CONFIGURAZIONE SMTP VIA .ENV ---
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];


        // --- DESTINATARI ---
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($destiantario, "$nome_destiantario"); 
        /**
         * #email, ricevuta come parametro dal form; 
         * $nome_studente, combina nome e coglnome in una stringa per inserire il nome del destinatario nella head della mail, utile per non finire nello spam
         */
        

        // --- CONTENUTO ---
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        
        $mail->Subject = $soggetto;

        

        if ($dati_immagine) {
           // 1. SANIFICAZIONE: Puliamo il nome del file da spazi e caratteri speciali
            $nomePulito = preg_replace('/[^a-zA-Z0-9._-]/', '-', $nome_immagine);
            $nomePulito = strtolower(trim($nomePulito, '-'));
                
           // 2. Passi la variabile $cid a PHPMailer
           $mail->addStringEmbeddedImage($dati_immagine, 'CID_placeholder', $nomePulito, 'base64', 'image/png');
        }

        if ($indirizzo_file && file_exists($indirizzo_file)) {
            $nomeFile = basename($indirizzo_file);

            $mail->addAttachment($indirizzo_file, $nomeFile);
        } 

        $mail->Body = $corpo;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Errore invio mail: " . $mail->ErrorInfo);
        return false;
    }
}

    /**
     * Recupera i dati dal $_POST, li valida e invia la mail.
     */
    private function elaboraEInvia() {
        // Recupero sicuro e sanificazione base dei dati dal form
        $indirizzo  = isset($_POST['indirizzo']) ? trim($_POST['indirizzo']) : '';
        $nome       = isset($_POST['nome_destinatario']) ? trim($_POST['nome_destinatario']) : '';
        $soggetto   = isset($_POST['soggetto']) ? trim($_POST['soggetto']) : '';
        $body_email = isset($_POST['body_email']) ? trim($_POST['body_email']) : '';

        // Esegui l'invio solo se i campi minimi sono presenti
        if (!empty($indirizzo) && !empty($body_email)) {
            // Chiamata alla tua funzione nel modulo esterno
            $this -> Invio_email($indirizzo, $nome, $soggetto, $body_email);
            
            // Opzionale: reindirizza per evitare il reinvio del form al refresh della pagina
            // header("Location: " . $_SERVER['REQUEST_URI']);
            // exit;
        }
    }

    /**
     * Renderizza il form con l'editor email.
     */
    public function Campo_email($allowImages = true) {
        ?>
        <div class="container_campo_mail" style="max-width: 600px; font-family: sans-serif; border: 1px solid #ccc; border-radius: 4px; padding: 15px; background: #f9f9f9;">
            
            <!-- Il form rimanda alla pagina corrente -->
            <form action="" method="POST" id="form_invio_mail">
                
                <!-- Campo nascosto di controllo per far capire al costruttore che abbiamo premuto "Invia" -->
                <input type="hidden" name="azione_invio_mail" value="1">
                
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Nome destinatario:</label>
                    <input type="text" name="nome_destinatario" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Indirizzo destinatario:</label>
                    <input type="email" name="indirizzo" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Soggetto:</label>
                    <input type="text" name="soggetto" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; box-sizing: border-box;">
                </div>

                <label style="display:block; font-weight:bold; margin-bottom:5px;">Messaggio:</label>
                
                <div class="toolbar_mail" style="background: #fff; border: 1px solid #ddd; border-bottom: none; padding: 5px; border-top-left-radius: 4px; border-top-right-radius: 4px;">
                    <button type="button" onclick="formattaTesto('bold')" style="font-weight:bold; padding:5px 10px; cursor:pointer;">B</button>
                    <button type="button" onclick="formattaTesto('italic')" style="font-style:italic; padding:5px 10px; cursor:pointer;">I</button>
                    <button type="button" onclick="formattaTesto('insertUnorderedList')" style="padding:5px 10px; cursor:pointer;">• Lista</button>
                    
                    <?php if ($allowImages): ?>
                        <button type="button" onclick="inserisciImmagine()" style="padding:5px 10px; cursor:pointer;">🖼️ Immagine</button>
                    <?php endif; ?>
                </div>

                <div id="editor_visivo" contenteditable="true" style="min-height: 200px; border: 1px solid #ddd; padding: 10px; background: #fff; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px; overflow-y: auto; margin-bottom: 15px;"></div>

                <textarea class="campo_mail" name="body_email" id="campo_mail_hidden" style="display: none;"></textarea>

                <button type="submit" style="background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">
                    Invia Mail
                </button>
            </form>
        </div>

        <script>
            function formattaTesto(comando) {
                document.execCommand(comando, false, null);
                document.getElementById('editor_visivo').focus();
            }

            function inserisciImmagine() {
                const url = prompt("Inserisci l'URL dell'immagine:");
                if (url) { document.execCommand('insertImage', false, url); }
            }

            document.getElementById('form_invio_mail').addEventListener('submit', function(e) {
                const htmlContenuto = document.getElementById('editor_visivo').innerHTML;
                const testoPuro = document.getElementById('editor_visivo').textContent.trim();

                if (testoPuro === "" && !htmlContenuto.includes('<img')) {
                    alert("Il corpo della mail non può essere vuoto!");
                    e.preventDefault();
                    return;
                }
                document.getElementById('campo_mail_hidden').value = htmlContenuto;
            });
        </script>
        <?php
    }
}