<?php
require_once "includes/config.php";
require_once "classes/Database.php"; 

$message = "";

if (isset($_POST["username"]) && isset($_POST["password"])) {
    
    $db = new Database();
    $conn = $db->getConnection();

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

   
    $checkSql = "SELECT id FROM users WHERE username = :username";
    $stmt = $conn->prepare($checkSql);
    $stmt->execute([':username' => $username]);
    
    if ($stmt->fetch()) {
        $message = "Username già in uso. Scegline un altro.";
    } else {

        $passHash = password_hash($password, PASSWORD_DEFAULT);

   
        $sql = "INSERT INTO users (username, password) VALUES (:user, :pass)";
        
        try {
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([':user' => $username, ':pass' => $passHash])) {
                
                header("Location: login.php");
                exit();
            } else {
                $message = "Errore nel salvataggio.";
            }
        } catch (PDOException $e) {
            $message = "Errore Database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blackjack - Registrazione</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h1>Registrazione Blackjack</h1>

        <?php if ($message): ?>
            <p style="color: red; font-weight: bold;"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label>Username:</label><br>
            <input type="text" name="username" required><br><br>

            <label>Password:</label><br>
            <input type="password" name="password" required><br><br>

            <button type="submit">Registrati</button>
        </form>
        
        <p>Hai già un account? <a href="login.php">Accedi qui</a></p>
    </div>
</body>
</html>