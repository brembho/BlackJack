<?php

session_start();

require_once "includes/config.php";
require_once "classes/Database.php";

// Controllo login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Controllo ID tavolo
if (!isset($_GET['table_id'])) {
    header("Location: lobby.php");
    exit;
}

$tableId = (int)$_GET['table_id'];
$userId  = $_SESSION['user_id'];

// Connessione al DB
$db = new Database();
$conn = $db->getConnection();


// Verifica esistenza tavolo
$stmt = $conn->prepare("SELECT id FROM game_tables WHERE id = ?");
$stmt->execute([$tableId]);

if (!$stmt->fetch()) {
    die("Tavolo non esistente");
}
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavolo #<?php echo $tableId; ?> - Blackjack</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="user-bar">
        <div class="user-info">
            Tavolo #<?php echo $tableId; ?>
        </div>
        <div>
            <a href="lobby.php" class="btn-logout" style="background:transparent; border:1px solid gold; color:gold;">Torna alla Lobby</a>
        </div>
    </div>

    <div class="game-table">
        
        <div class="dealer-area">
            <h3 style="color: #ddd; margin-bottom: 10px;">BANCO</h3>
            <div id="dealer-cards" class="hand"></div>
            <div id="dealer-score" class="score-badge">Punti: ?</div>
        </div>
        <div id="players-area" class="players-grid">
            <p style="color: white;">Caricamento giocatori...</p>
        </div>

        <div id="action-bar" class="actions-bar" style="display: none;">
            <div style="text-align: center; width: 100%;">
                <p style="color: gold; font-weight: bold; margin-bottom: 10px;">È IL TUO TURNO!</p>
                <button onclick="doAction('hit')" class="btn-hit">CARTA (+)</button>
                <button onclick="doAction('stand')" class="btn-stand">STO (-)</button>
            </div>
        </div>

    </div>

    <script>
        const currentTableId = <?php echo $tableId; ?>;
        const myUserId = <?php echo $userId; ?>;
    </script>
    
    <script src="assets/js/game.js"></script>

    <script>
        let o = document.querySelector("#dealer-cards");
        renderCards(o,["spades_A","spades_Q"]);
    </script>

</body>
</html>
