<?php

$numPlayers = 4;
  
class Strategy {
  public $players;

  /**
   * This is an array of Chunk objects.
   */
  public $chunks;

  public function __construct() {
    $this->chunks = array(new Chunk(0));
  }
  
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
}

class Card {
  private $deck;
  private $suit;
  private $card;

  public function __construct($move) {
    $this->deck = Card::getDeck($move);
    $this->suit = Card::getSuit($move);
    $this->card = Card::getCard($move);
  }

  public function __toString() {
    $str = $this->deck." ";
    switch ($this->suit) {
    case 0:
      $str .= "spades ";
      break;
    case 1:
      $str .= "hearts ";
      break;
    case 2:
      $str .= "diamonds ";
      break;
    case 3:
      $str .= "clubs ";
      break;
    }
    
    $str .= $this->card;
    return $str;
  }
  
  public static function getDeck($move) {
    return (int)($move / 52) + 1;
  }
  
  public static function getSuit($move) {
    return (int)($move / 13) % 4;
  }

  public static function getCard($move) {
    return $move % 13;
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
  
  public function getChunk() {
    $len = count($this->chunks);
    return $this->chunks[$len-1];
  }

  public function getPlayer() {
    $chunk = getChunk();
    return $chunk['player'];
  }
  public function addCard($move) {
    $card = new Card($move);

    $chunk = $this->getChunk();
    
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
}

class Random extends Strategy {
  public function getChunk() {
    $len = count($this->chunks);
    return $this->chunks[rand()%$len];
  }
}

class CoarseAscendingCombo extends Strategy {
  public function getChunk($deck, $suit, $card) {
    foreach ($this->chunks as $chunk) {
      if ($chunk['deck'] == $deck &&
          $chunk['suit'] == $suit &&
          rand() % $this->chunksInSuit[$suit] == 0) {
        return $chunk;
      }
    }
  }
}

function drawCard($card) {
  echo " $card ";
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
