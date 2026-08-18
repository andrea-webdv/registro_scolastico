<?php
require_once __DIR__ . "/Collegamento.php";

// Separa la data dall'ora in modo sicuro contro valori null o formati errati.
function separa_DataOra(?string $ts_inizio): ?array
{

    if (empty($ts_inizio) || $ts_inizio === '0000-00-00 00:00:00') {
        return null;
    }
    try {
        $dateTime = new DateTime($ts_inizio);

        // Formattiamo e restituiamo l'array
        return [
            'data' => $dateTime->format('Y-m-d'),
            'ora' => $dateTime->format('H:i:s')
        ];
    } catch (Exception $e) {
        return null;
    }
}

function Lettura_presenze(?array $filtri = []): array
{
    $mappa_colonne = [
        'ID_corso'    => '`lezioni`.`ID_corso`',
        'corsi'       => '`lezioni`.`ID_corso`',
        'cod_materia' => '`lezioni`.`cod_materia`',
        'materie'     => '`lezioni`.`cod_materia`',
        'ID_lezione'  => '`lezioni`.`ID_lezione`'
    ];

    $condizioni_where = [];
    $valori_where     = [];
    $valori_join      = [];
    $join_matricola   = "";

    if (!empty($filtri)) {
        foreach ($filtri as $filtro) {
            if (!is_array($filtro)) continue;

            foreach ($filtro as $chiave => $valore) {
                if ($valore === '' || $valore === null) continue;

                // Intercettazione specifica della matricola per la clausola ON della LEFT JOIN
                if ($chiave === 'matricola') {
                    $join_matricola = " AND `presenze`.`matricola` = ? ";
                    $valori_join[]  = $valore;
                    continue;
                }

                $colonnaSQL = $mappa_colonne[$chiave] ?? ("`" . $chiave . "`");

                if (is_array($valore)) {
                    if (!empty($valore)) {
                        $placeholders = implode(',', array_fill(0, count($valore), '?'));
                        $condizioni_where[] = "$colonnaSQL IN ($placeholders)";
                        foreach ($valore as $v) {
                            $valori_where[] = $v;
                        }
                    }
                } else {
                    $condizioni_where[] = "$colonnaSQL = ?";
                    $valori_where[]     = $valore;
                }
            }
        }
    }

    $query = "
        SELECT  
            `lezioni`.`ID_lezione`, 
            `lezioni`.`TS_inizio`, 
            `lezioni`.`TS_fine`,
            `presenze`.`ora_entrata`,
            `presenze`.`ora_uscita`,
            `materie`.`nome_materia`,
            `presenze`.`permessi`
        FROM `lezioni`
        LEFT JOIN `presenze` 
            ON `presenze`.`ID_lezione` = `lezioni`.`ID_lezione` $join_matricola
        LEFT JOIN `materie` 
            ON `materie`.`cod_materia` = `lezioni`.`cod_materia`
    ";

    if (!empty($condizioni_where)) {
        $query .= " WHERE " . implode(" AND ", $condizioni_where);
    }

    // Unione ordinata dei parametri: prima la matricola per la JOIN, poi i parametri per la WHERE
    $valori_totali = array_merge($valori_join, $valori_where);

    $richiesta_db = Collegamento();
    $query_prep   = $richiesta_db->prepare($query);
    $query_prep->execute($valori_totali);
    $risultati    = $query_prep->fetchAll(PDO::FETCH_ASSOC);
    $richiesta_db = null;

    $presenze = [];

    foreach ($risultati as $record) {
        $lettura_inizio  = separa_DataOra($record['TS_inizio'] ?? null);
        $lettura_fine    = separa_DataOra($record['TS_fine'] ?? null);
        $lettura_entrata = separa_DataOra($record['ora_entrata'] ?? null);
        $lettura_uscita  = separa_DataOra($record['ora_uscita'] ?? null);

        $presenze[] = [
            'ID_lezione'   => $record['ID_lezione'] ?? '',
            'data_lezione' => $lettura_inizio['data'] ?? '',
            'ora_inizio'   => $lettura_inizio['ora'] ?? '',
            'ora_fine'     => $lettura_fine['ora'] ?? '',
            'nome_materia' => $record['nome_materia'] ?? '',
            'ora_entrata'  => $lettura_entrata['ora'] ?? 'nessuna entrata',
            'ora_uscita'   => $lettura_uscita['ora'] ?? 'nessuna uscita',
            'permessi'     => $record['permessi']
        ];
    }

    return $presenze;
}

function Lettura_matricole(): array
{
    $richiesta_db = Collegamento();
    $query = $richiesta_db->prepare("SELECT `matricola` FROM studenti ORDER BY `matricola` ASC");
    $query->execute();
    $risultati = $query->fetchAll(PDO::FETCH_ASSOC);
    $richiesta_db = null;

    return $risultati;
}

function lettura_studenti(): array
{
    $query = "SELECT matricola, nome, cognome FROM studenti ORDER BY cognome ASC, nome ASC";
    $richiesta_db = Collegamento();
    $query = $richiesta_db->prepare($query);
    $query->execute();
    $risultati = $query->fetchAll(PDO::FETCH_ASSOC);
    $richiesta_db = null;

    return $risultati;
}


require_once __DIR__ . "/../materiali/libreria_debug.php";

function lettura_corsi($mostra_non_attivi = 0, ?string $filtro = ''): array
{
    // Aggiunto il campo 'durata' alla SELECT
    $query = "SELECT corsi.`ID_corso`, corsi.`nome_corso`, corsi.`cod_edizione`, durata, attivo FROM corsi";

    if (!empty($filtro)) {
        $query .= "
        INNER JOIN studenti_corsi ON corsi.`ID_corso` = studenti_corsi.`ID_corso`

        WHERE studenti_corsi.`matricola` = ?";    
        }
	if (!$mostra_non_attivi && !empty($filtro)) {
	        $query .= " AND attivo = 1";
	    } else if (!$mostra_non_attivi){
            $query .= " WHERE attivo = 1";
        }


    $query .= " ORDER BY nome_corso ASC ";

        console_log("query corsi: " .$query);
        console_log("valori query corsi: " . $filtro);


    $richiesta_db = Collegamento();
    $query = $richiesta_db->prepare($query);

    if (!empty($filtro)) {
         $query->execute([$filtro]); 
         
    } else { $query->execute(); };

    $risultati = $query->fetchAll(PDO::FETCH_ASSOC);
    $richiesta_db = null;

    return $risultati;
}

function lettura_materie(?string $filtro = '', ?string $corso = '' ): array
{
    // Aggiunto il campo 'durata_materia' alla SELECT
    $query = "SELECT materie.`cod_materia`, materie.`nome_materia`, materie.`durata_materia` FROM materie
        INNER JOIN corsi_materie ON materie.`cod_materia` = corsi_materie.`cod_materia`
        INNER join studenti_corsi ON corsi_materie.`ID_corso` = studenti_corsi.`ID_corso`
    ";

    if(!empty($filtro)){
        $query .= "
        WHERE studenti_corsi.`matricola` = ?";
    }
    if(!empty($corso) && !empty($filtro)){
        $query .= " AND  studenti_corsi.`ID_corso` = ?";
    }
    if (!empty($corso)) {
        $query .= " WHERE  studenti_corsi.`ID_corso` = ?";
    }
    $query .= " GROUP BY materie.`nome_materia` ORDER BY nome_materia ASC";

        console_log("query materie: " . $query);


    $richiesta_db = Collegamento();

    
    $query = $richiesta_db->prepare($query);
    $filtri = [];

    if (!empty($filtro)) {
        $filtri = [$filtro];

    } else if (!empty($corso) && !empty($filtro)) {
        $filtri = [$filtro, $corso];

    } else if (!empty($corso)) {
        $filtri = [$corso];
        
    }

    $query->execute($filtri);

    $risultati = $query->fetchAll(PDO::FETCH_ASSOC);
	$richiesta_db = null;

    return $risultati;
}

function lettura_corsi_studente(string $studente): array
{
    $query =  "
        SELECT DISTINCT studenti_corsi.`ID_corso`,  corsi.`nome_corso`, corsi.`durata`  
        FROM studenti_corsi 
        LEFT JOIN corsi on corsi.`ID_corso` = studenti_corsi.`ID_corso`
        where studenti_corsi.`matricola` = ?
		ORDER BY `corsi`.`nome_corso` ASC
    ";

    $richiesta_db = Collegamento();
    $query = $richiesta_db->prepare($query);
    $query->execute([$studente]);
    $risultati = $query->fetchAll(PDO::FETCH_ASSOC);
    $richiesta_db = null;

    return $risultati;
}

function lettura_materie_corso(array $id_corsi): array
{
    $appoggio = [];
    foreach ($id_corsi as $id) {
        $appoggio[] = "`corsi_materie`.`ID_corso` = ?";
    }

    $query = "
        SELECT `materie`.`cod_materia`, `materie`.`nome_materia`, `materie`.`durata_materia`
        FROM `corsi_materie`
        JOIN `materie` ON `materie`.`cod_materia` = `corsi_materie`.`cod_materia`
        WHERE
    ";
    $query .= implode(" OR ", $appoggio);

    $query .= "
        ORDER BY `materie`.`nome_materia` ASC
    ";


    $richiesta_db = Collegamento();
    $stmt = $richiesta_db->prepare($query);
    $stmt->execute($id_corsi);
    $risultati = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $richiesta_db = null;

    return $risultati;
}


function lettura_lezioni_corso(array $id_corsi): array
{
    $appoggio = [];
    foreach($id_corsi as $id){
        $appoggio[] = "`lezioni` . `ID_corso` = ?";
    }

    $query = "SELECT `lezioni`.`ID_lezione`, `lezioni`.`TS_inizio`, `lezioni`.`TS_fine`, `materie`.`nome_materia`, `lezioni`.`cod_aula`
              FROM `lezioni`
              LEFT JOIN `materie` ON `materie`.`cod_materia` = `lezioni`.`cod_materia`
              WHERE 
              ";
    $query .= implode(" OR ", $appoggio);
    
    $query .= " ORDER BY `lezioni`.`TS_inizio` ASC";


    $richiesta_db = Collegamento();
    $stmt = $richiesta_db->prepare($query);
    $stmt->execute($id_corsi);
    $risultati = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $richiesta_db = null;

    return $risultati;
}