# ♠️ Multiplayer PHP Blackjack

Un'applicazione web completa per giocare a Blackjack in multiplayer, sviluppata come progetto scolastico. Il sistema utilizza **PHP OOP**, **MySQL** per la persistenza dei dati e **AJAX** per l'aggiornamento in tempo reale del tavolo di gioco.

## 📋 Requisiti del Progetto

Il progetto soddisfa i seguenti requisiti tecnici:
- **Architettura Modulare:** Uso estensivo di classi e inclusione file (`require`, OOP).
- **Autenticazione Sicura:** Login e Registrazione con hashing delle password (`password_hash`).
- **Persistenza Dati:** Utilizzo di Database MySQL Relazionale.
- **Gestione Stato:** Uso di `$_SESSION` per l'utente e Database per lo stato del tavolo.
- **File System:** Sistema di logging su file `.txt` per tracciare gli eventi.
- **Multiplayer:** Sistema a polling (AJAX) per permettere a più utenti di giocare allo stesso tavolo.

---

## 🛠️ Tech Stack

* **Backend:** PHP 8.x (Object Oriented Programming)
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (Fetch API)
* **Hosting:** Compatibile con Apache (es. Altervista, XAMPP)

---

## 📂 Struttura del Progetto

```text
/blackjack-project
├── /api                  # Endpoint JSON per AJAX
│   ├── get_state.php     # Restituisce lo stato del tavolo (polling)
│   └── do_action.php     # Gestisce le mosse (Hit/Stand)
├── /assets
│   ├── /css/style.css    # Stili del tavolo e della UI
│   ├── /img/cards/       # Immagini delle carte (es. 10H.png)
│   └── /js/game.js       # Logica Frontend e chiamate AJAX
├── /classes              # Core Logic (OOP)
│   ├── Database.php      # Singleton Pattern per connessione DB
│   ├── User.php          # Gestione Auth e Crediti
│   ├── Table.php         # Gestione Lobby e Posti
│   ├── Deck.php          # Generazione e gestione mazzo
│   └── Game.php          # Regole Blackjack (Punteggi, Dealer AI)
├── /includes
│   ├── config.php        # Credenziali Database
│   └── functions.php     # Helper functions
├── /logs
│   └── game_log.txt      # Log testuale degli eventi
├── index.php             # Login Page
├── register.php          # Sign-up Page
├── lobby.php             # Lista tavoli attivi
├── tavolo.php            # Main Game Interface
└── install.sql           # Script importazione Database