<?php
session_start(); // Avvia la sessione per memorizzare l'utente

require_once __DIR__ . "/../collegamento_db/Collegamento.php";

$pdo = Collegamento();

// 2. Controllo dei dati inviati dal form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricola'])) {
    $matricola = trim($_POST['matricola']);

    // Prepariamo la query sulla tabella `studenti`
    $stmt = $pdo->prepare("SELECT matricola, nome, cognome FROM studenti WHERE matricola = ?");
    $stmt->execute([$matricola]);
    $studente = $stmt->fetch();

    if(isset($_SESSION['staff_codice'])) {
        header("location: ../index.php?e=loggato");
        exit();
    }

    if ($studente) {
        // MATRICOLA TROVATA: Salviamo i dati in sessione
        $_SESSION['studente_matricola'] = $studente['matricola'];
        $_SESSION['studente_nome'] = $studente['nome'] . " " . $studente['cognome'];
        
        // CAMBIATO QUI: Reindirizziamo alla dashboard dello studente
        header("Location: ../dashboard_studente.php");
        exit();
    } else {
        // MATRICOLA NON TROVATA
        echo "<script>alert('Matricola non presente nel database!'); window.location.href='index.php';</script>";
        exit();
    }
}
?>