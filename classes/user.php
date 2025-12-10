<?php 
session_start();
require_once "Database.php"; 

class user
{

    private $username;

    public function __construct() {
        $this->username = "";
    }

    public function Autenticazione($username, $password_inserita) {
        
        $sql = "SELECT id, username, password, credits FROM users WHERE username = :username";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':username' => $username]);
            
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);//PDO::FETCH_ASSOC ritorna la fetch come array associativo 

            if ($userRow && password_verify($password_inserita, $userRow['password'])) {
                
                $_SESSION['user_id'] = $userRow['id'];
                $_SESSION['username'] = $userRow['username'];
                $_SESSION['credits'] = $userRow['credits'];
                $this->username = $username;
                
                return true; 
            } else {
                return false; 
            }

        } catch (PDOException $e) {
            echo "Errore nel login: " . $e->getMessage();
            return false;
        }
    }

    public function isLogged() {
        return isset($_SESSION['user_id']);
    }



    
}





?>