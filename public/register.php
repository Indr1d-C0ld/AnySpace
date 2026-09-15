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

    $ipKey = rate_limit_client_ip();
    if (!rate_limit_check('register', $ipKey)) {
        $message = "<small>" . htmlspecialchars(rate_limit_message(rate_limit_retry_after('register', $ipKey))) . "</small>";
    } elseif (!empty($_POST['password']) && !empty($_POST['username']) && !empty($_POST['confirm'])) {
        $username = trim($_POST['username']);
        $email = trim((string) (isset($_POST['email']) ? $_POST['email'] : ''));

        // Prima si controllavano solo lunghezza del nome e corrispondenza
        // delle password: nessuna validazione vera dell'indirizzo
        // (FILTER_SANITIZE_EMAIL ripulisce ma non valida) e nessuna lunghezza
        // minima per la password.
        if ($_POST['password'] !== $_POST['confirm']) {
            $message = "<small>Le password non coincidono.</small>";
        } elseif (mb_strlen($username) > 21) {
            $message = "<small>Il nome utente non può superare i 21 caratteri.</small>";
        } elseif (strlen($_POST['password']) < 8) {
            $message = "<small>La password deve avere almeno 8 caratteri.</small>";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "<small>L'indirizzo e-mail non è valido.</small>";
        } else {
            // Il controllo dei duplicati confrontava i valori GREZZI mentre
            // l'INSERT ne salvava una versione normalizzata: per un nome con
            // caratteri speciali il controllo non trovava nulla, l'INSERT
            // violava l'indice UNIQUE e l'eccezione PDO non gestita si
            // presentava all'utente come errore 500.
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute(array($email, $username));
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

                // Il nome utente si salva GREZZO e si scappa in stampa (vedi
                // audit: prima veniva salvato già passato per htmlspecialchars,
                // e poi ri-scappato a video, con doppio escaping su apostrofi
                // e "&" — oltre a divergere da manage.php, che lo salvava
                // grezzo, aprendo una XSS).
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, date, interests) VALUES (?, ?, ?, NOW(), ?)");
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

                try {
                    $stmt->execute(array($username, $email, $password, $jsonInterests));
                } catch (PDOException $e) {
                    // Corsa fra due registrazioni simultanee: l'indice UNIQUE
                    // regge, ma l'utente deve vedere un messaggio, non un 500.
                    error_log('Registrazione fallita: ' . $e->getMessage());
                    $message = "<small>Registrazione non riuscita: nome utente o e-mail già in uso.</small>";
                    $emailcheck = false;
                }
            }

            if ($emailcheck) {
                $newUserId = $conn->lastInsertId();

                rate_limit_hit('register', $ipKey, 5, 3600, 3600);
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