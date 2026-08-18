<?php
require_once __DIR__ . "/Collegamento.php";


function Lettura_lezioni($filtri = [])
{
    
    $richiesta_db = Collegamento();
    
    // recupero opzionale delle lezioni svolte
    $colonne = [];
    $confronti = [];
    $query = "SELECT `cod_aula`, `ID_lezione`, `materie`.`nome_materia`, `TS_inizio` , `TS_fine`
        FROM `lezioni` 
        LEFT JOIN `materie` ON `lezioni`.`cod_materia` = `materie`.`cod_materia` 
        LEFT JOIN `corsi` ON `lezioni`.`id_corso` = `corsi`.`id_corso`  
    ";

    if(!empty($filtri)){
        
        $nome_tabella = $filtri[0];
        $parametri = $filtri[1];    

        foreach($parametri as $condizione) {
            foreach ($condizione as $nome_colonna => $valore) {
                $colonne[] = "`" . $nome_tabella . "`.`" . $nome_colonna . "` = ?";
                $confronti[] = $valore;
            }
        }

        $query .= " WHERE (" . implode(" OR ", $colonne) . ")";
        //filtro per le lezion future
        $query .= " AND TS_fine < NOW()";
    }


    $query = $richiesta_db->prepare($query);

    !empty($filtri)?
        $query->execute($confronti) :
        $query->execute()
    ;

    $risultati = $query->fetchAll(PDO::FETCH_ASSOC);
    $richiesta_db = null;

    return $risultati;
}
?>