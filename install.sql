

--!
--DA INSERIRE UNO ALLA VOLTA
--!



-- 1. Tabella Utenti
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    credits INT DEFAULT 1000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabella Tavoli
CREATE TABLE IF NOT EXISTS game_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status ENUM('waiting', 'betting', 'playing', 'finished') DEFAULT 'waiting',
    dealer_hand TEXT,
    turn_player_id INT DEFAULT NULL,
    shoe LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Tabella Giocatori al Tavolo (CON ENUM COMPLETO - includendo 'waiting_for_others')
CREATE TABLE IF NOT EXISTS game_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    user_id INT NOT NULL,
    hand TEXT,
    bet INT DEFAULT 0,
    status ENUM('betting', 'waiting_for_others', 'playing', 'stand', 'bust', 'blackjack', 'won', 'lost', 'push') DEFAULT 'betting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES game_tables(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_table_id (table_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;