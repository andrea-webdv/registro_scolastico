<?php
session_start();

// --- SIMULAZIONE DATI DAL DATABASE ---
// In un'app reale, questi dati verrebbero presi dal database SQL Server dopo il login.
// Per ora li simuliamo in sessione per far funzionare la pagina.
if (!isset($_SESSION['nome_studente'])) {
    $_SESSION['nome_studente'] = "Studente";
    // Usiamo il corso salvato dalla registrazione, o uno di default
    $_SESSION['corso_iscritto'] = isset($_SESSION['corso_preiscrizione']) ? $_SESSION['corso_preiscrizione'] : "Sviluppatore Web Full Stack (PHP/Python)";
    $_SESSION['ore_assenza'] = 16; // Ore di assenza accumulate da calcolare con query confronto presenze.id_lezione X lezioni.id_lezione su corso ...aggiungere a studente?
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgenForm - Area Studente</title>
    
    <link rel="stylesheet" href="./css/agenform.css">
    
    <style>
        /* --- STILI SPECIFICI PER LA DASHBOARD --- */
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
            margin-bottom: 30px;
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 6px;
            border-left: 6px solid var(--rosso_brand);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .titolo-benvenuto {
            color: #333333;
            font-size: 24px;
        }

        .bottone-assenze {
            background-color: #555555; /* Grigio scuro per differenziarlo dalle azioni principali */
            width: auto;
            padding: 10px 20px;
            margin-top: 0;
        }

        .bottone-assenze:hover {
            background-color: #333333;
        }

        /* --- CARD CORSO INTERATTIVA --- */
        .card-corso-iscritto {
            background-color: #ffffff;
            border-top: 6px solid var(--rosso_brand);
            border-radius: 6px;
            padding: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: transform 0.2s ease;
            text-align: center;
            position: relative;
        }

        .card-corso-iscritto:hover {
            transform: translateY(-3px);
        }

        .card-corso-iscritto h2 {
            color: var(--rosso_brand);
            margin-bottom: 10px;
        }

        .card-corso-iscritto p {
            color: #666;
            font-size: 14px;
        }

        .icona-freccia {
            display: inline-block;
            margin-top: 15px;
            color: var(--rosso_brand);
            font-size: 20px;
            transition: transform 0.3s ease;
        }

        .card-corso-iscritto.aperto .icona-freccia {
            transform: rotate(180deg);
        }

        /* --- CALENDARIO A SCOMPARSA --- */
        .contenitore-calendario {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-in-out, opacity 0.5s ease-in-out, margin-top 0.5s ease;
            opacity: 0;
            background-color: #fff;
            border-radius: 0 0 6px 6px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .contenitore-calendario.mostra {
            max-height: 1000px; /* Valore alto per permettere l'apertura fluida */
            opacity: 1;
            margin-top: 10px;
            padding: 20px;
            border-top: 1px dashed #ccc;
        }

        /* Griglia del calendario */
        .griglia-calendario {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .giorno-calendario {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid var(--grigio_brand);
        }

        .giorno-calendario.ha-lezione {
            border-left-color: var(--rosso_brand);
            background-color: rgba(149, 29, 59, 0.03);
        }

        .data-giorno {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .dettaglio-lezione {
            font-size: 13px;
            color: #555;
            line-height: 1.5;
        }

        .materia {
            font-weight: bold;
            color: var(--rosso_brand);
            display: block;
            margin-top: 5px;
        }

        /* --- FINESTRELLA (MODAL) ASSENZE --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(3px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 100;
        }

        .modal-overlay.attivo {
            display: flex;
        }

        .modal-box {
            background-color: #ffffff;
            padding: 35px;
            border-radius: 6px;
            border-top: 8px solid var(--rosso_brand);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .numero-assenze {
            font-size: 48px;
            font-weight: bold;
            color: var(--rosso_brand);
            margin: 15px 0;
            display: block;
        }
        
        .chiudi-modal {
            margin-top: 20px;
            cursor: pointer;
            color: #666;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <header class="barra-superiore">
        <div class="spazio-logo">
            <img src="./materiali/Logo_AgenForm_2.png" alt="Logo AgenForm" class="immagine-logo">
        </div>
        <nav class="gruppo-bottoni">
            <a href="dashboard.php" class="bottone-menu attivo">Dashboard</a>
            <a href="logout.php" class="bottone-menu">Esci</a>
        </nav>
    </header>

    <main>
        <div class="header-dashboard">
            <h1 class="titolo-benvenuto">Benvenuto, <?php echo htmlspecialchars($_SESSION['nome_studente']); ?>!</h1>
            <button id="btnApriAssenze" class="bottone-rosso bottone-assenze">Controlla Assenze</button>
        </div>

        <div class="card-corso-iscritto" id="cardCorso">
            <p>Il tuo corso attuale:</p>
            <h2><?php echo htmlspecialchars($_SESSION['corso_iscritto']); ?></h2>
            <p>Clicca qui per visualizzare il calendario delle lezioni</p>
            <span class="icona-freccia">▼</span>
        </div>

        <div class="contenitore-calendario" id="sezioneCalendario">
            <h3 style="color: #333; margin-bottom: 15px;">Calendario Lezioni - Settimana Corrente</h3>
            
            <div class="griglia-calendario" id="grigliaCalendario">
                </div>
        </div>
    </main>

    <div class="modal-overlay" id="modalAssenze">
        <div class="modal-box">
            <h2 style="color: #333; text-transform: uppercase;">Riepilogo Assenze</h2>
            <p>Finora hai maturato un totale di:</p>
            <span class="numero-assenze"><?php echo $_SESSION['ore_assenza']; ?> ore</span>
            <p style="font-size: 13px; color: #666;">Ti ricordiamo che il limite massimo consentito varia in base al modulo formativo.</p>
            
            <div id="btnChiudiAssenze" class="bottone-rosso" style="text-align: center; margin-top: 20px;">Ho capito</div>
        </div>
    </div>

    <script>
        // --- GESTIONE APERTURA/CHIUSURA CALENDARIO ---
        const cardCorso = document.getElementById('cardCorso');
        const sezioneCalendario = document.getElementById('sezioneCalendario');

        cardCorso.addEventListener('click', () => {
            // Aggiunge o rimuove le classi per l'animazione css
            cardCorso.classList.toggle('aperto');
            sezioneCalendario.classList.toggle('mostra');
        });

        // --- GESTIONE MODAL ASSENZE ---
        const modalAssenze = document.getElementById('modalAssenze');
        const btnApriAssenze = document.getElementById('btnApriAssenze');
        const btnChiudiAssenze = document.getElementById('btnChiudiAssenze');

        btnApriAssenze.addEventListener('click', () => {
            modalAssenze.classList.add('attivo');
        });

        btnChiudiAssenze.addEventListener('click', () => {
            modalAssenze.classList.remove('attivo');
        });

        // Chiudi il modal se si clicca fuori dal riquadro bianco
        modalAssenze.addEventListener('click', (e) => {
            if (e.target === modalAssenze) {
                modalAssenze.classList.remove('attivo');
            }
        });

        // --- GENERAZIONE DINAMICA DEL CALENDARIO ---
        // Usiamo JavaScript per creare un calendario fittizio per dimostrazione
        const lezioni = [
            { giorno: "Lunedì 10 Luglio", orario: "08:30 - 17:30", materia: "Programmazione ad Oggetti (OOP)" },
            { giorno: "Martedì 11 Luglio", orario: "08:30 - 17:30", materia: "Database SQL Server" },
            { giorno: "Mercoledì 12 Luglio", orario: "Nessuna lezione", materia: "" }, // Giorno libero
            { giorno: "Giovedì 13 Luglio", orario: "08:30 - 17:30", materia: "HTML & CSS Flexbox" },
            { giorno: "Venerdì 14 Luglio", orario: "08:30 - 17:30", materia: "Laboratorio Pratico" }
        ];

        const contenitoreGriglia = document.getElementById('grigliaCalendario');

        lezioni.forEach(lezione => {
            const divGiorno = document.createElement('div');
            // Se c'è una materia, aggiungiamo la classe "ha-lezione" per colorare il bordo di rosso
            divGiorno.className = lezione.materia !== "" ? 'giorno-calendario ha-lezione' : 'giorno-calendario';
            
            let htmlContenuto = `<div class="data-giorno">${lezione.giorno}</div>`;
            
            if (lezione.materia !== "") {
                htmlContenuto += `
                    <div class="dettaglio-lezione">
                        <strong>Orario:</strong> ${lezione.orario}<br>
                        <span class="materia">${lezione.materia}</span>
                    </div>
                `;
            } else {
                htmlContenuto += `<div class="dettaglio-lezione" style="color: #999;">${lezione.orario}</div>`;
            }

            divGiorno.innerHTML = htmlContenuto;
            contenitoreGriglia.appendChild(divGiorno);
        });
    </script>

</body>
</html>