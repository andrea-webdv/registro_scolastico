<?php session_start(); ?>
<?php
$kickback = $_GET['e'] ?? null;

// Se lo staff � loggato o c'� un parametro di ruotato, partiamo direttamente con la tessera girata sul retro
$lato_iniziale = (isset($_SESSION['staff_codice']) || isset($_GET['ruotato'])) ? "ruotato" : "";
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage - Gestione QR Code</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/agenform.css">
    <link rel="stylesheet" href="./css/login.css">
    <style>
        .error {
            padding: .5rem 2.4rem;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: large;
            font-weight: 600;
            position: absolute;
            top: 5%;
            border: 1px solid var(--rosso_brand);
            background-color: #e9e6e6;
            color: var(--rosso_brand);
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <?php
    if (isset($kickback) && $kickback == "non_loggato") {
        echo "
        <p class='error'> Errore: Effettuare il login per accadere al registro presenze!.<p>
        ";
    } elseif (isset($kickback) && $kickback == "loggato") {
        echo "
        <p class='error'> Errore: Effettuare il logout prima di effettuare un nuovo login!.<p>
        ";
    }
    ?>

    <!-- NAVBAR CON TITOLO PRINCIPALE -->
    <?php
    require_once __DIR__ . "/navbar.php";
    navbar([
        ['<a href="./registrazione/landing.php" class="btn-login" style="text-decoration:none; display:inline-block; margin-top:15px;">Simula candidatura e registrazione</a>']
    ], "Registro Elettronico Digitale");
    ?>

    <!-- TESSERA ROTANTE 3D (STUDENTE / STAFF) -->
    <div class="login-container-flip">
        <div class="scatola-login-inner <?= $lato_iniziale; ?>" id="tesseraLogin">

            <!-- FACCIA FRONTE: STUDENTE -->
            <div class="faccia-fronte scatola-card">
                <h2>Area Studente</h2>

                <?php if (isset($_SESSION['studente_matricola'])): ?>
                    <form action="./accesso/login_studente.php" method="POST" class="form-flip">
                        <p class="info-login">Loggato come: <strong><?= htmlspecialchars($_SESSION['studente_nome'] ?? 'Studente'); ?></strong></p>
                        <p class="info-login">Matricola: <strong><?= htmlspecialchars($_SESSION['studente_matricola']); ?></strong></p>
                        <input type="hidden" name="matricola" value="<?= htmlspecialchars($_SESSION['studente_matricola']); ?>">
                        <button type="submit" class="btn-login" style="text-decoration:none; display:inline-block; margin-top:15px;">Continua</button>
                        <a href="./accesso/logout.php" class="btn-login" style="text-decoration:none; display:inline-block; margin-top:15px;">Cambia Utente</a>
                    </form>
                <?php else: ?>
                    <form action="./accesso/login_studente.php" method="POST" class="form-flip">
                        <input type="text" name="matricola" placeholder="Inserisci Matricola" required>
                        <button type="submit" class="btn-login">Login Studente</button>
                    </form>
                <?php endif; ?>

                <button type="button" class="btn-switch-flip" id="btnSpostaAdAdmin">
                    Sei dello Staff? Clicca qui ??
                </button>
            </div>

            <!-- FACCIA RETRO: STAFF -->
            <div class="faccia-retro scatola-card">
                <h2>Area Staff</h2>

                <?php if (isset($_SESSION['staff_codice'])): ?>
                    <form action="./accesso/login_staff.php" method="POST" class="form-flip">
                        <p class="info-login">Loggato come: <strong><?= htmlspecialchars($_SESSION['staff_nome'] ?? 'Staff'); ?></strong></p>
                        <p class="info-login">Codice: <strong><?= htmlspecialchars($_SESSION['staff_codice']); ?></strong></p>
                        <input type="hidden" name="codice_staff" value="<?= htmlspecialchars($_SESSION['staff_codice']); ?>">
                        <button type="submit" class="btn-login" style="text-decoration:none; display:inline-block; margin-top:15px;">Continua</button>
                        <a href="./accesso/logout.php" class="btn-login" style="text-decoration:none; display:inline-block; margin-top:15px;">Cambia Utente</a>
                    </form>
                <?php else: ?>
                    <form action="./accesso/login_staff.php" method="POST" class="form-flip">
                        <input type="text" name="codice_staff" placeholder="Codice Staff" required>
                        <button type="submit" class="btn-login">Login Staff</button>
                    </form>
                <?php endif; ?>

                <button type="button" class="btn-switch-flip" id="btnSpostaAStudente">
                    Sei uno Studente? Clicca qui ??
                </button>
            </div>

        </div>
    </div>

    <!-- SCRIPT DI GESTIONE ROTAZIONE E TRANSIZIONE -->
    <script>
        // Animazione di rotazione 3D della tessera
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

</body>

</html>