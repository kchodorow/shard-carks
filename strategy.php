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
  $strategy->addCard($move);
}

$_SESSION['move']++;
$_SESSION['chunkCounts'] = Chunk::$countPerPlayer;

?>

<html>
 <title>Sharding Game</title>
 <body>
  <h2>Current strategy: <?php echo $strategy->__toString(); ?></h2>
  <form method="get">
   <input type="submit" value="Next Move"/>
  </form>

<?php
  if ($move >= 0) {
?>
<p>Dealer deals a <?php drawCard(new Card($move)); ?></p>
<?php
  }
?>
  
  <table>
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

  <form method="post">
   Or start a new game:
   <input type="radio" name="strategy" value="asc"/>Ascending
   <input type="radio" name="strategy" value="rand"/>Random
   <input type="radio" name="strategy" value="combo"/>Combo
   <input type="submit" name="action" value="Go"/>
  </form>
  
 </body>
</html>

