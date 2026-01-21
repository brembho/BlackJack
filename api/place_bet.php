<?php
// api/place_bet.php
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
    // 1. Controlla lo stato del tavolo
    $stmt = $conn->prepare("SELECT status, shoe FROM game_tables WHERE id = :id");
    $stmt->execute([':id' => $tableId]);
    $table = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se stiamo già giocando, blocca tutto!
    if ($table['status'] === 'playing') {
        echo json_encode(['success' => false, 'error' => 'Partita già in corso.']);
        exit;
    }

    // 2. Recupera e Gestisci il Sabot
    $shoe = json_decode($table['shoe']);
    if (!is_array($shoe) || count($shoe) < 15) {
        // Se il mazzo è finito o corrotto, ne creiamo uno nuovo
        $d = new Deck();
        $shoe = $d->generateShoe();
    }

    // 3. PESCA LE CARTE (ESATTAMENTE 2 A TESTA)
    $card1 = array_pop($shoe);
    $card2 = array_pop($shoe);
    $playerHand = [$card1, $card2]; 

    $dealerCard1 = array_pop($shoe);
    $dealerCard2 = array_pop($shoe);
    $dealerHand = [$dealerCard1, $dealerCard2]; 

    // 4. Aggiorna Giocatore
    $updPlayer = $conn->prepare("UPDATE game_players SET bet = :b, hand = :h, status = 'playing' WHERE table_id = :tid AND user_id = :uid");
    $updPlayer->execute([
        ':b' => $amount,
        ':h' => json_encode($playerHand),
        ':tid' => $tableId, 
        ':uid' => $userId
    ]);

    // 5. Aggiorna Tavolo
    $updTable = $conn->prepare("UPDATE game_tables SET status = 'playing', dealer_hand = :dh, shoe = :shoe, turn_player_id = :uid WHERE id = :tid");
    $updTable->execute([
        ':dh' => json_encode($dealerHand),
        ':shoe' => json_encode($shoe),
        ':uid' => $userId, 
        ':tid' => $tableId
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>