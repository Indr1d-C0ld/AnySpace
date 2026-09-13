<?php
// Gestione sessioni multiple: una riga per ogni sessione PHP attiva di un
// utente, per poterle elencare in Impostazioni e terminarle da remoto.

function parseUserAgent($ua) {
    $ua = (string) $ua;
    if ($ua === '') {
        return 'Sconosciuto';
    }

    $browser = 'Browser sconosciuto';
    if (stripos($ua, 'Edg/') !== false) {
        $browser = 'Edge';
    } elseif (stripos($ua, 'Chrome/') !== false && stripos($ua, 'Chromium') === false) {
        $browser = 'Chrome';
    } elseif (stripos($ua, 'Firefox/') !== false) {
        $browser = 'Firefox';
    } elseif (stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome') === false) {
        $browser = 'Safari';
    } elseif (stripos($ua, 'Chromium') !== false) {
        $browser = 'Chromium';
    }

    $os = 'sistema sconosciuto';
    if (stripos($ua, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (stripos($ua, 'Android') !== false) {
        $os = 'Android';
    } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
        $os = 'iOS';
    } elseif (stripos($ua, 'Mac OS X') !== false) {
        $os = 'macOS';
    } elseif (stripos($ua, 'Linux') !== false) {
        $os = 'Linux';
    }

    return "$browser su $os";
}

function recordSession($userId, $username) {
    global $conn;
    $sessionId = session_id();
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    // Nota: last_logon si aggiorna SOLO in inserimento — richiamare questa
    // funzione più volte per la stessa sessione (es. per "adottare" sessioni
    // aperte prima che questa funzionalità esistesse) non deve falsare il
    // significato di "da quando sei loggato".
    $stmt = $conn->prepare(
        "INSERT INTO sessions (session_id, user_id, user, user_agent, last_logon, last_activity, active) "
        . "VALUES (?, ?, ?, ?, NOW(), NOW(), 1) "
        . "ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), user = VALUES(user), "
        . "user_agent = VALUES(user_agent), last_activity = NOW(), active = 1"
    );
    $stmt->execute(array($sessionId, $userId, $username, $userAgent));
}

function touchSessionActivity() {
    global $conn;
    $stmt = $conn->prepare("UPDATE sessions SET last_activity = NOW() WHERE session_id = ? AND active = 1");
    $stmt->execute(array(session_id()));
}

/** False solo se la sessione corrente esiste in tabella ed è stata revocata esplicitamente. */
function isCurrentSessionActive() {
    global $conn;
    $stmt = $conn->prepare("SELECT active FROM sessions WHERE session_id = ?");
    $stmt->execute(array(session_id()));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row === false || (int) $row['active'] === 1;
}

function fetchUserSessions($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE user_id = ? AND active = 1 ORDER BY last_activity DESC");
    $stmt->execute(array($userId));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function revokeSession($sessionId, $userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE sessions SET active = 0 WHERE session_id = ? AND user_id = ?");
    return $stmt->execute(array($sessionId, $userId));
}

function revokeOtherSessions($userId, $exceptSessionId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE sessions SET active = 0 WHERE user_id = ? AND session_id != ?");
    return $stmt->execute(array($userId, $exceptSessionId));
}
