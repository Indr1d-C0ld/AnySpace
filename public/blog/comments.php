<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require("../../core/site/user.php");
require("../../core/site/comment.php");

if (!isset($_SESSION['userId'])) {
    $userId = null;
} else {
    $userId = $_SESSION['userId'];
}


if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
} else {
    $toid = $_GET['id']; 
}

$pager = paginate(countBlogComments($toid));
$blogComments = fetchBlogComments($toid, $pager['per_page'], $pager['offset']);
$comments = $blogComments;
$commentType = 'blog';
?>

<?php require_once("blog-header.php") ?>

<div class="simple-container">
    <h1>Commenti</h1>
    <p><a href="entry.php?id=<?= $toid ?>">&laquo; Torna al Post del Blog</a></p>
    <br>
    <?php include("../../core/components/comments_table.php"); ?>
    <?= pagination_links($pager) ?>

</div>

<?php require_once("../footer.php") ?>