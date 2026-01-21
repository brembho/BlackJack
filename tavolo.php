<?php
require_once 'includes/config.php';
require_once 'classes/Database.php';

if (!isset($_GET['table_id']) || !isset($_SESSION['user_id'])) {
    header('Location: lobby.php'); exit;
}
$tableId = $_GET['table_id'];
$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>BlackJack #<?php echo $tableId; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="game-header">
        <span>Tavolo #<?php echo $tableId; ?></span>
        <a href="lobby.php" class="btn-lobby">ESCI</a>
    </div>

    <div class="game-area">
        <div id="dealer-area" class="dealer-area">
            <h3>BANCO</h3>
            <div id="dealer-score" style="color:yellow; margin-bottom:10px;"></div>
            <div id="dealer-cards" class="hand"></div>
        </div>

        <div id="game-message" style="color:gold; font-size:1.2rem; margin: 20px 0; height: 30px;"></div>

        <div id="betting-area" style="display:none; background:rgba(0,0,0,0.9); padding:20px; border:2px solid gold; z-index:100; position:absolute;">
            <h2 style="color:gold; margin-bottom:15px;">PUNTA</h2>
            <input type="number" id="bet-amount" value="10" min="1" max="500">
            <button id="btn-place-bet" class="btn-place-bet" style="background:gold; color:black;">GIOCA</button>
            <div id="wait-message" style="display:none; color:yellow; font-size:0.7rem; margin-top:10px;">In attesa...</div>
        </div>

        <div id="players-area" class="players-grid"></div>

        <div id="action-bar" class="actions-bar" style="display:none;">
            <p style="color:gold; width:100%; text-align:center; margin-bottom:10px;">TOCCA A TE!</p>
            <button class="btn-hit">CARTA</button>
            <button class="btn-stand">STO</button>
        </div>
    </div>

    <script>
        const currentTableId = <?php echo $tableId; ?>;
        const myUserId = <?php echo $userId; ?>;
    </script>
    <script src="assets/js/game.js"></script>
</body>
</html>