<?php
    require_once __DIR__ . "/../collegamento_db/Collegamento.php";
    function check_in(string $matricola, int $ID_lezione)
    {
        $pdo = Collegamento();

        // Genero data e ora attuali
        $ora_entrata = date('Y-m-d H:i:s');
        $inizio_lezione = orari_lezione($ID_lezione);
        $inizio_lezione = $inizio_lezione["TS_inizio"];

        $ora_check_in = "";

        if($ora_entrata < $inizio_lezione){
        $ora_check_in = $inizio_lezione; 
        }else{
        $ora_check_in = $ora_entrata;
        }


        $query = "INSERT INTO presenze (ID_lezione, matricola, ora_entrata, ora_uscita) 
                VALUES (:id_lezione, :matricola, :ora_entrata, NULL)";

        $operazione = $pdo->prepare($query);

        // Eseguo passando i parametri in modo sicuro
        return $operazione->execute([
            'id_lezione' => $ID_lezione,
            'matricola' => $matricola,
            'ora_entrata' => $ora_check_in
        ]);
    }

    function check_out(string $matricola, int $ID_lezione)
    {
        $pdo = Collegamento();

        // Genero data e ora attuali
        $ora_uscita = date('Y-m-d H:i:s');
        $fine_lezione = orari_lezione($ID_lezione);
        $fine_lezione = $fine_lezione["TS_fine"];   



        $ora_chek_out = "";

        if ($ora_uscita > $fine_lezione) {
        $ora_chek_out = $fine_lezione;
        } else {
        $ora_chek_out = $ora_uscita;
        }

        $query = "UPDATE presenze SET ora_uscita = :ora_uscita 
                WHERE ID_lezione = :ID_lezione AND matricola = :matricola";

        $operazione = $pdo->prepare($query);

        return $operazione->execute([   
            'ID_lezione' => $ID_lezione,
            'matricola' => $matricola,
            'ora_uscita' => $ora_chek_out
        ]);
    }


    function orari_lezione(string $ID_lezione){
        $query_3 = "SELECT lezioni.`TS_inizio`, lezioni.`TS_fine` FROM lezioni WHERE `ID_lezione` = ?;";

        $pdo = Collegamento();

        $query_3 = $pdo->prepare($query_3);
        $query_3->execute([(int)$ID_lezione]);
        $orari_lezione = $query_3->fetch(PDO::FETCH_ASSOC);
        $pdo = null;

        return $orari_lezione;   
    }


    //  Ricezioine della scansione del QR code
    if ( $_SERVER['REQUEST_METHOD'] === 'POST') {

        $matricola_scannerizzata = $_POST['matricola'] ?? "";
        $ID_lezione_ricevuta = $_POST['ID_lezione'] ?? "";

        # verifica della esistenza della matricola

        if (empty($ID_lezione_ricevuta)) {

        // CASE: nessuna lezione nell'aula
        header("Location: ./../feedback_scansioni/pagina_errore.php?error=nessuna_lezione");
            exit();
        }else{


        $query = "SELECT presenze.*, lezioni.cod_aula, lezioni.cod_materia, lezioni.ID_lezione FROM presenze\n"

            . "LEFT JOIN `lezioni` on  `presenze`.`ID_lezione` = `lezioni`.`ID_lezione`\n"

            . "WHERE `ora_uscita` is null\n"
            
            . "AND presenze.`matricola` = ?"
        ;
    

        $pdo = Collegamento();

        $query = $pdo->prepare($query);
        $query->execute([$matricola_scannerizzata]);
        $presenze_aperte = $query->fetchAll(PDO::FETCH_ASSOC);
        $pdo = null;

        $conta_presenze_aperte = count($presenze_aperte);

        $ID_presenza_aperta = $presenze_aperte[0]['ID_lezione'] ?? "";


        // Struttura a rami per la gestione di check-in / check-out
        if ($conta_presenze_aperte > 1) {
            // CASE: errori

            // notifica alla segreteria dei record e reindirizzamento a pagina di errore. 
            require_once __DIR__ . "/../mail/Notifiche_segreteria.php";
            Notifica_errore_scansioni($presenze_aperte);


            header("Location: ./../feedback_scansioni/pagina_errore.php?error=anomalie_lettura");

        } elseif ($conta_presenze_aperte == 1) {

            if($ID_lezione_ricevuta == $ID_presenza_aperta){

                // CASE: check out
                check_out($matricola_scannerizzata, $ID_lezione_ricevuta);
                
                header("Location: ./../feedback_scansioni/pagina_checkout.php");

            } else{

                // CASE: check out + check in
                check_out($matricola_scannerizzata, $ID_presenza_aperta); 
                check_in($matricola_scannerizzata, $ID_lezione_ricevuta);

                header("Location: ./../feedback_scansioni/pagina_checkout_checkin.php");
            }

        } else {

            //CASE: check out già effettuato sulla stessa lezione

            //recupero della ultima presenza per confrontare l'id'lezione

            $query_2 = "SELECT presenze.ID_lezione FROM presenze\n"
                . "WHERE presenze.`matricola` = ?\n"
                . "ORDER BY presenze.`ora_entrata` DESC\n"
                . "LIMIT 1" 
            ;

            echo ("# # # #");

            echo ("query: " . $query_2);
            echo ("# # # #");

            echo ("matricola_scannerizzata: " . $matricola_scannerizzata);
            echo ("# # # #");

            $pdo = Collegamento();

            $query_2 = $pdo->prepare($query_2);
            $query_2->execute([$matricola_scannerizzata]);
            $ID_ultima_presenza = $query_2->fetchColumn();
            $pdo = null;

        if ($ID_lezione_ricevuta == $ID_ultima_presenza) {

            header("Location: ./../feedback_scansioni/pagina_errore.php?error=checkout_multiplo");

            // la segreteria deve poter modificare le presenze nella sua dashboard
            die();

        }else{

            // CASE: check in
            
            check_in($matricola_scannerizzata, $ID_lezione_ricevuta);
    
            header("Location: ./../feedback_scansioni/pagina_checkin.php");
        }

        }
    }
    }
?>
