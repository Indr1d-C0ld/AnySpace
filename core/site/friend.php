<?php
// Include database connection and settings once

function autoAddFriend($newUserId) {
    global $conn;
    $systemUserId = 1; 
    $insertStmt = $conn->prepare("INSERT INTO friends (sender, receiver, status) VALUES (:senderId, :receiverId, 'ACCEPTED')");
    $insertStmt->execute(array(':senderId' => $systemUserId, ':receiverId' => $newUserId));
}
function addFriend($senderId, $receiverId) {
    global $conn;
    
    if ($senderId == $receiverId) {
        exit("You cannot friend yourself.");
    }

    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM `friends` WHERE (sender = :senderId AND receiver = :receiverId) OR (sender = :receiverId AND receiver = :senderId)");
    $checkStmt->execute(array(':senderId' => $senderId, ':receiverId' => $receiverId));
    $exists = $checkStmt->fetchColumn() > 0;

    if ($exists) {
        exit('You are already friends or there is a friend request pending');
    }

    $insertStmt = $conn->prepare("INSERT INTO friends (sender, receiver, status) VALUES (:senderId, :receiverId, 'PENDING')");
    $insertStmt->execute(array(':senderId' => $senderId, ':receiverId' => $receiverId));

    header("Location: requests.php");
}

function acceptFriend($senderId, $receiverId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE friends SET status = 'ACCEPTED' WHERE sender = :senderId AND receiver = :receiverId AND status = 'PENDING'");
    $stmt->execute(array(':senderId' => $senderId, ':receiverId' => $receiverId));

    header("Location: requests.php");
}

function revokeFriend($senderId, $receiverId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM friends WHERE sender = :senderId AND receiver = :receiverId AND status = 'PENDING'");
    $stmt->execute(array(':senderId' => $senderId, ':receiverId' => $receiverId));
}

function removeFriend($senderId, $receiverId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM friends WHERE (sender = :senderId AND receiver = :receiverId) OR (sender = :receiverId AND receiver = :senderId) AND status = 'ACCEPTED'");
    $stmt->execute(array(':senderId' => $senderId, ':receiverId' => $receiverId));
}


function fetchFriends($pdo, $status, $column, $userId)
{
    $allowedColumns = array('receiver', 'sender');
    if (!in_array($column, $allowedColumns)) {
        throw new InvalidArgumentException("Invalid column name");
    }

    $query = "SELECT * FROM `friends` WHERE `$column` = :userId AND status = :status";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':userId' => $userId, ':status' => $status));

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchUserFriends($userId, $limit=null)
{
    global $conn;
    $query = "SELECT * FROM `friends` WHERE (receiver = :userId OR sender = :userId) AND status = 'ACCEPTED'";
    if ($limit !== null) {
        $query .= " LIMIT :limit";
    }

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':userId', $userId);
    if ($limit !== null) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Elenco degli id degli amici accettati di $userId (indipendentemente da chi ha inviato la richiesta). */
function fetchAcceptedFriendIds($userId) {
    $rows = fetchUserFriends($userId);
    $ids = array();
    foreach ($rows as $row) {
        $friendId = $row['sender'] == $userId ? $row['receiver'] : $row['sender'];
        $ids[] = (string) $friendId;
    }
    return $ids;
}

/**
 * Top 8 nell'ordine scelto dall'utente (users.top_friends, JSON di id).
 * Gli id non più amici vengono scartati; se restano meno di $limit posti,
 * li riempie con gli altri amici accettati (non già presenti), nell'ordine
 * di default. Se top_friends non è mai stato impostato, usa solo l'ordine
 * di default (comportamento precedente).
 */
function getOrderedTopFriends($userId, $limit = 8) {
    global $conn;
    $accepted = fetchAcceptedFriendIds($userId);

    $stmt = $conn->prepare("SELECT top_friends FROM users WHERE id = ?");
    $stmt->execute(array($userId));
    $raw = $stmt->fetchColumn();
    $chosen = $raw ? json_decode($raw, true) : null;

    $ordered = array();
    if (is_array($chosen)) {
        foreach ($chosen as $id) {
            $id = (string) $id;
            if (in_array($id, $accepted, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }
    }

    foreach ($accepted as $id) {
        if (count($ordered) >= $limit) {
            break;
        }
        if (!in_array($id, $ordered, true)) {
            $ordered[] = $id;
        }
    }

    return array_slice($ordered, 0, $limit);
}

/** Salva l'ordine scelto dall'utente per il Top 8 (solo amici accettati, max 8). */
function saveTopFriends($userId, array $friendIds) {
    global $conn;
    $accepted = fetchAcceptedFriendIds($userId);

    $clean = array();
    foreach ($friendIds as $id) {
        $id = (string) (int) $id;
        if (in_array($id, $accepted, true) && !in_array($id, $clean, true)) {
            $clean[] = $id;
        }
        if (count($clean) >= 8) {
            break;
        }
    }

    $stmt = $conn->prepare("UPDATE users SET top_friends = ? WHERE id = ?");
    $stmt->execute(array(json_encode($clean), $userId));
}

function checkFriend($userId, $targetId) {
    global $conn;
    if ($userId == $targetId) {
        return false;
    }

    $query = "SELECT COUNT(*) FROM friends 
              WHERE ((sender = :userId AND receiver = :targetId) OR (sender = :targetId AND receiver = :userId)) 
              AND status = 'ACCEPTED'";

    try {
        $stmt = $conn->prepare($query);
        $stmt->execute(array(':userId' => $userId, ':targetId' => $targetId));
        $count = $stmt->fetchColumn();

        return $count > 0; 
    } catch (PDOException $e) {
        error_log("Error checking friendship: " . $e->getMessage());
        return false; 
    }
}

function checkFriendPending($userId, $profileId) {
    global $conn;
    $query = "SELECT COUNT(*) AS count FROM friends WHERE (sender = :userId AND receiver = :profileId AND status = 'PENDING') OR (sender = :profileId AND receiver = :userId AND status = 'PENDING')";
    $stmt = $conn->prepare($query);
    $stmt->execute(array(':userId' => $userId, ':profileId' => $profileId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return ($row['count'] > 0);
}
