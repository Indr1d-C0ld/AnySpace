<?php
require_once("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/user.php"); 
require("../core/site/comment.php");

login_check();

$userId = $_SESSION['userId']; //needed by ../core/components/comments_table.php
$toid = isset($_GET['id']) ? (int)$_GET['id'] : 0; 
$pager = paginate(countComments($toid));
$comments = fetchComments($toid, $pager['per_page'], $pager['offset']);

?>

<?php require_once("header.php") ?>

<div class="simple-container">
    <h1>Commenti degli Amici di <?= htmlspecialchars(fetchName($toid)) ?></h1>
    <p><a href="profile.php?id=<?= (int) $toid ?>">&laquo; Torna al Profilo di <?= htmlspecialchars(fetchName($toid)) ?></a></p>
    <br>
    <?php include "../core/components/comments_table.php" ?>
    <?= pagination_links($pager) ?>
</div>

<?php require_once("footer.php") ?>