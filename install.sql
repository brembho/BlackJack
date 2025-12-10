
-- 1. Tabella Utenti
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    credits INT DEFAULT 1000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabella Tavoli (Le stanze di gioco)
CREATE TABLE game_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status ENUM('waiting', 'playing', 'finished') DEFAULT 'waiting',
    dealer_hand TEXT, -- Salveremo le carte come JSON: ["10H", "AD"]
    turn_player_id INT DEFAULT NULL, -- ID dell'utente che deve muovere
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabella Giocatori al Tavolo (Chi è seduto dove)
CREATE TABLE game_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    user_id INT NOT NULL,
    hand TEXT, -- JSON delle carte del giocatore
    bet INT DEFAULT 0,
    status ENUM('playing', 'stand', 'bust', 'blackjack') DEFAULT 'playing',
    FOREIGN KEY (table_id) REFERENCES game_tables(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);