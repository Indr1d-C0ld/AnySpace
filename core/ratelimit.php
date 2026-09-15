<?php
// Limitazione dei tentativi per gli endpoint sensibili (login, registrazione,
// reset password). Finora erano completamente privi di freno: un attaccante
// poteva provare password all'infinito, e reset.php permetteva di bombardare
// di e-mail un indirizzo registrato.
//
// L'archivio è una tabella dedicata invece della sessione, perché un
// attaccante che non invia il cookie di sessione otterrebbe altrimenti un
// contatore nuovo a ogni tentativo.

/** Crea la tabella al primo utilizzo: nessuna migrazione manuale da ricordare. */
function rate_limit_init() {
    global $conn;
    static $done = false;
    if ($done) {
        return;
    }
    $conn->exec(
        "CREATE TABLE IF NOT EXISTS `rate_limits` ("
        . "`id` int(11) NOT NULL AUTO_INCREMENT,"
        . "`bucket` varchar(64) NOT NULL,"
        . "`identifier` varchar(190) NOT NULL,"
        . "`attempts` int(11) NOT NULL DEFAULT 0,"
        . "`first_attempt` datetime NOT NULL,"
        . "`blocked_until` datetime NULL DEFAULT NULL,"
        . "PRIMARY KEY (`id`),"
        . "UNIQUE KEY `uk_rate_limits` (`bucket`,`identifier`),"
        . "KEY `ix_rate_limits_first` (`first_attempt`)"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $done = true;
}

/** Indirizzo del client. Nessun proxy davanti ad Apache qui: NON ci si fida di X-Forwarded-For. */
function rate_limit_client_ip() {
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * True se la richiesta è consentita, false se il limite è stato superato.
 * Non incrementa nulla: serve solo a decidere se procedere.
 */
function rate_limit_check($bucket, $identifier) {
    global $conn;
    rate_limit_init();

    $stmt = $conn->prepare("SELECT blocked_until FROM rate_limits WHERE bucket = ? AND identifier = ?");
    $stmt->execute(array($bucket, substr($identifier, 0, 190)));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['blocked_until'])) {
        return true;
    }
    return strtotime($row['blocked_until']) <= time();
}

/** Secondi mancanti alla fine del blocco (0 se non bloccato). */
function rate_limit_retry_after($bucket, $identifier) {
    global $conn;
    rate_limit_init();

    $stmt = $conn->prepare("SELECT blocked_until FROM rate_limits WHERE bucket = ? AND identifier = ?");
    $stmt->execute(array($bucket, substr($identifier, 0, 190)));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['blocked_until'])) {
        return 0;
    }
    return max(0, strtotime($row['blocked_until']) - time());
}

/**
 * Registra un tentativo fallito. Superati $maxAttempts dentro $windowSeconds,
 * blocca per $blockSeconds e azzera il conteggio.
 */
function rate_limit_hit($bucket, $identifier, $maxAttempts = 8, $windowSeconds = 900, $blockSeconds = 900) {
    global $conn;
    rate_limit_init();
    $identifier = substr($identifier, 0, 190);

    $stmt = $conn->prepare("SELECT * FROM rate_limits WHERE bucket = ? AND identifier = ?");
    $stmt->execute(array($bucket, $identifier));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $conn->prepare("INSERT INTO rate_limits (bucket, identifier, attempts, first_attempt) VALUES (?, ?, 1, NOW())")
             ->execute(array($bucket, $identifier));
        return;
    }

    // Finestra scaduta: si riparte da capo.
    if (strtotime($row['first_attempt']) < time() - $windowSeconds) {
        $conn->prepare("UPDATE rate_limits SET attempts = 1, first_attempt = NOW(), blocked_until = NULL WHERE id = ?")
             ->execute(array($row['id']));
        return;
    }

    $attempts = (int) $row['attempts'] + 1;
    if ($attempts >= $maxAttempts) {
        $conn->prepare("UPDATE rate_limits SET attempts = 0, first_attempt = NOW(), blocked_until = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE id = ?")
             ->execute(array($blockSeconds, $row['id']));
    } else {
        $conn->prepare("UPDATE rate_limits SET attempts = ? WHERE id = ?")
             ->execute(array($attempts, $row['id']));
    }
}

/** Da chiamare dopo un successo: azzera il contatore di quell'identificatore. */
function rate_limit_clear($bucket, $identifier) {
    global $conn;
    rate_limit_init();
    $conn->prepare("DELETE FROM rate_limits WHERE bucket = ? AND identifier = ?")
         ->execute(array($bucket, substr($identifier, 0, 190)));
}

/** Messaggio uniforme da mostrare all'utente bloccato. */
function rate_limit_message($seconds) {
    $minutes = (int) ceil($seconds / 60);
    return 'Troppi tentativi. Riprova fra circa ' . max(1, $minutes) . ' minuti.';
}
