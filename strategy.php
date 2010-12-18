<?php

require_once("game.php");

session_start();

if (isset($_POST['action']) && $_POST['action'] == 'reset') {
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
  $_SESSION['strategy'] = new Ascending();
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
  <h2>Step <?php echo "$move"; ?></h2>

  <form method="post">
   <input type="hidden" name="move" value="<?php echo '$move'; ?>"/>
   <input type="submit" value="Next Move"/>
  </form>

  <div>
   Dealer
  </div>

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
   <input type="submit" name="action" value="reset"/>
  </form>
  
 </body>
</html>

