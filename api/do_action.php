<?php
// api/do_action.php
session_start();
header('Content-Type: application/json');

require_once '../classes/Database.php';
require_once '../classes/Deck.php';
require_once '../classes/Game.php';

// Controllo login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non sei loggato']);
    exit;
}

// Ricevo i dati JSON dal frontend
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['table_id']) || !isset($data['action'])) {
    echo json_encode(['error' => 'Dati mancanti']);
    exit;
}

$tableId = intval($data['table_id']);
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

    // Recupero le carte del mazzo dal DB (array JSON)
    $deckArray = json_decode($table['deck'], true);
    $playerHands = json_decode($table['player_hands'], true);
    $dealerHand = json_decode($table['dealer_hand'], true);
    $currentTurn = $table['current_turn'];

    // Controllo se è il turno del giocatore
    if ($currentTurn != $userId) {
        echo json_encode(['error' => 'Non è il tuo turno']);
        exit;
    }

    $game = new Game(); // Classe che calcola punteggi, regole, ecc.
    $deck = new Deck($deckArray); // Ricrea il mazzo dal DB

    if ($action === 'hit') {
        $card = $deck->drawCard();
        $playerHands[$userId][] = $card;

        // Aggiorno punteggio
        $score = $game->calculateScore($playerHands[$userId]);
        if ($score > 21) {
            $table['status'] = 'bust'; // Sballato
            // Passa turno al dealer
            $table['current_turn'] = 'dealer';
        }

    } elseif ($action === 'stand') {
        // Passa turno al dealer
        $table['current_turn'] = 'dealer';
    } else {
        echo json_encode(['error' => 'Azione non valida']);
        exit;
    }

    // Salvo stato aggiornato nel DB
    $stmt = $db->prepare("UPDATE game_tables SET deck = ?, player_hands = ?, dealer_hand = ?, current_turn = ?, status = ? WHERE id = ?");
    $stmt->execute([
        json_encode($deck->getCards()),
        json_encode($playerHands),
        json_encode($dealerHand),
        $table['current_turn'],
        $table['status'] ?? '',
        $tableId
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
