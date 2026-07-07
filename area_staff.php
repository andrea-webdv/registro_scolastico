<?php
require_once "./moduli/Comunicazione_database.php";

$database = new Database('scuola');

$sql = "SELECT `lezioni`.`TS_inizio`, `lezioni`.`TS_fine`, `materie`.`nome_materia` FROM `lezioni`INNER JOIN `materie` ON `lezioni`.`cod_materia` = `materie`.`cod_materia`;";

$lezioni = $database->Query_select($sql);
#$lezioni = $lezioni[0];

setlocale(LC_TIME, 'it_IT.utf8', 'ita_ita');
##############
$json_dump = json_encode($lezioni);
echo "<script>
    alert('Descrivo cosa voglio controllare:\\n' + $json_dump);
</script>";
################
?>

<?php
session_start();

// 1. CONTROLLO ACCESSI PER LO STAFF
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'staff') {
    die("Accesso negato. Questa pagina è riservata al personale autorizzato AgenForm.");
}

// 2. CONNESSIONE AL DATABASE PER POPOLARE IL MENU A TENDINA
try {
    $conn = new PDO("mysql:host=localhost;dbname=scuola", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Recuperiamo l'elenco dei corsi disponibili
    $stmt_corsi = $conn->query("SELECT ID_corso, nome_corso FROM corsi ORDER BY nome_corso ASC");
    $corsi_disponibili = $stmt_corsi->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback sicuro se il database è mo/*  */mentaneamente offline durante i test
    $corsi_disponibili = [
        ['ID_corso' => '1', 'nome_corso' => 'Sviluppatore Web Full Stack (PHP/Python)'],
        ['ID_corso' => '2', 'nome_corso' => 'Tecnico Sistemi e Reti']
    ];
}

// 3. GESTIONE DELLO STATO DELLA PAGINA (Ricaricamento richiesto)
$vista = $_GET['vista'] ?? 'calendario'; // Può essere 'calendario' o 'gestione'
$corso_selezionato = $_GET['corso'] ?? "selezionare un corso"/* ($corsi_disponibili[0]['ID_corso'] ?? '') */;

if ($corso_selezionato) {

    foreach ($corsi_disponibili as $info_corso) {
        if ($info_corso['ID_corso'] == $corso_selezionato) {
            $_SESSION['nome_corso_staff'] = $info_corso['nome_corso'];
        };
    };
};

$_SESSION['ID_corso_staff'] = $corso_selezionato;
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgenForm - Area Staff</title>

    <link rel="stylesheet" href="./css/agenform.css">

    <style>
        main {
            flex: 1;
            max-width: 1000px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
        }

        .header-dashboard {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ffffff;
            padding: 20px 30px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .info-utente h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }

        /* Stile del selettore corsi che sostituisce il titolo statico rosso */
        .selettore-corso-form {
            margin-bottom: 25px;
        }

        .selettore-corso {
            font-size: 18px;
            font-weight: bold;
            color: var(--rosso_brand);
            border: 2px solid var(--rosso_brand);
            padding: 10px 15px;
            border-radius: 6px;
            background-color: white;
            cursor: pointer;
            width: 100%;
            max-width: 550px;
            font-family: inherit;
        }

        /* Pulsante personalizzato Rosso AgenForm richiesto */
        .bottone-staff-rosso {
            background-color: var(--rosso_brand);
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s ease, transform 0.1s ease;
            text-transform: uppercase;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .bottone-staff-rosso:hover {
            filter: brightness(1.15);
        }

        .bottone-staff-rosso:active {
            transform: scale(0.98);
        }

        /* MODIFICATO: L'altezza ora dipende esclusivamente dal contenuto interno */
        .riquadro-bianco-contenitore {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 6px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
            height: auto;
        }

        /* Layout della griglia del calendario */
        .griglia-calendario {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        .giorno-calendario {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-top: 4px solid var(--rosso_brand);
            padding: 15px;
            border-radius: 4px;
        }

        .giorno-calendario.ha-lezione {
            border-top: 4px solid var(--rosso_brand);
        }

        .data-giorno {
            font-weight: bold;
            margin-bottom: 10px;
            color: #444;
        }

        .dettaglio-lezione {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }

        .materia {
            color: var(--rosso_brand);
            font-weight: 600;
        }

        /* MODIFICATO: Altezza ottimizzata per racchiudere il form senza spazi vuoti giganti */
        .iframe-gestione {
            width: 100%;
            height: 360px;
            border: none;
            border-radius: 4px;
            display: block;
        }
    </style>
</head>

<body>

    <header class="barra-superiore">
        <div class="spazio-logo">
            <img src="./materiali/Logo_AgenForm_2.png" alt="Logo AgenForm" class="immagine-logo">
        </div>
        <nav class="gruppo-bottoni">
            <a href="area_staff.php" class="bottone-menu attivo">Area Personale</a>
            <a href="logout.php" class="bottone-menu">Esci</a>
        </nav>
    </header>

    <main>
        <div class="header-dashboard">
            <div class="info-utente">
                <h1>Benvenuto Staff</h1>
                <p>Gestione didattica e pianificazione dei corsi</p>
            </div>

            <?php if ($vista === 'gestione'): ?>
                <a href="area_staff.php?vista=calendario&corso=<?php echo urlencode($corso_selezionato); ?>" class="bottone-staff-rosso">Vedi Calendario</a>
            <?php else: ?>
                <a href="area_staff.php?vista=gestione&corso=<?php echo urlencode($corso_selezionato); ?>" class="bottone-staff-rosso">Gestisci Calendario</a>
            <?php endif; ?>
        </div>

        <form method="GET" action="area_staff.php" class="selettore-corso-form">
            <input type="hidden" name="vista" value="<?php echo htmlspecialchars($vista); ?>">
            <select name="corso" class="selettore-corso" onchange="this.form.submit()">
                <?php foreach ($corsi_disponibili as $corso): ?>
                    <option value=\"<?php echo htmlspecialchars($corso['ID_corso']); ?>\" <?php if ($corso_selezionato == $corso['ID_corso']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($corso['nome_corso']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="riquadro-bianco-contenitore">
            <?php if ($vista === 'gestione'): ?>
                <iframe src="gestione_calendario.php?embed=1" class="iframe-gestione"></iframe>
            <?php else: ?>
                <div class="griglia-calendario">
                    <?php if (!empty($lezioni)): ?>
                        <?php foreach ($lezioni as $lezione):
                            // Trasformiamo le stringhe datetime del database in oggetti DateTime
                            $inizio = new DateTime($lezione['TS_inizio']);
                            $fine = new DateTime($lezione['TS_fine']);

                            // Array dei giorni per la traduzione in italiano
                            $giorni_settimana = ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"];

                            // Estraggaimo il numero del giorno della settimana (0 = Domenica, 1 = Lunedì, ecc.)
                            $num_giorno_settimana = $inizio->format('w');

                            // Componiamo la data nel formato: "NomeGiorno GG/MM" (es: Lunedì 06/07)
                            $data_card = $giorni_settimana[$num_giorno_settimana] . " " . $inizio->format('d/m');

                            // Estraiamo gli orari di inizio e fine formato HH:MM
                            $orario_inizio = $inizio->format('H:i');
                            $orario_fine = $fine->format('H:i');
                        ?>

                            <!-- Card singola lezione -->
                            <div class="giorno-calendario">
                                <div class="data-giorno"><?= $data_card ?></div>
                                <div class="materia-titolo">
                                    <span class="materia"><?= htmlspecialchars($lezione['nome_materia']) ?></span>
                                </div>
                                <div class="dettaglio-lezione">
                                    <strong>Orario:</strong> <?= $orario_inizio ?> - <?= $orario_fine ?>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Nessuna lezione in programma.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>

</html>