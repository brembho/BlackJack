# BlackJack

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
