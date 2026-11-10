<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgenForm - Index</title>
    <link rel="stylesheet" href="agenform.css">
    <link rel="stylesheet" href="home.css">

 <style>
      *{
      margin:0;
      padding:0;
      font-family: 'Courier New', Courier, monospace;
      }

      body{
      background:#8b8b8b;
      }

     /* ================= HEADER ================= */

     .header{
       background:white;
        height:75px;
      display:flex;
      align-items:center;
      padding:0 60px;
      border-bottom:4px solid #9c1d3d;
     }

        .logoHeader{
        width:180px;
     }

      /* ================= HOMEPAGE ================= */

      .contenitoreHomepage{
       display:flex;
       justify-content:center;
       align-items:center;
       height:calc(100vh - 75px);
       }

       .riquadroHomepage{
       background:white;
        width:600px;
       padding:60px;
      border-radius:20px;
      box-shadow:0 15px 35px rgba(0,0,0,.25);

      display:flex;
      flex-direction:column;
      align-items:center;
     }

     .logoCentrale{
      width:400px;
       margin-bottom:8px;
      }

      .titoloHomepage{
       color:#9c1d3d;
      font-size:52px;
      margin-bottom:15px;
      }

     .sottotitoloHomepage{
      font-size:22px;
      margin-bottom:44px;
     }

       .contenitorePulsanti{
       display:flex;
       gap:30px;
      }

      .pulsanteHomepage{
      text-decoration:none;
      background:#9c1d3d;
      color:white;
      padding:15px 45px;
      border-radius:10px;
      font-size:22px;
     font-weight:bold;
     transition:0.3s;
     }

      .pulsanteHomepage:hover{
      background:#7d1731;
      transform:translateY(-3px);
      box-shadow:0 8px 18px rgba(0,0,0,.25);
      }

    </style>
</head>

<body>

<!-- CONTENUTO PRINCIPALE -->
    <main class="contenitoreHomepage">

        <div class="riquadroHomepage">

            <img src="./materiali/Logo_Alt.png" alt="Logo AgenForm" class="logoCentrale">

            <h1 class="titoloHomepage">
                Benvenuto
            </h1>

            <p class="sottotitoloHomepage">
                Gestione dei corsi e delle presenze <br>
                per continuare effetuare l'accesso
            </p>

            <div class="contenitorePulsanti">

                <a href="login.php" class="pulsanteHomepage">
                    Login
                </a>

                <a href="pre_registrazione.php" class="pulsanteHomepage">
                    Registrati
                </a>

            </div>

        </div>

    </main>

</body>
</html>
