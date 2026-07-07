<?php
require "./moduli/Comunicazione_database.php"; // Istanzia l'oggetto connessione
session_start();

$db = new Database("scuola");
$conn = $db->Collegamento();

### 1. LETTURA CONTROLLO ACCESSI E FILTRI
$matricola_sessione = $_SESSION['matricola'] ?? null;
$sessione_admin = $_SESSION['admin_loggato'] ?? false;

if (!$sessione_admin && $matricola_sessione == null) {
    header("Location: ./login.php");
    exit;
}

// Determiniamo se l'utente sta filtrando per una matricola specifica via GET/POST
$matricola_filtrata = null;
if (!$sessione_admin) {
    $matricola_filtrata = $matricola_sessione;
} elseif (isset($_GET['matricola_ricerca']) && !empty($_GET['matricola_ricerca'])) {
    $matricola_filtrata = $_GET['matricola_ricerca'];
}

// Identifichiamo il corso di riferimento
$id_corso_riferimento = $_SESSION['ID_corso'] ?? null;

// Se abbiamo una matricola filtrata ma non abbiamo il corso, lo recuperiamo per coerenza
if ($matricola_filtrata && !$id_corso_riferimento) {
    $stmt_c = $conn->prepare("SELECT ID_corso FROM studenti WHERE matricola = ?");
    $stmt_c->execute([$matricola_filtrata]);
    $res_c = $stmt_c->fetch(PDO::FETCH_ASSOC);
    if ($res_c) {
        $id_corso_riferimento = $res_c['ID_corso'];
    }
}

### 2. ESECUZIONE DELLA QUERY ROBUSTA (Speculare al report PDF)
$records = [];

if ($matricola_filtrata) {
    // VISTA STUDENTE: Lezioni con presenze/assenze individuali
    $query = "
        SELECT 
            c.ID_lezione,
            c.TS_inizio, 
            c.TS_fine, 
            m.nome_materia, 
            p.ora_entrata, 
            p.ora_uscita
        FROM lezioni c
        INNER JOIN materie m ON c.cod_materia = m.cod_materia
        LEFT JOIN presenze p ON c.ID_lezione = p.ID_lezione AND p.matricola = ?
        WHERE c.ID_corso = ?
        ORDER BY c.TS_inizio ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$matricola_filtrata, $id_corso_riferimento]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // VISTA CORSO GENERALE: Elenco lezioni con conteggio aggregato
    $query = "
        SELECT 
            c.ID_lezione,
            c.TS_inizio, 
            c.TS_fine, 
            m.nome_materia,
            COUNT(p.matricola) AS totale_presenti
        FROM lezioni c
        INNER JOIN materie m ON c.cod_materia = m.cod_materia
        LEFT JOIN presenze p ON c.ID_lezione = p.ID_lezione
        WHERE c.ID_corso = ?
        GROUP BY c.ID_lezione
        ORDER BY c.TS_inizio ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_corso_riferimento]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Presenze</title>
    <link rel="stylesheet" href="./css/stile.css">
    <link rel="stylesheet" href="./css/presenze.css">
</head>

<body>

    <main>

        <div class="principale-wrapper">

            <div class="titolo">
                <h1>Registro Presenze</h1>
            </div>

            <?php if ($sessione_admin): ?>
                <div class="ricerca-wrapper" style="margin-bottom: 20px; background: #f4f4f4; padding: 15px; border-radius: 5px;">
                    <form method="GET" action="presenze.php" style="display: flex; gap: 10px; align-items: center;">
                        <label for="matricola_ricerca" style="font-weight: bold;">Filtra per Matricola Studente:</label>
                        <input
                            type="text"
                            id="matricola_ricerca"
                            name="matricola_ricerca"
                            placeholder="Es. 10000001"
                            value="<?= htmlspecialchars($matricola_filtrata ?? '') ?>"
                            style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;"
                            maxlength="8">
                        <button type="submit" style="padding: 5px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Cerca
                        </button>
                        <?php if ($matricola_filtrata): ?>
                            <a href="presenze.php" style="padding: 5px 10px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
                                Azzera Filtro
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>

            <div class="tabella-container">
                <table class="tabella-dati">

                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Materia</th>
                            <th>Orario Lezione</th>
                            <?php if ($matricola_filtrata): ?>
                                <th>Orario Effettivo (Ingresso / Uscita)</th>
                                <th>Stato</th>
                            <?php else: ?>
                                <th>Studenti Presenti</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $record):
                                $data_lezione = date('d/m/Y', strtotime($record['TS_inizio']));
                                $orario_lezione = date('H:i', strtotime($record['TS_inizio'])) . ' - ' . date('H:i', strtotime($record['TS_fine']));

                                if ($matricola_filtrata) {
                                    if (!empty($record['ora_entrata'])) {
                                        $stato = 'Presente';
                                        $colore_testo = 'color: #009600; font-weight: bold;';
                                        $ora_u = ($record['ora_uscita'] === '00:00:00') ? 'In corso' : date('H:i', strtotime($record['ora_uscita']));
                                        $orario_effettivo = date('H:i', strtotime($record['ora_entrata'])) . ' / ' . $ora_u;
                                    } else {
                                        $stato = 'Assente';
                                        $colore_testo = 'color: #c80000; font-weight: bold;';
                                        $orario_effettivo = '---';
                                    }
                                }
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($data_lezione); ?></td>
                                    <td><?= htmlspecialchars($record['nome_materia']); ?></td>
                                    <td><?= htmlspecialchars($orario_lezione); ?></td>

                                    <?php if ($matricola_filtrata): ?>
                                        <td style="text-align: center;"><?= htmlspecialchars($orario_effettivo); ?></td>
                                        <td style="<?= $colore_testo ?>; text-align: center;"><?= $stato; ?></td>
                                    <?php else: ?>
                                        <td style="text-align: center; font-weight: bold;">
                                            <?= htmlspecialchars($record['totale_presenti'] ?? 0); ?> presenti
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">
                                    Nessuna lezione programmata o registrata per questo corso.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

            <div class="pulsantiera-report" style="margin-top: 25px; text-align: right;">
                <?php
                $url_pdf = "./moduli/report_presenze.php";
                if ($matricola_filtrata) {
                    $url_pdf .= "?matricola_studente=" . urlencode($matricola_filtrata);
                }
                ?>
                <a href="<?= $url_pdf ?>"
                    target="_blank"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; font-weight: bold; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: background 0.2s;"
                    onmouseover="this.style.backgroundColor='#bd2130'"
                    onmouseout="this.style.backgroundColor='#dc3545'">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle;">
                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 1A1.5 1.5 0 0 1 11 2.5V4h1.5a1.5 1.5 0 0 1 1.5 1.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 3 14.5v-12A1.5 1.5 0 0 1 4.5 1h5z" />
                        <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z" />
                    </svg>
                    Stampa Registro in PDF
                </a>
            </div>

        </div>

    </main>

</body>

</html>