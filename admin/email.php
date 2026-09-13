<?php
// Site functions
require("../core/conn.php");
require_once("../core/settings.php");

// Page functions
require("../core/site/admin/email.php");

admin_check();

$email_config = get_email_config();


require("header.php");
?>

<div class="simple-container">
    <div class="row edit-profile">
        <div class="col w-20 left">
            <!-- SIDEBAR CONTENT -->
        </div>
        <div class="col right">
            <h1>Impostazioni Email</h1>
            <p>Configura qui le impostazioni email del sito</p>
            <form method="post" class="ctrl-enter-submit">
                <button type="submit" name="submit">Salva Tutto</button>
                <br>
                <label for="category_smtp_host">
                    <h3>Host SMTP:</h3>
                </label>
                <p>Il nome host del tuo server SMTP</p>
                <input type="text" maxlength="65" class="status_input" id="category_smtp_host" name="category[smtp_host]" value="<?= htmlspecialchars($email_config['smtp_host']) ?>">

                <label for="category_smtp_port">
                    <h3>Porta SMTP:</h3>
                </label>
                <p>Il numero di porta del tuo server SMTP</p>
                <input type="text" maxlength="5" class="status_input" id="category_smtp_port" name="category[smtp_port]" value="<?= htmlspecialchars($email_config['smtp_port']) ?>">

                <label for="category_smtp_username">
                    <h3>Nome Utente SMTP:</h3>
                </label>
                <p>Il nome utente per il tuo server SMTP</p>
                <input type="text" maxlength="65" class="status_input" id="category_smtp_username" name="category[smtp_username]" value="<?= htmlspecialchars($email_config['smtp_username']) ?>">

                <label for="category_smtp_password">
                    <h3>Password SMTP:</h3>
                </label>
                <p>La password per il tuo server SMTP</p>
                <input type="password" maxlength="65" class="status_input" id="category_smtp_password" name="category[smtp_password]" value="<?= htmlspecialchars($email_config['smtp_password']) ?>">

                <label for="category_from_email">
                    <h3>Email Mittente:</h3>
                </label>
                <p>L'indirizzo email che apparirà come mittente</p>
                <input type="email" maxlength="65" class="status_input" id="category_from_email" name="category[from_email]" value="<?= htmlspecialchars($email_config['from_email']) ?>">

                <label for="category_require_verification">
                    <h3>Richiedi Verifica:</h3>
                </label>
                <p>Spunta questa casella per richiedere la verifica email ai nuovi account</p>
                <input type="checkbox" id="category_require_verification" name="category[require_verification]" <?= $email_config['require_verification'] ? 'checked' : '' ?>>

                <p></p>
                <button type="submit" name="submit">Salva Tutto</button>
            </form>
            <?php
            if (isset($_GET['status']) && $_GET['status'] === 'success') {
                echo "<p style='color: green;'>Impostazioni email aggiornate con successo.</p>";
            } elseif (isset($error)) {
                echo "<p style='color: red;'>$error</p>";
            }
            ?>
        </div>
    </div>
</div>

<?php require("../public/footer.php"); ?>