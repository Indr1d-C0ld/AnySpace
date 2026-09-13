<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/group.php");

login_check();

$userId = $_SESSION['userId'];
$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$group = fetchGroup($groupId);

if (!$group) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();
    if (isset($_POST['join'])) {
        joinGroup($groupId, $userId);
    } elseif (isset($_POST['leave'])) {
        leaveGroup($groupId, $userId);
    } elseif (isset($_POST['post_wall']) && !empty($_POST['comment'])) {
        postToGroupWall($groupId, $userId, $_POST['comment']);
    }
    header("Location: viewgroup.php?id=$groupId");
    exit;
}

$members = fetchGroupMembers($group);
$isMember = isGroupMember($groupId, $userId);
$wall = fetchGroupWall($groupId);
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($group['name']) ?> | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/normalize.min.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/style.min.css">
</head>

<body>
    <div class="master-container">
        <?php require_once("../../core/components/navbar.php"); ?>
        <main>
            <div class="simple-container">
                <p><a href="index.php">&laquo; Torna ai Gruppi</a></p>
                <h1><?= htmlspecialchars($group['name']) ?></h1>
                <p>Creato da <a href="../profile.php?id=<?= $group['author'] ?>"><?= htmlspecialchars(fetchName($group['author'])) ?></a>
                    — <time class="ago"><?= time_elapsed_string($group['date']) ?></time></p>
                <p><?= htmlspecialchars($group['description']) ?></p>
                <p><b><?= count($members) ?></b> membri:
                    <?php foreach ($members as $memberId): ?>
                        <a href="../profile.php?id=<?= $memberId ?>"><?= htmlspecialchars(fetchName($memberId)) ?></a><?php echo ($memberId !== end($members)) ? ', ' : ''; ?>
                    <?php endforeach; ?>
                </p>

                <form method="post">
                    <?= csrf_field() ?>
                    <?php if ($isMember): ?>
                        <button type="submit" name="leave">Lascia il Gruppo</button>
                    <?php else: ?>
                        <button type="submit" name="join">Unisciti al Gruppo</button>
                    <?php endif; ?>
                </form>

                <hr>
                <h2>Bacheca del Gruppo</h2>
                <?php if ($isMember): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <textarea required rows="4" cols="68" placeholder="Scrivi qualcosa alla bacheca del gruppo..." name="comment"></textarea><br>
                        <button type="submit" name="post_wall">Pubblica</button>
                    </form>
                <?php else: ?>
                    <p><i>Unisciti al gruppo per scrivere sulla bacheca.</i></p>
                <?php endif; ?>
                <br>

                <?php if (empty($wall)): ?>
                    <p><i>Nessun messaggio sulla bacheca ancora.</i></p>
                <?php else: ?>
                    <?php foreach ($wall as $post): ?>
                        <div class="comment-reply" style="margin-bottom: 10px;">
                            <p>
                                <b><a href="../profile.php?id=<?= $post['author'] ?>"><?= htmlspecialchars(fetchName($post['author'])) ?></a></b>
                                — <time class="ago"><?= time_elapsed_string($post['date']) ?></time>
                            </p>
                            <p><?= htmlspecialchars($post['text']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
        <?php require("../footer.php"); ?>
    </div>
</body>

</html>
