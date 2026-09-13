<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/mailer.php");
require_once("../core/site/verification.php");

$sent = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!empty($email)) {
        send_password_reset_email_by_address($email);
        $sent = true;
    }
}
?>
<?php require("header.php"); ?>

<div class="center-container">
    <div class="box standalone">
        <h4>Password dimenticata?</h4>
        <?php if ($sent): ?>
            <p>Se l'indirizzo è registrato, ti abbiamo inviato un'e-mail con le istruzioni per reimpostare la password.</p>
            <p><a href="login.php">Torna al login</a></p>
        <?php else: ?>
            <p>Inserisci il tuo indirizzo e-mail: ti manderemo un link per reimpostare la password.</p>
            <form action="" method="post">
                <?= csrf_field() ?>
                <input type="email" name="email" placeholder="E-Mail" required autofocus>
                <button type="submit">Invia il link di reset</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require("footer.php"); ?>
