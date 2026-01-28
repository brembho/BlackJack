<?php
// api/get_state.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../classes/Database.php';

if (!isset($_GET['table_id'])) exit(json_encode(["error" => "Manca ID"]));
$tableId = $_GET['table_id'];
$db = new Database(); $conn = $db->getConnection();

try {
    $table = $conn->prepare("SELECT status, dealer_hand, turn_player_id FROM game_tables WHERE id = :tid");
    $table->execute([':tid' => $tableId]);
    $tData = $table->fetch(PDO::FETCH_ASSOC);

    if (!$tData) exit(json_encode(["error" => "Tavolo non trovato"]));

    // RECUPERA CREDITI AGGIORNATI DELL'UTENTE CORRENTE
    $currUid = $_SESSION['user_id'] ?? 0;
    $credStmt = $conn->prepare("SELECT credits FROM users WHERE id = :uid");
    $credStmt->execute([':uid' => $currUid]);
    $currentCredits = $credStmt->fetchColumn();

    $playersRaw = $conn->prepare("SELECT p.*, u.username FROM game_players p JOIN users u ON p.user_id = u.id WHERE p.table_id = :tid");
    $playersRaw->execute([':tid' => $tableId]);
    
    $playersClean = [];
    foreach ($playersRaw->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $p['hand'] = json_decode($p['hand']);
        $p['is_me'] = ($p['user_id'] == $currUid);
        $playersClean[] = $p;
    }

    echo json_encode([
        "table" => [
            "id" => $tableId,
            "status" => $tData['status'],
            "dealer_hand" => json_decode($tData['dealer_hand']),
            "turn_player_id" => $tData['turn_player_id']
        ],
        "players" => $playersClean,
        "current_user_id" => $currUid,
        "my_credits" => $currentCredits // <--- ECCOLO!
    ]);

} catch (Exception $e) { echo json_encode(["error" => $e->getMessage()]); }
?>