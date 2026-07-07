<?php
class Database
{
    private $hostIP = "localhost"; #192.168.122.132
    private $db = ""; # qui sql accetat stringhe pure,backslash non necessarie

    public function __construct(string $DBname)
    {
        $this->db = $DBname;
    }

    private function Exception_handler ($ex)
    { 
        // Stampiamo i dettagli dell'errore in modo leggibile
        echo "<h2>Si è verificato un errore SQL:</h2>";
        echo "<p><strong>Messaggio:</strong> " . $ex->getMessage() . "</p>";
        echo "<p><strong>Codice Errore SQL:</strong> " . $ex->getCode() . "</p>";
        echo "<p><strong>File:</strong> " . $ex->getFile() . " alla linea " . $ex->getLine() . "</p>";   
    }

    public function Collegamento()
    {
        try {
            $conn = new PDO("mysql:host=" . $this->hostIP . ";port=3306;dbname=" . ($this->db ?? "scuola"), "root", "");
            // abilita gestione errori
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $ex) {
            $this->Exception_handler ($ex);
            die();
        }

        return $conn;
    }

    public function Dump_dati(string $query)
    {
        try {
            $Collegamento = $this->Collegamento();

            $query = $Collegamento->prepare($query);
            $query->execute();
            $risultati = $query->fetchAll(PDO::FETCH_ASSOC);

            $this->Chiudi($Collegamento);

            ###
            foreach ($risultati as $record){
                echo "<pre>";
                var_dump($record);
                echo "</pre>";
            }
            # Blocca l'esecuzione per studiare i dati senza caricare il resto della pagina
            die();
            ###
            
        } catch (PDOException $ex) {
            $this->Exception_handler ($ex);
            die();
        };
    }

    public function Query_select(string $query)
    {
        /*
         * riceve query gia' compilate.
         * 
         * raccogliere i nomi di colonne e parametri testuali tra `backlask`
         */
        try {
            $Collegamento = $this->Collegamento();

            $query = $Collegamento->prepare($query);
            $query->execute();
            $risultati = $query->fetchAll(PDO::FETCH_ASSOC);

            $this->Chiudi($Collegamento);

            return $risultati; #in struttura di array di array associativi
        } catch (PDOException $ex) {
            $this->Exception_handler ($ex);
            die();
        };
    }

    public function Query_exists(string $tabella, array $campi)
    { 
        /*
         * Verifica la presenza dei dati nei campi.
         * 
         * @param $campi da compilare con: [ ['colonna', 'valore'], ...] #rimpiazzabile con mappa key-value?
         */
        try {
            $Collegamento = $this->Collegamento();

            $Query_exists = "
            SELECT EXISTS(
                SELECT 1 
                FROM `" . $tabella . "` 
                WHERE 
            ";

            # SELECT EXISTS(SELECT 1 FROM admin WHERE codice_admin = :".$cod_admin.");"
            $query_where = [];
            $query_execute = [];

            #compone i due array in modo speculare: WHERE 'campo[0]' = :0 | :0 = 'campo[1]'
            for ($i=0; $i < count($campi) ; $i++) { 
                $query_where[] ="`" . $campi[$i][0] . "` = ?";
                $query_execute[] = $campi[$i][1];
            }
            $Query_exists = $Query_exists . implode(" AND ", $query_where) . ' )' ;

            $query = $Collegamento->prepare($Query_exists);
            $query->execute($query_execute);

            $risultati = $query->fetchColumn(); # fetchColumn permette di ricevere 1 | 0 come risposta della query EXISTS

            $this->Chiudi($Collegamento);

            return $risultati;
        } catch (PDOException $ex) {
            $this->Exception_handler ($ex);
            die();
        };
    }
    
    public function Query_insert(string $tabella, array $dati) {
        /*
         * @param $dati da compilare con: array("colonna" => 'valore', ...)
         */

        try {
            $Collegamento = $this->Collegamento();

            $keys = [];
            $placeholders = [];
            $values = [];

            foreach ($dati as $key => $value) {
                $keys[] = '`'.$key.'`';
                $values[] = $value;
                $placeholders[] = '?';
            }

            $nuovoStudente = "insert into " . $tabella . " (". implode(",", $keys) .") values (" . implode(",",$placeholders) . ")";  # values (?,?,?,?,?,?,?)";
            
            $query = $Collegamento->prepare($nuovoStudente);
            $inserimento = $query->execute($values); # execute rende un booleano, utile da assegnare a return per verifiche nel codice

            return $inserimento;
        } catch (PDOException $ex) {
            $this->Exception_handler ($ex);
            die();
        };
    } 

    public function Query_update(string $tabella, array $riferimento, array $dati) 
    {
        /*
         * @param $dati e $riferimento da compilare con: array("colonna" => 'valore', ...).
         * $riferimento deve contenere solo una coppia key - value
         */
        try {
            $Collegamento = $this->Collegamento();
            $main = [];
            $values =[];

            foreach ($dati as $key => $value) {
                $main[] = "`" . $key . "` = ?";
                $values[] = $value;
            }

            if (count($riferimento) !== 1) {
                throw new InvalidArgumentException(
                    "Errore: L'array dei criteri di riferimento (WHERE) deve contenere esattamente 1 elemento. Ricevuti: " . count($riferimento)
                );
            };
            $condition = key( $riferimento ) . " = ?";
            $values[] = current( $riferimento );

            $SQLprenotaUtenti = "update " . $tabella . " set ". implode(",", $main) ." where " . $condition; 

            $query = $Collegamento->prepare($SQLprenotaUtenti);
            $inserimento = $query->execute($values); # execute rende un booleano, utile da assegnare a return per verifiche nel codice

            return $inserimento;
        } catch (PDOException $ex) {
            $this->Exception_handler ($ex);
            die();
        };
    } 

    public function Chiudi(PDO $conn)
    {
        $conn = null;
    }

}

?>