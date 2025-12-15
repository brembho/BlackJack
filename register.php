<?php
// 1. Includiamo i file fondamentali
// config.php avvia la sessione in modo sicuro (come abbiamo corretto prima)
require_once "includes/config.php";
require_once "classes/Database.php";
require_once "includes/systemLog.php"; // Per scrivere nel file di log

$message = "";
$error = false;

// 2. Se l'utente ha premuto "REGISTRATI"
if (isset($_POST['register_btn'])) {

    // Puliamo l'input (trim toglie spazi vuoti prima e dopo)
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Controllo campi vuoti (sicurezza extra oltre all'HTML)
    if (empty($username) || empty($password)) {
        $message = "Compila tutti i campi!";
        $error = true;
    } else {
        // Connessione al DB
        $dbClass = new Database();
        $conn = $dbClass->getConnection();

        // A. CONTROLLO SE L'UTENTE ESISTE GIÀ
        $checkSql = "SELECT id FROM users WHERE username = :user";
        $stmt = $conn->prepare($checkSql);
        $stmt->execute([':user' => $username]);

        if ($stmt->fetch()) {
            $message = "Username già occupato. Scegline un altro.";
            $error = true;
        } else {
            // B. CREAZIONE NUOVO UTENTE
            
            // Hash della password (sicurezza fondamentale)
            $passHash = password_hash($password, PASSWORD_DEFAULT);

            // Inseriamo l'utente con 1000 crediti iniziali
            $insertSql = "INSERT INTO users (username, password, credits) VALUES (:user, :pass, 1000)";
            
            try {
                $stmtInsert = $conn->prepare($insertSql);
                $stmtInsert->execute([':user' => $username, ':pass' => $passHash]);

                // C. SCRIVIAMO IL LOG (Requisito File)
                systemLog("Nuovo utente registrato: $username");

                // D. REINDIRIZZAMENTO
                // Mandiamo l'utente al login con un messaggio di successo (opzionale) o diretto
                header("Location: index.php"); 
                exit();

            } catch (PDOException $e) {
                $message = "Errore Database: " . $e->getMessage();
                $error = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - Blackjack</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="login-container">
        <div style="font-size: 3rem; margin-bottom: 10px;">♦️</div>
        
        <h1>Crea Account</h1>

        <?php if ($message): ?>
            <div style="background: <?php echo $error ? 'rgba(211, 47, 47, 0.8)' : 'green'; ?>; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="text-align: left; margin-bottom: 5px; color: #ffc107; font-weight: bold;">Scegli Username</div>
            <input type="text" name="username" placeholder="Es. MarioRossi" required autocomplete="off">

            <div style="text-align: left; margin-bottom: 5px; color: #ffc107; font-weight: bold;">Scegli Password</div>
            <input type="password" name="password" placeholder="Minimo 4 caratteri" required>

            <button type="submit" name="register_btn">REGISTRATI ORA</button>
        </form>
        
        <p style="margin-top: 20px;">
            Hai già un account?<br>
            <a href="login.php" style="font-weight: bold; text-decoration: underline;">Torna al Login</a>
        </p>
    </div>

</body>
</html>