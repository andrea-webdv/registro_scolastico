<?php
session_start();
require_once __DIR__ . "/collegamento_db/Collegamento.php";
require_once __DIR__ . "/collegamento_db/Letture_incrociate.php"; // Aggiunta inclusione funzioni

if (!isset($_SESSION['staff_codice'])) {
    header("Location: index.php?e=non_loggato");
    exit();
}

$connessione = Collegamento();

// 1. Recupero i filtri inviati tramite l'URL (GET)
$filtro_corso   = $_GET['filtro_corso'] ?? '';
$filtro_materia = $_GET['filtro_materia'] ?? '';
$filtro_data    = $_GET['filtro_data'] ?? '';

// 2. Recupero le opzioni per i menu a tendina usando le funzioni centralizzate
// Invocazione di default: mostra solo i corsi attivi (parametro 0 implicito)
$corsi_disponibili = lettura_corsi();

// Logica di dipendenza a cascata: se è selezionato un corso, filtro le materie
if (!empty($filtro_corso)) {
    // Il primo parametro (filtro matricola) resta vuoto, il secondo riceve l'ID_corso
    $materie_disponibili = lettura_materie('', $filtro_corso);
} else {
    // Nessun corso selezionato: recupero tutte le materie attive
    $materie_disponibili = lettura_materie();
}

// 3. Recupero TUTTE le date occupate garantendo il formato YYYY-MM-DD da MySQL
$sql_date = "SELECT DISTINCT DATE_FORMAT(TS_inizio, '%Y-%m-%d') AS data_occupata FROM lezioni";
$date_occupate = $connessione->query($sql_date)->fetchAll(PDO::FETCH_COLUMN);

// 4. Costruzione della query base per le lezioni
$sql = "
    SELECT `lezioni`.`ID_lezione`, `lezioni`.`TS_inizio`, `lezioni`.`TS_fine`, `lezioni`.`sede`,
           `materie`.`nome_materia`, `corsi`.`nome_corso`
    FROM `lezioni`
    INNER JOIN `materie` ON `lezioni`.`cod_materia` = `materie`.`cod_materia`
    INNER JOIN `corsi` ON `lezioni`.`ID_corso` = `corsi`.`ID_corso`
";

$condizioni = [];
$parametri = [];

if (!empty($filtro_corso)) {
    $condizioni[] = "`lezioni`.`ID_corso` = ?";
    $parametri[] = $filtro_corso;
}

if (!empty($filtro_materia)) {
    $condizioni[] = "`lezioni`.`cod_materia` = ?";
    $parametri[] = $filtro_materia;
}

if (!empty($filtro_data)) {
    $condizioni[] = "CAST(`lezioni`.`TS_inizio` AS DATE) = ?";
    $parametri[] = $filtro_data;
}

if (count($condizioni) > 0) {
    $sql .= " WHERE " . implode(" AND ", $condizioni);
}

$sql .= " ORDER BY `lezioni`.`TS_inizio` ASC";

$query = $connessione->prepare($sql);
$query->execute($parametri);
$lezioni = $query->fetchAll(PDO::FETCH_ASSOC);
$connessione = null;

$giorni_settimana = ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"];
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Calendario Lezioni</title>

    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/agenform.css">

<!-- FLATPICKR -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
    <style>
    
    
        body {
            display: block;
            min-height: auto;
            background-color: transparent;
            color: #333333;
            padding: 10px;
        }

        /* BARRA DEI FILTRI COMPATTA */
        .barra-filtri {
            background-color: #f1f1f1;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        .form-filtri {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .gruppo-filtro {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .barra-filtri select,
        .barra-filtri input[type="text"] {
            padding: 7px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-family: inherit;
            font-size: 0.95rem;
            background-color: #ffffff;
            color: #333333;
        }

        .barra-filtri label {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .btn-reset-filtri {
            color: var(--rosso_brand, #d9534f);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 10px;
            border: 1px solid var(--rosso_brand, #d9534f);
            border-radius: 4px;
            transition: background-color 0.2s, color 0.2s;
        }

        .btn-reset-filtri:hover {
            background-color: var(--rosso_brand, #d9534f);
            color: #ffffff;
        }

        /* personalizzazione CALENDARIO A COMPARSA */
        
        /* GIORNI OCCUPATI DA ALMENO UNA LEZIONE: Scuro (Antracite) con testo bianco */
        .flatpickr-day.giorno-occupato {
            background-color: #2c3e50 !important;
            color: #ffffff !important;
            border-color: #2c3e50 !important;
            font-weight: bold !important;
        }

        .flatpickr-day.giorno-occupato:hover {
            background-color: #1a252f !important;
        }

        /* GIORNI LIBERI: Chiari e puliti */
        .flatpickr-day.giorno-libero {
            background-color: #f8f9fa !important;
            color: #495057 !important;
            border: 1px solid #e9ecef !important;
        }

        .flatpickr-day.giorno-libero:hover {
            background-color: #e2e8f0 !important;
        }

        /* GIORNO ATTUALMENTE SELEZIONATO */
        .flatpickr-day.selected {
            background-color: var(--rosso_brand, #d9534f) !important;
            border-color: var(--rosso_brand, #d9534f) !important;
            color: #ffffff !important;
        }

        /* GRIGLIA CARD LEZIONI */
        .griglia-calendario {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .giorno-calendario {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-top: 4px solid var(--rosso_brand, #d9534f);
            padding: 15px;
            border-radius: 4px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .giorno-calendario:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .data-giorno {
            font-weight: bold;
            margin-bottom: 10px;
            color: #444444;
        }

        .corso-lezione {
            font-size: 13px;
            color: #777777;
            margin-bottom: 4px;
        }

        .materia {
            color: var(--rosso_brand, #d9534f);
            font-weight: 600;
        }

        .dettaglio-lezione {
            font-size: 14px;
            color: #666666;
            line-height: 1.4;
        }
    </style>
</head>

<body>

 <!-- BARRA UNIFICATA DEI FILTRI -->
    <div class="barra-filtri">
        <form method="GET" action="calendario_lezioni.php" id="formFiltro" class="form-filtri">
            
            <!-- FILTRO CORSO -->
            <div class="gruppo-filtro">
                <label for="filtro_corso">Corso:</label>
                <select name="filtro_corso" id="filtro_corso" onchange="document.getElementById('formFiltro').submit();">
                    <option value="">-- Tutti i corsi --</option>
                    <?php foreach ($corsi_disponibili as $corso): ?>
                        <option value="<?= $corso['ID_corso'] ?>" <?= ($filtro_corso == $corso['ID_corso']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($corso['nome_corso']) . " Ed.: " . htmlspecialchars($corso['cod_edizione']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- FILTRO MATERIA -->
            <div class="gruppo-filtro">
                <label for="filtro_materia">Materia:</label>
                <select name="filtro_materia" id="filtro_materia" onchange="document.getElementById('formFiltro').submit();">
                    <option value="">-- Tutte le materie --</option>
                    <?php foreach ($materie_disponibili as $materia): ?>
                        <option value="<?= $materia['cod_materia'] ?>" <?= ($filtro_materia == $materia['cod_materia']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($materia['nome_materia']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- FILTRO DATA -->
            <div class="gruppo-filtro">
                <label for="filtro_data">Data:</label>
                <input type="text" name="filtro_data" id="filtro_data" placeholder="Seleziona data..." value="<?= htmlspecialchars($filtro_data) ?>" readonly style="cursor: pointer; background-color: #fff;">
            </div>

            <!-- PULSANTE DI RESET -->
            <?php if (!empty($filtro_corso) || !empty($filtro_materia) || !empty($filtro_data)): ?>
                <a href="calendario_lezioni.php" class="btn-reset-filtri" title="Rimuovi tutti i filtri">✕ Resetta Filtri</a>
            <?php endif; ?>

        </form>
    </div>

    <!-- GRIGLIA CALENDARIO (CARD LEZIONI) -->
    <div class="griglia-calendario">
        <?php if (!empty($lezioni)): ?>
            <?php foreach ($lezioni as $lezione):
                $inizio = new DateTime($lezione['TS_inizio']);
                $fine = new DateTime($lezione['TS_fine']);

                $num_giorno_settimana = $inizio->format('w');
                $data_card = $giorni_settimana[$num_giorno_settimana] . " " . $inizio->format('d/m');

                $orario_inizio = $inizio->format('H:i');
                $orario_fine = $fine->format('H:i');
            ?>
             <a href="modifica.php?id=<?= $lezione['ID_lezione'] ?>" target="_parent" style="text-decoration: none; color: inherit;">
                    <div class="giorno-calendario">
                        <div class="data-giorno"><?= $data_card ?></div>
                        <div class="corso-lezione"><?= htmlspecialchars($lezione['nome_corso']) ?></div>
                        <div class="materia"><?= htmlspecialchars($lezione['nome_materia']) ?></div>
                        <div class="dettaglio-lezione">
                            <?= $orario_inizio ?> - <?= $orario_fine ?><?= !empty($lezione['sede']) ? ' · ' . htmlspecialchars($lezione['sede']) : '' ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
             <p style="padding: 10px; color: #666;">Nessuna lezione trovata con i filtri selezionati.</p>
        <?php endif; ?>
    </div>

  <script>
        const dateOccupate = <?= json_encode($date_occupate) ?>;

        flatpickr("#filtro_data", {
            locale: "it",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            defaultDate: "<?= $filtro_data ?>",
            
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                // Formattazione nativa di Flatpickr per garantire il match esatto YYYY-MM-DD
                const dataFormattata = fp.formatDate(dayElem.dateObj, "Y-m-d");

                if (dateOccupate.includes(dataFormattata)) {
                    dayElem.classList.add("giorno-occupato");
                    dayElem.title = "Occupato - Lezione presente";
                } else {
                    dayElem.classList.add("giorno-libero");
                    dayElem.title = "Libero";
                }
            },

            onChange: function(selectedDates, dateStr, instance) {
                if (dateStr) {
                    document.getElementById('formFiltro').submit();
                }
            }
        });
    </script>
</body>

</html>
