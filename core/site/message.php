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
function fetchInbox($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM messages WHERE toid = ? OR author = ? ORDER BY date DESC");
    $stmt->execute(array($userId, $userId));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $conversations = array();
    foreach ($rows as $row) {
        $otherId = ($row['toid'] == $userId) ? $row['author'] : $row['toid'];
        if (!isset($conversations[$otherId])) {
            $conversations[$otherId] = array(
                'other_id' => $otherId,
                'last_msg' => $row['msg'],
                'last_date' => $row['date'],
                'unread' => 0,
            );
        }
        if ($row['toid'] == $userId && empty($row['read_at'])) {
            $conversations[$otherId]['unread']++;
        }
    }

    return array_values($conversations);
}

function fetchConversation($userId, $otherId, $limit = 100) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT * FROM messages WHERE (toid = :userId AND author = :otherId) OR (toid = :otherId AND author = :userId) ORDER BY date ASC LIMIT :limit"
    );
    $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':otherId', $otherId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
