<?php
function Exception_handler(mixed $ex)
{
    // Stampiamo i dettagli dell'errore in modo leggibile
    echo "<h2>Si è verificato un errore SQL:</h2>";
    echo "<p><strong>Messaggio:</strong> " . $ex->getMessage() . "</p>";
    echo "<p><strong>Codice Errore SQL:</strong> " . $ex->getCode() . "</p>";
    echo "<p><strong>File:</strong> " . $ex->getFile() . " alla linea " . $ex->getLine() . "</p>";
}


function Collegamento()
{
    try {
        $conn = new PDO("mysql:host=127.0.0.1;port=3306;dbname=scuola_sandbox", "root", "");
        // abilita gestione errori
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $ex) {
        Exception_handler ($ex);
        die();
    }
    
    return $conn;
}
?>