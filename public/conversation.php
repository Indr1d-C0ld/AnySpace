<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/user.php");
require_once("../core/site/friend.php");
require_once("../core/site/message.php");

login_check();

$userId = $_SESSION['userId'];
$otherId = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['to']) ? (int) $_POST['to'] : 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && !empty($_POST['message']) && $otherId) {
    csrf_verify();
    sendMessage($userId, $otherId, $_POST['message']);
    header("Location: conversation.php?id=$otherId");
    exit;
}

$otherInfo = $otherId ? fetchUserInfo($otherId) : null;

if ($otherId && $otherInfo) {
    markConversationRead($userId, $otherId);
    $thread = fetchConversation($userId, $otherId);
} else {
    $thread = array();
    $friendsList = array_merge(
        fetchFriends($conn, 'ACCEPTED', 'receiver', $userId),
        fetchFriends($conn, 'ACCEPTED', 'sender', $userId)
    );
}
?>
<?php require("header.php"); ?>

<div class="simple-container">
    <p><a href="messages.php">&laquo; Torna alla Posta</a></p>

    <?php if ($otherId && $otherInfo): ?>
        <h1>Conversazione con <?= htmlspecialchars($otherInfo['username']) ?></h1>
        <div class="comments">
            <?php if (empty($thread)): ?>
                <p><i>Nessun messaggio ancora. Scrivi il primo!</i></p>
            <?php else: ?>
                <?php foreach ($thread as $msg): ?>
                    <div class="comment-reply">
                        <p><b><?= $msg['author'] == $userId ? 'Tu' : htmlspecialchars($otherInfo['username']) ?>:</b>
                            <?= htmlspecialchars($msg['msg']) ?></p>
                        <p><small><time class="ago"><?= time_elapsed_string($msg['date']) ?></time></small></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <form method="post" class="ctrl-enter-submit">
            <?= csrf_field() ?>
            <input type="hidden" name="to" value="<?= $otherId ?>">
            <textarea class="big_textarea" name="message" required autofocus placeholder="Scrivi un messaggio..."></textarea>
            <button type="submit" name="submit">Invia</button>
        </form>
    <?php else: ?>
        <h1>Nuovo Messaggio</h1>
        <?php if (empty($friendsList)): ?>
            <p><i>Devi avere almeno un amico per poter scrivere un messaggio.</i></p>
        <?php else: ?>
            <form method="get">
                <label for="id">Scegli un amico:</label>
                <select name="id" id="id" required>
                    <option value="" disabled selected>Scegli...</option>
                    <?php foreach ($friendsList as $friend): ?>
                        <?php $friendId = $friend['sender'] == $userId ? $friend['receiver'] : $friend['sender']; ?>
                        <option value="<?= $friendId ?>"><?= htmlspecialchars(fetchName($friendId)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Scrivi</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require("footer.php"); ?>
