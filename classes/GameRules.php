<?php

class GameRules {

    // calcola il punteggio di una mano di Blackjack
    public static function calculateScore(array $hand) {
        $score = 0;
        $aces  = 0;

        foreach ($hand as $card) {
            // formato carta: seme_valore 
            list(, $value) = explode('_', $card);
            $value = (int)$value;

            if ($value === 1) {        // asso
                $aces++;
                $score += 11;
            } elseif ($value >= 10) { // figure
                $score += 10;
            } else {                  // carte numeriche
                $score += $value;
            }
        }

        // converte gli assi da 11 a 1 se si sballa
        while ($score > 21 && $aces > 0) {
            $score -= 10;
            $aces--;
        }

        return $score;
    }

    // true se la mano è Blackjack (21 con 2 carte)
    public static function isBlackJack(array $hand) {
        return count($hand) === 2 && self::calculateScore($hand) === 21;
    }

    // il banco pesca finché ha meno di 17
    public static function dealerShouldHit(array $dealerHand) {
        return self::calculateScore($dealerHand) < 17;
    }
}
