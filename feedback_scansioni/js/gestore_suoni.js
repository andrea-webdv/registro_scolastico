const PERCORSI_AUDIO = {
    // Nomi usati nelle pagine di successo
    'checkin_riuscito': 'suoni/checkin_riuscito.mp3',
    'checkout_riuscito': 'suoni/checkout_riuscito.mp3',
    'checkin_checkout': 'suoni/checkin_checkout.mp3', // o il file dedicato

    // Nomi usati nelle pagine di errore
    'checkin': 'suoni/checkin.mp3',
    'checkout': 'suoni/checkout.mp3',
    'errore': 'suoni/errore.mp3'
};

/**
 * Riproduce un file audio MP3/WAV
 * @param {string} tipo
 */
function riproduciSuono(tipo) {
    if (!PERCORSI_AUDIO[tipo]) {
        console.warn("Chiave audio non trovata:", tipo);
        return;
    }

    const audio = new Audio(PERCORSI_AUDIO[tipo]);
    audio.play().catch(error => {
        console.warn("Riproduzione audio bloccata o fallita:", error);
    });
}