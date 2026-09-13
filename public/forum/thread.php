<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/forum.php");

login_check();

$userId = $_SESSION['userId'];
$threadId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$thread = fetchThread($threadId);

if (!$thread) {
    header("Location: index.php");
    exit;
}

$board = fetchBoard($thread['board_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && !$thread['locked']) {
    csrf_verify();
    createPost($threadId, $userId, $_POST['content'] ?? '');
    header("Location: thread.php?id=$threadId#bottom");
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_post'])) {
    csrf_verify();
    deletePost((int) $_POST['delete_post'], $userId);
    header("Location: thread.php?id=$threadId");
    exit;
}

$posts = fetchPostsByThread($threadId);
?>
<?php require("forum-header.php"); ?>

<div class="simple-container">
    <p><a href="board.php?id=<?= $thread['board_id'] ?>">&laquo; Torna a <?= htmlspecialchars($board['name']) ?></a></p>
    <h1><?= htmlspecialchars($thread['title']) ?></h1>

    <?php foreach ($posts as $post): ?>
        <div class="comment-reply" style="margin-bottom: 10px;">
            <p>
                <b><a href="../profile.php?id=<?= $post['author'] ?>"><?= htmlspecialchars(fetchName($post['author'])) ?></a></b>
                — <time class="ago"><?= time_elapsed_string($post['date']) ?></time>
            </p>
            <p><?= $post['text'] ?></p>
            <?php if ($post['author'] == $userId): ?>
                <form method="post" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="delete_post" value="<?= $post['id'] ?>">
                    <button type="submit">Elimina</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <a name="bottom"></a>
    <?php if ($thread['locked']): ?>
        <p><i>Questa discussione è chiusa: non sono ammesse nuove risposte.</i></p>
    <?php else: ?>
        <h3>Rispondi</h3>
        <form method="post">
            <?= csrf_field() ?>
            <textarea name="content" rows="6" cols="68" required></textarea><br>
            <button type="submit" name="submit">Rispondi</button>
        </form>
    <?php endif; ?>
</div>

<?php require("../footer.php"); ?>
