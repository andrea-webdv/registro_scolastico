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
    <nav class="gruppo-bottoni">
    ');

    foreach ($bottoni as $bottone) {
        echo $bottone[0];
    };

    echo ('
    </nav>
    </header>
    <br><br><br>
    ');
}
?>