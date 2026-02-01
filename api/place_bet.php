<?php
// api/place_bet.php - VERSIONE CORRETTA
error_reporting(0); 
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Deck.php';

if (!isset($_POST['table_id']) || !isset($_POST['amount'])) {
    exit(json_encode(['error'=>'Dati mancanti']));
}

$tableId = $_POST['table_id'];
$amount = intval($_POST['amount']);
$userId = $_SESSION['user_id'];
$db = new Database(); 
$conn = $db->getConnection();

try {
    // Check Soldi
    $creds = $conn->query("SELECT credits FROM users WHERE id=$userId")->fetchColumn();
    if ($creds < $amount) {
        exit(json_encode(['success'=>false, 'error'=>"Crediti insufficienti (Hai $creds)"]));
    }
    if ($amount <= 0) {
        exit(json_encode(['success'=>false, 'error'=>"Importo non valido"]));
    }

    // Check Stato Tavolo
    $stmt = $conn->prepare("SELECT status, shoe, turn_player_id, dealer_hand FROM game_tables WHERE id=:id");
    $stmt->execute([':id'=>$tableId]);
    $table = $stmt->fetch(PDO::FETCH_ASSOC);

    // Controlla se il gioco è già iniziato
    if ($table['status'] === 'playing') {
        exit(json_encode(['success'=>false, 'error'=>'Il gioco è già iniziato!']));
    }

    // Transazione
    $conn->beginTransaction();
    
    // 1. Scala soldi e aggiorna sessione
    $conn->prepare("UPDATE users SET credits=credits-:a WHERE id=:u")->execute([':a'=>$amount, ':u'=>$userId]);
    $_SESSION['credits'] = $creds - $amount;
    
    // 2. Aggiorna Player (metti la scommessa e cambia status in 'waiting_for_others')
    $conn->prepare("UPDATE game_players SET bet=:b, status='waiting_for_others' WHERE table_id=:tid AND user_id=:uid")
         ->execute([':b'=>$amount, ':tid'=>$tableId, ':uid'=>$userId]);

    // 3. Controlla se TUTTI i giocatori hanno scommesso
    $totalPlayers = $conn->query("SELECT COUNT(*) FROM game_players WHERE table_id=$tableId")->fetchColumn();
    $playersWithBet = $conn->query("SELECT COUNT(*) FROM game_players WHERE table_id=$tableId AND bet > 0")->fetchColumn();
    
    // Se tutti hanno scommesso, inizia il gioco
    if ($totalPlayers > 0 && $totalPlayers == $playersWithBet) {
        // Gestione Sabot
        $shoe = json_decode($table['shoe']);
        if (!is_array($shoe) || count($shoe) < 20) {
            $d = new Deck(); 
            $shoe = $d->generateShoe();
        }
        
        // Pesca carte per TUTTI i giocatori e cambia status in 'playing'
        $players = $conn->query("SELECT id, user_id FROM game_players WHERE table_id=$tableId ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($players as $player) {
            $playerHand = [array_pop($shoe), array_pop($shoe)];
            $conn->prepare("UPDATE game_players SET hand=:h, status='playing' WHERE id=:id")
                 ->execute([':h'=>json_encode($playerHand), ':id'=>$player['id']]);
        }
        
        // Pesca carte per il dealer (solo se non le ha già)
        $dealerHand = json_decode($table['dealer_hand']);
        if (empty($dealerHand) || count($dealerHand) === 0) {
            $dealerHand = [array_pop($shoe), array_pop($shoe)];
        }
        
        // Assegna turno al primo giocatore
        $firstPlayer = $conn->query("SELECT user_id FROM game_players WHERE table_id=$tableId ORDER BY id ASC LIMIT 1")->fetchColumn();
        
        // Aggiorna stato tavolo
        $conn->prepare("UPDATE game_tables SET status='playing', dealer_hand=:dh, shoe=:s, turn_player_id=:uid WHERE id=:tid")
             ->execute([':dh'=>json_encode($dealerHand), ':s'=>json_encode($shoe), ':uid'=>$firstPlayer, ':tid'=>$tableId]);
    } else {
        // Non tutti hanno scommesso, rimani in 'betting'
        // Se era 'waiting', passa a 'betting'
        if ($table['status'] === 'waiting') {
            $conn->prepare("UPDATE game_tables SET status='betting' WHERE id=:tid")
                 ->execute([':tid'=>$tableId]);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) { 
    $conn->rollBack(); 
    error_log("Errore place_bet: " . $e->getMessage());
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); 
}
?>