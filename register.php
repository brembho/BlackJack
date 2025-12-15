<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/config.php";
require_once "classes/user.php";

$message = "";

if (isset($_POST["username"]) && isset($_POST["password"])) {
    $user = new User();
    

    if($user->Autenticazione(trim($_POST['username']), trim($_POST['password']))){
        header("Location: lobby.php");
        exit(); 
    }
    else{
        $message = "Credenziali errate o inesistenti.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Blackjack</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

  <div class="login-container">
        <div style="font-size: 3rem; margin-bottom: 10px;">♠️</div>
        <h1>Blackjack Login</h1>

        <?php if ($message): ?>
            <div style="background: rgba(211, 47, 47, 0.8); color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="text-align: left; margin-bottom: 5px; color: #ffc107; font-weight: bold;">Username</div>
            <input type="text" name="username" placeholder="Inserisci il tuo nome" required>

            <div style="text-align: left; margin-bottom: 5px; color: #ffc107; font-weight: bold;">Password</div>
            <input type="password" name="password" placeholder="Inserisci la password" required>

            <button type="submit">ENTRA AL TAVOLO</button>
        </form>
        
        <p style="margin-top: 20px;">
            Non hai ancora un account?<br>
            <a href="register.php" style="font-weight: bold; text-decoration: underline;">Registrati qui</a>
        </p>
    </div>
    
</body>
</html>