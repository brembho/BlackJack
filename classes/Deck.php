<?php
class Deck {
    private $cards = [];
    public function __construct() {
        $this->reset();
    }
    //crea il mazzo da zero
    public function reset() {
        $semi = ['H', 'D', 'C', 'S']; // Hearts, Diamonds, Clubs, Spades
        $valori = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        $this->cards = [];
        //costruzione del mazzo: combinazione di tutti i semi con tutti i valori
        foreach ($semi as $seme) {
            foreach ($valori as $valore) {
                $this->cards[] = $valore . $seme;
            }
        }
        shuffle($this->cards);
    }
    //pesca una carta (e la rimuove dal mazzo)
    public function drawCard() {
        if (empty($this->cards)) {
            return null; //mazzo finito ritorna null
        }
        return array_pop($this->cards);
    }

    //numero carte rimaste
    public function remainingCards() {
        return count($this->cards);
    }

    //restituisce lo stato attuale del mazzo sotto forma di array di carte
    //per salvare il mazzo nel DB
    public function getCards() {
        return $this->cards;
    }
    
    //carica il mazzo contenuto nel DB
    //permette di mantenere lo stato del mazzo tra richieste AJAX dei giocatori
    public function loadFromArray($array) {
        $this->cards = $array;
    }
}
?>
