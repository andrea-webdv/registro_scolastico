<?php
require_once __DIR__ . "/collegamento_db/Letture_incrociate.php"; //[cite: 1, 4]

// Recupero l'ID corso passato tramite GET per la selezione dinamica delle materie
$corso_selezionato_lezione = $_GET['corso_lezione'] ?? '';

// Se un corso è selezionato, estraggo le materie correlate tramite la funzione nativa
if (!empty($corso_selezionato_lezione)) {
    $materie_lezione = lettura_materie_corso([$corso_selezionato_lezione]); //[cite: 1]
} else {
    $materie_lezione = [];
}
?>

<?php
session_start();
require_once __DIR__ . "/collegamento_db/Collegamento.php";

if (!isset($_SESSION['staff_codice'])) {
    header("Location: index.php?e=non_loggato");
    exit();
}

$connessione = Collegamento();
$messaggio_successo = "";
$messaggio_errore = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    try {
        // 1. INSERIMENTO NUOVO CORSO ESTESO
        if ($azione === 'aggiungi_corso') {
            $nome_corso       = trim($_POST['nome_corso']);
            $cod_edizione     = trim($_POST['cod_edizione']); // Formato YYYY-MM (es. 2026-07)
            $cod_corso        = trim($_POST['cod_corso']);
            $attivo           = isset($_POST['attivo']) ? 1 : 0;
            $iscrizioni_aperte = isset($_POST['iscrizioni_aperte']) ? 1 : 0;
            $durata           = !empty($_POST['durata']) ? (int)$_POST['durata'] : null;

            if (!empty($nome_corso) && !empty($cod_corso)) {
                $sql = "INSERT INTO corsi (nome_corso, cod_edizione, cod_corso, attivo, accetta_iscrizioni, durata) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $connessione->prepare($sql);
                $stmt->execute([$nome_corso, $cod_edizione, $cod_corso, $attivo, $iscrizioni_aperte, $durata]);

                $messaggio_successo = "Corso '$nome_corso' ($cod_corso) aggiunto con successo!";
            } else {
                $messaggio_errore = "Compila tutti i campi obbligatori del corso (Nome e Codice Corso).";
            }
        }

        // 2. GESTIONE MATERIA (INSERIMENTO O MODIFICA DINAMICA)
        elseif ($azione === 'aggiungi_materia') {
            $cod_materia = trim($_POST['cod_materia']);
            $nome_materia = trim($_POST['nome_materia']);
            $durata_materia = !empty($_POST['durata_materia']) ? (int)$_POST['durata_materia'] : null;
            $id_corso_materia = $_POST['id_corso_materia'] ?? '';

            // Variabile di controllo generata dal JavaScript
            $tipo_operazione = $_POST['tipo_operazione_materia'] ?? 'insert';

            if (!empty($cod_materia) && !empty($nome_materia) && !empty($durata_materia) && !empty($id_corso_materia)) {
                $connessione->beginTransaction();
                try {

                    if ($tipo_operazione === 'update') {
                        // Procedura di aggiornamento record esistente
                        $stmt = $connessione->prepare("UPDATE materie SET nome_materia = ?, durata_materia = ? WHERE cod_materia = ?");
                        $stmt->execute([$nome_materia, $durata_materia, $cod_materia]);
                        $messaggio_successo = "Materia '$nome_materia' aggiornata con successo!";
                    } else {
                        // Procedura di inserimento nuovo record
                        $stmt = $connessione->prepare("INSERT INTO materie (cod_materia, nome_materia, durata_materia) VALUES (?, ?, ?)");
                        $stmt->execute([$cod_materia, $nome_materia, $durata_materia]);
                        $messaggio_successo = "Materia '$nome_materia' aggiunta con successo!";
                    }

                    // Associazione al corso (eseguita in entrambi i casi)
                    // Avviso: In caso di Update, questa istruzione aggiungerà un nuovo collegamento. 
                    // Assicurati che non generi duplicati se la chiave primaria composta nella tabella corsi_materie non è impostata.
                    $stmt2 = $connessione->prepare("INSERT IGNORE INTO corsi_materie (ID_corso, cod_materia) VALUES (?, ?)");
                    $stmt2->execute([$id_corso_materia, $cod_materia]);

                    $connessione->commit();
                } catch (PDOException $e) {
                    $connessione->rollBack();
                    throw $e;
                }
            } else {
                $messaggio_errore = "Compila tutti i campi della materia, inclusi durata e corso di appartenenza.";
            }
        }

        // 2bis. MODIFICA STATO CORSO ESISTENTE (attivo / accetta_iscrizioni)
        elseif ($azione === 'modifica_corso') {
            $id_corso_mod = $_POST['id_corso_modifica'] ?? '';
            // Ogni coppia (attiva/disattiva) arriva come un unico valore radio-like:
            // usiamo la presenza del checkbox "attiva_corso" / "attiva_iscrizioni" come stato finale.
            $nuovo_attivo    = ($_POST['stato_corso'] ?? '') === 'attiva' ? 1 : 0;
            $nuovo_iscrizioni = ($_POST['stato_iscrizioni'] ?? '') === 'attiva' ? 1 : 0;

            if (!empty($id_corso_mod)) {
                $stmt = $connessione->prepare("UPDATE corsi SET attivo = ?, accetta_iscrizioni = ? WHERE ID_corso = ?");
                $stmt->execute([$nuovo_attivo, $nuovo_iscrizioni, $id_corso_mod]);
                $messaggio_successo = "Corso aggiornato con successo!";
            } else {
                $messaggio_errore = "Seleziona un corso da modificare.";
            }
        }

        // 3. INSERIMENTO NUOVA LEZIONE (singola o su più giorni)
        elseif ($azione === 'aggiungi_lezione') {
            $data_lezione = $_POST['data_lezione'];
            $data_fine_intervallo = trim($_POST['data_fine_intervallo'] ?? '');
            $escludi_weekend = isset($_POST['escludi_weekend']);
            $ora_inizio   = $_POST['ora_inizio'];
            $ora_fine     = $_POST['ora_fine'];
            $cod_aula     = trim($_POST['cod_aula']);
            $sede         = trim($_POST['sede']) ?? "Savigliano";
            $id_corso     = $_POST['id_corso'];
            $cod_materia  = $_POST['cod_materia'];

            if (!empty($data_lezione) && !empty($ora_inizio) && !empty($ora_fine) && !empty($id_corso) && !empty($cod_materia)) {

                // Costruisce l'elenco delle date da inserire: solo il giorno scelto,
                // oppure ogni giorno tra data_lezione e data_fine_intervallo (se compilata)
                $date_da_inserire = [];
                $data_corrente = new DateTime($data_lezione);
                $data_limite = !empty($data_fine_intervallo) ? new DateTime($data_fine_intervallo) : clone $data_corrente;

                if ($data_limite < $data_corrente) {
                    $messaggio_errore = "La data fine non può essere precedente alla data inizio.";
                } else {
                    while ($data_corrente <= $data_limite) {
                        $giorno_settimana = (int)$data_corrente->format('N'); // 6 = sabato, 7 = domenica
                        if (!$escludi_weekend || $giorno_settimana < 6) {
                            $date_da_inserire[] = $data_corrente->format('Y-m-d');
                        }
                        $data_corrente->modify('+1 day');
                    }

                    if (empty($date_da_inserire)) {
                        $messaggio_errore = "Nessun giorno valido nell'intervallo selezionato (controlla l'esclusione weekend).";
                    } else {
                        $connessione->beginTransaction();
                        try {
                            $stmt = $connessione->prepare("INSERT INTO lezioni (TS_inizio, TS_fine, cod_aula, sede, ID_corso, cod_materia) VALUES (?, ?, ?, ?, ?, ?)");
                            foreach ($date_da_inserire as $giorno) {
                                $ts_inizio = $giorno . ' ' . $ora_inizio . ':00';
                                $ts_fine   = $giorno . ' ' . $ora_fine . ':00';
                                $stmt->execute([$ts_inizio, $ts_fine, $cod_aula, $sede, $id_corso, $cod_materia]);
                            }
                            $connessione->commit();

                            $n = count($date_da_inserire);
                            $messaggio_successo = $n === 1
                                ? "Nuova lezione programmata con successo!"
                                : "Programmate $n lezioni con successo (dal " . reset($date_da_inserire) . " al " . end($date_da_inserire) . ")!";
                        } catch (PDOException $e) {
                            $connessione->rollBack();
                            throw $e;
                        }
                    }
                }
            } else {
                $messaggio_errore = "Compila tutti i campi obbligatori per la lezione.";
            }
        }
    } catch (PDOException $e) {
        $messaggio_errore = "Errore nel salvataggio: " . $e->getMessage();
    }
}

$corsi = $connessione->query("SELECT ID_corso, cod_corso, nome_corso, attivo FROM corsi ORDER BY corsi.`cod_corso` DESC;")->fetchAll(PDO::FETCH_ASSOC);
$materie = $connessione->query("SELECT cod_materia, nome_materia FROM materie ORDER BY cod_materia DESC;")->fetchAll(PDO::FETCH_ASSOC);

$ultimo_cod_corso = $corsi[0]['cod_corso'] ?? 'C000';
$ultimo_cod_materia = $materie[0]['cod_materia'] ?? 'M000';

// Analisi e scomposizione della stringa
function nuovo_codice($codice)
{

    if (preg_match('/^([A-Za-z]+)(\d+)$/', $codice, $matches)) {
        $prefisso = $matches[1];                       // Es. "C"
        $lunghezza_cifre = strlen($matches[2]);       // Es. 3 cifre ("005")
        $prossimo_numero = (int)$matches[2] + 1;      // Es. 5 + 1 = 6

        // Ricostruzione con riempimento di zeri a sinistra
        return $prefisso . str_pad($prossimo_numero, $lunghezza_cifre, '0', STR_PAD_LEFT);
    } else {
        // Valore di default in caso di formato non riconosciuto
        exit();
    }
}

$ultimo_cod_corso = nuovo_codice($ultimo_cod_corso);
$ultimo_cod_materia = nuovo_codice($ultimo_cod_materia);

// Corsi modificabili: esclude solo quelli già terminati (attivo = 0 E accetta_iscrizioni = 0)
$corsi_modificabili = $connessione->query(
    "SELECT ID_corso, nome_corso, cod_edizione, attivo, accetta_iscrizioni FROM corsi 
     WHERE NOT (attivo = 0 AND accetta_iscrizioni = 0) 
     ORDER BY nome_corso, cod_edizione"
)->fetchAll(PDO::FETCH_ASSOC);

// refresh della pagina dopo 2 secondi
if (!empty($messaggio_successo) || !empty($messaggio_errore)) {
    header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Aggiungi Corsi e Lezioni - AgenForm</title>

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
        }

        .wrapper {
            max-width: 1000px;
            margin: 0 auto;
        }

        .titolo-pagina {
            color: #ffffff;
            margin-bottom: 15px;
            text-align: center;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.2rem;
            text-transform: uppercase;
        }

        .btn-indietro {
            display: inline-block;
            padding: 12px 28px;
            background-color: var(--rosso_brand, #951d3b);
            color: #ffffff !important;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
            transition: all 0.2s ease-in-out;
            text-align: center;
        }

        .btn-indietro:hover {
            background-color: #7a152f;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.35);
            transform: translateY(-2px);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-top: 4px solid var(--rosso_brand, #951d3b);
        }

        .card-full {
            grid-column: 1 / -1;
        }

        @media (max-width: 900px) {
            .grid-container {
                grid-template-columns: 1fr;
            }
        }

        .card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
            box-sizing: border-box;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        /* CSS Specifico per le Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
            font-weight: 600;
            color: #333;
        }

        .btn-salva {
            width: 100%;
            padding: 10px;
            font-size: 1.1rem;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="wrapper">

        <!-- TITOLO IN ALTO -->
        <h2 class="titolo-pagina">Gestione Organizzazione: Corsi, Materie e Lezioni</h2>

        <!-- PULSANTE SOTTO IL TITOLO -->
        <div style="text-align: center; margin-bottom: 25px;">
            <a href="dashboard_staff.php" class="btn-indietro">Ritorna alla Dashboard</a>
        </div>

        <!-- MESSAGGI DI AVVISO -->
        <?php if (!empty($messaggio_successo)): ?>
            <div class="alert-success"><?= htmlspecialchars($messaggio_successo) ?></div>
        <?php endif; ?>
        <?php if (!empty($messaggio_errore)): ?>
            <div class="alert-error"><?= htmlspecialchars($messaggio_errore) ?></div>
        <?php endif; ?>

        <div class="grid-container">

            <!-- CARD 1: CREA CORSO (ESTESA) -->
            <div class="card">
                <h3>1. Crea un nuovo Corso</h3>
                <form action="" method="POST">
                    <input type="hidden" name="azione" value="aggiungi_corso">

                    <div class="form-group">
                        <label for="nome_corso">Nome del Corso *</label>
                        <input type="text" name="nome_corso" id="nome_corso" placeholder="Es. Operatore di Database" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cod_corso">Codice Corso *</label>
                            <input type="text" name="cod_corso" id="cod_corso" placeholder="Es. C005" value="<?php echo htmlspecialchars($ultimo_cod_corso); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="cod_edizione">Codice Edizione (YYYY-MM)</label>
                            <input type="month" name="cod_edizione" id="cod_edizione">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="durata">Durata (Ore)</label>
                        <input type="number" name="durata" id="durata" min="1" placeholder="Es. 600">
                    </div>

                    <div class="form-row">
                        <div class="checkbox-group">
                            <input type="checkbox" name="attivo" id="attivo" value="1" checked>
                            <label for="attivo">Corso Attivo</label>
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" name="iscrizioni_aperte" id="iscrizioni_aperte" value="1" checked>
                            <label for="iscrizioni_aperte">Accetta Iscrizioni</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-create btn-salva">Aggiungi Corso</button>
                </form>
            </div>

            <!-- CARD 1bis: GESTISCI STATO CORSO ESISTENTE -->
            <div class="card">
                <h3>Gestisci Stato Corso</h3>
                <form action="" method="POST" id="form-modifica-corso">
                    <input type="hidden" name="azione" value="modifica_corso">
                    <input type="hidden" name="stato_corso" id="stato_corso" value="">
                    <input type="hidden" name="stato_iscrizioni" id="stato_iscrizioni" value="">

                    <div class="form-group">
                        <label for="id_corso_modifica">Seleziona Corso *</label>
                        <select name="id_corso_modifica" id="id_corso_modifica" required>
                            <option value="">-- Seleziona un corso --</option>
                            <?php foreach ($corsi_modificabili as $c): ?>
                                <option value="<?= $c['ID_corso'] ?>"
                                    data-attivo="<?= $c['attivo'] ?>"
                                    data-iscrizioni="<?= $c['accetta_iscrizioni'] ?>">
                                    <?= htmlspecialchars($c['nome_corso']) ?> — Ed. <?= htmlspecialchars($c['cod_edizione']) ?> (<?= $c['attivo'] ? 'Attivo' : 'Non attivo' ?>, <?= $c['accetta_iscrizioni'] ? 'Iscrizioni aperte' : 'Iscrizioni chiuse' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="blocco-checkbox" style="opacity: 0.4; pointer-events: none;">
                        <label>Stato Corso</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="chk_attiva_corso">
                            <label for="chk_attiva_corso">Attiva Corso</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="chk_disattiva_corso">
                            <label for="chk_disattiva_corso">Disattiva Corso</label>
                        </div>

                        <label style="margin-top: 15px; display: block;">Stato Iscrizioni</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="chk_attiva_iscrizioni">
                            <label for="chk_attiva_iscrizioni">Attiva Iscrizioni</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="chk_disattiva_iscrizioni">
                            <label for="chk_disattiva_iscrizioni">Disattiva Iscrizioni</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-create btn-salva" id="btn-salva-modifica" disabled>Salva Cambiamenti</button>
                </form>
            </div>

            <!-- CARD 2: CREA/MODIFICA MATERIA -->
            <div class="card">
                <h3>2. Crea/Modifica Materia</h3>
                <form action="" method="POST">
                    <input type="hidden" name="azione" value="aggiungi_materia">

                    <!-- Nuovi campi nascosti per gestire la logica di smistamento -->
                    <input type="hidden" name="tipo_operazione_materia" id="tipo_operazione_materia" value="insert">

                    <div class="form-group">
                        <label for="cod_materia">Codice Materia (ID Univoco) *</label>
                        <input type="text" name="cod_materia" id="cod_materia" placeholder="Es. M001" value="<?php echo htmlspecialchars($ultimo_cod_materia); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="nome_materia">Nome della Materia *</label>
                        <!-- L'attributo list="lista_materie" collega l'input al datalist -->
                        <input type="text" name="nome_materia" id="nome_materia" list="lista_materie" placeholder="Scrivi per cercare o inserisci un nuovo nome..." required autocomplete="off">

                        <datalist id="lista_materie">
                            <!-- Il ciclo popola le opzioni usando l'array già definito nel tuo script -->
                            <?php foreach ($materie as $m): ?>
                                <option data-cod="<?= htmlspecialchars($m['cod_materia']) ?>" value="<?= htmlspecialchars($m['nome_materia']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="durata_materia">Durata Materia (Ore) *</label>
                        <input type="number" name="durata_materia" id="durata_materia" min="1" placeholder="Es. 50" required>
                    </div>

                    <div class="form-group">
                        <label for="id_corso_materia">Corso di appartenenza *</label>
                        <select name="id_corso_materia" id="id_corso_materia" required>
                            <option value="">-- Seleziona un corso --</option>
                            <?php foreach ($corsi_modificabili as $c): ?>
                                <option value="<?= $c['ID_corso'] ?>"><?= htmlspecialchars($c['nome_corso']) ?> — Ed. <?= htmlspecialchars($c['cod_edizione']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-create btn-salva">Salva Materia</button>
                </form>
            </div>

            <!-- CARD 3: PROGRAMMA LEZIONE -->
            <div class="card card-full">
                <h3>3. Programma una nuova Lezione</h3>
                <form action="" method="POST">
                    <input type="hidden" name="azione" value="aggiungi_lezione">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_corso">Seleziona Corso *</label>
                            <select name="id_corso" id="id_corso" required>
                                <option value="">-- Seleziona un corso --</option>
                                <?php foreach ($corsi as $corso): ?>
                                    <?php if ($corso['attivo'] == 1): ?>
                                        <option value="<?= $corso['ID_corso'] ?>" <?= ($corso_selezionato_lezione == $corso['ID_corso']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($corso['nome_corso']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="cod_materia">Seleziona Materia *</label>
                            <select name="cod_materia" id="cod_materia" required <?= empty($corso_selezionato_lezione) ? 'disabled' : '' ?>>
                                <option value="">--<?= empty($corso_selezionato_lezione) ? 'Seleziona prima un corso' : 'Seleziona una materia' ?>--</option>
                                <?php foreach ($materie_lezione as $materia): ?>
                                    <option value="<?= $materia['cod_materia'] ?>"><?= htmlspecialchars($materia['nome_materia']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_lezione">Da (Data Inizio) *</label>
                            <input type="text" name="data_lezione" id="data_lezione" placeholder="Data inizio..." required readonly style="background-color: #fff; cursor: pointer;">
                        </div>

                        <div class="form-group">
                            <label for="data_fine_intervallo">A (Data Fine)</label>
                            <input type="text" name="data_fine_intervallo" id="data_fine_intervallo" placeholder="Lascia vuoto per un solo giorno..." readonly style="background-color: #fff; cursor: pointer;">
                        </div>

                        <div class="form-group">
                            <label for="ora_inizio">Ora Inizio *</label>
                            <input type="time" name="ora_inizio" id="ora_inizio" required>
                        </div>

                        <div class="form-group">
                            <label for="ora_fine">Ora Fine *</label>
                            <input type="time" name="ora_fine" id="ora_fine" required>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="escludi_weekend" id="escludi_weekend">
                        <label for="escludi_weekend">Escludi sabato e domenica</label>
                    </div>

                    <div class="form-group">
                        <label for="cod_aula">Aula</label>
                        <input type="text" name="cod_aula" id="cod_aula" maxlength="4" placeholder="Es. 114b" required>
                    </div>

                    <div class="form-group">
                        <label for="sede">Sede (Opzionale)</label>
                        <input type="text" name="sede" id="sede" placeholder="Es. Savigliano">
                    </div>

                    <button type="submit" class="btn btn-create btn-salva">Programma Lezione</button>
                </form>
            </div>

        </div>
    </div>
    <script>
        document.getElementById('id_corso').addEventListener('change', function() {
            const idCorso = this.value;
            if (idCorso) {
                window.location.href = 'aggiungi.php?corso_lezione=' + encodeURIComponent(idCorso);
            } else {
                window.location.href = 'aggiungi.php';
            }
        });
    </script>
    <script>
        // --- Gestione box "Gestisci Stato Corso" ---
        const selectCorsoMod = document.getElementById('id_corso_modifica');
        const blocco = document.getElementById('blocco-checkbox');
        const btnSalvaModifica = document.getElementById('btn-salva-modifica');

        const chkAttivaCorso = document.getElementById('chk_attiva_corso');
        const chkDisattivaCorso = document.getElementById('chk_disattiva_corso');
        const chkAttivaIscr = document.getElementById('chk_attiva_iscrizioni');
        const chkDisattivaIscr = document.getElementById('chk_disattiva_iscrizioni');

        const statoCorsoInput = document.getElementById('stato_corso');
        const statoIscrizioniInput = document.getElementById('stato_iscrizioni');

        // Rende una coppia di checkbox mutuamente esclusiva (comportamento "radio")
        function collegaCoppia(chkOn, chkOff, hiddenInput) {
            chkOn.addEventListener('change', () => {
                if (chkOn.checked) {
                    chkOff.checked = false;
                    hiddenInput.value = 'attiva';
                } else {
                    hiddenInput.value = 'disattiva';
                }
            });
            chkOff.addEventListener('change', () => {
                if (chkOff.checked) {
                    chkOn.checked = false;
                    hiddenInput.value = 'disattiva';
                } else {
                    hiddenInput.value = 'attiva';
                }
            });
        }
        collegaCoppia(chkAttivaCorso, chkDisattivaCorso, statoCorsoInput);
        collegaCoppia(chkAttivaIscr, chkDisattivaIscr, statoIscrizioniInput);

        selectCorsoMod.addEventListener('change', () => {
            const opzione = selectCorsoMod.selectedOptions[0];

            if (!selectCorsoMod.value) {
                blocco.style.opacity = '0.4';
                blocco.style.pointerEvents = 'none';
                btnSalvaModifica.disabled = true;
                return;
            }

            const attivo = opzione.dataset.attivo === '1';
            const iscrizioni = opzione.dataset.iscrizioni === '1';

            // Autocompila le checkbox in base allo stato attuale del corso
            chkAttivaCorso.checked = attivo;
            chkDisattivaCorso.checked = !attivo;
            chkAttivaIscr.checked = iscrizioni;
            chkDisattivaIscr.checked = !iscrizioni;

            statoCorsoInput.value = attivo ? 'attiva' : 'disattiva';
            statoIscrizioniInput.value = iscrizioni ? 'attiva' : 'disattiva';

            blocco.style.opacity = '1';
            blocco.style.pointerEvents = 'auto';
            btnSalvaModifica.disabled = false;
        });
    </script>
    <script>
        const fpInizio = flatpickr("#data_lezione", {
            locale: "it",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            minDate: "today",
            onChange: function(selectedDates, dateStr) {
                // La data fine non può essere precedente alla data inizio scelta
                fpFine.set('minDate', dateStr);
            }
        });

        const fpFine = flatpickr("#data_fine_intervallo", {
            locale: "it",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            minDate: "today"
        });
    </script>
    <script>
        // --- Gestione dinamica Insert/Update Materia ---
        const inputNomeMateria = document.getElementById('nome_materia');
        const inputCodMateria = document.getElementById('cod_materia');
        const inputTipoOperazione = document.getElementById('tipo_operazione_materia');

        // Memorizzo il codice suggerito dal PHP per ripristinarlo in caso di nuovo inserimento
        const codiceIniziale = inputCodMateria.value;

        inputNomeMateria.addEventListener('input', function() {
            const val = this.value;
            const options = document.getElementById('lista_materie').options;
            let materiaTrovata = false;

            // Scansione clinica delle opzioni disponibili
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === val) {
                    materiaTrovata = true;

                    // Imposta i parametri per l'UPDATE
                    inputTipoOperazione.value = 'update';

                    // Recupera il codice originale e blocca il campo per prevenire alterazioni di chiavi primarie
                    const codOriginale = options[i].getAttribute('data-cod');
                    inputCodMateria.value = codOriginale;
                    inputCodMateria.setAttribute('readonly', true);
                    inputCodMateria.style.backgroundColor = '#e9ecef'; // Feedback visivo
                    break;
                }
            }

            // Ripristino parametri per l'INSERT se il nome non esiste
            if (!materiaTrovata) {
                inputTipoOperazione.value = 'insert';
                inputCodMateria.value = codiceIniziale;
                inputCodMateria.removeAttribute('readonly');
                inputCodMateria.style.backgroundColor = '';
            }
        });
    </script>
</body>

</html>