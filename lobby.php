<?php
session_start();
require_once "includes/config.php";
require_once 'classes/TableManager.php';
require_once "includes/systemLog.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$tm = new TableManager();

// AGGIORNA CREDITI DAL DATABASE
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT credits FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    $_SESSION['credits'] = $user['credits'];
}

if (isset($_POST['create_btn'])) {
    $newTableId = $tm->createTable($_SESSION['user_id']);
    header("Location: tavolo.php?table_id=" . $newTableId);
    exit();
}

if (isset($_GET['join_id'])) {
    $tableId = $_GET['join_id'];
    $tm->joinTable($tableId, $_SESSION['user_id']);
    header("Location: tavolo.php?table_id=" . $tableId);
    exit();
}

$tavoli = $tm->getOpenTables();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/x-icon" href="assets/img/icona/Icona.ico">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Lobby Blackjack</title>
</head>
<body>
    <div class="login-container" style="max-width: 600px;">
        <h1>Lobby di Gioco</h1>
        <p>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        <p>Crediti: <?php echo $_SESSION['credits']; ?> €</p>

        <form method="POST">
            <button type="submit" name="create_btn" class="btn-hit">Crea Nuovo Tavolo</button>
        </form>

        <hr style="margin: 20px 0; border-color: #ffc107;">

        <h3>Tavoli Aperti:</h3>
        
        <?php if (count($tavoli) > 0): ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($tavoli as $tavolo): ?>
                    <li style="background: rgba(255,255,255,0.1); margin: 10px 0; padding: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <span>
                            Tavolo #<?php echo $tavolo['id']; ?> 
                            (Giocatori: <?php echo $tavolo['num_players']; ?>)
                        </span>
                        
                        <?php if ($tavolo['status'] == 'waiting' || $tavolo['status'] == 'betting'): ?>
                            <a href="lobby.php?join_id=<?php echo $tavolo['id']; ?>" 
                               style="background: gold; padding: 5px 10px; border-radius: 5px; color: black; font-weight: bold;">
                               ENTRA
                            </a>
                        <?php else: ?>
                            <span style="color: red;">IN GIOCO</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Nessun tavolo aperto. Creane uno tu!</p>
        <?php endif; ?>

        <br>
        <a href="logout.php" style="color: red;">Logout</a>
    </div>
</body>
</html>