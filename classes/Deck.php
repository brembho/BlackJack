<?php

class Deck {
    private $cards = [];

    public function __construct() {
        $this->reset();
    }

    // Crea e mescola il mazzo
    public function reset() {
        $seeds = ['hearts', 'diamonds', 'clubs', 'spades'];
        $this->cards = [];

        foreach ($seeds as $seed) {
            for ($i = 1; $i <= 13; $i++) {
                $value = str_pad($i, 2, '0', STR_PAD_LEFT);
                $this->cards[] = "{$seed}_{$value}";
            }
        }

        shuffle($this->cards);
    }

    //pesca una carta
    public function drawCard() {
        return array_pop($this->cards);
    }

    //ritorna il mazzo (per salvarlo nel DB)
    public function getCards() {
        return $this->cards;
    }

    //carica il mazzo dal DB
    public function loadFromArray(array $cards) {
        $this->cards = $cards;
    }
}
