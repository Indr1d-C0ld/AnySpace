<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/verification.php");

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$userId = verify_email_token($token);
$success = $userId !== false;
?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Conferma E-mail</h1>
    <?php if ($success): ?>
        <p>Il tuo indirizzo e-mail è stato confermato. Ora puoi accedere.</p>
        <p><a href="login.php">Vai al login</a></p>
    <?php else: ?>
        <p>Il link di conferma non è valido o è già stato usato.</p>
        <p><a href="login.php">Torna al login</a></p>
    <?php endif; ?>
</div>

<?php require("footer.php"); ?>
