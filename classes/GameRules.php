<?php
class GameRules {

    //prende l'array di carte(mano), lo converte in punti e restituisce il risultato
    //formato: ["10H", "AS", "QD", ...]
    public static function calculateScore($hand) {
        $score = 0;
        $aces = 0;

        foreach ($hand as $card) {
            //estrai solo il valore (senza il seme)
            $value = substr($card, 0, -1);

            if (is_numeric($value)) {
                $score += intval($value);
            } else {
                switch ($value) {
                    case 'A':
                        $aces++;
                        $score += 11; //assi inizialmente valgono 11
                        break;
                    case 'J':
                    case 'Q':
                    case 'K':
                        $score += 10;
                        break;
                }
            }
        }

        //se superi 21, cambia gli assi da 11 a 1 se possibile
        while ($score > 21 && $aces > 0) {
            $score -= 10;
            $aces--;
        }

        return $score;
    }


    //ritorna true se hai blackjack
    public static function isBlackJack($hand) {
        return (count($hand) == 2 && self::calculateScore($hand) == 21);
    }


    //dealer AI
    public static function dealerShouldHit($dealerHand) {
        return self::calculateScore($dealerHand) < 17;
    }
}
?>