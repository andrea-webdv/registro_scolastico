<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;

class Generatore_qr 
{
    public function __construct(){
    }

    public function generaQRCodeEndroid($nome, $cognome, $matricola) {
        $matricolaPulita = trim($matricola);
        if (strlen($matricolaPulita) !== 8) {
            throw new Exception("Errore: La matricola deve essere composta esattamente da 8 caratteri.");
        }

        // Formattazione del testo interno al QR (Ordine Aggiornato)
        $testoQR = "Matricola: " . $matricolaPulita . "\n";
        $testoQR .= "Nome: " . trim($nome) . "\n";
        $testoQR .= "Cognome: " . trim($cognome);

        // Configurazione del QR Code direttamente nel costruttore (Parametri Nominati)
        $qrCode = new QrCode(
            data: $testoQR,
            encoding: new Encoding('UTF-8'),
            size: 220,
            margin: 10
        );

        $writer = new PngWriter();
        $risultato = $writer->write($qrCode);

        // Restituisce la stringa Base64 dell'immagine
        return base64_encode($risultato->getString());
    }   

    #   genera matricole casuali
    public function generaMatricolaCasuale() {
        $caratteri = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $matricola = '';
        for ($i = 0; $i < 8; $i++) {
            $matricola .= $caratteri[rand(0, strlen($caratteri) - 1)];
        }
        return $matricola;
    }
}
?>