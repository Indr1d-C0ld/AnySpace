<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/user.php");
require_once("../core/site/report.php");

login_check();

$userId = $_SESSION['userId'];
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$typeMap = array(
    'user' => REPORT_TYPE_USER,
    'comment' => REPORT_TYPE_COMMENT,
    'blog_comment' => REPORT_TYPE_BLOG_COMMENT,
    'bulletin_comment' => REPORT_TYPE_BULLETIN_COMMENT,
);

if (!$id || !isset($typeMap[$type])) {
    header("Location: index.php");
    exit;
}

$contentType = $typeMap[$type];
$reportedOwnerId = ($contentType === REPORT_TYPE_USER) ? $id : null;

$done = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    csrf_verify();
    createReport($reportedOwnerId ?? 0, $userId, $contentType, $id);
    $done = true;
}
?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Segnala Contenuto</h1>
    <?php if ($done): ?>
        <p>Grazie, la tua segnalazione è stata inviata all'amministrazione.</p>
    <?php else: ?>
        <p>Stai per segnalare: <b><?= htmlspecialchars(report_type_label($contentType)) ?></b> (ID <?= $id ?>).</p>
        <form method="post">
            <?= csrf_field() ?>
            <button type="submit" name="submit">Conferma Segnalazione</button>
        </form>
    <?php endif; ?>
</div>

<?php require("footer.php"); ?>
