<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/forum.php");

login_check();

$userId = $_SESSION['userId'];
$boardId = isset($_GET['board']) ? (int) $_GET['board'] : (isset($_POST['board']) ? (int) $_POST['board'] : 0);
$board = fetchBoard($boardId);

if (!$board) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    csrf_verify();
    $threadId = createThread($boardId, $userId, $_POST['title'] ?? '', $_POST['content'] ?? '');
    if ($threadId) {
        header("Location: thread.php?id=$threadId");
        exit;
    }
    $error = 'Titolo e contenuto sono obbligatori.';
}
?>
<?php require("forum-header.php"); ?>

<div class="simple-container">
    <p><a href="board.php?id=<?= $boardId ?>">&laquo; Torna a <?= htmlspecialchars($board['name']) ?></a></p>
    <h1>Nuova Discussione in "<?= htmlspecialchars($board['name']) ?>"</h1>

    <?php if (!empty($error)): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="board" value="<?= $boardId ?>">
        <label for="title">Titolo:</label><br>
        <input type="text" id="title" name="title" style="width:100%; max-width:500px;" required><br><br>
        <label for="content">Messaggio:</label><br>
        <textarea id="content" name="content" rows="10" cols="68" required></textarea><br>
        <button type="submit" name="submit">Pubblica Discussione</button>
    </form>
</div>

<?php require("../footer.php"); ?>
