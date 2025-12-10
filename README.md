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


Colaninno:

    📂 File da creare:
    1. Cartella /classes

    [ ] Database.php: Gestisce la connessione al DB (PDO).

    [ ] User.php: Contiene metodi per login(), register(), getUserCredits().

    [ ] TableManager.php: Metodi per creare un nuovo tavolo nel DB e gestire chi si siede (joinTable()).

    2. Cartella /api (Il cuore del multiplayer)

    [ ] get_state.php: Questo file riceve un ID tavolo, legge il DB e stampa un JSON con la situazione attuale (es. {"carte_dealer": ["10H", "6D"], "turno_attuale": 5}). Serve al Frontend di B.

    3. Root (Pagine Utente)

    [ ] index.php: Form di Login.

    [ ] register.php: Form di Registrazione (ricorda password_hash).

    [ ] lobby.php: Pagina che mostra la lista dei tavoli attivi (presi dal DB) con un bottone "Entra" o "Crea Tavolo".

    [ ] logout.php: Distrugge la sessione.

    4. Cartella /logs

    [ ] system_logger.php (o dentro includes): Una funzione che scrive su game_log.txt ogni volta che qualcuno fa login o finisce una partita.


Brambilla:

    📂 File da creare:
    1. Cartella /classes

    [ ] Deck.php: Genera l'array di 52 carte, le mischia (shuffle) e ne pesca una (pop).

    [ ] GameRules.php: Contiene la logica pura. Es. calculateScore($arrayCarte) che restituisce il punteggio gestendo l'Asso a 1 o 11.

    2. Cartella /assets/js

    [ ] game.js: // Esempio di utilizzo (parte dello Studente B) fetch('api/get_state.php?table_id=1')
      .then(response => response.json()) // Converte la risposta in oggetto JS
      .then(data => {
          console.log(data); 
          // Qui B scriverà il codice per aggiornare le immagini:
          // data.players.forEach(player => { ... disegna carte ... })
      });

      

    Usa setInterval per chiamare api/get_state.php ogni 2 secondi.

    Aggiorna le immagini nel DOM in base al JSON ricevuto.

    Gestisce i click su "Carta" e "Sto".

    3. Cartella /api

    [ ] do_action.php: Riceve la mossa (Hit o Stand) dal Javascript. Usa la tua classe Deck per pescare una carta e aggiornare il DB (usando la connessione creata da A).

    4. Root (Pagina di Gioco)

    [ ] tavolo.php: La struttura HTML del tavolo. Deve avere dei div vuoti con ID specifici (es. <div id="dealer-cards"></div>) che il tuo Javascript riempirà.

    [ ] /assets/css/style.css: Tutto lo stile grafico (tavolo verde, carte, bottoni).

ASSIEME: 
    Questi fateli subito, in chiamata condivisa o su un solo PC, prima di dividervi:

    install.sql: Definite le colonne delle tabelle (users, game_tables, game_players). Se sbagliate questo all'inizio, dovrete riscrivere tutto il codice dopo.

    includes/config.php: Definite le costanti del database (Host, User, Password).

    💡 Come lavorare senza bloccarvi
    Studente A inizia creando il DB e la class Database.

    Studente B intanto crea la class Deck e l'HTML di tavolo.php (coi dati finti).

    Quando A ha finito la Login e la Lobby, B può collegare il suo tavolo ai dati reali.

    Il punto di incontro è l'API: A deve dire a B: "Guarda che il mio JSON esce con questo formato: {"hand": ["10H", "2D"]}", così B sa come leggerlo in Javascript.

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
