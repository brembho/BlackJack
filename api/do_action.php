<?php

session_start();
header('Content-Type: application/json');

require_once '../classes/Database.php';
require_once '../classes/Deck.php';
require_once '../classes/GameRules.php';

// Controllo login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non sei loggato']);
    exit;
}

// Ricevo i dati JSON dal frontend
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['table_id'], $data['action'])) {
    echo json_encode(['error' => 'Dati mancanti']);
    exit;
}

$tableId = (int)$data['table_id'];
$action = strtolower($data['action']);
$userId = $_SESSION['user_id'];

try {
    $db = Database::getInstance()->getConnection();

    // Recupero lo stato del tavolo dal DB
    $stmt = $db->prepare("SELECT * FROM game_tables WHERE id = ?");
    $stmt->execute([$tableId]);
    $table = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$table) {
        echo json_encode(['error' => 'Tavolo non trovato']);
        exit;
    }

    if ($table['current_turn'] != $userId) {
        echo json_encode(['error' => 'Non è il tuo turno']);
        exit;
    }

    $deckArray    = json_decode($table['deck'], true);
    $playerHands = json_decode($table['player_hands'], true);
    $dealerHand  = json_decode($table['dealer_hand'], true);
    $status      = $table['status'] ?? 'playing';

    $deck = new Deck();
    $deck->loadFromArray($deckArray);

    if ($action === 'hit') {
        $playerHands[$userId][] = $deck->drawCard();
        $score = GameRules::calculateScore($playerHands[$userId]);

        if ($score > 21) {
            $status = 'bust';
            $table['current_turn'] = 'dealer';
        }

    } elseif ($action === 'stand') {
        // Passa turno al dealer
        $table['current_turn'] = 'dealer';
    } else {
        echo json_encode(['error' => 'Azione non valida']);
        exit;
    }

    $stmt = $db->prepare("
        UPDATE game_tables 
        SET deck = ?, player_hands = ?, dealer_hand = ?, current_turn = ?, status = ?
        WHERE id = ?
    ");

    $stmt->execute([
        json_encode($deck->getCards()),
        json_encode($playerHands),
        json_encode($dealerHand),
        $table['current_turn'],
        $status,
        $tableId
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Errore DB']);
}

?>