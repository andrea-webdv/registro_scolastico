<?php
require_once __DIR__ . "/Collegamento.php";

function Lettura_aule()
{
    $richiesta_db = Collegamento();


    $query = $richiesta_db->prepare("SELECT `cod_aula` FROM lezioni GROUP BY `cod_aula`");
    $query->execute();
    $risultati = $query->fetchAll(PDO::FETCH_ASSOC);
    $richiesta_db = null;

    return $risultati;
}

function recupero_lezione(string $cod_aula, string $data)
{

    $sql = "SELECT `ID_lezione`\n"
        . "FROM lezioni\n"
        . "WHERE cod_aula = ?\n"
        . "  AND TS_inizio < ?\n"
        . "  AND ? < TS_fine\n"
        . "ORDER BY TS_inizio DESC\n" 
        . "LIMIT 1";                 
    ; 

    $richiesta_db = Collegamento();
    
    $query = $richiesta_db->prepare($sql);
    $query->execute([$cod_aula, $data, $data]);
    $risultati = $query->fetchColumn();
    $richiesta_db = null;

    return $risultati;
    
}
