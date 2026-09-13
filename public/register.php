<?php
require("../core/conn.php"); // Ensure this returns a PDO connection ($conn)
require_once("../core/settings.php");
require_once("../core/site/friend.php");
require_once("../core/mailer.php");
require_once("../core/site/verification.php");
require("../lib/password.php");

$message = ''; // Variable to hold messages for the user
$registered = false;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();
    if (!empty($_POST['password']) && !empty($_POST['username']) && !empty($_POST['confirm'])) {
        if ($_POST['password'] !== $_POST['confirm'] || strlen($_POST['username']) > 21) {
            $message = "<small>Le password non coincidono, oppure il nome utente è troppo lungo.</small>";
        } else {
            // Check for existing email or username
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $stmt->execute(array($_POST['email'], $_POST['username']));
            if ($stmt->fetch()) {
                $message .= "<small>Esiste già un utente con la stessa email o lo stesso nome utente!</small><br>";
                $emailcheck = false;
            } else {
                $emailcheck = true;
            }

            if ($emailcheck) {
                $interests = array(
                    "General" => "",
                    "Music" => "",
                    "Movies" => "",
                    "Television" => "",
                    "Books" => "",
                    "Heroes" => ""
                );
                $jsonInterests = json_encode($interests);

                $stmt = $conn->prepare("INSERT INTO users (username, email, password, date, interests) VALUES (?, ?, ?, NOW(), ?)");
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $username = htmlspecialchars($_POST['username']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt->execute(array($username, $email, $password, $jsonInterests));

                $newUserId = $conn->lastInsertId();

                autoAddFriend($newUserId);

                if (verification_required()) {
                    send_verification_email($newUserId, $email, $username);
                    $registered = true;
                } else {
                    regenerate_session();
                    $_SESSION['user'] = $username;
                    $_SESSION['userId'] = $newUserId;
                    recordSession($newUserId, $username);
                    header("Location: manage.php");
                    exit;
                }
            }
        }
    } else {
        $message = "<small>Compila tutti i campi richiesti.</small>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/normalize.min.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/style.min.css">
</head>

<body>
    <div class="master-container">
        <?php require("../core/components/navbar.php"); ?>
        <main>
                <h1>Iscriviti</h1>

                <br>
            <div class="center-container">
                <div class="contactInfo">
                    <div class="contactInfoTop">
                        <!-- This is long deprecated - remove asap -->
                        <center>Vantaggi</center>
                    </div>
                    - Fai nuove amicizie!<br>
                    - Parla con le persone!<br>
                    - Senza algoritmi!<br>
                    - Gratis e Open Source
                </div>
                <br>
                <br>
                <?php if ($message)
                    echo $message; ?>
                <?php if ($registered): ?>
                    <p>Account creato! Ti abbiamo inviato un'e-mail di conferma: clicca sul link per attivare l'account e poter accedere.</p>
                    <p><a href="login.php">Vai al login</a></p>
                <?php else: ?>
                <form action="" method="post">
                    <?= csrf_field() ?>
                    <input required placeholder="Nome utente" type="text" name="username"><br>
                    <input required placeholder="E-Mail" type="email" name="email"><br>
                    <input required placeholder="Password" type="password" name="password"><br>
                    <input required placeholder="Conferma Password" type="password" name="confirm"><br><br>
                    <input type="submit" value="Registrati">
                </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>