<?php
session_start();
require_once 'classes/TableManager.php';
require_once "includes/system_logger.php";

// Se non sei loggato, torna al login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$tm = new TableManager();

// LOGICA: Se premo "Crea Tavolo"
if (isset($_POST['create_btn'])) {
    $newTableId = $tm->createTable($_SESSION['user_id']);
    // Vai subito al tavolo
    header("Location: tavolo.php?table_id=" . $newTableId);
    exit();
}

// LOGICA: Se premo "Unisciti" su un tavolo esistente
if (isset($_GET['join_id'])) {
    $tableId = $_GET['join_id'];
    $tm->joinTable($tableId, $_SESSION['user_id']);
    header("Location: tavolo.php?table_id=" . $tableId);
    exit();
}

// Recupera la lista dei tavoli per disegnarli
$tavoli = $tm->getOpenTables();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Lobby Blackjack</title>
</head>
<body>
    <div class="login-container" style="max-width: 600px;">
        <h1>Lobby di Gioco</h1>
        <p>Benvenuto, <?php echo $_SESSION['username']; ?>!</p>
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
                        
                        <a href="lobby.php?join_id=<?php echo $tavolo['id']; ?>" 
                           style="background: gold; padding: 5px 10px; border-radius: 5px; color: black; font-weight: bold;">
                           ENTRA
                        </a>
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