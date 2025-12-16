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
$db = Database::getInstance()->getConnection();

// Verifica esistenza tavolo
$stmt = $db->prepare("SELECT id FROM game_tables WHERE id = ?");
$stmt->execute([$tableId]);

if (!$stmt->fetch()) {
    die("Tavolo non esistente");
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Tavolo #<?= $tableId ?> - Blackjack</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Barra superiore -->
<div class="user-bar">
    <div>Tavolo #<?= $tableId ?></div>
    <a href="lobby.php">Lobby</a>
</div>

<!-- Tavolo da gioco -->
<div class="game-table">

    <!-- Banco -->
    <div class="dealer-area">
        <h3>BANCO</h3>
        <div id="dealer-cards" class="hand"></div>
        <div id="dealer-score">Punti: ?</div>
    </div>

    <hr>

    <!-- Giocatori (riempiti da JS) -->
    <div id="players-area" class="players-grid"></div>

    <!-- Azioni -->
    <div id="action-bar" style="display:none;">
        <button onclick="doAction('hit')">CARTA</button>
        <button onclick="doAction('stand')">STO</button>
    </div>

</div>

<script>
    // Variabili usate da game.js
    const currentTableId = <?= $tableId ?>;
    const myUserId = <?= $userId ?>;
</script>

<script src="assets/js/game.js"></script>

</body>
</html>
