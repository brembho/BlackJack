// ID del tavolo corrente, va definito nel PHP di tavolo.php
const tableId = document.getElementById('table-id').value;

// Elementi DOM
const dealerDiv = document.getElementById('dealer-cards');
const playerDiv = document.getElementById('player-cards');
const hitButton = document.getElementById('hit-btn');
const standButton = document.getElementById('stand-btn');
const statusDiv = document.getElementById('game-status');

// Funzione per aggiornare le carte nel DOM
function updateCards(cardsDiv, cardsArray) {
    cardsDiv.innerHTML = ''; // pulisce le carte
    cardsArray.forEach(card => {
        const img = document.createElement('img');
        img.src = `assets/img/cards/${card}.png`;
        img.alt = card;
        img.classList.add('card-img'); //stile in CSS
        cardsDiv.appendChild(img);
    });
}

// Funzione per fare fetch dello stato del tavolo
async function fetchGameState() {
    try {
        const response = await fetch(`api/get_state.php?table_id=${tableId}`);
        const data = await response.json();

        // Aggiorna carte dealer e giocatore
        updateCards(dealerDiv, data.dealer);
        updateCards(playerDiv, data.player);

        // Aggiorna lo status della partita
        statusDiv.textContent = data.status || '';

        // Disabilita bottoni se non è il turno del giocatore
        if (data.current_turn !== data.player_id) {
            hitButton.disabled = true;
            standButton.disabled = true;
        } else {
            hitButton.disabled = false;
            standButton.disabled = false;
        }

    } catch (error) {
        console.error('Errore fetchGameState:', error);
    }
}

// Funzione per inviare azioni al server
async function sendAction(action) {
    try {
        const response = await fetch('api/do_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table_id: tableId, action })
        });
        const data = await response.json();

        if (data.error) {
            alert(data.error);
        } else {
            // Aggiorna immediatamente lo stato dopo la mossa
            fetchGameState();
        }
    } catch (error) {
        console.error('Errore sendAction:', error);
    }
}

// Event listeners
hitButton.addEventListener('click', () => sendAction('hit'));
standButton.addEventListener('click', () => sendAction('stand'));

// Polling automatico ogni 2 secondi
setInterval(fetchGameState, 2000);

// Primo fetch all'apertura della pagina
fetchGameState();
