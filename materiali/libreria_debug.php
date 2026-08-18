<?php

function console_log($data)
{
    echo '<script>
        ';
    echo 'console.log(' . json_encode($data) .
        ')';
    echo '
        </script>';
}

function log_sessione()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    var_dump($_SESSION);

    echo '<script>
        ';
    echo 'console.log(' . json_encode($_SESSION) .
        ')';
    echo '
        </script>';
}

function Visualizza_dati(array $risultati)
{
    if (empty($risultati)) {
        echo "<script>console.log('Dump_dati: nessun record trovato.');</script>";
        return;
    }

    echo <<<HTML
            <dialog open id="php-debug-dialog" style="
                position: fixed;
                top: 10%;
                left: 50%;
                transform: translateX(-50%);
                width: 80%;
                max-width: 800px;
                max-height: 75vh;
                border: 2px solid #dc3545;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                background: #212529;
                color: #f8f9fa;
                padding: 20px;
                font-family: monospace;
                overflow-y: auto;
                z-index: 99999;
            ">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #495057; padding-bottom: 10px; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #dc3545;">🕵️‍♂️ PHP Debug - Dump Dati</h3>
                    <button onclick="document.getElementById('php-debug-dialog').close()" style="
                        background: #dc3545;
                        color: white;
                        border: none;
                        padding: 5px 12px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: bold;
                    ">Chiudi</button>
                </div>
                
                <div style="background: #111; padding: 10px; border-radius: 4px;">
        HTML;

    // Stampiamo i record dentro il dialog
    foreach ($risultati as $record) {
        echo "<pre style='margin: 0 0 15px 0; border-bottom: 1px dashed #343a40; padding-bottom: 10px;'>";
        var_dump($record);
        echo "</pre>";
    }

    echo <<<HTML
                </div>
            </dialog>
        HTML;

    return die();
}
