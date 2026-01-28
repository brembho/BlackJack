/* assets/js/game.js DEFINITIVO */

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

if(btnPlaceBet) btnPlaceBet.addEventListener('click', placeBet);
if(btnHit) btnHit.addEventListener('click', () => doAction('hit'));
if(btnStand) btnStand.addEventListener('click', () => doAction('stand'));

/* --- IMMAGINI --- */
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

/* --- API --- */
async function placeBet() {
    const amount = document.getElementById('bet-amount').value;
    // Blocca il tasto per evitare doppi click
    btnPlaceBet.disabled = true;
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
    btnPlaceBet.disabled = false;
}

async function doAction(action) {
    try {
        await fetch('api/do_action.php', {
            method: 'POST', body: new URLSearchParams({ 'table_id': currentTableId, 'action': action })
        });
        fetchGameState();
    } catch(err) { console.error(err); }
}

/* --- RENDERING --- */
function renderDealer(table) {
    dealerDiv.innerHTML = '';
    if (table.status === 'betting') {
        dealerScoreDiv.style.display = 'none';
        return;
    }
    dealerScoreDiv.style.display = 'block';
    dealerScoreDiv.innerText = (table.status === 'playing') ? 'Punti: ?' : 'Punti: (Vedi carte)';
    
    if (table.dealer_hand) {
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
    playersDiv.innerHTML = '';
    players.forEach(p => {
        if (p.status === 'betting' && (!p.hand || p.hand.length === 0)) return;
        
        const div = document.createElement('div');
        div.className = 'player-box';
        if (String(p.user_id) === String(turnPlayerId)) div.classList.add('player-turn');

        let statusTxt = `Bet: €${p.bet}`;
        if(p.status === 'won') statusTxt = "VINTO! ";
        if(p.status === 'lost') statusTxt = "PERSO ";
        if(p.status === 'bust') statusTxt = "SBALLATO ";
        if(p.status === 'push') statusTxt = "PAREGGIO ";

        div.innerHTML = `
            <div style="margin-bottom:5px;">${p.username} ${p.is_me ? '(TU)' : ''}</div>
            <div class="hand" id="hand-${p.user_id}"></div>
            <div style="color:gold; font-size:0.9rem;">${statusTxt}</div>
        `;
        playersDiv.appendChild(div);

        if(p.hand) {
            const handC = document.getElementById(`hand-${p.user_id}`);
            p.hand.forEach(c => {
                const img = document.createElement('img');
                img.classList.add('card');
                img.src = getCardImageSrc(c);
                handC.appendChild(img);
            });
        }
    });
}

/* --- GAME LOOP --- */
async function fetchGameState() {
    try {
        const res = await fetch(`api/get_state.php?table_id=${currentTableId}`);
        const data = await res.json();
        if (data.error) return;

        const table = data.table;
        const me = data.players.find(p => String(p.user_id) === String(data.current_user_id));

        // 1. STATO SCOMMESSA
        if (table.status === 'betting') {
            dealerDiv.innerHTML = '';
            gameMessage.innerText = '';
            
            // Mostra il pannello scommesse
            bettingArea.style.display = 'block';
            
            // Se ho già scommesso (sono in 'playing' o altro ma il tavolo è 'betting' - raro) 
            // OPPURE se sono 'betting' (nuovo round)
            if (!me || me.status === 'betting') {
                btnPlaceBet.style.display = 'inline-block';
                waitMessage.style.display = 'none';
            } else {
                // Ho puntato, aspetto start
                btnPlaceBet.style.display = 'none';
                waitMessage.style.display = 'block';
                waitMessage.innerText = "Puntata fatta. Aspetta...";
            }
            actionBar.style.display = 'none';
        } 
        
        // 2. STATO GIOCO
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

        // 3. FINE ROUND E RESET AUTOMATICO
        else if (table.status === 'finished') {
            actionBar.style.display = 'none';
            renderDealer(table);
            renderPlayers(data.players, null);
            
            if (gameMessage.innerText === '') {
                gameMessage.innerText = "MANO CONCLUSA! Prossimo round tra poco";
                // RESET AUTOMATICO
                setTimeout(async () => {
                    await fetch('api/reset_round.php', {
                        method: 'POST', body: new URLSearchParams({ 'table_id': currentTableId })
                    });
                }, 4000);
            }
        }
    } catch(e) { console.error(e); }
}

setInterval(fetchGameState, 2000);
fetchGameState();