<?php
error_reporting(0); 
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Deck.php';

if (!isset($_POST['table_id']) || !isset($_POST['amount'])) {
    echo json_encode(['error' => 'Dati mancanti']);
    exit;
}

$tableId = $_POST['table_id'];
$amount = intval($_POST['amount']);
$userId = $_SESSION['user_id'];

$db = new Database();
$conn = $db->getConnection();

try {
    // 1. Controlla stato tavolo
    $stmt = $conn->prepare("SELECT status, shoe FROM game_tables WHERE id = :id");
    $stmt->execute([':id' => $tableId]);
    $table = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($table['status'] === 'playing') {
        echo json_encode(['success' => false, 'error' => 'Partita già in corso. Aspetta la fine.']);
        exit;
    }

    // 2. Prepara il Sabot
    $shoe = json_decode($table['shoe']);
    if (count($shoe) < 15) { // Se carte finite, rimescola
        $d = new Deck();
        $shoe = $d->generateShoe();
    }

    // 3. Pesca le carte (2 a te, 2 al banco)
    $playerHand = [array_pop($shoe), array_pop($shoe)];
    $dealerHand = [array_pop($shoe), array_pop($shoe)];

    // 4. Aggiorna Giocatore (Bet + Carte)
    $updPlayer = $conn->prepare("UPDATE game_players SET bet = :b, hand = :h, status = 'playing' WHERE table_id = :tid AND user_id = :uid");
    $updPlayer->execute([
        ':b' => $amount,
        ':h' => json_encode($playerHand),
        ':tid' => $tableId, ':uid' => $userId
    ]);

    // 5. Aggiorna Tavolo (Stato Playing + Carte Banco)
    $updTable = $conn->prepare("UPDATE game_tables SET status = 'playing', dealer_hand = :dh, shoe = :shoe, turn_player_id = :uid WHERE id = :tid");
    $updTable->execute([
        ':dh' => json_encode($dealerHand),
        ':shoe' => json_encode($shoe),
        ':uid' => $userId, ':tid' => $tableId
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>