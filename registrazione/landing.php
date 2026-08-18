<?php
require_once __DIR__ . "/../collegamento_db/Collegamento.php";
#require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . "/../navbar.php";
$corsiDisponibili = [];

$lettura = Collegamento();
$query = "SELECT `nome_corso`, `ID_corso`, `cod_edizione`, `durata` FROM `corsi` WHERE `accetta_iscrizioni` = ?";

$query = $lettura->prepare($query);

$query->execute([1]);
$corsiDisponibili = $query->fetchAll(PDO::FETCH_ASSOC);
$lettura = null;

if (empty($corsiDisponibili)) $corsiDisponibili = [
    [
        "nome_corso" => "Sviluppatore Web Full Stack (PHP/Python)",
        "ID_corso" => 1,
        "cod_edizione" => "sample",
        "durata" => "800 ore"
    ],
    [
        "nome_corso" => "Tecnico Sistemi e Reti Cloud",
        "ID_corso" => 2,
        "cod_edizione" => "sample",
        "durata" => "600 ore"
    ],
    [
        "nome_corso" => "Programmatore database SQL Server",
        "ID_corso" => 3,
        "cod_edizione" => "sample",
        "durata" => "500 ore"
    ],
    [
        "nome_corso" => "Esperto Web Design & Flexbox",
        "ID_corso" => 4,
        "cod_edizione" => "sample",
        "durata" => "400 ore"
    ],
    [
        "nome_corso" => "Cybersecurity & Network Specialist",
        "ID_corso" => 5,
        "cod_edizione" => "sample",
        "durata" => "700 ore"
    ],
    [
        "nome_corso" => "Amministratore di Sistema Linux",
        "ID_corso" => 6,
        "cod_edizione" => "sample",
        "durata" => "550 ore"
    ]
];

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgenForm - Offerta Formativa</title>
    <link rel="stylesheet" href="./../css/agenform.css">
    <link rel="stylesheet" href="./../css/landing.css">
</head>

<body>
    <?php
    navbar([[
        '<a href="./../index.php" class="btn btn-login">Torna al Login</a>'
        ]], "Offerta formativa");
    ?>
    <main>
        <h1 class="titolo-pagina">Offerta Formativa</h1>
        <p class="sottotitolo-pagina">Seleziona un corso per inviare la tua pre-iscrizione</p>

        <div class="contenitore-corsi" id="grigliaCorsi">
            <?php foreach ($corsiDisponibili as $corso): ?>
                
                <div class="card_corso">
                    <div>
                        <div class="edizione_corso">edizione di: <span><?php echo $corso["cod_edizione"] ?></span></div>
                        <div class="nome_corso"><strong><span><?php echo $corso["nome_corso"] ?></span></strong></div>
                    </div>
                    <div class="durata_corso">
                        <span style="font-weight: 600;">durata: <?php echo $corso["durata"] ?></span>
                        <span class="edizione_corso" style="color:#d9534f;"> clicca per selezionare</span>
                    </div>
                    <div hidden class="id_corso"><span><?php echo $corso["ID_corso"] ?></span></div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <dialog class="modal-overlay" id="modale_scelta">
        <div class="modal_box">
            <form action="candidatura.php" method="GET">
                <h2>Candidati ora!</h2>
                <p>
                    Hai selezionato il corso
                    <span class="corso-selezionato-testo" id="nomeCorsoSelezionato"></span>.<br>
                    Clicca qua sotto per confermare e compilare il modulo di candidatura.
                </p>
                <input type="hidden" name="id" id="id">
                <input type="hidden" name="corso" id="corso">

                <button type="submit" id="pulsanteProcedi" class="bottone-rosso" style="display: block; text-align: center; text-decoration: none;">
                    voglio candidarmi
                </button>
            </form>
            <button class="link-annulla" id="chiudiModal">Torna indietro</button>
        </div>
    </dialog>

    <script>
        const cards = document.querySelectorAll(".card_corso");
        const modale = document.querySelector("#modale_scelta");
        const form = modale.querySelector("form");
        const nomeCorsoInviato = modale.querySelector("#corso");
        const id_corsoInviato = modale.querySelector("#id");
        const chiudiBtn = modale.querySelector("#chiudiModal");


        cards.forEach(card => {

            card.addEventListener('click', () => {
                nomeCorsoSelezionato = card.querySelector('.nome_corso span').innerText;
                idCorsoSelezionato = card.querySelector('.id_corso span').innerText;

                nomeCorsoInviato.value = nomeCorsoSelezionato;
                id_corsoInviato.value = idCorsoSelezionato;

                modale.showModal();
            });
        });

        chiudiBtn.addEventListener('click', () => {
            modale.close();
        });
    </script>
</body>

</html>