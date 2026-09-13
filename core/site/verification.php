<?php
// Verifica e-mail e reset password: generazione/controllo token, invio e-mail.

function generate_token() {
    return bin2hex(random_bytes(32));
}

function anyspace_base_url() {
    return 'https://' . DOMAIN_NAME . BASE_PATH;
}

function send_verification_email($userId, $email, $username) {
    global $conn;
    $token = generate_token();

    $stmt = $conn->prepare("UPDATE users SET verification_token = ?, verification_sent_at = NOW() WHERE id = ?");
    $stmt->execute(array($token, $userId));

    $link = anyspace_base_url() . '/verify.php?token=' . $token;
    $subject = 'Conferma la tua email su ' . SITE_NAME;
    $body = "<p>Ciao " . htmlspecialchars($username) . ",</p>"
        . "<p>Benvenuto/a su " . htmlspecialchars(SITE_NAME) . "! Conferma il tuo indirizzo e-mail cliccando sul link qui sotto:</p>"
        . "<p><a href=\"$link\">$link</a></p>"
        . "<p>Se non hai richiesto tu questa registrazione, ignora questo messaggio.</p>";

    return send_mail($email, $subject, $body, true);
}

function verify_email_token($token) {
    global $conn;
    if (empty($token)) {
        return false;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ?");
    $stmt->execute(array($token));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return false;
    }

    $stmt = $conn->prepare("UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = ?");
    $stmt->execute(array($user['id']));

    return $user['id'];
}

function is_email_verified($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT email_verified_at FROM users WHERE id = ?");
    $stmt->execute(array($userId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && $row['email_verified_at'] !== null;
}

function verification_required() {
    $config = anyspace_email_config();
    return !empty($config['require_verification']);
}

/** Restituisce true se è stata inviata (o il rate-limit ha impedito l'invio, comunque non un errore visibile). */
function resend_verification_email($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT username, email, verification_sent_at FROM users WHERE id = ?");
    $stmt->execute(array($userId));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        return false;
    }

    // Non più di un invio ogni 60 secondi per evitare abusi del modulo.
    if (!empty($user['verification_sent_at'])) {
        $lastSent = strtotime($user['verification_sent_at']);
        if ($lastSent !== false && (time() - $lastSent) < 60) {
            return true;
        }
    }

    return send_verification_email($userId, $user['email'], $user['username']);
}

function send_password_reset_email_by_address($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute(array($email));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Risponde sempre "ok" all'utente anche se l'indirizzo non esiste, per non
    // rivelare quali email sono registrate.
    if (!$user) {
        return true;
    }

    $token = generate_token();
    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
    $stmt->execute(array($token, $user['id']));

    $link = anyspace_base_url() . '/resetpassword.php?token=' . $token;
    $subject = 'Reimposta la password su ' . SITE_NAME;
    $body = "<p>Ciao " . htmlspecialchars($user['username']) . ",</p>"
        . "<p>Hai chiesto di reimpostare la tua password su " . htmlspecialchars(SITE_NAME) . ". Il link è valido per un'ora:</p>"
        . "<p><a href=\"$link\">$link</a></p>"
        . "<p>Se non sei stato tu, ignora questo messaggio: la tua password resterà invariata.</p>";

    return send_mail($email, $subject, $body, true);
}

/** Ritorna l'id utente se il token è valido e non scaduto, altrimenti false. */
function check_reset_token($token) {
    global $conn;
    if (empty($token)) {
        return false;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute(array($token));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ? $user['id'] : false;
}

function reset_password_with_token($token, $newPassword) {
    global $conn;
    $userId = check_reset_token($token);
    if (!$userId) {
        return false;
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt->execute(array($hashed, $userId));

    return true;
}
