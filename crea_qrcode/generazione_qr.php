
<?php
    function generazione_qr($matricola_std, $cognome_std, $nome_std){
        // Formatta la stringa per farla combaciare con la lettura del nostro scanner
        $qrData = $matricola_std . ";" . $cognome_std . ";" . $nome_std;
        
        // Codifica il testo per l'URL dell'API
        $urlEncodedData = urlencode($qrData);
        
        // Crea l'URL dell'API per la generazione del QR Code
        return ["url" => "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . $urlEncodedData, "data" => $qrData] ;
    }
?>