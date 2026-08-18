<?php

function navbar(array $bottoni, ?string $titolo = "")
{
    // creazione dell'indirizzo con root del progetto
    $logo_agenform =  __DIR__ . '\materiali\Logo_AgenForm_2.png'; #necessario scrivere l'indirizzo come string con backslash
    $logo_agenform = str_replace('\\', '/', $logo_agenform);
    $logo_agenform = str_replace($_SERVER['DOCUMENT_ROOT'], '', $logo_agenform);

    echo ('
    <header class="barra-superiore">
    <div class="spazio-logo">
     <img src="' . $logo_agenform . '" alt="Logo AgenForm" class="immagine-logo">
    </div>
    ');
    
    if (!empty($titolo)) { echo ('<h1 style="color:#444444;margin: auto;font-size: xx-large;"> '. $titolo .' </h1>');}

    echo ('
    <div class="menu-wrapper">
    <!-- Pulsante Hamburger -->
    <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleMenuNav()" aria-label="Apri menu">
      ☰
    </button>
    <nav id="gruppoBottoni" class="gruppo-bottoni">
    ');

    foreach ($bottoni as $bottone) {
        echo $bottone[0];
    };

    echo ('
    </nav>
    </div>
    </header>
    <br><br><br>
    ');

    // sostituzione con hamburger
    echo("
    <script>
        function toggleMenuNav() {
            const menu = document.getElementById('gruppoBottoni');
            menu.classList.toggle('attivo');
        }
    
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('gruppoBottoni');
            const btn = document.getElementById('hamburgerBtn');
            
            if (menu && btn && !menu.contains(event.target) && !btn.contains(event.target)) {
                menu.classList.remove('attivo');
            }
        });
    </script>
    ");
}
?>
