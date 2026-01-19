<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Deck.php';

class TableManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function createTable($userId) {
        try {
            // 1. Generiamo il Sabot gigante
            $deck = new Deck();
            $shoe = $deck->generateShoe(); 
            $shoeJson = json_encode($shoe);

            // 2. Creiamo il tavolo in stato 'betting' (mani vuote)
            $sql = "INSERT INTO game_tables (status, dealer_hand, shoe, created_at) 
                    VALUES ('betting', '[]', :shoe, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':shoe' => $shoeJson]);
            $tableId = $this->db->lastInsertId();

            // 3. Siedi il creatore (senza carte)
            $this->joinTable($tableId, $userId);

            return $tableId;

        } catch (PDOException $e) {
            die("Errore creazione: " . $e->getMessage());
        }
    }

    public function joinTable($tableId, $userId) {
        // Controlla se già seduto
        $checkSql = "SELECT id FROM game_players WHERE table_id = :tid AND user_id = :uid";
        $stmt = $this->db->prepare($checkSql);
        $stmt->execute([':tid' => $tableId, ':uid' => $userId]);

        if ($stmt->rowCount() == 0) {
            // Entra in stato 'betting' con mano vuota
            $sql = "INSERT INTO game_players (table_id, user_id, status, hand, bet) 
                    VALUES (:tid, :uid, 'betting', '[]', 0)";
            $this->db->prepare($sql)->execute([':tid' => $tableId, ':uid' => $userId]);
            return true;
        }
        return false;
    }

    public function getOpenTables() {
        $sql = "SELECT t.id, t.status, COUNT(p.id) as num_players 
                FROM game_tables t 
                LEFT JOIN game_players p ON t.id = p.table_id
                WHERE t.status != 'finished' 
                GROUP BY t.id ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>