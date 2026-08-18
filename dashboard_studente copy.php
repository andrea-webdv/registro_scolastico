<?php
session_start();

// 1. Controllo di sicurezza: se lo studente non è loggato, viene reindirizzato alla index
if (!isset($_SESSION['studente_matricola'])) {
    header("Location: index.php?e=non_loggato");
    exit();
}

require_once __DIR__ . "/collegamento_db/Letture_incrociate.php";
require_once __DIR__ . "/pdf/Calcolo_ore.php";

// 2. Definizione della classe Presenza (se non già caricata)
if (!class_exists('Presenza')) {
    class Presenza
    {
        public $data, $inizio, $fine, $materia, $entrata, $uscita;

        public function __construct($data, $inizio, $fine, $materia, $entrata, $uscita)
        {
            $this->data = $data;
            $this->inizio = $inizio;
            $this->fine = $fine;
            $this->materia = $materia;
            $this->entrata = $entrata;
            $this->uscita = $uscita;
        }
    }
}

// 3. Recupero dei dati dello studente in sessione
$matricola = $_SESSION['studente_matricola'] ?? '';
$nome_studente = $_SESSION['studente_nome'] ?? 'Studente';

// 4. Recupero filtri selezionati
$corsi_selezionati = $_GET['corsi'] ?? [];
$materie_selezionate = $_GET['materie'] ?? [];
$corso_selezionato = $_GET['corso'] ?? null;



// Recupero dei corsi reali a cui è iscritto lo studente
$corsi_studente = lettura_corsi_studente($matricola);
$iscritto_a_piu_corsi = count($corsi_studente) > 1;



// Se non è stato selezionato un corso specifico da GET, si usa il primo corso associato
if (empty($corsi_selezionati) && !empty($corsi_studente)) {
    foreach ($corsi_studente as $id_del_corso) {
        $corsi_selezionati[] = $id_del_corso['ID_corso'];
    }
}


// 5. Lettura dal Database con filtri dinamici
$filtri = [];
$riferimento_durata = []; # utile al calcolo ore
$durata_selezionata = 0;

$filtri[] = ['matricola' => $matricola];

if (!empty($materie_selezionate)) {
    $filtri[] = ['materie' => $materie_selezionate];

    $riferimento_durata = ['materie', []];
    foreach ($materie_selezionate as $cod_materia) {
        $riferimento_durata[1][] = ['cod_materia' => $cod_materia];
    };
} elseif (!empty($corsi_selezionati)) {
    $filtri[] = ['corsi' => $corsi_selezionati];

    $riferimento_durata = ['corsi', []];
    foreach ($corsi_selezionati as $ID_corso) {
        $riferimento_durata[1][] = ['ID_corso' => $ID_corso];
    };
} else {
    $default_corso = $corsi_studente[0]['ID_corso'];

    $filtri[] = ['corsi' => [$default_corso]];
    $riferimento_durata = ['corsi', [['ID_corso' => $default_corso]]];
}


$presenze_db = Lettura_presenze($filtri);

#$iscritto_a_piu_corsi = count($corsi_studente) > 1;

// Recupero solo le materie appartenenti al corso attualmente selezionato
$id_corsi_frequentati = [];
foreach ($corsi_studente as $corso) {
    $id_corsi_frequentati[] = $corso['ID_corso'];
}

if (!empty(array_column($filtri, 'ID_corso'))) {
    $materie_raw = lettura_materie_corso(!empty($id_corsi_frequentati) ? $id_corsi_frequentati : []);
} else {
    $materie_raw = lettura_materie_corso(!empty($corsi_selezionati) ? $corsi_selezionati : []);
}


$materie_disponibili = [];

foreach ($materie_raw as $m) {
    $materie_disponibili[$m['cod_materia']] = $m['nome_materia'];
}

// --- LETTURA E CONVERSIONE LEZIONI DAL DB PER IL CALENDARIO ---
$lezioni_dal_db = [];

$lezioni_dal_db = lettura_lezioni_corso(!empty($corsi_selezionati) ? $corsi_selezionati : []);




$giorni_settimana = ['Domenica', 'Lunedi', 'Martedi', 'Mercoledi', 'Giovedi', 'Venerdi', 'Sabato'];

$lezioni_db = [];

if (!empty($lezioni_dal_db)) {
    foreach ($lezioni_dal_db as $riga) {
        $dt_inizio = new DateTime($riga['TS_inizio']);
        $dt_fine   = new DateTime($riga['TS_fine']);
        $num_giorno = (int)$dt_inizio->format('N');

        $lezioni_db[] = [
            'giorno'  => $giorni_settimana[$num_giorno] ?? '',
            'data'    => $dt_inizio->format('d-m-Y'),
            'materia' => $riga['nome_materia'] ?? '',
            'orario'  => $dt_inizio->format('H:i') . ' - ' . $dt_fine->format('H:i'),
            'aula'   => $riga['cod_aula'] ?? ''
        ];
    }
}

// 6. Creazione della griglia fissa di 6 lezioni (Lunedì - Sabato)
$calendario_settimana = [];


// raccolta delle lezioni della settimana per il calendario da visualizzare
function calcola_calendario_settimana(array $lezioni): array
{
    $oggi = new DateTime();
    $num_giorno_settimana = (int)$oggi->format('N'); // 1 = Lunedì, 2 = Martedì, ..., 7 = Domenica

    // Calcolo del Lunedì della settimana corrente (se oggi è Lunedì sottrae 0 giorni)
    $data_lunedi = (clone $oggi)->modify('-' . ($num_giorno_settimana - 1) . ' days')->setTime(0, 0, 0);

    // Limite superiore: Lunedì + 6 giorni (Domenica alle 23:59:59)
    $data_limite = (clone $data_lunedi)->modify('+6 days')->setTime(23, 59, 59);

    $calendario_settimana = [];

    foreach ($lezioni as $lezione) {
        $dt_lezione = null;

        // Verifica la presenza di TS_inizio (dati grezzi) o 'data' (formattata d-m-Y)
        if (isset($lezione['TS_inizio'])) {
            $dt_lezione = new DateTime($lezione['TS_inizio']);
        } elseif (isset($lezione['data'])) {
            $dt_lezione = DateTime::createFromFormat('d-m-Y', $lezione['data']);
            if ($dt_lezione) {
                $dt_lezione->setTime(0, 0, 0);
            }
        }

        // Inclusione nell'intervallo della settimana corrente
        if ($dt_lezione && $dt_lezione >= $data_lunedi && $dt_lezione <= $data_limite) {
            $calendario_settimana[] = $lezione;
        }
    }

    return $calendario_settimana;
}

// Assegnazione del calendario settimanale
$calendario_settimana = calcola_calendario_settimana($lezioni_db ?? []);


//da qui in poi è incluso il recupero dei dati per il calcolo delle ore di presenza
$minuti_totali_presenza = 0;
$riepilogo_ore = ['durata' => 0, 'ore_presenza' => 0, 'ore_assenza' => 0];
$permessi = 0;

$presenze = [];

if (!empty($presenze_db)) {
    foreach ($presenze_db as $record) {
        $p = new Presenza(
            $record['data_lezione'],
            $record['ora_inizio'],
            $record['ora_fine'],
            $record['nome_materia'],
            $record['ora_entrata'],
            $record['ora_uscita']
        );
        $presenze[] = $p;

        // accumulo del minutaggio delle presenze SOLO se la matricola è selezionata
        if (!empty($matricola)) {
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
}

$corsi_lista = "";
$materie_lista = "";

if (!empty($filtri) && !empty(array_column($filtri, 'matricola'))) {
    $corsi_lista = function_exists('lettura_corsi') ? lettura_corsi($filtri[0]['matricola']) : [];
    $materie_lista = function_exists('lettura_materie') ? lettura_materie($filtri[0]['matricola']) : [];

    $corsi_in_conto = lettura_corsi($corsi = $corsi_selezionati);
    foreach ($corsi_in_conto as $corso) {
        $durata_selezionata += $corso["durata"];
    };
}

$riepilogo_ore = calcola_riepilogo_ore($minuti_totali_presenza, $riferimento_durata, $permessi);

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Studente - Registro Presenze</title>

    <!-- Importazione Font Barlow Condensed -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Fogli di stile AgenForm -->
    <link rel="stylesheet" href="./css/agenform.css">
    <link rel="stylesheet" href="./css/registro_presenze.css">

    <style>
        .container-dashboard {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .header-dashboard {
            text-align: center;
            margin-bottom: 25px;
        }

        .badge-matricola {
            background-color: var(--rosso_brand, #a30000);
            color: #ffffff;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 1.1rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .contenitore-logout {
            margin-top: 15px;
            text-align: center;
        }

        .pulsanti-navigazione {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        /* Riduce la larghezza della tabella presenze e la centra.
           Nota: se le colonne non si restringono di conseguenza, il motivo
           è nelle regole di registro_presenze.css (larghezze fisse sulle
           .cell) - in quel caso va corretto lì. */
        .finestra_scorrimento {
            max-width: 700px;
            margin: 0 auto;
        }

        .messaggio-vuoto {
            text-align: center;
            background-color: #ffffff;
            color: #333333;
            padding: 35px;
            border-radius: 8px;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            margin-top: 20px;
        }

        /* Modale "Cambia corso" */
        .modale-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            z-index: 100;
        }

        .modale-contenuto {
            background-color: #ffffff;
            color: #333333;
            padding: 30px;
            border-radius: 8px;
            min-width: 300px;
            max-width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        /* Animazioni e stili specifici */
        @keyframes comparsaModale {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modale-animata {
            animation: comparsaModale 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        #tabella-calendario {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 25px;
            font-family: inherit;
        }

        #tabella-calendario th,
        #tabella-calendario td {
            padding: 12px 15px;
            border-bottom: 1px solid #eeeeee;
            text-align: left;
        }

        #tabella-calendario th {
            background-color: var(--rosso_brand, #a30000);
            color: #ffffff;
            font-weight: 600;
        }

        #tabella-calendario tr:last-child td {
            border-bottom: none;
        }

        .cella-vuota {
            color: #999999;
            font-style: italic;
        }

        .btn-chiudi-modale {
            width: 100%;
            margin-top: 10px;
        }

        .gruppo-filtri {
            margin-bottom: 20px;
            text-align: left;
        }

        .gruppo-filtri h3 {
            font-size: 1.1rem;
            color: #333333;
            border-bottom: 2px solid #eeeeee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .lista-checkbox {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 180px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .lista-checkbox label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            cursor: pointer;
            color: #444;
        }

        .lista-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--rosso_brand, #a30000);
        }

        /* regole di stile per il conteggio ore */
        #suggerimento {
            max-width: 700px;
            margin: 0 auto;
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
            max-width: 700px;
            margin: 0 auto;
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

    <div class="container-dashboard">
        <!-- TITOLO, Matricola e Pulsante Logout -->
        <div class="header-dashboard">

            <p style="font-size: 2.05rem; color: #ffffff; margin-top: 5px;">
                Benvenuto/a, <strong><?= htmlspecialchars($nome_studente) ?></strong>
            </p>
            <span class="badge-matricola">Matricola: <?= htmlspecialchars($matricola) ?></span>
            <div class="contenitore-logout">
                <a href="./accesso/logout.php" class="btn btn-create btn-logout">
                    Logout
                </a>
            </div>
        </div>

        <!-- BOTTONI NAVIGAZIONE IN STILE AGENFORM -->
        <div class="pulsanti-navigazione">
            <button type="button" class="btn btn-create" onclick="apriModaleCalendario()">
                Visualizza calendario
            </button>

            <button type="button" class="btn btn-create" onclick="apriModaleFiltri()">
                Corsi/Materie
            </button>

            <form action="pdf/genera_pdf_calendario.php" id="form_calendario" method="post">
                <input type="hidden" name="calendario" id="dati_calendario">
                <a href="" id="link_calendario" class="btn btn-create">
                    Scarica Calendario
                </a>
            </form>

            <a href="pdf/genera_pdf.php?filtri=<?= urlencode(json_encode($filtri)) ?>&totale=<?php echo htmlspecialchars(json_encode($riepilogo_ore)); ?>" class="btn btn-create">
                Scarica Presenze
            </a>

        </div>
        <h1>Dashboard Presenze</h1>

        <!-- TABELLA PRESENZE DAL DB -->
        <?php if (!empty($presenze)): ?>
            <div class="finestra_scorrimento">
                <div class="table-container">
                    <div class="row header">
                        <div class="cell">Data</div>
                        <div class="cell">Materia</div>
                        <div class="cell">Inizio</div>
                        <div class="cell">Fine</div>
                        <div class="cell">Entrata</div>
                        <div class="cell">Uscita</div>
                    </div>
                    <?php foreach ($presenze as $p): ?>
                        <div class="row">
                            <div class="cell"><?= htmlspecialchars($p->data) ?></div>
                            <div class="cell"><?= htmlspecialchars($p->materia) ?></div>
                            <div class="cell"><?= htmlspecialchars($p->inizio) ?></div>
                            <div class="cell"><?= htmlspecialchars($p->fine) ?></div>
                            <div class="cell"><?= htmlspecialchars($p->entrata) ?></div>
                            <div class="cell"><?= htmlspecialchars($p->uscita) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="messaggio-vuoto">
                <p>Nessun record di presenza trovato per la matricola <strong><?= htmlspecialchars($matricola) ?></strong>.</p>
            </div>
        <?php endif; ?>

        <!-- PANNELLO DI RIEPILOGO ORE (VISIBILE SOLO SE E' SELEZIONATA UNA MATRICOLA) -->
        <?php if (!empty($matricola)): ?>
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

    <!-- MODALE "CALENDARIO LEZIONI" -->
    <div id="modale-calendario" class="modale-overlay" style="display:none;">
        <div class="modale-contenuto modale-animata">
            <h2 style="text-align: center; color: var(--rosso_brand, #a30000); margin-bottom: 5px;">Calendario Settimanale</h2>
            <p style="text-align: center; color: #666; font-size: 0.9rem;">Programmazione dal Lunedì al Sabato</p>

            <table id="tabella-calendario">
                <thead>
                    <tr>
                        <th>Giorno</th>
                        <th>Materia</th>
                        <th>Orario</th>
                        <th>Aula</th>
                    </tr>
                </thead>
                <tbody>
                <tbody>
                    <?php
                    $giorni_settimana = ['Lunedi', 'Martedi', 'Mercoledi', 'Giovedi', 'Venerdi', 'Sabato'];
                    $g_idx = 0; // Indice del giorno corrente nell'array $giorni_settimana
                    $primo_elemento_giorno = true;

                    if (!empty($calendario_settimana)):
                        foreach ($calendario_settimana as $lezione):
                            // Se si passa a un nuovo giorno rispetto a quello trattato precedentemente
                            if (!$primo_elemento_giorno && $giorni_settimana[$g_idx] !== $lezione['giorno']) {
                                $g_idx++;
                                $primo_elemento_giorno = true;
                            }

                            // Fallback per giorni mancanti/senza lezioni precedenti al giorno della lezione attuale
                            while ($g_idx < count($giorni_settimana) && $giorni_settimana[$g_idx] !== $lezione['giorno']) {
                    ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($giorni_settimana[$g_idx]) ?></strong></td>
                                    <td class="cella-vuota">Nessuna lezione</td>
                                    <td class="cella-vuota">-</td>
                                    <td class="cella-vuota">-</td>
                                </tr>
                            <?php
                                $g_idx++;
                                $primo_elemento_giorno = true;
                            }

                            // Rendering del giorno e della lezione corrispondente
                            if ($g_idx < count($giorni_settimana) && $giorni_settimana[$g_idx] === $lezione['giorno']):
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($primo_elemento_giorno): ?>
                                            <strong><?= htmlspecialchars($lezione['giorno']) ?></strong>
                                            <?php if (isset($lezione['data'])): ?>
                                                <br><small><?= htmlspecialchars($lezione['data']) ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($lezione['materia']) ?></td>
                                    <td><?= htmlspecialchars($lezione['orario']) ?></td>
                                    <td><?= htmlspecialchars($lezione['aula']) ?></td>
                                </tr>
                        <?php
                                $primo_elemento_giorno = false;
                            endif;
                        endforeach;
                    endif;

                    // Incremento dell'indice se l'ultimo giorno processato ha contenuto lezioni
                    if (!$primo_elemento_giorno) {
                        $g_idx++;
                    }

                    // Completamento con placeholder per eventuali giorni rimanenti della settimana
                    while ($g_idx < count($giorni_settimana)) {
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($giorni_settimana[$g_idx]) ?></strong></td>
                            <td class="cella-vuota">Nessuna lezione</td>
                            <td class="cella-vuota">-</td>
                        </tr>
                    <?php
                        $g_idx++;
                    }
                    ?>
                </tbody>
                </tbody>
            </table>

            <button type="button" class="btn btn-create btn-chiudi-modale" onclick="chiudiModaleCalendario()">Chiudi Calendario</button>
        </div>
    </div>

    <!-- MODALE "FILTRA PRESENZE" -->
    <div id="modale-filtri" class="modale-overlay" style="display:none;">
        <div class="modale-contenuto modale-animata">
            <h2 style="text-align: center; color: var(--rosso_brand, #a30000); margin-bottom: 15px;">Filtra Corsi o Materie</h2>

            <form method="GET" action="">
                <?php if ($iscritto_a_piu_corsi): ?>
                    <!-- filtro corsi -->
                    <div class="gruppo-filtri">
                        <h3>Seleziona Corsi</h3>
                        <div class="lista-checkbox">
                            <?php foreach ($corsi_studente as $corso): ?>
                                <label>
                                    <input type="checkbox" name="corsi[]" value="<?= htmlspecialchars($corso['ID_corso']) ?>"
                                        <?= in_array($corso['ID_corso'], $corsi_selezionati) ? 'checked' : '' ?>> <!-- è possibile recuperare qui la durata del corso -->
                                    <?= htmlspecialchars($corso['nome_corso']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- filtro materie -->
                <div class="gruppo-filtri">
                    <h3>Seleziona Materie</h3>
                    <div class="lista-checkbox">
                        <?php if (!empty($materie_disponibili)): ?>
                            <?php foreach ($materie_disponibili as $cod_materia => $nome_materia): ?>
                                <label>
                                    <input type="checkbox" name="materie[]" value="<?= htmlspecialchars($cod_materia) ?>"
                                        <?= in_array($cod_materia, $materie_selezionate) ? 'checked' : '' ?>> <!-- è possibile recuperare qui la durata della materia -->
                                    <?= htmlspecialchars($nome_materia) ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #666; font-style: italic;">Nessuna materia trovata.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="submit" class="btn btn-create" style="flex: 1;">Applica Filtri</button>
                    <button type="button" class="btn btn-create btn-chiudi-modale" style="flex: 1; margin-top: 0; background-color: #666;" onclick="chiudiModaleFiltri()">Annulla</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT GESTIONE MODALI -->
    <script>
        // Gestione Modale Calendario
        function apriModaleCalendario() {
            document.getElementById('modale-calendario').style.display = 'flex';
        }

        function chiudiModaleCalendario() {
            document.getElementById('modale-calendario').style.display = 'none';
        }

        // Gestione Modale Corsi
        function apriModaleCorsi() {
            let el = document.getElementById('modale-corsi');
            if (el) el.style.display = 'flex';
        }

        function chiudiModaleCorsi() {
            let el = document.getElementById('modale-corsi');
            if (el) el.style.display = 'none';
        }

        // Gestione Modale Filtri
        function apriModaleFiltri() {
            document.getElementById('modale-filtri').style.display = 'flex';
        }

        function chiudiModaleFiltri() {
            document.getElementById('modale-filtri').style.display = 'none';
        }

        // Chiusura cliccando all'esterno del contenuto della modale
        window.onclick = function(event) {
            let modaleCal = document.getElementById('modale-calendario');
            let modaleCor = document.getElementById('modale-corsi');
            let modaleFil = document.getElementById('modale-filtri');

            if (event.target == modaleCal) modaleCal.style.display = "none";
            if (modaleCor && event.target == modaleCor) modaleCor.style.display = "none";
            if (event.target == modaleFil) modaleFil.style.display = "none";
        };
    </script>
    <script>
        const stampa_calendario = document.getElementById('form_calendario');
        const dati_calendario = document.getElementById('dati_calendario');
        const avvio_stampa_calendario = document.getElementById('link_calendario');

        /* opzione da scartare: passare direttaemnte il  codice html espone ad attacchi cross-site scripting
        const tabella_calendario = document.getElementById('tabella-calendario'); 
        */

        avvio_stampa_calendario.addEventListener("click", (e) => {
            e.preventDefault();

            let dati = <?php echo json_encode($calendario_settimana) ?>;


            dati_calendario.value = JSON.stringify(dati);

            stampa_calendario.submit();
        })
    </script>
</body>

</html>