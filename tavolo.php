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
    <link rel="icon" type="image/x-icon" href="assets/img/icona/Icona.ico">
    <title>BlackJack #<?php echo $tableId; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="game-header">
        
        <div class="header-left">
            <span>Tavolo #<?php echo $tableId; ?></span>
        </div>

        <div class="header-center">
            <div class="saldo-box">
                SALDO: €<span id="user-credits-display">...</span>
            </div>
        </div>

        <div class="header-right">
            <a href="lobby.php" class="btn-lobby">ESCI</a>
        </div>

    </div>

    <div class="game-area">
        <div id="dealer-area" class="dealer-area">
            <h3>BANCO</h3>
            <div id="dealer-score" style="color:yellow; margin-bottom:10px;"></div>
            <div id="dealer-cards" class="hand"></div>
        </div>

        <div id="game-message" style="color:gold; font-size:1.2rem; display:flex; justify-content: center; align-items:center; margin: 20px 0; height: 30px;"></div>

        <div id="betting-area" style="display:none;" class="betting-panel">
            <div id="casellaPuntata" style="display:inline">
                <h2 style="color:gold; margin-bottom:15px;">PUNTA</h2>
                <input type="number" id="bet-amount" value="10" min="1">
            </div>
            <button id="btn-place-bet" class="btn-place-bet">GIOCA</button>
            <div id="wait-message" style="display:none; color:yellow; font-size:1rem; margin-top:10px;">In attesa...</div>
        </div>

        <div id="players-area" class="players-grid"></div>

        <div id="action-bar" class="actions-bar" style="display:none;">
            <button class="btn-hit">CARTA</button>
            <button class="btn-stand">STO</button>
        </div>

        <div id="restart-timer-container" style="display:none; justify-content: center; align-items:center; margin: 10px 0;">
            <span style="color: white; display:flex; justify-content: center; align-items:center; font-size: 0.9rem;">Prossimo turno in: </span>
            <span id="timer-seconds" style="color: #ff4757; margin-top: 5px; display:flex; justify-content: center; align-items:center; font-size: 1.5rem; font-weight: bold;">5</span>
        </div>
    </div>

    <script>
        const currentTableId = <?php echo $tableId; ?>;
        const myUserId = <?php echo $userId; ?>;
    </script>
    <script src="assets/js/game.js"></script>
</body>
</html>