

--!
--DA INSERIRE UNO ALLA VOLTA
--!



-- 1. Tabella Utenti
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    credits INT DEFAULT 1000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabella Tavoli (CON TUTTE LE COLONNE DALL'INIZIO)
CREATE TABLE game_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status ENUM('waiting', 'betting', 'playing', 'finished') DEFAULT 'waiting',
    dealer_hand TEXT,
    turn_player_id INT DEFAULT NULL,
    shoe LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabella Giocatori al Tavolo (CON ENUM COMPLETO)
CREATE TABLE game_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    user_id INT NOT NULL,
    hand TEXT,
    bet INT DEFAULT 0,
    status ENUM('betting', 'playing', 'stand', 'bust', 'blackjack', 'won', 'lost', 'push') DEFAULT 'betting',
    FOREIGN KEY (table_id) REFERENCES game_tables(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);