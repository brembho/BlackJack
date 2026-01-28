<?php
error_reporting(0); 
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';

function calculateScore($hand) {
    $score = 0; $aces = 0;
    foreach ($hand as $card) {
        $val = substr($card, 0, -1);
        if (is_numeric($val)) $score += intval($val);
        elseif (in_array($val, ['J', 'Q', 'K'])) $score += 10;
        elseif ($val === 'A') { $score += 11; $aces++; }
    }
    while ($score > 21 && $aces > 0) { $score -= 10; $aces--; }
    return $score;
}

$userId = $_SESSION['user_id'];
$tableId = $_POST['table_id'];
$action = $_POST['action'];

$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("SELECT * FROM game_players WHERE table_id = :tid AND user_id = :uid");
    $stmt->execute([':tid' => $tableId, ':uid' => $userId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtT = $conn->prepare("SELECT * FROM game_tables WHERE id = :tid");
    $stmtT->execute([':tid' => $tableId]);
    $table = $stmtT->fetch(PDO::FETCH_ASSOC);

    if ($table['turn_player_id'] != $userId) { echo json_encode(['error' => 'Non è il tuo turno']); exit; }

    $hand = json_decode($player['hand']);
    $shoe = json_decode($table['shoe']);
    $status = $player['status'];

    if ($action === 'hit') {
        $hand[] = array_pop($shoe);
        $score = calculateScore($hand);
        $_SESSION["punteggioPlayer"] = $score;
        if ($score > 21) {
            $status = 'bust';
            $upd = $conn->prepare("UPDATE game_players SET hand = :h, status = :s WHERE id = :id");
            $upd->execute([':h' => json_encode($hand), ':s' => $status, ':id' => $player['id']]);
            // Salva sabot aggiornato prima di passare il turno
            $conn->prepare("UPDATE game_tables SET shoe = :s WHERE id = :id")->execute([':s'=>json_encode($shoe), ':id'=>$tableId]);
            passTurn($conn, $tableId, $shoe);
            echo json_encode(['success' => true]);
            exit;
        }
    } else if ($action === 'stand') {
        $status = 'stand';
    }

    // Salva stato giocatore
    $upd = $conn->prepare("UPDATE game_players SET hand = :h, status = :s WHERE id = :id");
    $upd->execute([':h' => json_encode($hand), ':s' => $status, ':id' => $player['id']]);

    // Salva sabot
    $conn->prepare("UPDATE game_tables SET shoe = :s WHERE id = :id")->execute([':s'=>json_encode($shoe), ':id'=>$tableId]);

    if ($status === 'stand') {
        passTurn($conn, $tableId, $shoe);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }

function passTurn($conn, $tableId, $shoe) {
    // Semplificazione: passa subito al Dealer (Single Player logic)
    // Se volessi multiplayer reale, qui cercheresti il prossimo player con status 'playing'
    
    // Logica Dealer
    $stmt = $conn->prepare("SELECT dealer_hand FROM game_tables WHERE id = :id");
    $stmt->execute([':id' => $tableId]);
    $dealerHand = json_decode($stmt->fetch()['dealer_hand']);
    
    $dScore = calculateScore($dealerHand);
    while ($dScore < 17) {
        $dealerHand[] = array_pop($shoe);
        $dScore = calculateScore($dealerHand);
    }

    // Aggiorna tabella come FINISHED
    $upd = $conn->prepare("UPDATE game_tables SET dealer_hand = :h, status = 'finished', shoe = :s, turn_player_id = NULL WHERE id = :id");
    $upd->execute([':h' => json_encode($dealerHand), ':s' => json_encode($shoe), ':id' => $tableId]);

    // Calcola vincitori
    checkWinners($conn, $tableId, $dScore);
}

function checkWinners($conn, $tableId, $dealerScore) {
    $players = $conn->query("SELECT * FROM game_players WHERE table_id = $tableId")->fetchAll(PDO::FETCH_ASSOC);
    foreach($players as $p) {
        $h = json_decode($p['hand']);
        $s = calculateScore($h);
        $st = 'lost';
        if ($p['status'] == 'bust') $st = 'lost';
        elseif ($dealerScore > 21) $st = 'won';
        elseif ($s > $dealerScore) $st = 'won';
        elseif ($s == $dealerScore) $st = 'push';
        
        $conn->prepare("UPDATE game_players SET status = :s WHERE id = :id")->execute([':s'=>$st, ':id'=>$p['id']]);
    }
}
?>