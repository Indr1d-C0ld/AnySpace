<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/verification.php");
require("../lib/password.php");

$token = isset($_GET['token']) ? trim($_GET['token']) : (isset($_POST['token']) ? trim($_POST['token']) : '');
$validUserId = check_reset_token($token);
$message = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validUserId) {
    csrf_verify();
    $newPassword = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (empty($newPassword) || $newPassword !== $confirm) {
        $message = 'Le password non coincidono.';
    } else {
        reset_password_with_token($token, $newPassword);
        $done = true;
    }
}
?>
<?php require("header.php"); ?>

<div class="center-container">
    <div class="box standalone">
        <h4>Reimposta Password</h4>
        <?php if ($done): ?>
            <p>Password aggiornata. Ora puoi accedere.</p>
            <p><a href="login.php">Vai al login</a></p>
        <?php elseif (!$validUserId): ?>
            <p>Il link di reset non è valido o è scaduto.</p>
            <p><a href="reset.php">Richiedi un nuovo link</a></p>
        <?php else: ?>
            <?php if ($message): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>
            <form action="" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="password" name="password" placeholder="Nuova Password" required autofocus><br><br>
                <input type="password" name="confirm" placeholder="Conferma Password" required><br><br>
                <button type="submit">Imposta la nuova password</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require("footer.php"); ?>
