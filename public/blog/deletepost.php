<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/blog.php");

login_check();

$user = $_SESSION['user'];
$userId = $_SESSION['userId'];

$commentId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$commentId) {
    header("Location: index.php");
    exit;
}

$blogEntry = fetchBlogEntry($_GET['id']);
$authorId = $blogEntry['author'];

$isUserAuthor = ($userId == $authorId);

if (!$isUserAuthor) {
    header("Location: entry.php?id=" . $blogEntry['id']);
    exit;
} else { 
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $confirmation = isset($_POST['confirmation']) ? strtoupper(trim($_POST['confirmation'])) : '';
        if ($confirmation == 'ELIMINA') {
            $commentId = $_GET['id'];
            deleteBlogEntry($commentId, $userId);
            header("Location: user.php?id=" . $userId );
        exit;
    }
}
}
?>
<?php require("blog-header.php"); ?>

<div class="simple-container">
    <h1>Conferma Eliminazione</h1>
    <form method="POST" action="">
        <p>Scrivi "ELIMINA" per confermare l'eliminazione del post:</p>
        <input type="text" name="confirmation" required>
        <button type="submit" name="submit">Elimina</button>
        <button onclick="location.href='entry.php?id=<?= $blogEntry['id'] ?>'; return false;" type="button" name="cancel">Annulla</button>
    </form>
</div>
<?php require("../footer.php"); ?>

</body>

</html>