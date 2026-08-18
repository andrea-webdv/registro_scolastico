<?php
    require_once __DIR__ . "/Collegamento.php";

    require_once __DIR__ . "/../materiali/libreria_debug.php";

    function Query_insert(string $tabella, array $dati, ) {
        /**
        * @param $dati da compilare con: array("colonna" => 'valore', ...)
        */

        try {
            $Collegamento = Collegamento();

            $keys = [];
            $placeholders = [];
            $values = [];

            foreach ($dati as $key => $value) {
                $keys[] = '`'.$key.'`';
                $values[] = $value;
                $placeholders[] = '?';
            }

            $query = "
            insert into " . $tabella . " (". implode(",", $keys) .") values (" . implode(",",$placeholders) . ");";

 
            $dato = [];
            
            switch ($tabella) {
                case 'studenti':
                    $query_S = "
                        SELECT @ultima_matricola_generata;
                    ";  # rende la matricola generata a lato db tramite trigger

                    $query .= $query_S;
     
                break;

                case 'candidature':
                    $query_C = "
                            SELECT LAST_INSERT_ID();
                    ";  # rende la matricola generata a lato db tramite trigger

                    $query .= $query_C;
                    
                break;

                default:
                    exit();
                break;
            };
            
            console_log("query di insert: " . $query);
            console_log("valori di insert: " . json_encode($values));

            $query = $Collegamento->prepare($query);
            $query->execute($values); # execute rende un booleano, utile da assegnare a return per verifiche nel codice
            
            if ($tabella === 'studenti'||'candidature'){ $query->nextRowset(); }

            $dato =  $query->fetchColumn();
            console_log("dato ricevuto in risposta dalla query: " . json_encode($dato));


            $Collegamento = null;
            
            return $dato;
        } catch (PDOException $ex) {
            console_log(Exception_handler ($ex));
            $Collegamento = null;
            die();
        };
    } 

    function Query_precompiled(string $query, ?array $dati = []){
        /**
        * riceve query gia' compilate.
        * nel casoc he la query ricerchi dei valori
        * è necessario usare i placeholder '?' 
        * e passare l'array opzionale con la sequenza di dati
        */

        try {
            $Collegamento = Collegamento();
            
            console_log("ricevuta query precompilata: " . $query);
            console_log("dati per la query precompilata: " . json_encode($dati));
            
            $query = $Collegamento->prepare($query);

            if (!empty($dati)) {
                $query->execute($dati);
            }else{
                $query->execute();
            }
            
            $risultati = $query->fetchAll(PDO::FETCH_ASSOC);

            $Collegamento = null;
            
            return $risultati;

        } catch (PDOException $ex) {
            Exception_handler ($ex);
            die();
        };
    }

?>