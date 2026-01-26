<?php
// classes/Deck.php
class Deck {
    private $suits = ['H', 'D', 'C', 'S']; 
    private $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

    // Genera un Sabot da 6 mazzi (312 carte)
    public function generateShoe() {
        $shoe = [];
        for ($i = 0; $i < 6; $i++) {
            foreach ($this->suits as $suit) {
                foreach ($this->values as $value) {
                    $shoe[] = $value . $suit; // Es: "10H"
                }
            }
        }
        shuffle($shoe);
        return $shoe;
    }
}
?>