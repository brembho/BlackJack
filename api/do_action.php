<?php
// api/do_action.php
error_reporting(0); 
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';

// Funzione calcolo (standard)
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
    // ... (Parte Logica Hit/Stand identica a prima) ...
    $stmt = $conn->prepare("SELECT * FROM game_players WHERE table_id = :tid AND user_id = :uid");
    $stmt->execute([':tid' => $tableId, ':uid' => $userId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtT = $conn->prepare("SELECT * FROM game_tables WHERE id = :tid");
    $stmtT->execute([':tid' => $tableId]);
    $table = $stmtT->fetch(PDO::FETCH_ASSOC);

    if ($table['turn_player_id'] != $userId) exit(json_encode(['error' => 'Non è il tuo turno']));

    $hand = json_decode($player['hand']);
    $shoe = json_decode($table['shoe']);
    $status = $player['status'];

    if ($action === 'hit') {
        $hand[] = array_pop($shoe);
        $score = calculateScore($hand);
        
        if ($score > 21) {
            $status = 'bust';
            // Aggiorna player
            $conn->prepare("UPDATE game_players SET hand=:h, status=:s WHERE id=:id")
                 ->execute([':h'=>json_encode($hand), ':s'=>$status, ':id'=>$player['id']]);
            // Aggiorna sabot
            $conn->prepare("UPDATE game_tables SET shoe=:s WHERE id=:id")->execute([':s'=>json_encode($shoe), ':id'=>$tableId]);
            
            passTurn($conn, $tableId, $shoe); // Se sballi, tocca al dealer
            echo json_encode(['success' => true]);
            exit;
        }
    } else if ($action === 'stand') {
        $status = 'stand';
    }

    $conn->prepare("UPDATE game_players SET hand=:h, status=:s WHERE id=:id")
         ->execute([':h'=>json_encode($hand), ':s'=>$status, ':id'=>$player['id']]);
    
    $conn->prepare("UPDATE game_tables SET shoe=:s WHERE id=:id")->execute([':s'=>json_encode($shoe), ':id'=>$tableId]);

    if ($status === 'stand') {
        passTurn($conn, $tableId, $shoe);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }


// --- FUNZIONI DI GIOCO ---

function passTurn($conn, $tableId, $shoe) {
    // 1. Il Dealer Gioca
    $stmt = $conn->prepare("SELECT dealer_hand FROM game_tables WHERE id = :id");
    $stmt->execute([':id' => $tableId]);
    $dealerHand = json_decode($stmt->fetch()['dealer_hand']);
    
    $dScore = calculateScore($dealerHand);
    while ($dScore < 17) {
        $dealerHand[] = array_pop($shoe);
        $dScore = calculateScore($dealerHand);
    }

    // 2. Tavolo Finito
    $conn->prepare("UPDATE game_tables SET dealer_hand = :h, status = 'finished', shoe = :s, turn_player_id = NULL WHERE id = :id")
         ->execute([':h' => json_encode($dealerHand), ':s' => json_encode($shoe), ':id' => $tableId]);

    // 3. PAGAMENTI (Check Winners & Pay)
    checkWinnersAndPay($conn, $tableId, $dScore);
}

function checkWinnersAndPay($conn, $tableId, $dealerScore) {
    $players = $conn->query("SELECT * FROM game_players WHERE table_id = $tableId")->fetchAll(PDO::FETCH_ASSOC);

    foreach($players as $p) {
        $hand = json_decode($p['hand']);
        $pScore = calculateScore($hand);
        $bet = intval($p['bet']);
        $finalStatus = 'lost';
        $payout = 0;

        // Logica Vincita
        if ($p['status'] == 'bust') {
            $finalStatus = 'lost';
        } elseif ($dealerScore > 21) {
            $finalStatus = 'won';
            $payout = $bet * 2; // Raddoppio
        } elseif ($pScore > $dealerScore) {
            $finalStatus = 'won';
            $payout = $bet * 2;
        } elseif ($pScore == $dealerScore) {
            $finalStatus = 'push';
            $payout = $bet; // Ti ridà i soldi
        } else {
            $finalStatus = 'lost';
        }

        // Se hai fatto Blackjack (21 con 2 carte) paghiamo 3:2 (cioè 2.5x)
        if ($pScore == 21 && count($hand) == 2 && $finalStatus == 'won') {
            $finalStatus = 'won'; // Lo chiamiamo won ma paghiamo di più
            $payout = $bet + ($bet * 1.5);
        }

        // AGGIORNA STATUS PLAYER
        $conn->prepare("UPDATE game_players SET status = :s WHERE id = :id")
             ->execute([':s'=>$finalStatus, ':id'=>$p['id']]);

        // PAGAMENTO REALE SU TABELLA USERS
        if ($payout > 0) {
            $conn->prepare("UPDATE users SET credits = credits + :pay WHERE id = :uid")
                 ->execute([':pay' => $payout, ':uid' => $p['user_id']]);
        }
    }
}
?>