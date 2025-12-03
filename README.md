# BlackJack

Studente A: "Il Backend Manager"
Focus: Database, Autenticazione, Sicurezza e File.

    Compiti:

    Creare il Database e la connessione PHP (Database.php).

    Creare il sistema di Registrazione (con hash della password).

    Creare il sistema di Login e gestione delle Sessioni.

    Gestire il requisito File (creare il sistema di log su file .txt).

    Gestire l'aggiornamento dei crediti nel DB a fine partita.

Studente B: "Il Game Developer"
Focus: Logica del Blackjack, Classi OOP, Interfaccia.

    Compiti:

    Creare la classe Mazzo (Deck.php - array di carte, mescolare, pescare).

    Creare la logica del punteggio (Asso vale 1 o 11, figure valgono 10).

    Disegnare l'interfaccia grafica (HTML/CSS) del tavolo da gioco.

    Gestire i pulsanti "Carta" e "Sto" e mostrare le carte a schermo.







/blackjack-project
├── /assets
│   ├── /css (style.css)
│   └── /img (immagini delle carte)
├── /classes              <-- Qui usate la OOP
│   ├── Database.php      (Gestione connessione DB)
│   ├── User.php          (Login, Registrazione, Hash)
│   ├── Deck.php          (Mazzo di carte, mischia, pesca)
│   └── Game.php          (Logica del blackjack: punteggi, vittoria/sconfitta)
├── /includes
│   ├── config.php        (Credenziali DB)
│   └── functions.php     (Funzioni generali)
├── /logs                 <-- Per il requisito "file"
│   └── game_log.txt      (Salviamo qui un log testuale delle partite)
├── index.php             (Home / Login form)
├── dashboard.php         (Pagina principale dopo il login)
├── tavolo.php            (Il gioco vero e proprio)
├── logout.php
└── install.sql           (File per creare il DB)
