<?php
// Inviti alla registrazione. Chiude una richiesta presente fin dall'inizio
// del progetto: aprire l'istanza solo ai propri contatti invece che a
// chiunque passi di lì.
//
// I codici li genera soltanto l'amministratore (admin/invites.php). Il
// controllo in registrazione si attiva con la chiave `requireInvite` nella
// configurazione esterna: finché è false la registrazione resta aperta, e
// gli inviti eventualmente creati restano semplicemente inutilizzati.

/** Crea la tabella al primo utilizzo, come per rate_limits: nessuna migrazione da ricordare. */
function invite_init() {
    global $conn;
    static $done = false;
    if ($done) {
        return;
    }
    $conn->exec(
        "CREATE TABLE IF NOT EXISTS `invites` ("
        . "`id` int(11) NOT NULL AUTO_INCREMENT,"
        . "`code` varchar(32) NOT NULL,"
        . "`created_by` int(11) NOT NULL,"
        . "`created_at` datetime NOT NULL,"
        . "`note` varchar(255) NOT NULL DEFAULT '',"
        . "`expires_at` datetime NULL DEFAULT NULL,"
        . "`used_by` int(11) NULL DEFAULT NULL,"
        . "`used_at` datetime NULL DEFAULT NULL,"
        . "`revoked` tinyint(1) NOT NULL DEFAULT 0,"
        . "PRIMARY KEY (`id`),"
        . "UNIQUE KEY `uk_invites_code` (`code`),"
        . "KEY `ix_invites_used_by` (`used_by`)"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $done = true;
}

/** True se la registrazione richiede un codice di invito. */
function invite_required() {
    return defined('REQUIRE_INVITE') && REQUIRE_INVITE;
}

/**
 * Genera un codice leggibile ad alta voce: niente 0/O e 1/I/L, che si
 * confondono quando il codice viene dettato o trascritto a mano.
 */
function invite_generate_code() {
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $max = strlen($alphabet) - 1;
    $parts = array();
    for ($group = 0; $group < 3; $group++) {
        $chunk = '';
        for ($i = 0; $i < 4; $i++) {
            $chunk .= $alphabet[random_int(0, $max)];
        }
        $parts[] = $chunk;
    }
    return implode('-', $parts); // es. K7PQ-2M4X-TRWD
}

/**
 * Crea $count inviti. $expiresDays = 0 significa "non scade".
 * Restituisce i codici creati.
 */
function invite_create($adminId, $count = 1, $note = '', $expiresDays = 30) {
    global $conn;
    invite_init();

    $count = max(1, min(50, (int) $count));
    $note = trim(strip_tags((string) $note));
    if (mb_strlen($note) > 255) {
        $note = mb_substr($note, 0, 255);
    }
    $expiresDays = max(0, min(3650, (int) $expiresDays));

    $created = array();
    for ($i = 0; $i < $count; $i++) {
        // Ritenta in caso di collisione sull'indice UNIQUE: improbabile con
        // 31^12 combinazioni, ma non va lasciato al caso.
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = invite_generate_code();
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO invites (code, created_by, created_at, note, expires_at) "
                    . "VALUES (?, ?, NOW(), ?, " . ($expiresDays > 0 ? "DATE_ADD(NOW(), INTERVAL ? DAY)" : "NULL") . ")"
                );
                $params = array($code, $adminId, $note);
                if ($expiresDays > 0) {
                    $params[] = $expiresDays;
                }
                $stmt->execute($params);
                $created[] = $code;
                break;
            } catch (PDOException $e) {
                if ($attempt === 4) {
                    error_log('invite_create: impossibile generare un codice univoco — ' . $e->getMessage());
                }
            }
        }
    }
    return $created;
}

/** Normalizza ciò che l'utente digita: spazi, minuscole, trattini mancanti. */
function invite_normalize($code) {
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    if (strlen($code) === 12) {
        $code = substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
    }
    return $code;
}

/**
 * Verifica un codice SENZA consumarlo (per mostrare un errore utile nel
 * modulo). Restituisce la riga dell'invito oppure false.
 */
function invite_lookup($code) {
    global $conn;
    invite_init();

    $code = invite_normalize($code);
    if ($code === '') {
        return false;
    }
    $stmt = $conn->prepare("SELECT * FROM invites WHERE code = ?");
    $stmt->execute(array($code));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : false;
}

/** Motivo per cui un invito non è spendibile, o '' se è valido. */
function invite_problem($invite) {
    if (!$invite) {
        return 'Codice di invito non valido.';
    }
    if (!empty($invite['revoked'])) {
        return 'Questo invito è stato annullato.';
    }
    if (!empty($invite['used_by'])) {
        return 'Questo invito è già stato utilizzato.';
    }
    if (!empty($invite['expires_at']) && strtotime($invite['expires_at']) < time()) {
        return 'Questo invito è scaduto.';
    }
    return '';
}

/**
 * Consuma l'invito assegnandolo a $userId. L'UPDATE è condizionato allo
 * stato "ancora libero": due registrazioni simultanee con lo stesso codice
 * non possono riuscire entrambe, perché solo una vedrà `used_by IS NULL`.
 * Restituisce true se l'invito è stato effettivamente assegnato.
 */
function invite_claim($code, $userId) {
    global $conn;
    invite_init();

    $stmt = $conn->prepare(
        "UPDATE invites SET used_by = ?, used_at = NOW() "
        . "WHERE code = ? AND used_by IS NULL AND revoked = 0 "
        . "AND (expires_at IS NULL OR expires_at > NOW())"
    );
    $stmt->execute(array($userId, invite_normalize($code)));
    return $stmt->rowCount() === 1;
}

function invite_revoke($id) {
    global $conn;
    invite_init();
    // Un invito già speso non si annulla: l'account esiste ormai, e
    // marcarlo revocato darebbe un resoconto fuorviante.
    $stmt = $conn->prepare("UPDATE invites SET revoked = 1 WHERE id = ? AND used_by IS NULL");
    $stmt->execute(array($id));
    return $stmt->rowCount() > 0;
}

function invite_delete_spent() {
    global $conn;
    invite_init();
    return (int) $conn->exec(
        "DELETE FROM invites WHERE revoked = 1 OR (expires_at IS NOT NULL AND expires_at <= NOW() AND used_by IS NULL)"
    );
}

function invite_count() {
    global $conn;
    invite_init();
    return (int) $conn->query("SELECT COUNT(*) FROM invites")->fetchColumn();
}

function invite_list($limit = 30, $offset = 0) {
    global $conn;
    invite_init();
    $stmt = $conn->prepare("SELECT * FROM invites ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Etichetta di stato per l'elenco in amministrazione. */
function invite_status_label($invite) {
    if (!empty($invite['used_by'])) {
        return 'usato';
    }
    if (!empty($invite['revoked'])) {
        return 'annullato';
    }
    if (!empty($invite['expires_at']) && strtotime($invite['expires_at']) < time()) {
        return 'scaduto';
    }
    return 'disponibile';
}

/** Indirizzo da consegnare all'invitato: il codice arriva già compilato. */
function invite_url($code) {
    return 'https://' . DOMAIN_NAME . BASE_PATH . '/register.php?invite=' . urlencode($code);
}
