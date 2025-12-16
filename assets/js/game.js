// variabili tavolo e utente
const tableId = currentTableId;
const myUserId = currentUserId;

// elementi DOM
const dealerDiv = document.getElementById('dealer-cards');
const dealerScoreDiv = document.getElementById('dealer-score');
const playersDiv = document.getElementById('players-area');
const actionBar = document.getElementById('action-bar');

const hitButton = document.querySelector('.btn-hit');
const standButton = document.querySelector('.btn-stand');

// funzione per trasformare carta in immagine
function cardToImage(card) {
    const suits = { 'H': 'hearts', 'D': 'diamonds', 'C': 'clubs', 'S': 'spades' };
    let value = card.slice(0, -1);
    const suit = suits[card.slice(-1)];

    if (value === 'A') value = '01';
    else if (value === 'J') value = '11';
    else if (value === 'Q') value = '12';
    else if (value === 'K') value = '13';
    else value = value.padStart(2,'0');

    return `assets/img/cards/card_${suit}_${value}.png`;
}

// funzione per disegnare le carte in un div
function renderCards(container, cards) {
    container.innerHTML = '';
    cards.forEach(card => {
        const img = document.createElement('img');
        img.src = cardToImage(card);
        img.alt = card;
        img.classList.add('card');
        container.appendChild(img);
    });
}

// mostra le carte del dealer
function renderDealer(table) {
    dealerDiv.innerHTML = '';
    table.dealer_hand.forEach((card, index) => {
        const img = document.createElement('img');
        if (index === 0 && table.status === 'playing') img.src = 'assets/img/cards/back.png';
        else img.src = cardToImage(card);
        img.classList.add('card');
        dealerDiv.appendChild(img);
    });
    dealerScoreDiv.textContent = table.status === 'playing' ? 'Punti: ?' : 'Punti visibili';
}

// mostra i giocatori
function renderPlayers(players, turnPlayerId) {
    playersDiv.innerHTML = '';
    players.forEach(p => {
        const div = document.createElement('div');
        div.classList.add('player-box');
        if (p.user_id === turnPlayerId) div.classList.add('player-turn');
        if (p.is_me) div.classList.add('player-me');

        div.innerHTML = `<h4>${p.username}</h4><div class="hand" id="hand-${p.user_id}"></div><div class="player-status">${p.status ?? ''}</div>`;
        playersDiv.appendChild(div);

        const handDiv = document.getElementById(`hand-${p.user_id}`);
        renderCards(handDiv, p.hand);
    });
}

// mostra o nasconde barra azioni
function handleActionBar(turnPlayerId, currentUserId) {
    actionBar.style.display = (turnPlayerId === currentUserId) ? 'block' : 'none';
}

// invia azione al server
async function doAction(action) {
    try {
        const response = await fetch('api/do_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table_id: tableId, action })
        });
        const data = await response.json();
        if (data.error) alert(data.error);
        fetchGameState();
    } catch(err) {
        console.error('Errore doAction:', err);
    }
}

// aggiorna stato tavolo
async function fetchGameState() {
    try {
        const res = await fetch(`api/get_state.php?table_id=${tableId}`);
        const data = await res.json();
        if (data.error) return;

        renderDealer(data.table);
        renderPlayers(data.players, data.table.turn_player_id);
        handleActionBar(data.table.turn_player_id, data.current_user_id);
    } catch(err) {
        console.error('Errore fetchGameState:', err);
    }
}

// polling automatico
setInterval(fetchGameState, 2000);
fetchGameState();
