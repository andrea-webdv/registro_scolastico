<?php

/**
 * Converte i minuti di presenza in ore arrotandate alla mezz'ora
 * e restituisce il riepilogo confrontato con la durata di riferimento.
 *
 * @param float|int $minuti_totali Il totale dei minuti calcolati dal ciclo presenze.
 * @param float|int $durata_riferimento La durata (in ore) del corso o della materia.
 * @return array Mappa contenente durata, ore di presenza arrotandate e stima assenze.
 */

require_once __DIR__ . "/../collegamento_db/Lettura_lezioni.php";
require_once __DIR__ . "/../collegamento_db/Collegamento.php";

function lettura_durata(array $riferimenti){


    $chiamata = Collegamento();
    $accumulatore = [];
    $durata = 0;

    switch ($riferimenti[0]) {
        case 'materie':
            $query = "SELECT materie.`durata_materia` as `durata` FROM materie WHERE";
            $record = [];
            $codici = [];
            foreach($riferimenti[1] as $riferimento){
                foreach($riferimento as $codice) {
                    $record[] = " materie.`cod_materia` = ?";
                    $codici[] = $codice;
                }
            }

            $query .= implode( " OR" , $record);


            $query = $chiamata->prepare($query);
            $query->execute($codici);
            $accumulatore = $query->fetchAll(PDO::FETCH_ASSOC);
            $chiamata = null;
        break;

        case 'corsi':

            $query = "SELECT corsi.`durata` FROM corsi WHERE";
            $record = [];
            $codici = [];
            foreach ($riferimenti[1] as $riferimento) {
                foreach ($riferimento as $codice) {

                    $record[] = " corsi.`ID_corso` = ?";
                    $codici[] = $codice;
                }
            }

            $query .= implode(" OR", $record);

            $query = $chiamata->prepare($query);
            $query->execute($codici);
            $accumulatore = $query->fetchAll(PDO::FETCH_ASSOC);
            $chiamata = null;
        break;
    }

    foreach($accumulatore as $record) {

        $durata += (int)$record['durata'];
    }
    
    return $durata;

}

function calcola_riepilogo_ore($minuti_totali, $riferimento_lezioni= [], $permessi = 0): array
{
    // Conversione da minuti a ore
    $ore_grezze = $minuti_totali / 60;

    // Arrotondamento alla mezz'ora più vicina (es. 1.3 -> 1.5 | 1.2 -> 1.0)
    $ore_presenza_arrotondate = round($ore_grezze * 2) / 2;

    // Calcolo preliminare assenze (responsabilità soggetta a successive espansioni)
    $ore_assenza = 0;


    // calolo delle ore da sottarre dal totale di assenze, con la lettura delle ore delle lezioni future
    $ore_effettive = 0;

    $riferimento_durata = 0;

    //letura delle lezioni
    if(!empty($riferimento_lezioni)){
        $minuti_effettivi = 0;

        // query distinte per recuperare durata corsi o materie in base a filtri[0];
        $lezioni_ad_oggi = Lettura_lezioni($riferimento_lezioni);


        $riferimento_durata = lettura_durata($riferimento_lezioni);
        
        
        
        
        foreach($lezioni_ad_oggi as $lezione){
            
            $ts_inizio = strtotime($lezione['TS_inizio']);
            $ts_fine = strtotime($lezione['TS_fine']);

            if ($ts_fine > $ts_inizio) {
                $minuti_effettivi += ($ts_fine - $ts_inizio) / 60;
            }
        }

        $ore_effettive = $minuti_effettivi / 60;
    }



    $ore_assenza = max(0, $ore_effettive - $ore_presenza_arrotondate - $permessi);
    
    $array_risultante = [
        'durata'        => (float) $riferimento_durata,
        'ore_presenza'  => (float) $ore_presenza_arrotondate,
        'ore_assenza'   => (float) $ore_assenza
    ];


    return [
        'durata'        => (float) $riferimento_durata,
        'ore_presenza'  => (float) $ore_presenza_arrotondate,
        'ore_assenza'   => (float) $ore_assenza 
    ];
}
