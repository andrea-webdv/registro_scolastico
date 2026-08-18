<?php
require_once __DIR__ . "/Collegamento.php";

function Controllo_presenze(?array $filtri = [])
{
    $query = "SELECT * FROM   `presenze`";

    // aggiunta di filtri
    $valori = [];

    if (count($filtri) != 0) {
        $query .= " WHERE ";
        $condizioni = [];

        foreach ($filtri as $filtro) {
            foreach ($filtro as $key => $value) {
                $condizioni[] = "`" . $key . "` = ?";
                $valori[] = $value;
            }
        }

        $query .= implode(" AND ", $condizioni);
    }


    # error_log("query: ". $query);
    $richiesta_db = Collegamento();

    $query = $richiesta_db->prepare($query);
    $query->execute($valori);
    $risultati = $query->fetchAll(PDO::FETCH_ASSOC);
    $richiesta_db = null;
    

    return ($risultati);
}
?>