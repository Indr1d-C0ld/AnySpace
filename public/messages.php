<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/user.php");
require_once("../core/site/message.php");

login_check();

$userId = $_SESSION['userId'];
$pager = paginate(countInbox($userId));
$conversations = fetchInbox($userId, $pager['per_page'], $pager['offset']);
?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Posta</h1>
    <p>[<a href="conversation.php">Nuovo Messaggio</a>]</p>
    <?php if (empty($conversations)): ?>
        <p><i>Non hai ancora nessun messaggio.</i></p>
    <?php else: ?>
        <table class="comments-table" cellspacing="0" cellpadding="3" bordercolor="ffffff" border="1">
            <thead>
                <tr>
                    <th scope="col">Da</th>
                    <th scope="col">Ultimo Messaggio</th>
                    <th scope="col">Quando</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conversations as $conv): ?>
                    <?php
                    $otherName = fetchName($conv['other_id']);
                    $preview = $conv['last_msg'];
                    if (mb_strlen($preview) > 60) {
                        $preview = mb_substr($preview, 0, 60) . '...';
                    }
                    ?>
                    <tr<?= $conv['unread'] > 0 ? ' style="font-weight:bold;"' : '' ?>>
                        <td><a href="profile.php?id=<?= $conv['other_id'] ?>"><?= htmlspecialchars($otherName) ?></a></td>
                        <td><a href="conversation.php?id=<?= $conv['other_id'] ?>"><?= htmlspecialchars($preview) ?></a></td>
                        <td><time class="ago"><?= time_elapsed_string($conv['last_date']) ?></time></td>
                        <td><?php if ($conv['unread'] > 0): ?><span class="count"><?= $conv['unread'] ?></span> nuovi<?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= pagination_links($pager) ?>
    <?php endif; ?>
</div>

<?php require("footer.php"); ?>
