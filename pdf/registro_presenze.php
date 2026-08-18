<?php
// Classe Presenza
class Presenza
{
    public $id_lezione, $data, $inizio, $fine, $materia, $entrata, $uscita;

    public function __construct($id_lezione, $data, $inizio, $fine, $materia, $entrata, $uscita)
    {
        $this->id_lezione = $id_lezione;
        $this->data = $data;
        $this->inizio = $inizio;
        $this->fine = $fine;
        $this->materia = $materia;
        $this->entrata = $entrata;
        $this->uscita = $uscita;
    }
}


session_start();
require_once __DIR__ . "/../collegamento_db/Letture_incrociate.php";
require_once __DIR__ . "/Calcolo_ore.php"; 


// Reindirizzamento in caso di assenza di login
if (!isset($_SESSION['staff_codice']) && !isset($_SESSION['studente_matricola'])) {
    header("Location: ./../index.php?e=login");
    exit();
}


$filtri = [];
$staff_auth = isset($_SESSION['staff_codice']) && !isset($_SESSION['studente_matricola']);
$mat_sel = "";
$nome_studente = '';

if ($staff_auth) {
    // Reset dei filtri se richiesto dal link
    if (isset($_GET['reset'])) {
        unset($_SESSION['matricola_selezionata'], $_SESSION['corso_selezionato'], $_SESSION['materia_selezionata'], $_SESSION['mostra_non_attivi']);
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit();
    }

    // Salvataggio filtri in Sessione al submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['matricola_selezionata'] = !empty($_POST['filtro_matricola']) ? explode('|', $_POST['filtro_matricola'])[0] : '';
        $_SESSION['matricola_nome'] = !empty($_POST['filtro_matricola']) ? explode('|', $_POST['filtro_matricola'])[1] : '';
        $_SESSION['corso_selezionato'] = $_POST['filtro_corso'] ?? '';
        $_SESSION['materia_selezionata'] = $_POST['filtro_materia'] ?? '';
        // Salvataggio dello stato del checkbox "mostra non attivi"
        $_SESSION['mostra_non_attivi'] = isset($_POST['mostra_non_attivi']) ? 1 : 0;
    }

    $mat_sel = $_SESSION['matricola_selezionata'] ?? $_SESSION['studente_matricola'] ?? '';

    $cor_sel = $_SESSION['corso_selezionato'] ?? '';
    $mat_materia_sel = $_SESSION['materia_selezionata'] ?? '';
    $mostra_non_attivi = $_SESSION['mostra_non_attivi'] ?? 0; // Recupero valore checkbox
} else {
    // Se lo studente è loggato, mostra solo le sue presenze
    $matricola_sessione = $_SESSION['studente_matricola'] ?? null;
    $mat_sel = $matricola_sessione ?? '';
    $cor_sel = '';
    $mat_materia_sel = '';
    $mostra_non_attivi = 0;
}

// Selezione corso tramite modale (link "?corso=ID_corso")
if (isset($_GET['corso']) && $_GET['corso'] !== '') {
    $_SESSION['corso_selezionato'] = $_GET['corso'];

    // Pattern Post/Redirect/Get: ripulisco la query string dal parametro
    // "corso" così non resta nell'URL e non viene riapplicato ad ogni refresh
    $query_pulita = $_GET;
    unset($query_pulita['corso']);
    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?')
        . (!empty($query_pulita) ? '?' . http_build_query($query_pulita) : '');

    header("Location: " . $redirect_url);
    exit();
}

// Applico il filtro corso eventualmente salvato in sessione tramite modale
// (solo se lo staff non ha già scelto un corso dal menu a tendina)
if ($staff_auth && empty($cor_sel)) {
    $cor_sel = $_SESSION['corso_selezionato'] ?? '';
}

// 4bis. Compilazione delle variabili "filtri selezionati" con la stessa struttura ad
// array usata da dashboard_studente.php ($corsi_selezionati, $materie_selezionate).
// I filtri di registro_presenze sono a selezione singola (menu a tendina anziché
// checkbox), quindi ogni valore selezionato viene incapsulato in un array di un elemento,
// così le funzioni importate (che richiedono la stessa struttura in entrambe le pagine)
// ricevono sempre un array, sia da qui che da dashboard_studente.php.
$corsi_selezionati = !empty($cor_sel) ? [$cor_sel] : [];
$materie_selezionate = !empty($mat_materia_sel) ? [$mat_materia_sel] : [];

$matricola_selzionata = !empty($mat_sel);

// 5. Costruzione dei filtri per la lettura dal Database, con la stessa struttura
// prodotta da dashboard_studente.php per le funzioni importate in comune
$filtri = [];
$riferimento_lezioni = [];
$durata_selezionata = 0;
$corsi_frequentati = [];

if (!empty($mat_sel)) {
    $filtri[] = ['matricola' => $mat_sel];

    // Lettura dei corsi frequentati dalla matricola selezionata/loggata: utile sia
    // come fallback quando non è stato scelto un corso/materia, sia al calcolo ore
    $corsi_frequentati = lettura_corsi_studente($mat_sel);
}

if (!empty($materie_selezionate)) {
    $filtri[] = ['materie' => $materie_selezionate];

    $riferimento_lezioni = ['materie', []];
    foreach ($materie_selezionate as $cod_materia) {
        $riferimento_lezioni[1][] = ['cod_materia' => $cod_materia];
    };
} elseif (!empty($corsi_selezionati)) {
    $filtri[] = ['corsi' => $corsi_selezionati];

    $riferimento_lezioni = ['corsi', []];
    foreach ($corsi_selezionati as $ID_corso) {
        $riferimento_lezioni[1][] = ['ID_corso' => $ID_corso];
    };
} elseif ($matricola_selzionata && !empty($corsi_frequentati)) {
    // Fallback: nessun corso/materia selezionato esplicitamente, si usa il primo
    // corso frequentato dalla matricola selezionata/loggata (come in dashboard_studente.php)
    $default_corso = $corsi_frequentati[0]['ID_corso'];

    $filtri[] = ['corsi' => [$default_corso]];
    $riferimento_lezioni = ['corsi', [['ID_corso' => $default_corso]]];
}


// La query viene eseguita solo ora, a valle della costruzione completa di $filtri
// (in precedenza veniva eseguita prima che i filtri di corso/materia fossero aggiunti
// nel formato corretto, ed usava un formato legacy non più letto dall'ultima versione
// delle funzioni importate)
$presenze_su_db = lettura_presenze($filtri);
$presenze = [];

// Lettura elenchi per i menu a tendina
$studenti_lista = function_exists('lettura_studenti') ? lettura_studenti() : [];

// aggiunta del filtro alle quey, per visualizzare solo i corsi e le materie dello studente
$filtro_matricola = isset($filtri[0]['matricola']) ? $filtri[0]['matricola'] : null;
$filtro_corso = isset($filtri[0]['corsi']) ? $filtri[0]['corsi'] : null;

        require_once __DIR__ . "/../materiali/libreria_debug.php";
        console_log("filtri: " . $filtro_matricola . " & " . json_encode($filtri));

if (!empty($filtri) && $filtro_corso) { 
    $corsi_lista = function_exists('lettura_corsi') ? lettura_corsi($mostra_non_attivi) : [];
    $materie_lista = function_exists('lettura_materie') ? lettura_materie("", $filtro_corso[0]) : [];

} else if ($filtro_matricola) {
    $corsi_lista = function_exists('lettura_corsi') ? lettura_corsi(0, $filtro_matricola) : [];
    $materie_lista = function_exists('lettura_materie') ? lettura_materie($filtro_matricola) : [];


} else if ($mostra_non_attivi && $filtro_matricola) {
    $corsi_lista = function_exists('lettura_corsi') ? lettura_corsi($mostra_non_attivi, $filtro_matricola) : [];
    $materie_lista = function_exists('lettura_materie') ? lettura_materie($filtro_matricola) : [];

} else if ($mostra_non_attivi && $filtro_corso) {
    $corsi_lista = function_exists('lettura_corsi') ? lettura_corsi($mostra_non_attivi, $filtro_matricola) : [];
    $materie_lista = function_exists('lettura_materie') ? lettura_materie($filtro_matricola) : [];
 
} else if ($mostra_non_attivi) {
    $corsi_lista = function_exists('lettura_corsi') ? lettura_corsi($mostra_non_attivi, $filtro_matricola) : [];
    $materie_lista = function_exists('lettura_materie') ? lettura_materie() : [];

} else {
    $corsi_lista = function_exists('lettura_corsi') ? lettura_corsi(0, $filtro_matricola) : [];
    $materie_lista = function_exists('lettura_materie') ? lettura_materie() : [];
}

// --- INIZIO NUOVA LOGICA DI CALCOLO CONDIZIONATA ---

$minuti_totali_presenza = 0;
$riepilogo_ore = ['durata' => 0, 'ore_presenza' => 0, 'ore_assenza' => 0];
$permessi = 0;

// IL CALCOLO SI AVVIA SOLO SE E' SELEZIONATA O LOGGATA UNA MATRICOLA
// ($matricola_selzionata, $corsi_frequentati e $riferimento_lezioni sono già stati
// compilati più sopra, con la stessa struttura usata da dashboard_studente.php)

foreach ($presenze_su_db as $record) {
    $p = new Presenza(
        $record['ID_lezione'] ?? '',
        $record['data_lezione'] ?? '',
        $record['ora_inizio'] ?? '',
        $record['ora_fine'] ?? '',
        $record['nome_materia'] ?? '',
        $record['ora_entrata'] ?? '',
        $record['ora_uscita'] ?? ''
    );
    $presenze[] = $p;

    // accumulo del minutaggio delle presenze SOLO se la matricola è selezionata
    if ($matricola_selzionata) {
        if ($p->entrata !== 'nessuna entrata' && $p->uscita !== 'nessuna uscita' && !empty($p->entrata) && !empty($p->uscita)) {
            /* $ts_in = strtotime($p->entrata);
            $ts_out = strtotime($p->uscita); */
            $ts_in = strtotime($record['ora_entrata']);
            $ts_out = strtotime($record['ora_uscita']);
            if ($ts_out > $ts_in) {
                $minuti_totali_presenza += ($ts_out - $ts_in) / 60;
            }
            $permessi += $record['permessi'];
        }
    }
}

// Procediamo al calcolo SOLO se una matricola è selezionata o loggata.
// $riferimento_lezioni è già stato compilato in precedenza, con la stessa struttura
// prodotta da dashboard_studente.php per l'ultima versione delle funzioni importate.
if ($matricola_selzionata) {
    $riepilogo_ore = calcola_riepilogo_ore($minuti_totali_presenza, $riferimento_lezioni, $permessi);
}

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Registro Presenze</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../css/agenform.css">
    <link rel="stylesheet" href="../css/registro_presenze.css">

    <style>
        body {
            margin: 0;
            background-color: transparent;
            min-height: unset;
            display: block;
            color: #333333;
            padding: 2px 2px 10px;
            font-family: 'Barlow Condensed', sans-serif;
        }

        .layout-due-colonne {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            width: 100%;
            max-width: 100%;
            margin: 0;
        }

        .colonna-sinistra {
            flex: 1;
            min-width: 0;
        }

        .colonna-destra {
            width: 250px;
            flex-shrink: 0;
            background-color: #ffffff;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #e3e3e3;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .colonna-destra h2 {
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--rosso_brand, #951d3b);
            padding-bottom: 6px;
            margin-top: 0;
            margin-bottom: 12px;
            color: #333333;
            text-align: center;
        }

        .label-filtro {
            display: block;
            margin-top: 10px;
            margin-bottom: 4px;
            font-weight: 600;
            font-size: 14px;
            color: #333333;
        }

        .select-filtro-block {
            width: 100%;
            padding: 8px 10px;
            font-size: 13px;
            font-family: inherit;
            color: #333333;
            background-color: #ffffff;
            border: 1.5px solid #cccccc;
            border-radius: 6px;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s ease;
        }

        .select-filtro-block:focus {
            border-color: var(--rosso_brand, #951d3b);
        }

        .finestra_scorrimento {
            max-height: 385px;
            overflow-y: auto;
            border-radius: 8px;
            border: 1px solid #e3e3e3;
        }

        .table-container {
            width: 100%;
        }

        .table-container .row {
            display: grid;
            grid-template-columns: 1.1fr 0.8fr 0.8fr 1.3fr 0.9fr 0.9fr;
            background-color: #ffffff;
        }

        .table-container .row.header {
            background-color: #f2f2f2;
            border-bottom: 2px solid #ccc;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .table-container .row.header .cell {
            color: #000000 !important;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 8px;
        }

        .table-container .row:not(.header) .cell {
            color: #333333;
            font-size: 15px;
            padding: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .table-container .row:not(.header):nth-child(even) {
            background-color: #f9f9f9;
        }

        .table-container .row:not(.header):hover {
            background-color: rgba(149, 29, 59, 0.05);
        }

        .btn-reset-filtri {
            display: block;
            width: 100%;
            margin-top: 15px;
            padding: 8px;
            text-align: center;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 6px;
            color: #333;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-reset-filtri:hover {
            background-color: #e2e2e2;
        }

        /* regole di stile per il conteggio ore */
        #suggerimento {
            font-size: 20px;
            color: #666;
            text-transform: uppercase;
            display: block;
            font-weight: 600;
            border-bottom: 3px solid var(--rosso_brand, #951d3b);
            padding: 1rem 0px 0.4rem 0px;
            text-align: center;
        }

        #report_presenze {
            background: #fff;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #e3e3e3;
            display: flex;
            justify-content: space-around;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);

            * div:nth-child(n+2) {
                border-left: 1px solid #eee;
                padding-left: 15px;
            }

            * {
                font-size: 18px;
            }
        }


        .titoletto_report {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            display: block;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <!-- Contenitore principale per Tabella e Filtri -->
    <div class="layout-due-colonne">

        <div class="colonna-sinistra">

            <div class="finestra_scorrimento">
                <div class="table-container">
                    <div class="row header">
                        <div class="cell">Data</div>
                        <div class="cell">Inizio</div>
                        <div class="cell">Fine</div>
                        <div class="cell">Materia</div>
                        <div class="cell">Entrata</div>
                        <div class="cell">Uscita</div>
                    </div>
                    <?php if (!empty($presenze)): ?>
                        <?php foreach ($presenze as $p): ?>
                            <?php
                            // Inizializziamo la URL di modifica per evitare warning in caso di accesso non staff
                            $url_modifica = '';

                            // Costruiamo i dati da inviare a modifica.php
                            if ($staff_auth) {
                                // urlencode() assicura che gli spazi e i formati orari viaggino sicuri nell'URL
                                if ($staff_auth) {
                                    // Includiamo id=$p->id_lezione per superare il controllo in modifica.php
                                    $url_modifica = "../modifica.php?id=" . urlencode($p->id_lezione) .
                                        "&data=" . urlencode($p->data) .
                                        "&entrata=" . urlencode($p->entrata) .
                                        "&uscita=" . urlencode($p->uscita);

                                    // Se c'è una matricola selezionata nei filtri, la passiamo
                                    $matricola = $_SESSION['matricola_selezionata'] ?? '';
                                    if (!empty($matricola)) {
                                        $url_modifica .= "&matricola=" . urlencode($matricola);
                                    }
                                }

                                // Se c'è una matricola filtrata, passiamo anche quella per sapere di quale studente si tratta
                                $matricola = $_SESSION['matricola_selezionata'] ?? '';
                                if (!empty($matricola)) {
                                    $url_modifica .= "&matricola=" . urlencode($matricola);
                                }
                            }
                            ?>

                            <!-- Aggiungiamo l'onclick e il cursore a comparsa se l'utente è Staff -->
                            <div class="row"
                                <?php if ($staff_auth): ?>
                                onclick="window.parent.location.href='<?= htmlspecialchars($url_modifica) ?>';"
                                style="cursor: pointer;"
                                title="Clicca per modificare questa presenza"
                                <?php endif; ?>>
                                <div class="cell"><?= htmlspecialchars($p->data) ?></div>
                                <div class="cell"><?= htmlspecialchars($p->inizio) ?></div>
                                <div class="cell"><?= htmlspecialchars($p->fine) ?></div>
                                <div class="cell"><?= htmlspecialchars($p->materia) ?></div>
                                <div class="cell"><?= htmlspecialchars($p->entrata) ?></div>
                                <div class="cell"><?= htmlspecialchars($p->uscita) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?> /*if (empty($mat_sel) && empty($cor_sel))*/
                        <div class="row">
                            <div class="cell" style="grid-column: span 6; text-align: center; padding: 20px; color: #777;">
                                Nessuna presenza trovata con i filtri selezionati.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PANNELLO DI RIEPILOGO ORE (VISIBILE SOLO SE E' SELEZIONATA UNA MATRICOLA) -->
            <?php if ($matricola_selzionata): ?>
                <div id="report_presenze">
                    <div>
                        <span class="titoletto_report">Durata Prevista</span>
                        <strong style="color: #333;"><?= $riepilogo_ore['durata'] ?> h</strong>
                    </div>
                    <div>
                        <span class="titoletto_report">Ore Presenza (Arrot.)</span>
                        <strong style="color: var(--rosso_brand, #951d3b);"><?= $riepilogo_ore['ore_presenza'] ?> h</strong>
                    </div>
                    <div>
                        <span class="titoletto_report">Stima Assenze</span>
                        <strong style="color: #333;"><?= $riepilogo_ore['ore_assenza'] ?> h</strong>
                    </div>
                </div>
            <?php else: ?>
                <div>
                    <div id="suggerimento">Selezionare una matricola ed eventualmente una materia per leggere il totale di presenze</div>
                </div>
            <?php endif; ?>
            <!-- FINE PANNELLO RIEPILOGO -->
        </div>

        <!-- Colonna Filtri laterale (Visibile solo per lo Staff) -->
        <?php if ($staff_auth): ?>
            <div style="display:flex;flex-direction: column;gap: 0.8rem;">
                <div class="colonna-destra">
                    <h2>Opzioni</h2>

                    <form id="form_filtri_staff" action="" method="post">

                        <!-- 1. Filtro Matricole -->
                        <label for="filtro_matricola" class="label-filtro">Matricole:</label>
                        <select id="filtro_matricola" name="filtro_matricola" class="select-filtro-block" onchange="this.form.submit()">
                            <option value="">-- Tutte le matricole --</option>
                            <?php if (!empty($studenti_lista)): ?>
                                <?php foreach ($studenti_lista as $std): ?>
                                    <?php $selected = (($mat_sel ?? '') === $std['matricola']) ? 'selected' : ''; ?>
                                    <option value="<?= htmlspecialchars($std['matricola']) . '|' . htmlspecialchars($std['nome']) . ' ' . htmlspecialchars($std['cognome']) ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($std['matricola']) ?> — <?= htmlspecialchars($std['cognome']) ?> <?= htmlspecialchars($std['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <!-- 2. Filtro Corsi -->
                        <label for="filtro_corso" class="label-filtro">Corso:</label>
                        <!-- Checkbox per mostrare o nascondere i corsi non attivi -->
                        <div style="margin-bottom: 6px; font-size: 13px;">
                            <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                                <input type="checkbox" name="mostra_non_attivi" value="1" onchange="this.form.submit()" <?= !empty($mostra_non_attivi) ? 'checked' : '' ?>>
                                Mostra corsi non attivi
                            </label>
                        </div>
                        <select id="filtro_corso" name="filtro_corso" class="select-filtro-block" onchange="this.form.submit()">
                            <option value="">-- Tutti i corsi --</option>
                            <?php if (!empty($corsi_lista)): ?>
                                <?php foreach ($corsi_lista as $corso): ?>
                                    <?php $selected = ((string)($cor_sel ?? '') === (string)$corso['ID_corso']) ? 'selected' : ''; ?>
                                    <option value="<?= htmlspecialchars($corso['ID_corso']) ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($corso['nome_corso']) ?> (<?= htmlspecialchars($corso['cod_edizione']) ?>) <?= (isset($corso['attivo']) && $corso['attivo'] == 0) ? '[NON ATTIVO]' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <!-- 3. Filtro Materie -->
                        <label for="filtro_materia" class="label-filtro">Materia:</label>
                        <select id="filtro_materia" name="filtro_materia" class="select-filtro-block" onchange="this.form.submit()">
                            <option value="">-- Tutte le materie --</option>
                            <?php if (!empty($materie_lista)): ?>
                                <?php foreach ($materie_lista as $materia): ?>
                                    <?php $selected = (($mat_materia_sel ?? '') === $materia['cod_materia']) ? 'selected' : ''; ?>
                                    <option value="<?= htmlspecialchars($materia['cod_materia']) ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($materia['nome_materia']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <a href="?reset=1" class="btn-reset-filtri">Azzera Tutti i Filtri</a>
                    </form>
                </div>
                <div class="button-container" style="margin-top: 5px; margin-bottom: 15px; display: flex; justify-content: center;">
                    <a href="./genera_pdf.php?filtri=<?php echo htmlspecialchars(json_encode($filtri)); ?>&totale=<?php echo htmlspecialchars(json_encode($riepilogo_ore)); ?>" class="btn btn-create" style="font-size: 16px; padding: 10px 30px; letter-spacing: 0.5px;">
                        Scarica PDF Presenze
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>


</body>



</html>