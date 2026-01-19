/* assets/js/game.js */

// Riferimenti DOM
const dealerDiv = document.getElementById('dealer-cards');
const dealerScoreDiv = document.getElementById('dealer-score');
const playersDiv = document.getElementById('players-area');
const bettingArea = document.getElementById('betting-area');
const actionBar = document.getElementById('action-bar');
const gameMessage = document.getElementById('game-message');
const waitMessage = document.getElementById('wait-message');

// Bottoni
const btnPlaceBet = document.getElementById('btn-place-bet');
const btnHit = document.querySelector('.btn-hit');
const btnStand = document.querySelector('.btn-stand');

// Listeners
if(btnPlaceBet) btnPlaceBet.addEventListener('click', placeBet);
if(btnHit) btnHit.addEventListener('click', () => doAction('hit'));
if(btnStand) btnStand.addEventListener('click', () => doAction('stand'));

/* --- 1. GESTIONE NOMI IMMAGINI --- */
function getCardImageSrc(cardCode) {
    if (!cardCode) return '';
    
    // cardCode: "10H", "2D", "JC", "AS"
    let value = cardCode.slice(0, -1); 
    const suitChar = cardCode.slice(-1);
    
    const suitsMap = { 'H': 'hearts', 'D': 'diamonds', 'C': 'clubs', 'S': 'spades' };
    const suitName = suitsMap[suitChar];

    
    if (value === 'J') value = 'j';
    else if (['Q', 'K', 'A'].includes(value)) value = value;
    else if (parseInt(value) < 10) value = '0' + value;

    return `assets/img/cards/${suitName}_${value}.png`;
}

/* --- 2. LOGICA API --- */
async function placeBet() {
    const amount = document.getElementById('bet-amount').value;
    try {
        const res = await fetch('api/place_bet.php', {
            method: 'POST', body: new URLSearchParams({ 'table_id': currentTableId, 'amount': amount })
        });
        const data = await res.json();
        if (data.success) {
            bettingArea.style.display = 'none';
            fetchGameState();
        } else {
            alert(data.error);
        }
    } catch (e) { console.error(e); }
}

async function doAction(action) {
    try {
        await fetch('api/do_action.php', {
            method: 'POST', body: new URLSearchParams({ 'table_id': currentTableId, 'action': action })
        });
        fetchGameState();
    } catch(err) { console.error(err); }
}

/* --- 3. RENDERING --- */
function renderDealer(table) {
    dealerDiv.innerHTML = '';
    dealerScoreDiv.innerText = (table.status === 'playing') ? 'Punti: ?' : 'Punti: (Vedi carte)';
    
    if (table.dealer_hand && table.dealer_hand.length > 0) {
        table.dealer_hand.forEach((cardCode, index) => {
            const img = document.createElement('img');
            img.classList.add('card');
            
            // Carta coperta se è la 2° e stiamo giocando
            if (index > 0 && table.status === 'playing') {
                img.src = 'assets/img/cards/back.png'; // Assicurati di avere questo file o usa un jolly
            } else {
                img.src = getCardImageSrc(cardCode);
            }
            // Fallback se manca l'immagine
            img.onerror = function() { this.style.display='none'; };
            dealerDiv.appendChild(img);
        });
    }
}

function renderPlayers(players, turnPlayerId) {
    playersDiv.innerHTML = '';
    players.forEach(p => {
        // Se siamo in betting e non hai puntato, non mostrare mano vuota
        if (p.status === 'betting' && (!p.hand || p.hand.length == 0)) return;

        const div = document.createElement('div');
        div.className = 'player-seat';
        if (String(p.user_id) === String(turnPlayerId)) div.classList.add('active-turn');

        let statusTxt = `Bet: €${p.bet}`;
        if(p.status === 'won') statusTxt = "VINTO ";
        if(p.status === 'lost') statusTxt = "PERSO";
        if(p.status === 'bust') statusTxt = "SBALLATO";
        if(p.status === 'push') statusTxt = "PAREGGIO";

        div.innerHTML = `
            <div>${p.username} ${p.is_me ? '(TU)' : ''}</div>
            <div class="hand" id="hand-${p.user_id}"></div>
            <div class="score-badge">${statusTxt}</div>
        `;
        playersDiv.appendChild(div);

        const handC = document.getElementById(`hand-${p.user_id}`);
        if(p.hand) {
            p.hand.forEach(c => {
                const img = document.createElement('img');
                img.classList.add('card');
                img.src = getCardImageSrc(c);
                handC.appendChild(img);
            });
        }
    });
}

async function fetchGameState() {
    try {
        // Usa currentTableId definito in tavolo.php
        const res = await fetch(`api/get_state.php?table_id=${currentTableId}`);
        const data = await res.json();
        if (data.error) return;

        const table = data.table;
        const me = data.players.find(p => String(p.user_id) === String(data.current_user_id));

        // RESETTA VISTE
        if (table.status === 'betting') {
            dealerDiv.innerHTML = '';
            // Se io devo ancora puntare
            if (!me || me.status === 'betting') {
                bettingArea.style.display = 'block';
                waitMessage.style.display = 'none';
                btnPlaceBet.style.display = 'inline-block';
            } else {
                // Ho puntato, aspetto start
                bettingArea.style.display = 'block';
                btnPlaceBet.style.display = 'none';
                waitMessage.style.display = 'block';
                waitMessage.innerText = "Puntata fatta. Aspetta...";
            }
            actionBar.style.display = 'none';
        } 
        else if (table.status === 'playing') {
            bettingArea.style.display = 'none';
            renderDealer(table);
            renderPlayers(data.players, table.turn_player_id);
            
            if (String(table.turn_player_id) === String(data.current_user_id)) {
                actionBar.style.display = 'flex';
            } else {
                actionBar.style.display = 'none';
            }
        }
        else if (table.status === 'finished') {
            bettingArea.style.display = 'none';
            actionBar.style.display = 'none';
            renderDealer(table);
            renderPlayers(data.players, null);
            gameMessage.innerText = "MANO FINITA! Ricarica tra 5s...";
            
            // Auto-reload per nuova mano (semplificazione)
            setTimeout(() => location.reload(), 5000);
        }

    } catch(e) { console.error(e); }
}

setInterval(fetchGameState, 2000);
fetchGameState();