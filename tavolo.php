<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$tableId = isset($_GET['table_id']) ? intval($_GET['table_id']) : 1;

require_once 'classes/Database.php';

// Recupero i giocatori seduti al tavolo
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT users.id, users.username FROM game_players 
                          JOIN users ON game_players.user_id = users.id
                          WHERE game_players.table_id = ?");
    $stmt->execute([$tableId]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Errore DB: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blackjack Multiplayer - Tavolo <?php echo $tableId; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="table-container">

    <header>
        <h1>Blackjack Multiplayer</h1>
        <p>Giocatore: <?php echo $_SESSION['username'] ?? $userId; ?></p>
        <p>Tavolo ID: <?php echo $tableId; ?></p>
    </header>

    <!-- Campo nascosto con ID tavolo e ID giocatore -->
    <input type="hidden" id="table-id" value="<?php echo $tableId; ?>">
    <input type="hidden" id="player-id" value="<?php echo $userId; ?>">

    <!-- Sezione Dealer -->
    <section class="dealer-section">
        <h2>Dealer</h2>
        <div id="dealer-cards" class="cards-row"></div>
        <div id="dealer-score" class="score"></div>
    </section>

    <!-- Sezione Giocatori -->
    <section class="players-section">
        <?php foreach ($players as $player): ?>
            <div class="player" id="player-<?php echo $player['id']; ?>">
                <h3><?php echo htmlspecialchars($player['username']); ?></h3>
                <div class="cards-row" id="player-cards-<?php echo $player['id']; ?>"></div>
                <div class="score" id="player-score-<?php echo $player['id']; ?>"></div>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Status della partita -->
    <div id="game-status" class="game-status"></div>

    <!-- Controlli del giocatore -->
    <div class="controls">
        <button id="hit-btn">Carta</button>
        <button id="stand-btn">Sto</button>
    </div>

</div>

<script src="assets/js/game.js"></script>
</body>
</html>
