<?php
require "./moduli/Comunicazione_database.php";
session_start();

### lettura dati provenienti da login o menù filtro

$matricola_studente = $_SESSION['matricola'] ?? null;
$sessione_admin = $_SESSION['admin_loggato'] ??  False; 

### verifica per presenza di matricola o cod_admin, forza reindirizamento a login. da sostituire con pagina apposita per ux
if (!$sessione_admin and $matricola_studente == null) {
    header("Location: ./index.php");
}

### variablie per rendering condizionale, echo nella assegnazione di classi
$full_report = false;


$query_tutte_presenze = "SELECT * FROM `vista_registro_presenze`;";

### variabili di appoggio per i filtri, 
$filtro_matricola = $matricola_studente ?? null;

$filtro_corso = $_SESSION['cod_corso'] ?? null;

### variabili per aggiunta al testo della query cod_corso / matricola. 
# se la sessione consegue al login di uno studente (matricola), assegna il valore per la query dei dati dello studente

$query_matricola = "";
$query_corso = "";

if ($sessione_admin) {
    $full_report = true;
} else if (isset($matricola_studente)) {
    
    $query_matricola = "and presenze.`matricola` = " . $filtro_matricola;
}

if (isset($filtro_corso)) {
    $query_corso = "and presenze.`nome_materia` = " . $filtro_corso;
}

$query_composta = $query_tutte_presenze . $query_corso . $query_matricola . " ;";

/*
            ############################
            echo "<pre> query composta: ";
                var_dump($query_composta);
            echo "</pre>";
            echo "<pre> stamoa di full report? : ";
                var_dump($full_report);
            echo "</pre>";
            echo "<pre>  variabili filtri: ";
            echo " matricola: ";
                var_dump($filtro_matricola);
            echo "  corso: ";
                var_dump($filtro_corso);
            echo "</pre>";


             die(); 
            ############################ 
*/

$connessione = new Database("scuola");
$records = $connessione->Query_select($query_composta);

?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I miei corsi</title>

    <link rel="stylesheet" href="./css/agenform.css">
    <link rel="stylesheet" href="./css/home.css">

    <!-- <style>
        /* riquadro con testo "elenco delle presenze associate a matricola" */
        .riquadro-matricola.full_report {
            display: none;
        }

        .colonna_matricola.full_report {
            display: block;
        }

        .riquadro-matricola {
            display: initial;
        }

        .colonna_matricola {
            display: none;
        }

        #campo_matricola{
            display: none;
        }
        #campo_matricola.full_report{
            display: block;
        }


        /*         .opzione_corso{
            display:none
        }

        .opzione_corso.asked{
            display: inline;

        } */


        /* tabella scrollabile */
        .tabella-corsi-wrapper {
            max-height: 60vh;
            overflow-y: auto;S
            display: block;
        }

        .tabella-corsi {
            width: 100%;
            border-collapse: collapse;
        }

        .tabella-corsi thead {
            position: sticky;
            top: 0;
            z-index: 1;
        }
    </style> -->
</head>

<body>

    <header class="barra-superiore">

        <div class="spazio-logo">
                <img src="./materiali/Logo_AgenForm_2.png" alt="Logo AgenForm"
                    class="immagine-logo">
        </div>

        <nav class="gruppo-bottoni">
            <a href="home.php" class="bottone-menu">Homepage</a>
            <a href="./moduli/logout.php" class="bottone-menu attivo">Logout</a>
        </nav>

    </header>
    <main class="zona-centrale">

        <div class="scatola-corsi">

            <h1 class="titolo-corsi">Registro Presenze</h1>
            <br>
            <?php if (!$full_report){
            echo('
                <p class="sottotitolo" > 
                    Elenco delle presenze associate a
                </p>
                <br>
                <div class="riquadro-matricola">
                    <strong>Matricola:</strong>'
            );
            echo htmlspecialchars($matricola_studente);
            echo('
                </div> 
            ');   
            }; ?>

            <?php echo htmlspecialchars($matricola_studente); ?>
            <?php if ($filtro_corso != '') {
                echo ("
                    <strong >Materia:</strong>"
                    . htmlspecialchars($filtro_corso)
                );
            } ?>
            <br style="display: block;height: 1rem;">

            <div class="tabella-corsi-wrapper">
                <table class="tabella-corsi">

                    <thead>
                        <tr>
                            <th>Corso</th>
                            <th>inizio lezione</th>
                            <?php if($full_report) echo(
                                '<th class="nome">Nome</th> '.
                                '<th class="cognome">Cognome</th> '
                            );?>
                            <th>Ora ingresso</th>
                            <th>Ora uscita</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($records as $record): ?>

                            <tr>
                                <td><?= $record['nome_corso'] ?? ""; ?></td>
                                <td><?= $record['TS_inizio'] ?? ""; ?></td>
                                <?php if($full_report){
                                    
                                    echo('<td>');
                                    echo $record['cognome'] ?? "";
                                    echo('</td>');
                                    echo('<td>');
                                    echo $record['nome'] ?? "";
                                    echo('</td>');
                                }; ?>
                                <td><?= $record['ora_entrata'] ?? ""; ?></td>
                                <td><?= $record['ora_uscita'] ?? ""; ?></td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>
            </div>

        </div>

    </main>

</body>

</html>