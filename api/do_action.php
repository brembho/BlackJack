<?php
// api/do_action.php
error_reporting(0); ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';

function calculateScore($hand) {
    if (!$hand || empty($hand)) return 0;
    
    $score = 0;
    $aces = 0;
    
    foreach ($hand as $card) {
        $value = substr($card, 0, -1);
        
        if ($value === 'J' || $value === 'Q' || $value === 'K') {
            $score += 10;
        } elseif ($value === 'A') {
            $score += 11;
            $aces++;
        } else {
            $score += intval($value);
        }
    }
    
    while ($score > 21 && $aces > 0) {
        $score -= 10;
        $aces--;
    }
    
    return $score;
}

$userId = $_SESSION['user_id'];
$tableId = $_POST['table_id'];
$action = $_POST['action'];

$db = new Database(); $conn = $db->getConnection();

try {
    // Controlli Turno
    $table = $conn->query("SELECT * FROM game_tables WHERE id=$tableId")->fetch(PDO::FETCH_ASSOC);
    if ($table['turn_player_id'] != $userId) exit(json_encode(['error'=>'Non è il tuo turno!']));

    $player = $conn->query("SELECT * FROM game_players WHERE table_id=$tableId AND user_id=$userId")->fetch(PDO::FETCH_ASSOC);
    $hand = json_decode($player['hand']);
    $shoe = json_decode($table['shoe']);
    $status = 'playing';

    if ($action === 'hit') {
        $hand[] = array_pop($shoe);
        if (calculateScore($hand) > 21) $status = 'bust';
    } elseif ($action === 'stand') {
        $status = 'stand';
    }

    // Aggiorna Player e Sabot
    $conn->prepare("UPDATE game_players SET hand=:h, status=:s WHERE id=:id")
         ->execute([':h'=>json_encode($hand), ':s'=>$status, ':id'=>$player['id']]);
    $conn->prepare("UPDATE game_tables SET shoe=:s WHERE id=:id")->execute([':s'=>json_encode($shoe), ':id'=>$tableId]);

    // SE IL TURNO È FINITO (Stand o Bust), PASSA AL PROSSIMO
    if ($status === 'bust' || $status === 'stand') {
        passTurnToNextPlayer($conn, $tableId, $userId, $shoe);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) { echo json_encode(['error'=>$e->getMessage()]); }


// --- FUNZIONE MULTIPLAYER: CERCA IL PROSSIMO ---
function passTurnToNextPlayer($conn, $tableId, $currentUserId, $shoe) {
    // 1. Cerca un giocatore 'playing' con ID maggiore del mio (il prossimo nella lista)
    $nextPlayer = $conn->query("SELECT user_id FROM game_players WHERE table_id=$tableId AND status='playing' AND user_id != $currentUserId ORDER BY id ASC LIMIT 1")->fetchColumn();

    if ($nextPlayer) {
        // C'è un altro umano che deve giocare! Passagli il turno.
        $conn->prepare("UPDATE game_tables SET turn_player_id = :uid WHERE id = :tid")
             ->execute([':uid'=>$nextPlayer, ':tid'=>$tableId]);
    } else {
        // Nessun umano rimasto. TOCCA AL DEALER.
        playDealerHand($conn, $tableId, $shoe);
    }
}

function playDealerHand($conn, $tableId, $shoe) {
    $dealerHand = json_decode($conn->query("SELECT dealer_hand FROM game_tables WHERE id=$tableId")->fetchColumn());
    while (calculateScore($dealerHand) < 17) {
        $dealerHand[] = array_pop($shoe);
    }
    
    // Tavolo Finito
    $conn->prepare("UPDATE game_tables SET dealer_hand=:h, status='finished', shoe=:s, turn_player_id=NULL WHERE id=:id")
         ->execute([':h'=>json_encode($dealerHand), ':s'=>json_encode($shoe), ':id'=>$tableId]);
    
    // Pagamenti
    checkWinnersAndPay($conn, $tableId, calculateScore($dealerHand));
}

function checkWinnersAndPay($conn, $tableId, $dealerScore) {
    // ... Stesso codice di prima per i pagamenti ...
    $players = $conn->query("SELECT * FROM game_players WHERE table_id = $tableId")->fetchAll(PDO::FETCH_ASSOC);
    foreach($players as $p) {
        $hand = json_decode($p['hand']); $pScore = calculateScore($hand);
        $bet = $p['bet']; $pay = 0; $st = 'lost';

        if ($p['status'] != 'bust') {
            if ($dealerScore > 21 || $pScore > $dealerScore) { $st='won'; $pay=$bet*2; }
            elseif ($pScore == $dealerScore) { $st='push'; $pay=$bet; }
        }
        if($st=='won' && $pScore==21 && count($hand)==2) $pay = $bet + ($bet*1.5); // Blackjack

        $conn->prepare("UPDATE game_players SET status=:s WHERE id=:id")->execute([':s'=>$st, ':id'=>$p['id']]);
        if($pay > 0) $conn->query("UPDATE users SET credits=credits+$pay WHERE id=".$p['user_id']);
    }
}
?>