<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/user.php");
require_once("../core/mailer.php");
require_once("../core/site/verification.php");

admin_check();

require("../core/config.php");

$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user = fetchUserInfo($userId);

if (!$user) {
    header("Location: users.php");
    exit;
}

$actionMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['resend_verification'])) {
        resend_verification_email($userId);
        $actionMessage = "E-mail di verifica reinviata.";
    } elseif (isset($_POST['manually_verify'])) {
        $stmt = $conn->prepare("UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = ?");
        $stmt->execute(array($userId));
        $actionMessage = "Utente verificato manualmente.";
    } elseif (isset($_POST['ban_user'])) {
        $stmt = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
        $stmt->execute(array($userId));
        $actionMessage = "Utente bannato.";
    } elseif (isset($_POST['unban_user'])) {
        $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
        $stmt->execute(array($userId));
        $actionMessage = "Utente sbannato.";
    } elseif (isset($_POST['send_password_reset'])) {
        send_password_reset_email_by_address($user['email']);
        $actionMessage = "E-mail di reset password inviata.";
    } elseif (isset($_POST['change_password'])) {
        $newPassword = $_POST['new_password'];
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        changePassword($userId, $hashedPassword);
        $actionMessage = "Password cambiata con successo.";
    }
    $user = fetchUserInfo($userId);
}
?>

<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Modifica Utente: <?php echo htmlspecialchars(fetchName($userId)); ?></h1>

    <?php if ($actionMessage): ?>
        <div class="alert"><?php echo $actionMessage; ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <?= csrf_field() ?>
        <h2>Informazioni Utente</h2>
        <p>Nome Utente: <?php echo htmlspecialchars(fetchName($userId)); ?></p>
        <p>Email: <?php echo htmlspecialchars(fetchEmail($userId)); ?></p>
        <p>Foto Profilo: <img src="<?php echo htmlspecialchars(fetchPFP($userId)); ?>" alt="Foto Profilo" style="width: 50px; height: 50px;"></p>
        <p>Email confermata: <?= !empty($user['email_verified_at']) ? 'Sì (' . htmlspecialchars($user['email_verified_at']) . ')' : 'No' ?></p>

        <h2>Azioni Amministratore</h2>
        <button type="submit" name="resend_verification">Reinvia E-mail di Verifica</button>
        <button type="submit" name="manually_verify">Verifica Manualmente l'Account</button>

        <?php if (!empty($user['is_banned'])): ?>
            <button type="submit" name="unban_user">Sbanna Utente</button>
        <?php else: ?>
            <button type="submit" name="ban_user">Banna Utente</button>
        <?php endif; ?>

        <button type="submit" name="send_password_reset">Invia E-mail di Reset Password</button>

        <h2>Cambia Password</h2>
        <input type="password" name="new_password" placeholder="Nuova Password" required>
        <button type="submit" name="change_password">Cambia Password</button>
    </form>

    <a href="users.php">Torna alla Lista Utenti</a>
</div>

<?php require("../public/footer.php"); ?>
