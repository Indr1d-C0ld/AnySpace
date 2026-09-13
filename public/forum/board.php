<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/forum.php");

login_check();

$boardId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$board = fetchBoard($boardId);

if (!$board) {
    header("Location: index.php");
    exit;
}

$threads = fetchThreadsByBoard($boardId);
?>
<?php require("forum-header.php"); ?>

<div class="simple-container">
    <p><a href="index.php">&laquo; Torna al Forum</a></p>
    <h1><?= htmlspecialchars($board['name']) ?></h1>
    <p><?= htmlspecialchars($board['description']) ?></p>
    <p>[<a href="newthread.php?board=<?= $boardId ?>">Apri una nuova Discussione</a>]</p>

    <?php if (empty($threads)): ?>
        <p><i>Nessuna discussione ancora. Sii il primo!</i></p>
    <?php else: ?>
        <table class="bulletin-table">
            <thead>
                <tr>
                    <th scope="col">Discussione</th>
                    <th scope="col">Autore</th>
                    <th scope="col">Risposte</th>
                    <th scope="col">Ultima Attività</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($threads as $thread): ?>
                    <tr>
                        <td>
                            <?php if ($thread['pinned']): ?><b>[in evidenza]</b> <?php endif; ?>
                            <a href="thread.php?id=<?= $thread['id'] ?>"><?= htmlspecialchars($thread['title']) ?></a>
                        </td>
                        <td><a href="../profile.php?id=<?= $thread['author'] ?>"><?= htmlspecialchars(fetchName($thread['author'])) ?></a></td>
                        <td><?= (int) $thread['reply_count'] ?></td>
                        <td><time class="ago"><?= time_elapsed_string($thread['last_post_at']) ?></time></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require("../footer.php"); ?>
