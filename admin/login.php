<?php
require("../core/conn.php");
require_once("../core/settings.php");
require("../lib/password.php"); // compatibility library for PHP 5.3

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'login') {
        csrf_verify();

        // Sanitize input
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        // Prepare SQL statement for login
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->execute(array($email));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $isAdmin = ($user && $user['id'] == $adminUser);

        if ($user && $isAdmin && password_verify($password, $user['password'])) {
            regenerate_session();
            $_SESSION['user'] = $user['username'];
            $_SESSION['userId'] = $user['id'];
            recordSession($user['id'], $user['username']);

            header("Location: index.php");
            exit;
        } else {
            echo '<p>Le informazioni di accesso non esistono, la password è errata, oppure l\'utente non ha i permessi necessari.</p><hr>';
        }
    }
}
?>
<?php require_once("header.php") ?>
            <div class="center-container">
                <div class="box standalone">
                    <!-- Login/Signup Form -->
                    <h4>Accedi per continuare.</h4>
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
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                    <p><small>Password amministratore dimenticata? Usa <code>php core/tools/resetAdminPass.php</code> da riga di comando sul server.</small></p>
                </div>
            </div>
        </main>
    </div>
</body>

</html>