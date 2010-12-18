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

  // TODO: this would look nicer if new shard was random, not sequential
  public function rebalance() {
    // check for min/max
    $min = -1;
    $minVal = 0;
    $max = 0;
    $maxVal = -1;
    foreach (Chunk::$countPerPlayer as $player => $count) {
      if ($min == -1 || $count < $minVal) {
        $min = $player;
        $minVal = $count;
      }
      if ($count > $maxVal) {
        $max = $player;
        $maxVal = $count;
      }
    }

    // migrate if imbalanced
    if (Chunk::$countPerPlayer[$min]+1 < Chunk::$countPerPlayer[$max]) {
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

  protected function addToChunk($move, $chunk) {
    $card = new Card($move);
    
    if (count($chunk->cards) < 4) {
      $chunk->cards[] = $card;
    }
    else {
      $newChunk = new Chunk($chunk->player);
      $newChunk->setCards(array_slice($chunk->cards, 0, 2));
      $newChunk->cards[] = $card;
      
      $chunk->setCards(array_slice($chunk->cards, 2));
      
      $this->chunks[] = $newChunk;
      
      $this->rebalance();
    }
  }

  abstract public function getChunk($move);
  abstract public function addCard($move);
  abstract public function __toString();
}

class Card {
  private $move;

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

}

class Chunk {
  public static $countPerPlayer;

  // integer, 0 --> num players-1
  public $player;
  
  public $cards;
  
  public function __construct($player) {
    $this->player = $player;

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
}

class Ascending extends Strategy {
  
  public function getChunk($move) {
    $len = count($this->chunks);
    return $this->chunks[$len-1];
  }

  public function addCard($move) {
    $chunk = $this->getChunk($move);
    $this->addToChunk($move, $chunk);
  }

  public function __toString() {
    return "(ascending)";
  }
}

class Random extends Strategy {
  public function getChunk($move) {
    $len = count($this->chunks);
    return $this->chunks[rand()%$len];
  }

  public function addCard($move) {
    $numChunks = count($this->chunks);
    $chunk = $this->chunks[rand(0, $numChunks-1)];
    $this->addToChunk($move, $chunk);
  }

  public function __toString() {
    return "(random)";
  }
}

class CoarseAscendingCombo extends Strategy {
  public function __construct() {
    parent::__construct();
    
    $this->chunks[0]->min = "000000 000000";
    // allows 1,000,000 moves
    $this->chunks[0]->max = "999999 999999";
  }
  
  public function getChunk($move) {
    $possible = array();
    
    foreach ($this->chunks as $chunk) {
      if ($chunk->min <= $move && $chunk->max >= $move) {
        $possible[] = $chunk;
      }
    }

    return $this->chunks[rand(0, count($possible)-1)];
  }
  
  public function addCard($move) {    
    $chunk = $this->getChunk($move);
    $this->addToChunk($move, $chunk);
  }

  protected function addToChunk($move, $chunk) {
    $card = new Card($move);
    
    if (count($chunk->cards) < 4) {
      $chunk->cards[] = $card;
    }
    else {
      // TODO: should be a 50/50 chance of ending up in the new chunk
      $newChunk = new Chunk($chunk->player);
      $newChunk->setCards(array_slice($chunk->cards, 2, 2));
      $newChunk->cards[] = $card;
      $newChunk->min = CoarseAscendingCombo::getOrderingStr($newChunk->cards[0]);
      $newChunk->max = $chunk->max;
      
      $chunk->setCards(array_slice($chunk->cards, 0, 2));
      // keep min
      $chunk->max = CoarseAscendingCombo::getOrderingStr($chunk->cards[1]);
      
      $this->chunks[] = $newChunk;
      
      $this->rebalance();
    }
  }

  private static function getOrderingStr($card) {
    return str_pad($card->getDeck(), 6, "0", STR_PAD_LEFT)
      ." ".str_pad($card->getSuit(), 6, "0", STR_PAD_LEFT);
  }

  public function __toString() {
    return "(coarse-grained ascending, random)";
  }
}

function drawCard($card) {
  $cardNum = $card->getSuit()*13+$card->getCard();
  $cardNum = str_pad($cardNum, 2, "0", STR_PAD_LEFT);
  echo "<img src='images/cards/c_$cardNum.png'/>";
}

function drawCards($cards) {
  echo "<td>";
  foreach ($cards as $card) {
    drawCard($card);
  }
  echo "</td>";
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
    $player[$chunk->player][] = $chunk->cards;
  }

  $more = 4;
  $count = 0;
  while ($more > 0 && $count < 10) {
    echo "<tr>";
    
    $more = 0;
    
    for ($i=0; $i<4; $i++) {
      
      if (!isset($player[$i][$count])) {
        continue;
      }

      $more++;
      drawCards($player[$i][$count]);
    }

    echo "</tr>";
    $count++;
  }
}

?>
