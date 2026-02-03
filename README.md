# ♠️ Multiplayer PHP Blackjack

Un'applicazione web completa per giocare a Blackjack in multiplayer, sviluppata come progetto scolastico. Il sistema utilizza **PHP OOP**, **MySQL** per la persistenza dei dati e **AJAX** per l'aggiornamento in tempo reale del tavolo di gioco.

## 🎯 Funzionalità Principali

- **Registrazione/Login** con hashing password sicuro
- **Lobby dinamica** per creare/unirsi a tavoli
- **Blackjack multiplayer** con gestione automatica turni
- **Sistema di crediti** 1000 crediti iniziali, gestione vincite/perdite
- **Polling real-time** (1.5s) per aggiornamenti gioco
- **Regole casinò complete**: blackjack 3:2, dealer obbligato fino a 16
- **Reset automatico** dopo ogni mano

---

## 🛠️ Tech Stack

* **Backend:** PHP 8.x (Object Oriented Programming)
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (Fetch API)
* **Hosting:** Compatibile con Apache (es. Altervista, XAMPP)

---

## 📂 Struttura del Progetto

```text
blackjack/
├── api/                  # Endpoint AJAX
│   ├── do_action.php     # Gestione azioni giocatore
│   ├── get_state.php     # Stato del tavolo per il polling
│   ├── place_bet.php     # Sistema di puntate
│   └── reset_round.php   # Funzionalità di reset automatico
├── assets/               # Risorse frontend
│   ├── css/style.css     # Stile tema casinò
│   ├── js/game.js        # Polling in tempo reale e UI
│   ├── img/cards/        # Immagini delle carte
│   └── img/icona/        # Icona
├── classes/              # Logica OOP
│   ├── Database.php      # Gestore connessione PDO
│   ├── User.php          # Autenticazione e controllo utenti
│   ├── TableManager.php  # Creazione e gestione tavoli
│   ├── Deck.php          # Generazione del deck (6 mazzi)
│   └── GameRules.php     # Regole di punteggio del blackjack
├── includes/             # Configurazione
│   ├── config.php        # Credenziali database
│   └── systemLog.php     # Log delle attività
├── logs/                 # Log di gioco
├── login.php             # Autenticazione utente
├── logout.php            # Logout
├── register.php          # Registrazione nuovo utente
├── lobby.php             # Selezione tavoli (lobby)
├── tavolo.php            # Interfaccia principale di gioco
└── install.sql           # Schema del database
