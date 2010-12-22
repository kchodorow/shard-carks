<?php

$numPlayers = 4;
  
abstract class Strategy {
  public $players;

  /**
   * This is an array of Chunk objects.
   */
  public $chunks;

  public function __construct() {
    $this->chunks = array(new Chunk(0));
  }

  public function getChunk($card) {
    foreach ($this->chunks as $chunk) {
      if ($chunk->min <= $card->criteria && $chunk->max > $card->criteria) {
        return $chunk;
      }
    }

    throw new Exception("Couldn't find a chunk for ".$card->criteria.", shouldn't happen");
  }

  public function rebalance() {
    global $numPlayers;
    
    // check for max
    $max = 0;
    $maxVal = -1;
    foreach (Chunk::$countPerPlayer as $player => $count) {
      if ($count > $maxVal) {
        $max = $player;
        $maxVal = $count;
      }
    }

    // migrate if imbalanced
    for ($i=0; $i<10; $i++) {
      // crappiest balancer ever
      $min = rand(0,$numPlayers-1);
      
      if (Chunk::$countPerPlayer[$min] < Chunk::$countPerPlayer[$max]) {
        foreach ($this->chunks as $chunk) {
          if ($chunk->player == $max) {
            $chunk->player = $min;
            Chunk::$countPerPlayer[$max]--;
            Chunk::$countPerPlayer[$min]++;
            return;
          }
        }
      }
    }
  }

  protected function checkBalance($chunk) {    
    if (count($chunk->cards) >= 4) {
      $this->chunks[] = $chunk->split();      
      $this->rebalance();
    }
  }

  abstract public function addCard($move);
  abstract public function __toString();
}

class Card {
  private $move;
  public $criteria;
  
  public function __construct($move) {
    $this->move = $move;
  }

  public function getDeck() {
    return (int)($this->move / 52) + 1;
  }
  
  public function getSuit() {
    return (int)($this->move / 13) % 4;
  }

  public function getCard() {
    return $this->move % 13;
  }
  
  public function draw() {
    $cardNum = $this->getSuit()*13+$this->getCard();
    $cardNum = str_pad($cardNum, 2, "0", STR_PAD_LEFT);
    echo "<img src='images/cards/c_$cardNum.png'/>";
  }
}

class Chunk {
  public static $countPerPlayer;

  // integer, 0 --> num players-1
  public $player;
  public $cards;
  public $min;
  public $max;
  
  public function __construct($player) {
    $this->player = $player;
    $this->cards = array();
    
    Chunk::$countPerPlayer[$player]++;
  }
  
  public function setCards($cards) {
    $this->cards = $cards;
  }

  public static function setupCounts() {
    global $numPlayers;
    
    for ($i=0; $i<$numPlayers; $i++) {
      Chunk::$countPerPlayer[$i] = 0;
    }
  }

  public function add($card) {
    for ($i=0; $i<count($this->cards); $i++) {
      if ($this->cards[$i]->criteria >= $card->criteria) {
        array_splice($this->cards, $i, 0, array($card));
        return;
      }
    }
    $this->cards[] = $card;
  }
  
  public function split() {
    $newChunk = new Chunk($this->player);
    
    $middle = (int)(count($this->cards)/2);

    // middle to eo array
    $newChunk->setCards(array_slice($this->cards, $middle));
    
    $newChunk->min = $newChunk->cards[0]->criteria;
    $newChunk->max = $this->max;

    // beginning to middle of array
    $this->setCards(array_slice($this->cards, 0, $middle));
    
    // keep min
    $this->max = $newChunk->cards[0]->criteria;

    return $newChunk;
  }

  public function draw() {
    echo "<td><div>".$this->min." &rarr; ".$this->max."</div>";
    foreach ($this->cards as $card) {
      $card->draw();
    }
    echo "</td>";
  }
}

class Ascending extends Strategy {
  public function __construct() {
    parent::__construct();
    
    $this->chunks[0]->min = "000000";
    $this->chunks[0]->max = "999999";
  }
  
  public function addCard($move) {
    $card = new Card($move);
    $card->criteria = $move;
    
    $chunk = $this->getChunk($card);
    $chunk->add($card);

    $this->checkBalance($chunk);
    
    return $card;
  }

  public function __toString() {
    return "(ascending)";
  }
}

class Random extends Strategy {
  public function __construct() {
    parent::__construct();
    
    $this->chunks[0]->min = "000000";
    $this->chunks[0]->max = "999999";
  }

  public function addCard($move) {
    $card = new Card($move);
    $card->criteria = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);

    $chunk = $this->getChunk($card);
    $chunk->add($card);

    $this->checkBalance($chunk);

    return $card;
  }

  public function __toString() {
    return "(random)";
  }
}

class CoarseAscendingCombo extends Strategy {
  public function __construct() {
    parent::__construct();
    
    $this->chunks[0]->min = "000000 000000";
    $this->chunks[0]->max = "999999 999999";
  }
    
  public function addCard($move) {
    $card = new Card($move);
    $card->criteria = str_pad($card->getDeck(), 6, "0", STR_PAD_LEFT)
      ." ".str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);

    $chunk = $this->getChunk($card);
    $chunk->add($card);    

    $this->checkBalance($chunk);

    return $card;
  }

  public function __toString() {
    return "(coarse-grained ascending, random)";
  }
}


function drawTable($chunks) {
  // this is memory-inefficient, but chunks are stored by range and displayed
  // by player.
  global $numPlayers;

  $player = array();
  for ($i=0; $i<$numPlayers; $i++) {
    $player[] = array();
  }
  
  foreach ($chunks as $chunk) {
    $player[$chunk->player][] = $chunk;
  }

  $more = 4;
  $count = 0;
  while ($more > 0 && $count < 10) {
    echo "<tr>";
    
    $more = 0;
    
    for ($i=0; $i<4; $i++) {
      
      if (!isset($player[$i][$count])) {
        echo "<td></td>";
        continue;
      }

      $more++;
      $player[$i][$count]->draw();
    }

    echo "</tr>";
    $count++;
  }
}

?>
