<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/forum.php");

login_check();

$boards = fetchBoards();
?>
<?php require("forum-header.php"); ?>

<div class="simple-container">
    <h1>Forum</h1>
    <?php if (empty($boards)): ?>
        <p><i>Nessuna bacheca ancora.</i></p>
    <?php else: ?>
        <table class="bulletin-table">
            <thead>
                <tr>
                    <th scope="col">Bacheca</th>
                    <th scope="col">Discussioni</th>
                    <th scope="col">Messaggi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($boards as $board): ?>
                    <tr>
                        <td>
                            <a href="board.php?id=<?= $board['id'] ?>"><b><?= htmlspecialchars($board['name']) ?></b></a>
                            <br><small><?= htmlspecialchars($board['description']) ?></small>
                        </td>
                        <td><?= (int) $board['thread_count'] ?></td>
                        <td><?= (int) $board['post_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require("../footer.php"); ?>
