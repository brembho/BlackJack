<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/Database.php"; // __DIR__ aiuta a trovare il file corretto

class User {

    private $db;

    public function __construct() {
        // Creiamo la connessione appena creiamo l'oggetto User
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Restituisce TRUE se il login ha successo, FALSE se fallisce
    public function Autenticazione($username, $password_inserita) {
        
        // 1. Prepariamo la query (Cerchiamo ID, Password e Crediti)
        $sql = "SELECT id, username, password, credits FROM users WHERE username = :username";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            // 2. Eseguiamo la query passando il parametro in sicurezza
            $stmt->execute([':username' => $username]);
            
            // 3. Recuperiamo la riga (se esiste)
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. Controlliamo se l'utente esiste E se la password corrisponde all'hash
            if ($userRow && password_verify($password_inserita, $userRow['password'])) {
                
                // LOGIN RIUSCITO! Salva i dati importanti nella sessione
                $_SESSION['user_id'] = $userRow['id'];
                $_SESSION['username'] = $userRow['username'];
                $_SESSION['credits'] = $userRow['credits'];
                
                return true; 
            } else {
                // Utente non trovato o password sbagliata
                return false; 
            }

        } catch (PDOException $e) {
            echo "Errore nel login: " . $e->getMessage();
            return false;
        }
    }

    // Metodo extra utile: Controlla se l'utente è loggato
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}
?>