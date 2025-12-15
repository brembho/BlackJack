<?php
require_once "includes/config.php";
require_once "classes/user.php";
require_once "includes/systemLog.php";

$user = new user();
$message = "";
if (isset($_POST["username"]) && isset($_POST["password"])) {


    if($user->Autenticazione(trim($_POST['username']), trim($_POST['password']))){
        header("Location: lobby.php");
    }
    else{
        $message ="Credenzili errate o inesistenti";
    }



}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Document</title>
</head>
<body>

  <div class="login-container">
        <h1>Login Blackjack</h1>

        <?php if ($message): ?>
            <p style="color: red; font-weight: bold;"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label>Username:</label><br>
            <input type="text" name="username" required><br><br>

            <label>Password:</label><br>
            <input type="password" name="password" required><br><br>

            <button type="submit">Avanti</button>
        </form>
        
        <p>Non hai un account? <a href="register.php">Regustrati qui</a></p>
    </div>
    
</body>
</html>