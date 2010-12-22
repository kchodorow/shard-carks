<?php

require_once("game.php");

session_start();

if (isset($_POST['action']) && $_POST['action'] == 'Go') {
  unset($_SESSION['move']);
  unset($_SESSION['strategy']);
  unset($_SESSION['chunkCounts']);
}

$move = -1;
if (!isset($_SESSION['chunkCounts'])) {
  Chunk::setupCounts();
  $_SESSION['chunkCounts'] = Chunk::$countPerPlayer;
}  
if (!isset($_SESSION['move'])) {
  $_SESSION['move'] = $move;
}
if (!isset($_SESSION['strategy'])) {
  if (!isset($_POST['strategy']) || $_POST['strategy'] == "asc") {
    $_SESSION['strategy'] = new Ascending();
  }
  else if ($_POST['strategy'] == "rand") {
    $_SESSION['strategy'] = new Random();
  }
  else if ($_POST['strategy'] == "combo") {
    $_SESSION['strategy'] = new CoarseAscendingCombo();
  }
}

$move = $_SESSION['move'];
$strategy = $_SESSION['strategy'];
Chunk::$countPerPlayer = $_SESSION['chunkCounts'];

if ($move >= 0) {
  $card = $strategy->addCard($move);
}

$_SESSION['move']++;
$_SESSION['chunkCounts'] = Chunk::$countPerPlayer;

?>
<html>
 <head>
  <title>Shard Carks - A MongoDB Sharding Game</title>
  <link href="css/game.css" rel="stylesheet" type="text/css"/>
 </head>
 <body>

  <center>
  
<?php
  if ($move >= 0) {
    echo "To deal the next card, click on";
  }
  else {
    echo "To begin, click on";
  }
?>

   <form method="get">
    <input type="submit" value="Next Move"/>
   </form>
   <form method="post">
    Or use a different strategy:
    <input <?php if ($strategy instanceof Ascending) { echo "checked"; } ?> type="radio" name="strategy" value="asc"/>Ascending
    <input <?php if ($strategy instanceof Random) { echo "checked"; } ?> type="radio" name="strategy" value="rand"/>Random
    <input <?php if ($strategy instanceof CoarseAscendingCombo) { echo "checked"; } ?> type="radio" name="strategy" value="combo"/>Combo
    <input type="submit" name="action" value="Go"/>
   </form>
  </center>

<?php if ($move >= 0) { ?>
   <div class="dealer">
    <b>Dealer deals a </b><?php $card->draw(); ?>
   </div>
<?php } ?>

  <table class="players">
   <tr>
    <th>Player 1</th>
    <th>Player 2</th>
    <th>Player 3</th>
    <th>Player 4</th>
   </tr>

<?php
  drawTable($strategy->chunks);
?>
  
  </table>
  </div>
 </body>
</html>

