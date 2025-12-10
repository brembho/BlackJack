<?php
require_once __DIR__ . '/Database.php';

class TableManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function createTable($userId) {
        try {
            $sql = "INSERT INTO game_tables (status, created_at) VALUES ('waiting', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $tableId = $this->db->lastInsertId();

            $this->joinTable($tableId, $userId);

            return $tableId;

        } catch (PDOException $e) {
            die("Errore creazione tavolo: " . $e->getMessage());
        }
    }

    public function joinTable($tableId, $userId) {
        try {
            $checkSql = "SELECT id FROM game_players WHERE table_id = :tid AND user_id = :uid";
            $stmt = $this->db->prepare($checkSql);
            $stmt->execute([':tid' => $tableId, ':uid' => $userId]);

            if ($stmt->rowCount() == 0) {
                // Inserisce l'utente nella tabella 'game_players'
                // Inizializziamo la mano come array vuoto JSON '[]'
                $sql = "INSERT INTO game_players (table_id, user_id, status, hand) 
                        VALUES (:tid, :uid, 'playing', '[]')";
                $insertStmt = $this->db->prepare($sql);
                $insertStmt->execute([':tid' => $tableId, ':uid' => $userId]);
                return true;
            }
            return false; 

        } catch (PDOException $e) {
            return false;
        }
    }

    public function getOpenTables() {
        try {
            $sql = "SELECT t.id, t.created_at, COUNT(p.id) as num_players 
                    FROM game_tables t 
                    LEFT JOIN game_players p ON t.id = p.table_id
                    WHERE t.status = 'waiting'
                    GROUP BY t.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }
}
?>