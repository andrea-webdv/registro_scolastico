<?php
require "./moduli/pulizia_sessione.php";
require_once './moduli/Comunicazione_database.php';
require_once './moduli/ComponenteScannerQR.php'; // Includiamo il modulo dello scanner

session_start();

$errore_studente = "";
$errore_admin = "";

$lato_iniziale = $_GET['ruotato'] ?? "";
$class = "";

$_SESSION['admin_loggato'] = null;

// Istanziamo il componente scanner QR
$scanner = new ComponenteScannerQR();

// -------------------------------------------------------------------------
// INTERCETTAZIONE LETTURA QR BADGE (Richiesta AJAX asincrona)
// -------------------------------------------------------------------------
$dati_qr_studente = $scanner->intercettaEAutenticaQR();

if ($dati_qr_studente !== false) {
    // Se la classe ha decodificato correttamente un QR, forziamo l'header JSON
    header('Content-Type: application/json');

    $matricola_qr = $dati_qr_studente['matricola'];

    $verifica_db = new Database("scuola");
    // Utilizziamo il tuo metodo Query_exists sulla tabella 'studenti'
    $risultato_verifica = $verifica_db->Query_exists('studenti', [["Matricola", $matricola_qr]]);

    if ($risultato_verifica == 1) {
        Pulizia_sessione();
        $_SESSION['ruolo'] = 'studenti';
        $_SESSION["matricola"] = $matricola_qr;

        // Rispondiamo al Javascript dicendo che il login è avvenuto con successo e dove andare
        echo json_encode([
            'stato' => 'successo',
            'messaggio' => 'Login Badge Riuscito! Accesso in corso...',
            'redirect' => './home.php' // Istruzione di reindirizzamento per la pagina
        ]);
        exit();
    } else {
        echo json_encode([
            'stato' => 'errore',
            'messaggio' => 'Matricola del Badge errata o non registrata.'
        ]);
        exit();
    }
}

// -------------------------------------------------------------------------
// GESTIONE DEI LOGIN TRADIZIONALI TRAMITE FORM (POST Standard)
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CASO 1: LOGIN MANUALE STUDENTE (Inviato dal form Fronte) 
    if (isset($_POST['tipo_login']) && $_POST['tipo_login'] === 'studenti') {

        $matricola = trim($_POST['matricola'] ?? '');

        if (empty($matricola)) {
            $errore_studente = "Inserisci la tua matricola.";
        } else {
            $_SESSION['ruolo'] = 'studenti';
            $verifica_login = new Database("scuola");
            $risultato_verifica = $verifica_login->Query_exists('studenti', [["Matricola", $matricola]]);

            if ($risultato_verifica == 1) {
                Pulizia_sessione();
                $_SESSION["matricola"] = $matricola;
                header("Location: ./home.php");
                exit();
            } else {
                $errore_studente = "Matricola errata. Riprova.";
                $class = "errore_login";
            }
        }
    }

    // CASO 2: LOGIN ADMIN/STAFF (Inviato dal form Retro)
    if (isset($_POST['tipo_login']) && $_POST['tipo_login'] === 'staff') {
        $codice_admin = trim($_POST['codice_admin'] ?? '');

        if (empty($codice_admin)) {
            $errore_admin = "Per favore, inserisci il codice admin.";
            $lato_iniziale = "ruotato";
        } else {
            // CORRETTO: Adesso interroga realmente il database per lo staff!
            $verifica_login = new Database("scuola");

            // Usiamo il nome corretto della tabella ('staff') e della colonna ('codice_staff') dal tuo file SQL
            $risultato_verifica = $verifica_login->Query_exists('staff', [['codice_staff', $codice_admin]]);

            if ($risultato_verifica == 1) {
                Pulizia_sessione();
                $_SESSION['ruolo'] = "staff";
                $_SESSION['admin_loggato'] = true;

                header("Location: area_staff.php");
                exit();
            } else {
                $errore_admin = "Codice Admin errato o non autorizzato.";
                $class = "errore_login";
                $lato_iniziale = "ruotato"; // Mantiene la tessera girata sul retro
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenform Registra - Login studente</title>
    <link rel="stylesheet" href="./css/agenform.css">
    <link rel="stylesheet" href="./css/login.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        .scritta-istruzione {
            display: initial;
        }

        .scritta-errore {
            display: none;
        }

        .scritta-istruzione.errore_login {
            display: none !important;
        }

        .scritta-errore.errore_login {
            display: block;
            color: brown;
        }

        /* Adattamento per far convivere form e scanner nel layout della scheda */
        .scatola-login {
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>

<body>

    <header class="barra-superiore">
        <div class="spazio-logo">
            <img src="./materiali/Logo_Alt.png" alt="Logo AgenForm" class="immagine-logo">
        </div>
        <nav class="gruppo-bottoni">
            <a href="index.php" class="bottone-menu">Corsi</a>
            <a href="login.php" class="bottone-menu attivo">Login</a>
        </nav>
    </header>

    <main class="zona-centrale">
        <div class="login-container" style="perspective: 1000px; background: none; box-shadow: none; padding: 0;">
            <div class="scatola-login-inner <?= $lato_iniziale; ?>" id="tesseraLogin">

                <div class="faccia-fronte scatola-login">
                    <h1>Registro AgenForm</h1>
                    <br>

                    <?php if (!empty($errore_studente)): ?>
                        <div class="messaggio-errore">
                            <?= htmlspecialchars($errore_studente); ?>
                        </div>
                    <?php endif; ?>

                    <?php $scanner->renderizzaBannerHtml(); ?>

                    <p style="margin: 15px 0; color: #888; font-size: 13px;">— OPPURE COMENTA MANUALMENTE —</p>

                    <form action="login.php" method="POST">
                        <input type="hidden" name="tipo_login" value="studenti">

                        <label class="scritta-istruzione <?= $class ?>">Inserire matricola</label>
                        <label class="scritta-errore <?= $class ?>">errore: verifica matricola / codice admin</label>
                        <input type="text" name="matricola" class="campo-input" placeholder="Matricola" minlength="8" maxlength="8" required>

                        <label class="scritta-istruzione">Password</label>
                        <input type="password" class="campo-input" placeholder="Password">

                        <button type="submit" class="bottone-login">LOGIN ></button>
                    </form>

                    <div class="spazio-link-admin" style="display: flex; flex-direction: column; gap: 12px; align-items: center; margin-top: 15px;">
                        <a href="recupero_password.php" class="link-admin">Hai dimenticato la password?</a>
                        <button type="button" class="link-switch-login" id="btnSpostaAdAdmin">Area Riservata Staff</button>
                    </div>
                </div>

                <div class="faccia-retro scatola-login">
                    <h1>Area Riservata Staff</h1>
                    <br>
                    <?php if (!empty($errore_admin)): ?>
                        <div class="messaggio-errore">
                            <?= htmlspecialchars($errore_admin); ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <input type="hidden" name="tipo_login" value="staff">

                        <label class="scritta-istruzione">Inserire Codice Admin Autorizzato</label>
                        <input type="text" name="codice_admin" class="campo-input" placeholder="Codice Admin" minlength="8" maxlength="8" required>

                        <label class="scritta-istruzione">Password</label>
                        <input type="password" class="campo-input" placeholder="Password">

                        <button type="submit" class="bottone-login">ACCEDI ></button>
                    </form>

                    <div class="spazio-link-admin" style="margin-top: 20px;">
                        <a href="#" class="link-switch-login" id="btnSpostaAStudente">Torna all'area Studenti</a>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        const tessera = document.getElementById('tesseraLogin');
        const vaiAdAdmin = document.getElementById('btnSpostaAdAdmin');
        const vaiAStudente = document.getElementById('btnSpostaAStudente');

        if (tessera && vaiAdAdmin && vaiAStudente) {
            vaiAdAdmin.addEventListener('click', function(e) {
                e.preventDefault();
                tessera.classList.add('ruotato');
            });

            vaiAStudente.addEventListener('click', function(e) {
                e.preventDefault();
                tessera.classList.remove('ruotato');
            });
        }
    </script>

    <?php $scanner->renderizzaScriptJavascript(); ?>

    <script>
        // Modifichiamo l'ascoltatore globale del fetch dello scanner per supportare il reindirizzamento immediato
        document.addEventListener("DOMContentLoaded", function() {
            const vecchioFetch = window.fetch;
            window.fetch = function(...args) {
                return vecchioFetch(...args).then(response => {
                    // Controlliamo se la risposta del server contiene dati di redirect
                    const clone = response.clone();
                    clone.json().then(data => {
                        if (data && data.stato === 'successo' && data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000); // Reindirizza dopo 1 secondo per mostrare il messaggio di successo
                        }
                    }).catch(() => {}); // Ignora se la risposta non è un JSON valido
                    return response;
                });
            };
        });
    </script>

</body>

</html>