/* assets/js/game.js - VERSIONE CORRETTA PER PRIMO GIOCATORE */
const dealerDiv = document.getElementById('dealer-cards');
const dealerScoreDiv = document.getElementById('dealer-score');
const playersDiv = document.getElementById('players-area');
const bettingArea = document.getElementById('betting-area');
const actionBar = document.getElementById('action-bar');
const gameMessage = document.getElementById('game-message');
const waitMessage = document.getElementById('wait-message');
const btnPlaceBet = document.getElementById('btn-place-bet');
const casellaPuntata = document.getElementById('casellaPuntata');
const btnHit = document.querySelector('.btn-hit');
const btnStand = document.querySelector('.btn-stand');

let currentGameState = null;
let lastUpdate = 0;
let gameLoopInterval;
let isMyTurn = false;

// Event listeners
if(btnPlaceBet) btnPlaceBet.addEventListener('click', placeBet);
if(btnHit) btnHit.addEventListener('click', () => doAction('hit'));
if(btnStand) btnStand.addEventListener('click', () => doAction('stand'));

// Funzione per calcolare il punteggio
function calculateHandScore(hand) {
    if (!hand || hand.length === 0) return 0;

    let score = 0;
    let aces = 0;

    hand.forEach(cardCode => {
        let val = cardCode.slice(0, -1);
        if (['J', 'Q', 'K'].includes(val)) {
            score += 10;
        } else if (val === 'A') {
            score += 11;
            aces++;
        } else {
            score += parseInt(val);
        }
    });

    while (score > 21 && aces > 0) {
        score -= 10;
        aces--;
    }

    return score;
}

// Funzione per ottenere l'immagine della carta
function getCardImageSrc(cardCode) {
    if (!cardCode) return '';
    let value = cardCode.slice(0, -1); 
    const suitChar = cardCode.slice(-1);
    const suitsMap = { 'H': 'hearts', 'D': 'diamonds', 'C': 'clubs', 'S': 'spades' };
    const suitName = suitsMap[suitChar];

    if (value === 'J') value = 'j';
    else if (['Q', 'K', 'A'].includes(value)) value = value;
    else if (parseInt(value) < 10) value = '0' + value;

    return `assets/img/cards/${suitName}_${value}.png`;
}

// Place bet
async function placeBet() {
    const amount = document.getElementById('bet-amount').value;
    if (amount <= 0) {
        alert("Importo non valido");
        return;
    }
    
    btnPlaceBet.disabled = true;
    try {
        const res = await fetch('api/place_bet.php', {
            method: 'POST', 
            body: new URLSearchParams({ 'table_id': currentTableId, 'amount': amount })
        });
        const data = await res.json();
        if (data.success) {
            bettingArea.style.display = 'none';
            // Forza aggiornamento immediato
            setTimeout(() => fetchGameState(true), 500);
        } else {
            alert(data.error || "Errore nella scommessa");
        }
    } catch (e) { 
        console.error(e);
        alert("Errore di connessione");
    }
    btnPlaceBet.disabled = false;
}

// Do action
async function doAction(action) {
    try {
        const res = await fetch('api/do_action.php', {
            method: 'POST', 
            body: new URLSearchParams({ 'table_id': currentTableId, 'action': action })
        });
        const data = await res.json();
        if (data.error) {
            alert(data.error);
        }
        // Forza aggiornamento immediato dopo azione
        setTimeout(() => fetchGameState(true), 500);
    } catch(err) { 
        console.error(err);
        alert("Errore di connessione");
    }
}

// Render dealer
function renderDealer(table) {
    dealerDiv.innerHTML = '';
    
    if (table.status === 'betting' || table.status === 'waiting') {
        dealerScoreDiv.style.display = 'none';
        dealerDiv.innerHTML = '<div style="color: white; padding: 20px;">Il banco attende...</div>';
        return;
    }
    
    dealerScoreDiv.style.display = 'block';

    if (table.dealer_hand && table.dealer_hand.length > 0) {
        let dealerScore = 0;
        let scoreText = "";

        if (table.status === 'playing') {
            // Mostra solo la prima carta durante il gioco
            const visibleHand = [table.dealer_hand[0]];
            dealerScore = calculateHandScore(visibleHand);
            scoreText = `Punti: ${dealerScore} + (?)`;
            
            // Carta scoperta
            const img1 = document.createElement('img');
            img1.classList.add('card');
            img1.src = getCardImageSrc(table.dealer_hand[0]);
            img1.alt = table.dealer_hand[0];
            img1.onerror = function() { this.src = 'assets/img/cards/back.png'; };
            dealerDiv.appendChild(img1);
            
            // Carta coperta
            const img2 = document.createElement('img');
            img2.classList.add('card');
            img2.src = 'assets/img/cards/back.png';
            img2.alt = 'Carta coperta';
            dealerDiv.appendChild(img2);
        } else {
            // Mostra tutte le carte a fine partita
            dealerScore = calculateHandScore(table.dealer_hand);
            scoreText = `Punti: ${dealerScore}`;
            
            table.dealer_hand.forEach(cardCode => {
                const img = document.createElement('img');
                img.classList.add('card');
                img.src = getCardImageSrc(cardCode);
                img.alt = cardCode;
                img.onerror = function() { this.src = 'assets/img/cards/back.png'; };
                dealerDiv.appendChild(img);
            });
        }

        dealerScoreDiv.innerText = scoreText;
    }
}

// Render players
function renderPlayers(players, turnPlayerId) {
    playersDiv.innerHTML = '';
    
    players.forEach(p => {
        const div = document.createElement('div');
        div.className = 'player-box';
        if (String(p.user_id) === String(myUserId)) {
            div.classList.add('my-player');
        }
        if (String(p.user_id) === String(turnPlayerId)) {
            div.classList.add('player-turn');
        }

        const score = calculateHandScore(p.hand);
        let statusTxt = '';
        let statusClass = '';
        let showScore = false;
        
        switch(p.status) {
            case 'betting': 
                statusTxt = "IN ATTESA"; 
                statusClass = 'status-waiting'; 
                break;
            case 'waiting_for_others': 
                statusTxt = "PRONTO"; 
                statusClass = 'status-ready'; 
                break;
            case 'playing': 
                statusTxt = "IN GIOCO"; 
                statusClass = 'status-playing'; 
                showScore = true;
                break;
            case 'stand': 
                statusTxt = "STO"; 
                statusClass = 'status-stand'; 
                showScore = true;
                break;
            case 'bust': 
                statusTxt = "SBALLATO"; 
                statusClass = 'status-bust'; 
                showScore = true;
                break;
            case 'blackjack': 
                statusTxt = "BLACKJACK!"; 
                statusClass = 'status-blackjack'; 
                showScore = true;
                break;
            case 'won': 
                statusTxt = "VINTO!"; 
                statusClass = 'status-won'; 
                showScore = true;
                break;
            case 'lost': 
                statusTxt = "PERSO"; 
                statusClass = 'status-lost'; 
                showScore = true;
                break;
            case 'push': 
                statusTxt = "PAREGGIO"; 
                statusClass = 'status-push'; 
                showScore = true;
                break;
        }

        div.innerHTML = `
            <div style="margin-bottom:5px; font-weight:bold; color:${p.user_id == myUserId ? 'gold' : 'white'}">
                ${p.username} ${p.user_id == myUserId ? '(TU)' : ''}
            </div>
            <div class="hand" id="hand-${p.user_id}"></div>
            ${showScore ? `<div style="font-size:1.2rem; font-weight:bold; color:white; margin:5px 0;">Punti: ${score}</div>` : ''}
            <div style="color:gold; margin:5px 0;">Puntata: €${p.bet}</div>
            <div class="${statusClass}" style="font-size:1.5rem; font-weight:bold; margin-top:5px;">${statusTxt}</div>
        `;
        playersDiv.appendChild(div);

        // Render carte
        const handContainer = document.getElementById(`hand-${p.user_id}`);
        if (handContainer) {
            handContainer.innerHTML = '';
            if (p.hand && p.hand.length > 0) {
                p.hand.forEach(c => {
                    const img = document.createElement('img');
                    img.classList.add('card');
                    img.src = getCardImageSrc(c);
                    img.alt = c;
                    img.onerror = function() { 
                        this.src = 'assets/img/cards/back.png'; 
                    };
                    handContainer.appendChild(img);
                });
            }
        }
    });
}

// Fetch game state
async function fetchGameState(force = false) {
    const now = Date.now();
    if (!force && now - lastUpdate < 1000) return; // Rate limiting
    
    try {
        lastUpdate = now;
        const res = await fetch(`api/get_state.php?table_id=${currentTableId}&_=${now}`);
        const data = await res.json();
        
        if (data.error) {
            console.error("Errore:", data.error);
            return;
        }

        currentGameState = data;
        
        // Aggiorna crediti
        if (document.getElementById('user-credits-display')) {
            document.getElementById('user-credits-display').innerText = data.my_credits;
        }

        const table = data.table;
        const players = data.players;
        const me = players.find(p => String(p.user_id) === String(data.current_user_id));
        
        // Salva se è il mio turno
        isMyTurn = (String(table.turn_player_id) === String(data.current_user_id));

        // RESET VISUALE
        bettingArea.style.display = 'none';
        waitMessage.style.display = 'none';
        actionBar.style.display = 'none';
        gameMessage.innerText = '';

        // Gestione stati del gioco
        if (table.status === 'waiting') {
            dealerDiv.innerHTML = '';
            dealerScoreDiv.style.display = 'none';
            gameMessage.innerText = 'In attesa di giocatori...';
            bettingArea.style.display = 'block';
            btnPlaceBet.style.display = 'inline-block';
            renderPlayers(players, null);
        } 
        else if (table.status === 'betting') {
            dealerDiv.innerHTML = '';
            dealerScoreDiv.style.display = 'none';
            
            // Se ho già scommesso, mostro solo messaggio di attesa
            if (me && me.bet > 0) {
                bettingArea.style.display = 'block';
                casellaPuntata.style.display ='none';
                btnPlaceBet.style.display = 'none';
                waitMessage.style.display = 'block';
                waitMessage.innerText = "Puntata fatta. Aspetta gli altri giocatori...";
            } else {
                bettingArea.style.display = 'block';
                casellaPuntata.style.display ='inline';
                btnPlaceBet.style.display = 'inline-block';
            }
            
            renderPlayers(players, null);
        } 
        else if (table.status === 'playing') {
            // NASCONDI COMPLETAMENTE L'AREA DI PUNTATA
            bettingArea.style.display = 'none';
            waitMessage.style.display = 'none';
            
            renderDealer(table);
            renderPlayers(players, table.turn_player_id);
            
            if (isMyTurn) {
                actionBar.style.display = 'flex';
                gameMessage.innerText = "È IL TUO TURNO!";
            } else {
                actionBar.style.display = 'none';
                const activePlayer = players.find(p => String(p.user_id) === String(table.turn_player_id));
                if (activePlayer) {
                    gameMessage.innerText = `Attendi... tocca a ${activePlayer.username}`;
                } else {
                    gameMessage.innerText = "Attendi il tuo turno...";
                }
            }
        }
        // ... dentro fetchGameState ...
else if (table.status === 'finished') {
    bettingArea.style.display = 'none';
    waitMessage.style.display = 'none';
    actionBar.style.display = 'none';
    
    renderDealer(table);
    renderPlayers(players, null);
    
    // Mostra risultati
    if (me) {
        switch(me.status) {
            case 'won': gameMessage.innerText = "HAI VINTO!"; break;
            case 'lost': gameMessage.innerText = "Hai perso."; break;
            case 'push': gameMessage.innerText = "Pareggio."; break;
            case 'blackjack': gameMessage.innerText = "BLACKJACK! VINTO!"; break;
            default: gameMessage.innerText = "Mano terminata.";
        }
    }

    // --- NUOVA LOGICA TIMER ---
    const timerContainer = document.getElementById('restart-timer-container');
    const timerDisplay = document.getElementById('timer-seconds');
    
    // Mostriamo il timer solo se non è già visibile (per evitare di resettare il countdown continuamente)
    if (timerContainer.style.display === 'none') {
        timerContainer.style.display = 'block';
        let secondsLeft = 5;
        timerDisplay.innerText = secondsLeft;

        const countdownInterval = setInterval(() => {
            secondsLeft--;
            if (secondsLeft >= 0) {
                timerDisplay.innerText = secondsLeft;
            }
            if (secondsLeft <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);

        // Reset automatico dopo 5 secondi
        setTimeout(async () => {
            if (currentGameState && currentGameState.table.status === 'finished') {
                try {
                    await fetch('api/reset_round.php', {
                        method: 'POST', 
                        body: new URLSearchParams({ 'table_id': currentTableId })
                    });
                    // Nascondi il timer dopo il reset
                    timerContainer.style.display = 'none';
                    setTimeout(() => fetchGameState(true), 1000);
                } catch (e) {
                    console.error("Errore reset:", e);
                    timerContainer.style.display = 'none';
                }
            } else {
                timerContainer.style.display = 'none';
            }
        }, 5000);
    }
} else {
    // Se lo stato non è 'finished', assicuriamoci che il timer sia nascosto
    document.getElementById('restart-timer-container').style.display = 'none';
}
        
    } catch(e) { 
        console.error("Errore fetch:", e);
        if (!gameMessage.innerText.includes("Errore")) {
            gameMessage.innerText = "Errore di connessione...";
        }
    }
}

// Avvio del game loop
function startGameLoop() {
    if (gameLoopInterval) clearInterval(gameLoopInterval);
    gameLoopInterval = setInterval(fetchGameState, 1500); // Polling ogni 1.5 secondi
    fetchGameState(true); // Fetch immediato
}

// Inizializza il gioco
document.addEventListener('DOMContentLoaded', function() {
    startGameLoop();
});

// Refresh manuale
window.addEventListener('focus', function() {
    fetchGameState(true);
});