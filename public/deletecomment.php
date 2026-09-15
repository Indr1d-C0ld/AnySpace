<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/comment.php");

login_check();

$user = $_SESSION['user'];
$userId = $_SESSION['userId'];

$commentId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$commentId) {
    header("Location: home.php");
    exit;
}

$comment = fetchComment($commentId);

if (!$comment) {
    header("Location: home.php");
    exit;
}

$authorId = $comment['author'];

// Può cancellare chi ha scritto il commento, il proprietario del profilo su
// cui è stato lasciato (moderazione di casa propria) e l'amministratore. Il
// template mostrava già il pulsante "Elimina" al padrone di casa, ma qui
// passava solo l'autore: il pulsante rimandava indietro senza fare nulla.
$isUserAuthor = ($userId == $authorId)
    || ($userId == $comment['toid'])
    || ((int) $userId === (int) ADMIN_USER);

if (!$isUserAuthor) {
    header("Location: comments.php?id=" . $comment['toid']);
    exit;
} else { 
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        csrf_verify();        $confirmation = isset($_POST['confirmation']) ? strtoupper(trim($_POST['confirmation'])) : '';
        if ($confirmation == 'ELIMINA') {
            deleteComment($commentId, $userId);
            header("Location: comments.php?id=" . $comment['toid'] );
        exit;
    }
}
}
?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Conferma Eliminazione</h1>
    <form method="POST" action="">
        <?= csrf_field() ?>
        <p>Scrivi "ELIMINA" per confermare l'eliminazione del commento:</p>
        <input type="text" name="confirmation" required>
        <button type="submit" name="submit">Elimina</button>
        <button onclick="location.href='comments.php?id=<?= $comment['toid'] ?>'; return false;" type="button" name="cancel">Annulla</button>
    </form>
</div>
<?php require("footer.php"); ?>

</body>

</html>