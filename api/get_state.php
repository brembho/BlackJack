<?php

header('Content-Type: application/json'); // Diciamo al browser: "Ti sto mandando dati, non HTML"
session_start();
require_once __DIR__ . '/../classes/Database.php';


if (!isset($_GET['table_id'])) {
    echo json_encode(["error" => "Manca ID tavolo"]);
    exit;
}

$tableId = $_GET['table_id'];
$dbClass = new Database();
$conn = $dbClass->getConnection();

try {
 
    $sqlTable = "SELECT status, dealer_hand, turn_player_id FROM game_tables WHERE id = :tid";
    $stmt = $conn->prepare($sqlTable);
    $stmt->execute([':tid' => $tableId]);
    $tableData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tableData) {
        echo json_encode(["error" => "Tavolo non trovato"]);
        exit;
    }

    $sqlPlayers = "SELECT p.user_id, p.hand, p.status, p.bet, u.username 
                   FROM game_players p 
                   JOIN users u ON p.user_id = u.id 
                   WHERE p.table_id = :tid";
    
    $stmtPlayers = $conn->prepare($sqlPlayers);
    $stmtPlayers->execute([':tid' => $tableId]);
    $playersRaw = $stmtPlayers->fetchAll(PDO::FETCH_ASSOC);

    $playersClean = [];
    foreach ($playersRaw as $p) {
        // json_decode trasforma la stringa "[...]" in un array vero
        $p['hand'] = json_decode($p['hand']); 
        
        // Calcoliamo se è "Il mio turno" o "Sono io"
        $p['is_me'] = ($p['user_id'] == $_SESSION['user_id']);
        
        $playersClean[] = $p;
    }


    // Puliamo la mano del dealer
    
    $dealerHand = json_decode($tableData['dealer_hand']);

    //  RISPOSTA FINALE
    $response = [
        "table" => [
            "id" => $tableId,
            "status" => $tableData['status'], 
            "dealer_hand" => $dealerHand,
            "turn_player_id" => $tableData['turn_player_id']
        ],
        "players" => $playersClean,
        "current_user_id" => $_SESSION['user_id'] // frontend per controlli
    ];

    // Spediamo tutto al Javascript
    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(["error" => "Errore DB: " . $e->getMessage()]);
}
?>