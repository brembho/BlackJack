<?php
// api/do_action.php - VERSIONE MULTIPLAYER
error_reporting(0); 
ini_set('display_errors', 0);
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

$userId = $_SESSION['user_id'] ?? 0;
$tableId = $_POST['table_id'] ?? 0;
$action = $_POST['action'] ?? '';

$db = new Database(); 
$conn = $db->getConnection();

try {
    // Controlli Turno
    $table = $conn->query("SELECT * FROM game_tables WHERE id=$tableId")->fetch(PDO::FETCH_ASSOC);
    if ($table['turn_player_id'] != $userId) {
        exit(json_encode(['error'=>'Non è il tuo turno!']));
    }

    $player = $conn->query("SELECT * FROM game_players WHERE table_id=$tableId AND user_id=$userId")->fetch(PDO::FETCH_ASSOC);
    $hand = json_decode($player['hand']);
    $shoe = json_decode($table['shoe']);
    $status = 'playing';

    if ($action === 'hit') {
        $hand[] = array_pop($shoe);
        $score = calculateScore($hand);
        
        if ($score > 21) {
            $status = 'bust';
        } elseif ($score === 21) {
            $status = 'stand'; // Automatico con 21
        }
    } elseif ($action === 'stand') {
        $status = 'stand';
    } else {
        exit(json_encode(['error'=>'Azione non valida']));
    }

    // Aggiorna Player e Sabot
    $conn->prepare("UPDATE game_players SET hand=:h, status=:s WHERE id=:id")
         ->execute([':h'=>json_encode($hand), ':s'=>$status, ':id'=>$player['id']]);
    
    $conn->prepare("UPDATE game_tables SET shoe=:s WHERE id=:id")
         ->execute([':s'=>json_encode($shoe), ':id'=>$tableId]);

    // SE IL TURNO È FINITO (Stand, Bust o 21), PASSA AL PROSSIMO
    if ($status === 'bust' || $status === 'stand') {
        passTurnToNextPlayer($conn, $tableId, $userId);
    } else {
        // Se ancora playing, rimane il suo turno
        echo json_encode(['success' => true]);
        exit();
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) { 
    error_log("Errore do_action: " . $e->getMessage());
    echo json_encode(['error'=>$e->getMessage()]); 
}

function passTurnToNextPlayer($conn, $tableId, $currentUserId) {
    // Trova il prossimo giocatore che deve giocare
    $nextPlayer = $conn->query("
        SELECT user_id 
        FROM game_players 
        WHERE table_id = $tableId 
        AND user_id != $currentUserId 
        AND status = 'playing'
        ORDER BY id ASC 
        LIMIT 1
    ")->fetchColumn();

    if ($nextPlayer) {
        // C'è un altro giocatore
        $conn->prepare("UPDATE game_tables SET turn_player_id = :uid WHERE id = :tid")
             ->execute([':uid'=>$nextPlayer, ':tid'=>$tableId]);
    } else {
        // Nessun giocatore rimasto, tocca al dealer
        playDealerHand($conn, $tableId);
    }
}

function playDealerHand($conn, $tableId) {
    // Recupera mano del dealer e sabot
    $table = $conn->query("SELECT dealer_hand, shoe FROM game_tables WHERE id=$tableId")->fetch(PDO::FETCH_ASSOC);
    $dealerHand = json_decode($table['dealer_hand']);
    $shoe = json_decode($table['shoe']);
    
    // Regola del dealer: pesca fino a 17, su 17 si ferma
    while (calculateScore($dealerHand) < 17) {
        $dealerHand[] = array_pop($shoe);
    }
    
    $dealerScore = calculateScore($dealerHand);
    
    // Tavolo Finito
    $conn->prepare("UPDATE game_tables SET dealer_hand=:h, status='finished', shoe=:s, turn_player_id=NULL WHERE id=:id")
         ->execute([':h'=>json_encode($dealerHand), ':s'=>json_encode($shoe), ':id'=>$tableId]);
    
    // Calcola vincitori e pagamenti
    checkWinnersAndPay($conn, $tableId, $dealerScore);
}

function checkWinnersAndPay($conn, $tableId, $dealerScore) {
    $players = $conn->query("SELECT * FROM game_players WHERE table_id = $tableId")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($players as $p) {
        $hand = json_decode($p['hand']); 
        $pScore = calculateScore($hand);
        $bet = $p['bet']; 
        $pay = 0; 
        $st = 'lost';

        // Se il giocatore non è bust
        if ($p['status'] != 'bust') {
            // Controlla se ha fatto blackjack (21 con 2 carte)
            $isBlackjack = ($pScore == 21 && count($hand) == 2);
            $dealerBlackjack = ($dealerScore == 21 && count(json_decode($conn->query("SELECT dealer_hand FROM game_tables WHERE id=$tableId")->fetchColumn())) == 2);
            
            if ($isBlackjack && !$dealerBlackjack) {
                // Blackjack vince 3:2
                $st = 'won';
                $pay = $bet + ($bet * 1.5);
            } elseif ($dealerScore > 21 || $pScore > $dealerScore) {
                $st = 'won';
                $pay = $bet * 2; // Vince 1:1
            } elseif ($pScore == $dealerScore) {
                $st = 'push';
                $pay = $bet; // Restituisce la puntata
            }
        }
        
        // Aggiorna stato giocatore
        $conn->prepare("UPDATE game_players SET status=:s WHERE id=:id")
             ->execute([':s'=>$st, ':id'=>$p['id']]);
        
        // Aggiorna crediti
        if($pay > 0) {
            $conn->prepare("UPDATE users SET credits = credits + :pay WHERE id = :uid")
                 ->execute([':pay'=>$pay, ':uid'=>$p['user_id']]);
        }
    }
}
?>