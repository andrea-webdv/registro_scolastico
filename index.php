<?php
require "./moduli/Comunicazione_database.php";

$lettura = new Database("scuola");
$query = "SELECT `nome_corso`, `ID_corso`, `cod_edizione`, `durata` FROM `corsi` WHERE `accetta_iscrizioni` = 1";
$corsiDisponibili = $lettura->Query_select($query);
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgenForm - Offerta Formativa</title>
    <link rel="stylesheet" href="css/agenform.css">
    <link rel="stylesheet" href="css/index.css">
</head>


<body>

    <header class="barra-superiore">
        <div class="spazio-logo">
            <img src="./materiali/Logo_AgenForm_2.png" alt="Logo AgenForm" class="immagine-logo">
        </div>

        <nav class="gruppo-bottoni">
            <a href="index.php" class="bottone-menu attivo">Corsi</a>
            <a href="login.php" class="bottone-menu">Login</a>
        </nav>
    </header>
    <main>
        <h1 class="titolo-pagina">Offerta Formativa</h1>
        <p class="sottotitolo-pagina">Seleziona un corso per inviare la tua pre-iscrizione</p>

        <div class="contenitore-corsi" id="grigliaCorsi"></div>
    </main>

    <div class="modal-overlay" id="modalPreRegistrazione">
        <div class="modal-box">
            <h2>Candidati ora!</h2>
            <p>clicca il link sottostante e compila il modulo per candidarti al corso: <span class="corso-selezionato-testo" id="nomeCorsoSelezionato"></span></p>

            <a id="pulsanteProcedi" href="candidatura.php" class="bottone-rosso" style="display: block; text-align: center; text-decoration: none;">
                voglio candidarmi
            </a>

            <span class="link-annulla" id="chiudiModal">Torna indietro</span>
        </div>
    </div>

    <script>
        // Array dei corsi
        /** @type {Array<{nome_corso: string, cod_edizione: string}>} */
        const corsiDisponibili = <?php echo json_encode($corsiDisponibili, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>;

        if (!corsiDisponibili) corsiDisponibili = [{
                nome_corso: "Sviluppatore Web Full Stack (PHP/Python)",
                cod_edizione: "Tecnologie ICT",
                durata: "800 ore"
            },
            {
                nome_corso: "Tecnico Sistemi e Reti Cloud",
                cod_edizione: "Sistemi e Reti",
                durata: "600 ore"
            },
            {
                nome_corso: "Programmatore database SQL Server",
                cod_edizione: "Data Management",
                durata: "500 ore"
            },
            {
                nome_corso: "Esperto Web Design & Flexbox",
                cod_edizione: "Design Digitale",
                durata: "400 ore"
            },
            {
                nome_corso: "Cybersecurity & Network Specialist",
                cod_edizione: "Sicurezza Informatica",
                durata: "700 ore"
            },
            {
                nome_corso: "Amministratore di Sistema Linux",
                cod_edizione: "Sistemi",
                durata: "550 ore"
            }
        ];

        // Funzione per estrarre 3 corsi casuali
        function estraiCorsiCasuali(lista, quantita) {
            const mescolata = [...lista].sort(() => 0.5 - Math.random());
            return mescolata.slice(0, quantita);
        }

        const grigliaCorsi = document.getElementById('grigliaCorsi');
        const modal = document.getElementById('modalPreRegistrazione');
        const chiudiModal = document.getElementById('chiudiModal');
        const nomeCorsoSelezionato = document.getElementById('nomeCorsoSelezionato');
        const pulsanteProcedi = document.getElementById('pulsanteProcedi');

        //const corsiSelezionati = estraiCorsiCasuali(corsiDisponibili, 3);

        corsiDisponibili.forEach(corso => {
            const card = document.createElement('div');
            card.className = 'card-corso';
            /* rimpiazzare settore con con il codice edizione  */
            card.innerHTML = `
                <div>
                <div class="titolo-corso">${corso.nome_corso}</div>
                <div class="tag-settore">edizione di: ${corso.cod_edizione}</div>
                </div>
                <div class="durata-corso">Durata: ${corso.durata}</div>
            `;

            card.addEventListener('click', () => {
                nomeCorsoSelezionato.textContent = corso.nome_corso;

                // Manda alla pagina di registrazione con il nome del corso
                pulsanteProcedi.href = `candidatura.php?corso=${encodeURIComponent(corso.nome_corso)}&id=${encodeURIComponent(corso.ID_corso)}`;

                modal.classList.add('attivo');
            });

            grigliaCorsi.appendChild(card);
        });

        // Chiusura modal
        chiudiModal.addEventListener('click', () => {
            modal.classList.remove('attivo');
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('attivo');
            }
        });
    </script>
</body>

</html>