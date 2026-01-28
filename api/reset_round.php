<?php
// api/reset_round.php
error_reporting(0); ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';

if (!isset($_POST['table_id'])) exit(json_encode(['error'=>'No ID']));
$tableId = $_POST['table_id'];
$conn = (new Database())->getConnection();

// Resetta tavolo a 'betting' ma mantiene il sabot
$conn->prepare("UPDATE game_tables SET status='betting', dealer_hand='[]', turn_player_id=NULL WHERE id=:id")
     ->execute([':id'=>$tableId]);

// Resetta giocatori
$conn->prepare("UPDATE game_players SET status='betting', hand='[]', bet=0 WHERE table_id=:id")
     ->execute([':id'=>$tableId]);

echo json_encode(['success'=>true]);
?>