<?php
// Messaggi privati fra utenti.

function sendMessage($fromId, $toId, $text) {
    global $conn;
    $text = strip_tags(trim($text));
    if ($text === '' || $fromId == $toId) {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO messages (toid, author, msg, date) VALUES (?, ?, ?, NOW())");
    return $stmt->execute(array($toId, $fromId, $text));
}

/**
 * Elenco delle conversazioni dell'utente: un rigo per ogni corrispondente,
 * con l'ultimo messaggio e il conteggio dei non letti.
 */
/**
 * Elenco delle conversazioni, una riga per corrispondente.
 *
 * Prima caricava in PHP TUTTI i messaggi dell'utente per poi raggrupparli:
 * con una casella piena significava trasferire l'intera cronologia a ogni
 * apertura della pagina. Il raggruppamento ora lo fa il database, e la
 * pagina ne chiede solo una fetta per volta.
 */
function fetchInbox($userId, $limit = null, $offset = 0) {
    global $conn;
    $sql = "SELECT other_id,"
        . " SUBSTRING_INDEX(GROUP_CONCAT(msg ORDER BY date DESC SEPARATOR 0x1f), 0x1f, 1) AS last_msg,"
        . " MAX(date) AS last_date,"
        . " SUM(CASE WHEN toid = :uid AND read_at IS NULL THEN 1 ELSE 0 END) AS unread"
        . " FROM (SELECT CASE WHEN toid = :uid THEN author ELSE toid END AS other_id, msg, date, toid, read_at"
        . "       FROM messages WHERE toid = :uid OR author = :uid) m"
        . " GROUP BY other_id ORDER BY last_date DESC";
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':uid', (int) $userId, PDO::PARAM_INT);
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Numero di corrispondenti distinti, per il calcolo delle pagine. */
function countInbox($userId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT COUNT(DISTINCT CASE WHEN toid = :uid THEN author ELSE toid END)"
        . " FROM messages WHERE toid = :uid OR author = :uid"
    );
    $stmt->bindValue(':uid', (int) $userId, PDO::PARAM_INT);
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function fetchConversation($userId, $otherId, $limit = 100, $offset = 0) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT * FROM messages WHERE (toid = :userId AND author = :otherId) OR (toid = :otherId AND author = :userId) ORDER BY date ASC LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':userId', (int) $userId, PDO::PARAM_INT);
    $stmt->bindValue(':otherId', (int) $otherId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countConversation($userId, $otherId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM messages WHERE (toid = :userId AND author = :otherId) OR (toid = :otherId AND author = :userId)"
    );
    $stmt->bindValue(':userId', (int) $userId, PDO::PARAM_INT);
    $stmt->bindValue(':otherId', (int) $otherId, PDO::PARAM_INT);
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function markConversationRead($userId, $otherId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE messages SET read_at = NOW() WHERE toid = ? AND author = ? AND read_at IS NULL");
    $stmt->execute(array($userId, $otherId));
}

function countUnreadMessages($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE toid = ? AND read_at IS NULL");
    $stmt->execute(array($userId));
    return (int) $stmt->fetchColumn();
}
