<?php
// api/place_bet.php
error_reporting(0); ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Deck.php';

if (!isset($_POST['table_id']) || !isset($_POST['amount'])) exit(json_encode(['error'=>'Dati mancanti']));

$tableId = $_POST['table_id'];
$amount = intval($_POST['amount']);
$userId = $_SESSION['user_id'];
$db = new Database(); $conn = $db->getConnection();

try {
    $stmt = $conn->prepare("SELECT status, shoe FROM game_tables WHERE id = :id");
    $stmt->execute([':id' => $tableId]);
    $table = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($table['status'] === 'playing') exit(json_encode(['success'=>false, 'error'=>'Round in corso']));

    // Gestione Sabot
    $shoe = json_decode($table['shoe']);
    if (!is_array($shoe) || count($shoe) < 15) {
        $d = new Deck(); $shoe = $d->generateShoe();
    }

    // PESCA 2 CARTE ESATTE
    $playerHand = [array_pop($shoe), array_pop($shoe)];
    $dealerHand = [array_pop($shoe), array_pop($shoe)];

    // Aggiorna Player
    $conn->prepare("UPDATE game_players SET bet=:b, hand=:h, status='playing' WHERE table_id=:tid AND user_id=:uid")
         ->execute([':b'=>$amount, ':h'=>json_encode($playerHand), ':tid'=>$tableId, ':uid'=>$userId]);

    // Aggiorna Tavolo
    $conn->prepare("UPDATE game_tables SET status='playing', dealer_hand=:dh, shoe=:s, turn_player_id=:uid WHERE id=:tid")
         ->execute([':dh'=>json_encode($dealerHand), ':s'=>json_encode($shoe), ':uid'=>$userId, ':tid'=>$tableId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) { echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); }
?>  