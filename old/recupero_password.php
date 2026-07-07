<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenform Registra - Recupero Password</title>
    <link rel="stylesheet" href="./css/agenform.css">
    <link rel="stylesheet" href="./css/recupero_password.css">
</head>
<body>

    <header class="barra-superiore">
        <div class="spazio-logo">
                <img src="./materiali/Logo_AgenForm_2.png" alt="Logo AgenForm" class="immagine-logo">

        </div>
        
        <nav class="gruppo-bottoni">
            <a href="home.php" class="bottone-menu">Homepage</a>
            <a href="login.php" class="bottone-menu">Login</a>
            <a href="pre_registrazione.php" class="bottone-menu">Registrazione</a>
        </nav>
    </header>

    <main class="zona-centrale">
        <div class="scatola-recupero">
            
            <h1>Recupero Password</h1>
            <p class="sottotitolo">Inserisci la tua email o il tuo numero di telefono per ricevere le istruzioni di ripristino</p>
            
            <form action="" method="post">
                
                <label class="scritta-istruzione">Indirizzo Email o Numero di Telefono</label>
                <input type="text" name="contatto_recupero" class="campo-input" placeholder="E-mail o numero di telefono" required>
                
                <button type="submit" class="bottone-rosso">Invia Richiesta</button>
            </form>
            
            <a href="login.php" class="link-ritorno">Torna al Login</a>

        </div>
    </main>

</body>
</html>