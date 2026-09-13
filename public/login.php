<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/mailer.php");
require_once("../core/site/verification.php");
require_once("../core/site/user.php");
require("../lib/password.php"); // compatibility library for PHP 5.3

$unverifiedEmail = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'login') {
        csrf_verify();

        // Sanitize input
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        // Prepare SQL statement for login
        $stmt = $conn->prepare("SELECT id, username, password, is_banned FROM users WHERE email = ?");
        $stmt->execute(array($email));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if (!empty($user['is_banned'])) {
                echo '<p>Questo account è stato sospeso.</p><hr>';
            } elseif (verification_required() && !is_email_verified($user['id'])) {
                $unverifiedEmail = $email;
                echo '<p>Devi confermare la tua e-mail prima di poter accedere. Controlla la posta in arrivo.</p><hr>';
            } else {
                regenerate_session();
                $_SESSION['user'] = $user['username'];
                $_SESSION['userId'] = $user['id'];
                updateLastLogon($user['id']);
                recordSession($user['id'], $user['username']);

                header("Location: home.php");
                exit;
            }
        } else {
            echo '<p>Le informazioni di accesso non esistono o la password è errata.</p><hr>';
        }
    } elseif ($_POST['action'] == 'resend') {
        csrf_verify();
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(array($email));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            resend_verification_email($user['id']);
        }
        echo '<p>Se l\'indirizzo è registrato, ti abbiamo inviato una nuova e-mail di conferma.</p><hr>';
    }
}
?>
<?php require_once("header.php") ?>
            <div class="center-container">
                <div class="box standalone">
                    <?php if ($unverifiedEmail): ?>
                    <form action="" method="post" style="margin-bottom: 1em;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="resend">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($unverifiedEmail) ?>">
                        <button type="submit">Invia di nuovo l'e-mail di conferma</button>
                    </form>
                    <?php endif; ?>
                    <!-- Login/Signup Form -->
                    <h4>Accedi o iscriviti per continuare.</h4>
                    <form action="" method="post" name="theForm" id="theForm">
                        <?= csrf_field() ?>
                        <input name="client_id" type="hidden" value="web">
                        <table>
                            <tbody>
                                <tr class="email">
                                    <td class="label"><label for="email">E-Mail:</label></td>
                                    <td class="input"><input type="email" name="email" id="email" autocomplete="email"
                                            value="" required></td>
                                </tr>
                                <tr class="password">
                                    <td class="label"><label for="password">Password:</label></td>
                                    <td class="input"><input name="password" type="password" id="password"
                                            autocomplete="current-password" required></td>
                                </tr>
                                <tr class="remember">
                                    <td></td>
                                    <td>
                                        <input type="checkbox" name="remember" value="yes" id="checkbox">
                                        <label for="checkbox">Ricorda la mia E-mail</label>
                                    </td>
                                </tr>
                                <tr class="buttons">
                                    <td></td>
                                    <td>
                                        <button type="submit" class="login_btn" name="action"
                                            value="login">Accedi</button>
                                            <button type="button" class="signup_btn" onclick="location.href='register.php'" name="action" value="signup">Iscriviti</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                    <a class="forgot" href="<?= BASE_PATH ?>/reset.php">Hai dimenticato la password?</a>
                </div>
            </div>
        </main>
    </div>
</body>

</html>