/* assets/js/game.js - Fix Carte Triple e Undefined */

// DOM Elements
const dealerDiv = document.getElementById('dealer-cards');
const dealerScoreDiv = document.getElementById('dealer-score');
const playersDiv = document.getElementById('players-area');
const bettingArea = document.getElementById('betting-area');
const actionBar = document.getElementById('action-bar');
const gameMessage = document.getElementById('game-message');
const waitMessage = document.getElementById('wait-message');

const btnPlaceBet = document.getElementById('btn-place-bet');
const btnHit = document.querySelector('.btn-hit');
const btnStand = document.querySelector('.btn-stand');

// Listeners
if(btnPlaceBet) btnPlaceBet.addEventListener('click', placeBet);
if(btnHit) btnHit.addEventListener('click', () => doAction('hit'));
if(btnStand) btnStand.addEventListener('click', () => doAction('stand'));

/* --- 1. GESTIONE IMMAGINI --- */
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

/* --- 2. API CALLS --- */
async function placeBet() {
    const amount = document.getElementById('bet-amount').value;
    // Disabilita il bottone per evitare doppi click
    btnPlaceBet.disabled = true;
    
    try {
        const res = await fetch('api/place_bet.php', {
            method: 'POST', body: new URLSearchParams({ 'table_id': currentTableId, 'amount': amount })
        });
        const data = await res.json();
        
        if (data.success) {
            bettingArea.style.display = 'none';
            // Aggiorna subito lo stato
            fetchGameState();
        } else {
            alert(data.error);
        }
    } catch (e) { console.error(e); }
    
    btnPlaceBet.disabled = false; // Riabilita se serve
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
    dealerDiv.innerHTML = ''; // Pulisce sempre prima di disegnare
    
    // Se siamo in scommessa, nascondi i punti undefined
    if (table.status === 'betting') {
        dealerScoreDiv.style.display = 'none';
        return;
    }
    dealerScoreDiv.style.display = 'block';
    dealerScoreDiv.innerText = (table.status === 'playing') ? 'Punti: ?' : 'Punti: (Vedi carte)';
    
    if (table.dealer_hand && table.dealer_hand.length > 0) {
        table.dealer_hand.forEach((cardCode, index) => {
            const img = document.createElement('img');
            img.classList.add('card');
            
            if (index > 0 && table.status === 'playing') {
                img.src = 'assets/img/cards/back.png';
            } else {
                img.src = getCardImageSrc(cardCode);
            }
            img.onerror = function() { this.style.display='none'; };
            dealerDiv.appendChild(img);
        });
    }
}

function renderPlayers(players, turnPlayerId) {
    playersDiv.innerHTML = ''; // FONDAMENTALE: Pulisce tutto per evitare duplicati
    
    players.forEach(p => {
        if (p.status === 'betting' && (!p.hand || p.hand.length == 0)) return;

        const div = document.createElement('div');
        div.className = 'player-box'; // Usa la classe corretta del CSS
        if (String(p.user_id) === String(turnPlayerId)) div.classList.add('player-turn');

        let statusTxt = `Bet: €${p.bet}`;
        if(p.status === 'won') statusTxt = "VINTO";
        if(p.status === 'lost') statusTxt = "PERSO";
        if(p.status === 'bust') statusTxt = "SBALLATO";
        if(p.status === 'push') statusTxt = "PAREGGIO";

        div.innerHTML = `
            <div style="margin-bottom:5px;">${p.username} ${p.is_me ? '(TU)' : ''}</div>
            <div class="hand" id="hand-${p.user_id}"></div>
            <div class="score-badge" style="color:gold; font-size:0.7rem;">${statusTxt}</div>
        `;
        playersDiv.appendChild(div);

        const handC = document.getElementById(`hand-${p.user_id}`);
        // Renderizza le carte SOLO se esistono
        if(p.hand && Array.isArray(p.hand)) {
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
        const res = await fetch(`api/get_state.php?table_id=${currentTableId}`);
        const data = await res.json();
        if (data.error) return;

        const table = data.table;
        const me = data.players.find(p => String(p.user_id) === String(data.current_user_id));

        // STATO SCOMMESSA
        if (table.status === 'betting') {
            dealerDiv.innerHTML = ''; 
            dealerScoreDiv.style.display = 'none'; // Nasconde punti
            
            if (!me || me.status === 'betting') {
                bettingArea.style.display = 'block';
                waitMessage.style.display = 'none';
            } else {
                bettingArea.style.display = 'block';
                document.getElementById('btn-place-bet').style.display = 'none'; // Nasconde solo il bottone
                waitMessage.style.display = 'block';
                waitMessage.innerText = "Puntata accettata. Attendi...";
            }
            actionBar.style.display = 'none';
        } 
        // STATO GIOCO
        else {
            bettingArea.style.display = 'none';
            // Resetta il bottone bet per il prossimo round
            document.getElementById('btn-place-bet').style.display = 'inline-block';
            
            renderDealer(table);
            renderPlayers(data.players, table.table_turn_id || table.turn_player_id);
            
            if (table.status === 'playing' && String(table.turn_player_id) === String(data.current_user_id)) {
                actionBar.style.display = 'flex';
            } else {
                actionBar.style.display = 'none';
            }

            if (table.status === 'finished') {
                 gameMessage.innerText = "MANO FINITA! Ricarica...";
                 setTimeout(() => location.reload(), 4000);
            }
        }

    } catch(e) { console.error(e); }
}

setInterval(fetchGameState, 2000);
fetchGameState();