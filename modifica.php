<?php
session_start();
require_once __DIR__ . "/collegamento_db/Collegamento.php";

if (!isset($_SESSION['staff_codice'])) {
    header("Location: index.php?e=non_loggato");
    exit();
}

$connessione = Collegamento();
$messaggio = "";
$messaggio_presenza = "";

$id_lezione_get = $_GET['id'] ?? null;


// Se non viene passato un ID valido, reindirizziamo o blocchiamo
if (!$id_lezione_get) {
    header("Location: dashboard_staff.php");
    exit();
}
// --- NUOVO CODICE: CATTURA DATI DALLA TABELLA PRESENZE ---
$matricola_get   = $_GET['matricola'] ?? '';
$data_pres_get   = $_GET['data'] ?? '';
// Tronchiamo i secondi (es. da 08:30:00 a 08:30) per i campi HTML <input type="time">
$entrata_get     = !empty($_GET['entrata']) ? substr($_GET['entrata'], 0, 5) : '';
$uscita_get      = !empty($_GET['uscita']) ? substr($_GET['uscita'], 0, 5) : '';
// ---------------------------------------------------------

// Gestione dei salvataggi POST (distinguiamo se si salva la lezione, la presenza o si cancella)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Salvataggio / Modifica Lezione
    if (isset($_POST['azione']) && $_POST['azione'] === 'salva_lezione') {
        $data_lezione = $_POST['data_lezione'] ?? '';
        $ora_inizio   = $_POST['ora_inizio'] ?? '';
        $ora_fine     = $_POST['ora_fine'] ?? '';
        $aula         = $_POST['aula'] ?? '';
        $id_corso     = $_POST['id_corso'] ?? '';
        $cod_materia  = $_POST['cod_materia'] ?? '';

        $ts_inizio = $data_lezione . ' ' . $ora_inizio . ':00';
        $ts_fine   = $data_lezione . ' ' . $ora_fine . ':00';

        $sql = "UPDATE lezioni 
                SET TS_inizio = ?, TS_fine = ?, cod_aula = ?, ID_corso = ?, cod_materia = ? 
                WHERE ID_lezione = ?";
        $stmt = $connessione->prepare($sql);
        $stmt->execute([$ts_inizio, $ts_fine, $aula, $id_corso, $cod_materia, $id_lezione_get]);
        $messaggio = "Lezione modificata con successo!";
    }

    // 2. Inserimento / Modifica Manuale Presenza Studente (con gestione orari NULL)
    if (isset($_POST['azione']) && $_POST['azione'] === 'modifica_presenza') {
        $matricola   = trim($_POST['matricola'] ?? '');
        $ora_entrata = $_POST['ora_entrata'] ?? '';
        $ora_uscita  = $_POST['ora_uscita'] ?? '';
        $data_pres   = $_POST['data_presenza'] ?? '';

        if (!empty($matricola) && !empty($data_pres)) {
            $ts_entrata = !empty($ora_entrata) ? $data_pres . ' ' . $ora_entrata . ':00' : null;
            $ts_uscita  = !empty($ora_uscita) ? $data_pres . ' ' . $ora_uscita . ':00' : null;

            // Verifica se esiste già un record di presenza per questa lezione e matricola
            $sql_check = "SELECT ID_lezione FROM presenze WHERE ID_lezione = ? AND matricola = ?";
            $stmt_check = $connessione->prepare($sql_check);
            $stmt_check->execute([$id_lezione_get, $matricola]);
            $esistente = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($esistente) {
                // Aggiornamento consentendo anche valori NULL
                $sql_pres = "UPDATE presenze SET ora_entrata = ?, ora_uscita = ? WHERE ID_lezione = ? AND matricola = ?";
                $stmt_pres = $connessione->prepare($sql_pres);
                $stmt_pres->execute([$ts_entrata, $ts_uscita, $id_lezione_get, $matricola]);
                $messaggio_presenza = "Presenza modificata con successo per la matricola $matricola!";
            } else {
                // Inserimento nuovo record
                $sql_pres = "INSERT INTO presenze (ID_lezione, matricola, ora_entrata, ora_uscita) VALUES (?, ?, ?, ?)";
                $stmt_pres = $connessione->prepare($sql_pres);
                $stmt_pres->execute([$ts_entrata, $ts_uscita, $id_lezione_get, $matricola]);
                $messaggio_presenza = "Presenza registrata con successo per la matricola $matricola!";
            }
        } else {
            $messaggio_presenza = "Errore: Inserisci almeno la matricola e la data.";
        }
    }

    // 3. Cancellazione Presenza Studente
    if (isset($_POST['azione']) && $_POST['azione'] === 'cancella_presenza') {
        $matricola = trim($_POST['matricola'] ?? '');

        if (!empty($matricola)) {
            $sql_del = "DELETE FROM presenze WHERE ID_lezione = ? AND matricola = ?";
            $stmt_del = $connessione->prepare($sql_del);
            $stmt_del->execute([$id_lezione_get, $matricola]);
            $messaggio_presenza = "Presenza cancellata con successo per la matricola $matricola!";
        } else {
            $messaggio_presenza = "Errore: Inserisci la matricola dello studente da cancellare.";
        }
    }
}

// Recupero dati della lezione corrente
$stmt = $connessione->prepare("SELECT * FROM lezioni WHERE ID_lezione = ?");
$stmt->execute([$id_lezione_get]);
$valori_form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$valori_form) {
    header("Location: dashboard_staff.php");
    exit();
}

$corsi = $connessione->query("SELECT ID_corso, nome_corso, cod_edizione FROM corsi ORDER BY nome_corso, cod_edizione")->fetchAll(PDO::FETCH_ASSOC);
$materie = $connessione->query("SELECT cod_materia, nome_materia FROM materie ORDER BY nome_materia")->fetchAll(PDO::FETCH_ASSOC);

$sql_date = "SELECT DISTINCT DATE_FORMAT(TS_inizio, '%Y-%m-%d') AS data_occupata FROM lezioni";
$date_occupate = $connessione->query($sql_date)->fetchAll(PDO::FETCH_COLUMN);

$data_valore       = !empty($valori_form['TS_inizio']) ? date('Y-m-d', strtotime($valori_form['TS_inizio'])) : '';
$ora_inizio_valore = !empty($valori_form['TS_inizio']) ? date('H:i', strtotime($valori_form['TS_inizio'])) : '';
$ora_fine_valore   = !empty($valori_form['TS_fine']) ? date('H:i', strtotime($valori_form['TS_fine'])) : '';
// Decidiamo quale data mostrare nel modulo delle presenze
$data_presenza_default = !empty($data_pres_get) ? $data_pres_get : $data_valore;
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Modifica Lezione e Gestione Presenze</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/agenform.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>

    <style>
        body {
            padding: 20px;
            color: #333;
            background-color: var(--grigio_brand, #86878b);
            font-family: 'Barlow Condensed', sans-serif;
        }

        /* CONTAINER PRINCIPALE CENTRATO */
        .page-wrapper {
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        /* CONTAINER DEL PULSANTE INDIETRO */
        .btn-container {
            text-align: center;
            margin-bottom: 20px;
            width: 100%;
        }

        /* STRUTTURA A TRE COLONNE / SEZIONI CENTRATE */
        .layout-modifica {
            display: flex;
            gap: 20px;
            width: 100%;
            justify-content: center;
            align-items: flex-start;
            box-sizing: border-box;
        }

        /* COLONNA SINISTRA: MODIFICA LEZIONE (Stessa dimensione di colonna-presenze) */
        .colonna-form {
            width: 380px;
            flex-shrink: 0;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            box-sizing: border-box;
        }

        /* COLONNA DESTRA: MODIFICA PRESENZE */
        .colonna-presenze {
            width: 380px;
            flex-shrink: 0;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-top: 4px solid var(--rosso_brand, #951d3b);
            box-sizing: border-box;
        }

        /* PULSANTE RETRO DASHBOARD */
        .btn-indietro {
            display: inline-block;
            padding: 10px 24px;
            background-color: var(--rosso_brand, #951d3b);
            color: #ffffff !important;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
            transition: all 0.2s ease-in-out;
        }

        .btn-indietro:hover {
            background-color: #7a152f;
            transform: translateY(-2px);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 9px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form-row {
            display: flex;
            gap: 12px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .alert {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .btn-salva {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            font-size: 1rem;
        }

        .bottoni-presenza-container {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            width: 100%;
            box-sizing: border-box;
        }

        .bottoni-presenza-container button {
            flex: 1;
            padding: 10px 4px;
            font-size: 0.8rem;
            box-sizing: border-box;
            min-width: 0;
        }

        h2 {
            margin-top: 0;
            font-size: 1.4rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .flatpickr-day.giorno-occupato {
            background-color: #2c3e50 !important;
            color: #ffffff !important;
            font-weight: bold !important;
        }

        .flatpickr-day.giorno-libero {
            background-color: #f8f9fa !important;
            color: #495057 !important;
            border: 1px solid #e9ecef !important;
        }

        .flatpickr-day.selected {
            background-color: var(--rosso_brand, #d9534f) !important;
            color: #ffffff !important;
        }
    </style>
</head>

<body>
    <?php
    require_once __DIR__ . "/navbar.php";
    navbar([
        ['<a href="dashboard_staff.php" class="btn-indietro">← Ritorna alla Dashboard</a>']
    ], "Gestione lezioni e presenze");
    ?>

    <div class="page-wrapper">

        <div class="layout-modifica">

            <!-- COLONNA SINISTRA: MODIFICA LEZIONE -->
            <div class="colonna-form">
                <h2>Modifica Lezione</h2>

                <?php if (!empty($messaggio)): ?>
                    <div class="alert"><?= $messaggio ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <input type="hidden" name="azione" value="salva_lezione">

                    <div class="form-group">
                        <label for="id_corso">Corso</label>
                        <select name="id_corso" id="id_corso" required>
                            <option value="">-- Seleziona un corso --</option>
                            <?php foreach ($corsi as $corso): ?>
                                <option value="<?= $corso['ID_corso'] ?>" <?= ($valori_form['ID_corso'] == $corso['ID_corso']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($corso['nome_corso']) ?> — Ed. <?= htmlspecialchars($corso['cod_edizione']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cod_materia">Materia</label>
                        <select name="cod_materia" id="cod_materia" required>
                            <option value="">-- Seleziona una materia --</option>
                            <?php foreach ($materie as $materia): ?>
                                <option value="<?= $materia['cod_materia'] ?>" <?= ($valori_form['cod_materia'] == $materia['cod_materia']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($materia['nome_materia']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="data_lezione">Data Lezione</label>
                        <input type="text" name="data_lezione" id="data_lezione" value="<?= $data_valore ?>" required readonly style="background-color: #fff; cursor: pointer;">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ora_inizio">Ora Inizio</label>
                            <input type="time" name="ora_inizio" id="ora_inizio" value="<?= $ora_inizio_valore ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="ora_fine">Ora Fine</label>
                            <input type="time" name="ora_fine" id="ora_fine" value="<?= $ora_fine_valore ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="aula">Aula</label>
                        <input type="text" name="aula" id="aula" value="<?= htmlspecialchars($valori_form['cod_aula']) ?>" placeholder="es. Laboratorio 3">
                    </div>

                    <button type="submit" class="btn btn-create btn-salva">Salva Modifiche Lezione</button>
                </form>
            </div>

            <!-- COLONNA DESTRA: MODIFICA / GESTIONE PRESENZA STUDENTE -->
            <div class="colonna-presenze">
                <h2>Modifica Presenze</h2>

                <?php if (!empty($messaggio_presenza)): ?>
                    <div class="alert"><?= $messaggio_presenza ?></div>
                <?php endif; ?>

                <form action="" method="POST" id="form-presenza">
                    <input type="hidden" name="azione" id="azione_presenza" value="modifica_presenza">

                    <!-- NUOVO BLOCCO CON VALORI PRE-COMPILATI -->
                    <div class="form-group">
                        <label for="matricola">Matricola Studente</label>
                        <input type="text" name="matricola" id="matricola" placeholder="es. 12345" required value="<?= htmlspecialchars($matricola_get) ?>">
                    </div>

                    <div class="form-group">
                        <label for="data_presenza">Data Entrata / Presenza</label>
                        <input type="text" name="data_presenza" id="data_presenza" placeholder="Seleziona data..." required readonly style="background-color: #fff; cursor: pointer;">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ora_entrata">Ora Entrata (lascia vuoto per NULL)</label>
                            <input type="time" name="ora_entrata" id="ora_entrata" value="<?= htmlspecialchars($entrata_get) ?>">
                        </div>

                        <div class="form-group">
                            <label for="ora_uscita">Ora Uscita (lascia vuoto per NULL)</label>
                            <input type="time" name="ora_uscita" id="ora_uscita" value="<?= htmlspecialchars($uscita_get) ?>">
                        </div>
                    </div>

                    <div class="bottoni-presenza-container">
                        <button type="submit" class="btn btn-create" style="background-color: #2c3e50;" onclick="document.getElementById('azione_presenza').value='modifica_presenza';">Modifica Presenza</button>
                        <button type="submit" class="btn btn-create" style="background-color: var(--rosso_brand, #951d3b);" onclick="document.getElementById('azione_presenza').value='cancella_presenza';">Cancella Presenza</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        const dateOccupate = <?= json_encode($date_occupate) ?>;

        // Inizializzazione Flatpickr per la data della lezione
        flatpickr("#data_lezione", {
            locale: "it",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            defaultDate: "<?= $data_valore ?>",
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const dataFormattata = fp.formatDate(dayElem.dateObj, "Y-m-d");
                if (dateOccupate.includes(dataFormattata)) {
                    dayElem.classList.add("giorno-occupato");
                    dayElem.title = "Occupato - Lezione presente";
                } else {
                    dayElem.classList.add("giorno-libero");
                }
            }
        });

        // Inizializzazione Flatpickr per la data della presenza manuale
        flatpickr("#data_presenza", {
            locale: "it",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            defaultDate: "<?= $data_presenza_default ?>" // Usa la variabile $data_presenza_default creata in alto
        });
    </script>

</body>

</html>