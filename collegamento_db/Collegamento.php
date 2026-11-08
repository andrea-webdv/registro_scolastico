<?php
$rootPath = dirname(__DIR__);
require_once $rootPath . "/vendor/autoload.php";
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function Exception_handler(mixed $ex)
{
    // Stampiamo i dettagli dell'errore in modo leggibile
    echo "<h2>Si è verificato un errore SQL:</h2>";
    echo "<p><strong>Messaggio:</strong> " . $ex->getMessage() . "</p>";
    echo "<p><strong>Codice Errore SQL:</strong> " . $ex->getCode() . "</p>";
    echo "<p><strong>File:</strong> " . $ex->getFile() . " alla linea " . $ex->getLine() . "</p>";
}


function Collegamento()
{
    try {
        $dsn = "mysql:host=sql.freedb.tech;port=3306;dbname=freedb_36z7taS3;charset=utf8mb4";
        $user = $_ENV['FREEDB_USER'];
        $pw = $_ENV['FREEDB_PASS'];
        $conn = new PDO($dsn, $user, $pw, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
        echo "Connected successfully!";
    } catch (PDOException $ex) {
        Exception_handler ($ex);
        die();
    }
    
    return $conn;
    
    $dsn = "mysql:host=sql.freedb.tech;port=3306;dbname=freedb_36z7taS3;charset=utf8mb4";
    $pdo = new PDO($dsn, "u_M77p9Q", "reQdiIo3DkJs", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Connected successfully!";
}
?>