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
    // Check Soldi
    $creds = $conn->query("SELECT credits FROM users WHERE id=$userId")->fetchColumn();
    if ($creds < $amount) exit(json_encode(['success'=>false, 'error'=>"Crediti insufficienti (Hai $creds)"]));

    // Check Stato Tavolo
    $stmt = $conn->prepare("SELECT status, shoe, turn_player_id FROM game_tables WHERE id=:id");
    $stmt->execute([':id'=>$tableId]);
    $table = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se il gioco è già in fase avanzata (qualcuno sta muovendo), non puoi entrare
    if ($table['status'] === 'playing' && $table['turn_player_id'] !== NULL) {
        exit(json_encode(['success'=>false, 'error'=>'Mano già iniziata. Aspetta il prossimo round.']));
    }

    // Gestione Sabot
    $shoe = json_decode($table['shoe']);
    if (!is_array($shoe) || count($shoe) < 20) {
        $d = new Deck(); $shoe = $d->generateShoe();
    }

    // Pesca le TUE carte
    $playerHand = [array_pop($shoe), array_pop($shoe)];

    // Transazione
    $conn->beginTransaction();
    
    // 1. Scala soldi
    $conn->prepare("UPDATE users SET credits=credits-:a WHERE id=:u")->execute([':a'=>$amount, ':u'=>$userId]);
    
    // 2. Aggiorna Player (Status diventa 'playing')
    $conn->prepare("UPDATE game_players SET bet=:b, hand=:h, status='playing' WHERE table_id=:tid AND user_id=:uid")
         ->execute([':b'=>$amount, ':h'=>json_encode($playerHand), ':tid'=>$tableId, ':uid'=>$userId]);

    // 3. LOGICA DI INIZIO GIOCO:
    // Se il Dealer non ha carte, gliene diamo 2.
    // Il turno viene assegnato al PRIMO giocatore seduto che ha status 'playing'.
    $dealerHand = json_decode($conn->query("SELECT dealer_hand FROM game_tables WHERE id=$tableId")->fetchColumn());
    
    if (empty($dealerHand) || count($dealerHand) == 0) {
        $dealerHand = [array_pop($shoe), array_pop($shoe)];
    }

    // Trova chi deve iniziare (il primo ID tra quelli che stanno giocando)
    // Se nessuno aveva il turno, lo diamo al primo trovato.
    $firstPlayer = $conn->query("SELECT user_id FROM game_players WHERE table_id=$tableId AND status='playing' ORDER BY id ASC LIMIT 1")->fetchColumn();

    $conn->prepare("UPDATE game_tables SET status='playing', dealer_hand=:dh, shoe=:s, turn_player_id=:uid WHERE id=:tid")
         ->execute([':dh'=>json_encode($dealerHand), ':s'=>json_encode($shoe), ':uid'=>$firstPlayer, ':tid'=>$tableId]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) { $conn->rollBack(); echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); }
?>