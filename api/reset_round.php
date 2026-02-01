<?php
// api/reset_round.php - VERSIONE CORRETTA
error_reporting(0); 
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Deck.php';

if (!isset($_POST['table_id'])) {
    exit(json_encode(['error'=>'No ID']));
}

$tableId = $_POST['table_id'];
$conn = (new Database())->getConnection();

try {
    $conn->beginTransaction();
    
    // 1. Resetta tavolo a 'betting'
    $conn->prepare("UPDATE game_tables SET status='betting', dealer_hand='[]', turn_player_id=NULL WHERE id=:id")
         ->execute([':id'=>$tableId]);
    
    // 2. Resetta giocatori (mantieni seduti ma resetta tutto)
    $conn->prepare("UPDATE game_players SET hand='[]', bet=0, status='betting' WHERE table_id=:id")
         ->execute([':id'=>$tableId]);
    
    // 3. Rigenera il sabot
    $deck = new Deck();
    $newShoe = $deck->generateShoe();
    $conn->prepare("UPDATE game_tables SET shoe=:shoe WHERE id=:id")
         ->execute([':shoe'=>json_encode($newShoe), ':id'=>$tableId]);
    
    $conn->commit();
    echo json_encode(['success'=>true]);
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Errore reset_round: " . $e->getMessage());
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
?>