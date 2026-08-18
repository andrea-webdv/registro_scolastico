<?php
session_start(); // Avvia la sessione per memorizzare lo staff

require_once __DIR__ . "/../collegamento_db/Collegamento.php";

$pdo = Collegamento();

// 2. Controllo dei dati inviati dal form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codice_staff'])) {
    $codice_staff = trim($_POST['codice_staff']);

    // Prepariamo la query sulla tabella `staff`
    $stmt = $pdo->prepare("SELECT codice_staff, nome, cognome FROM staff WHERE codice_staff = ?");
    $stmt->execute([$codice_staff]);
    $staff = $stmt->fetch();


    if(isset($_SESSION['studente_matricola'])) {
        header("location: ../index.php?e=loggato");
        exit();
    }

    if ($staff) {
        // CODICE STAFF TROVATO: Salviamo i dati in sessione
        $_SESSION['staff_codice'] = $staff['codice_staff'];
        $_SESSION['staff_nome'] = $staff['nome'] . " " . $staff['cognome'];
        
        // Reindirizziamo l'utente alla dashboard dello staff
        header("Location: ../dashboard_staff.php");
        exit();
    } else {
        // CODICE STAFF NON TROVATO
        echo "<script>alert('Codice staff non presente nel database!'); window.location.href='index.php';</script>";
        exit();
    }
}
?>