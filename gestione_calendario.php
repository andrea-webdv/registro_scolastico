<?php
// 1. AVVIO SESSIONE E CONTROLLO ACCESSI
session_start();
date_default_timezone_set('Europe/Rome');

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'staff') {
    die("Accesso negato. Questa pagina è riservata al personale autorizzato AgenForm.");
}

// Controllo se la pagina è incorporata nell'area staff
$is_embedded = isset($_GET['embed']) && $_GET['embed'] == '1';

// 2. CONNESSIONE AL DATABASE
try {
    $conn = new PDO("mysql:host=localhost;dbname=scuola", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione: " . $e->getMessage());
}

$dati_lezione=[];

$ID_corso_selezionato = $_SESSION['ID_corso_staff'] ?? null;
$nome_corso_selezionato = $_SESSION['nome_corso_staff'] ?? null;

// 3. GESTIONE DEL SALVATAGGIO DATI
 $messaggio = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data_lezione = $_POST['data_lezione'];
    $ora_inizio = $_POST['ora_inizio'];
    $ora_fine = $_POST['ora_fine'];
    $cod_materia = $_POST['cod_materia'];

    $ts_inizio = $data_lezione . " " . $ora_inizio . ":00";
    $ts_fine = $data_lezione . " " . $ora_fine . ":00";

    $dati_lezione = [$ts_inizio, $ts_fine, $cod_materia, $ID_corso_selezionato ];

    /* AGGIUNTA FUTURA
    if (!empty($dati_lezione)) {
        $sql = "UPDATE lezioni SET `TS_inizio` = ?, `TS_fine` = ?, `cod_materia` = ?, `ID_corso` = ?;
        $stmt = $conn->prepare($sql);
        $stmt->execute([$ts_inizio, $ts_fine, $cod_materia, $id_lezione]);
        $messaggio = "Lezione aggiornata con successo!";
    } else {
    }  */

##############
$json_dump = json_encode($dati_lezione);
echo "<script>
    alert('Descrivo cosa voglio controllare:\\n' + $json_dump);
</script>";
################
        $sql = "INSERT INTO calendari (TS_inizio, TS_fine, cod_aula, sede, cod_materia, ID_corso) VALUES (?, ?, 1, 1, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($dati_lezione);
        // $messaggio = "Nuova lezione inserita con successo!";
}

// 4. RECUPERO DELLE MATERIE
$errore_lettura = false;
$lista_materie = [];


if(! $ID_corso_selezionato){
    echo "<script>
        alert('Selezionare un corso prima di gestire il calendario');
    </script>";
    header("Location: area_staff.php");
}


try {
    $query_materie = "SELECT cod_materia, nome_materia FROM materie ORDER BY nome_materia ASC"; # aggiungere corso come where con valore opzionale null, aggiungere form con select corsi in pagina
    $stmt_materie = $conn->query($query_materie);
    $lista_materie = $stmt_materie->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore_lettura = true;
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff AgenForm - Gestione Calendario</title>
    <link rel="stylesheet" href="./css/agenform.css">
    <style>
        main {
            flex: 1;
            max-width: 800px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
        }

        .pannello-admin {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 6px;
            border-top: 6px solid var(--rosso_brand);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .titolo-sezione {
            color: var(--rosso_brand);
            margin-bottom: 25px;
            font-size: 22px;
            text-transform: uppercase;
        }

        .messaggio-successo {
            background-color: #d4edda;
            color: #155724;
            border: 1.5px solid #c3e6cb;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }

        .messaggio-errore-personalizzato {
            background-color: #f8d7da;
            color: #721c24;
            border: 1.5px solid #f5c6cb;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }

        /* --- STILI REGOLE PER QUANDO LA PAGINA È EMBEDDED --- */
        <?php if ($is_embedded): ?>html,
        body {
            background: #ffffff !important;
            /* Riempie di bianco ogni porzione di spazio dell'iframe */
            min-height: auto !important;
        }

        main {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
        }

        .pannello-admin {
            box-shadow: none !important;
            /* Rimuove l'ombra doppia */
            padding: 10px 0 0 0 !important;
            /* Azzera i pad laterali per usare lo spazio dell'area staff */
            border-top: none !important;
            /* Rimuove la linea rossa doppia */
        }

        <?php endif; ?>
    </style>
</head>

<body>

    <?php if (!$is_embedded): ?>
        <header class="barra-superiore">
            <div class="spazio-logo">
                <img src="./materiali/Logo_AgenForm_2.png" alt="Logo AgenForm" class="immagine-logo">
            </div>
            <nav class="gruppo-bottoni">
                <a href="#" class="bottone-menu attivo">Gestione Lezioni</a>
                <a href="logout.php" class="bottone-menu">Esci (Staff)</a>
            </nav>
        </header>
    <?php endif; ?>

    <main>
        <div class="pannello-admin">
            <h2 class="titolo-sezione">Inserisci o Modifica Lezione</h2>
            <?php
                echo "<p class='scritta-istruzione'> . $nome_corso_selezionato . </p>"
            ?>

            <?php if ($errore_lettura): ?>
                <div class="messaggio-errore-personalizzato">Errore di comunicazione, cretino</div>
            <?php endif; ?>

            <?php if (!empty($messaggio)): ?>
                <div class="messaggio-successo"><?php echo htmlspecialchars($messaggio); ?></div>
            <?php endif; ?>

            <form action="gestione_calendario.php?embed=<?php echo $is_embedded ? '1' : '0'; ?>" method="POST">
                <input type="hidden" name="id_lezione" value="">

                <label class="scritta-istruzione" for="data_lezione">Data della Lezione</label>
                <input type="date" name="data_lezione" id="data_lezione" class="campo-input" required>
                <div class="riga-campi">
                    <div class="mezzo-campo">
                        <label class="scritta-istruzione" for="ora_inizio">Ora Inizio</label>
                        <input type="time" name="ora_inizio" id="ora_inizio" class="campo-input" required>
                    </div>
                    <div class="mezzo-campo">
                        <label class="scritta-istruzione" for="ora_fine">Ora Fine</label>
                        <input type="time" name="ora_fine" id="ora_fine" class="campo-input" required>
                    </div>
                </div>

                <label class="scritta-istruzione" for="cod_materia">Seleziona Materia</label>
                <select name="cod_materia" id="cod_materia" class="campo-input" required>
                    <option value="" disabled selected>-- Scegli una materia dal database --</option>

                    <?php foreach ($lista_materie as $materia): ?>
                        <option value="<?php echo $materia['cod_materia']; ?>">
                            <?php echo htmlspecialchars($materia['nome_materia']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="bottone-rosso" style="margin-top: 20px;" <?php if ($errore_lettura) echo 'disabled'; ?>>Salva Lezione</button>
            </form>
        </div>
    </main>

</body>

</html>