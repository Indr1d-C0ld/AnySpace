<?php
function fetchUsers($limit = null, $offset = 0)
{
    global $conn;
    $sql = "SELECT * FROM `users` ORDER BY id ASC";
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    $stmt = $conn->prepare($sql);
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countUsers() {
    global $conn;
    return (int) $conn->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
}

function fetchUserInfo($userId)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM `users` WHERE id = :id");
    $stmt->execute(array(':id' => $userId));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fetchUserPassword($userId)
{
    global $conn;
    $stmt = $conn->prepare("SELECT password FROM `users` WHERE id = :id");
    $stmt->execute(array(':id' => $userId));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// attribute specific functions
function fetchName($id) {
    global $conn; // Use the globally defined connection
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = :id");
    $stmt->execute(array(':id' => $id));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($result) === 0) return 'error';
    $name = $result[0]['username']; 
    return $name;
}

function fetchEmail($id) {
    global $conn; // Use the globally defined connection
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = :id");
    $stmt->execute(array(':id' => $id));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($result) === 0) return 'error';
    $name = $result[0]['email']; 
    return $name;
}

function fetchPFP($id) {
    global $conn; // Use the globally defined connection
    $stmt = $conn->prepare("SELECT pfp FROM users WHERE id = :id");
    $stmt->execute(array(':id' => $id));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($result) === 0) return 'error';
    $pfp = $result[0]['pfp']; 
    return $pfp;
}

function fetchUserStatus($id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['status'])) {
            return json_decode($result['status'], true);
        } else {
            return null;
        }

    } catch (PDOException $e) {
        error_log('Fetch user status failed: ' . $e->getMessage());
        return null;
    }
}

function addFavorite($userId, $favoriteId) {
    global $conn;
    // Fetch the current favorites JSON string
    $query = "SELECT favorites FROM favorites WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute(array($userId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentFavorites = $row ? json_decode($row['favorites'], true) : array();
    if (!is_array($currentFavorites)) {
        $currentFavorites = array();
    }

    // Senza questo controllo ogni invio del form accodava un duplicato: i
    // Preferiti finivano per elencare lo stesso utente N volte.
    $favoriteId = (string) (int) $favoriteId;
    $currentFavorites = array_values(array_unique(array_map('strval', $currentFavorites)));
    if (!in_array($favoriteId, $currentFavorites, true)) {
        $currentFavorites[] = $favoriteId;
    }

    $updatedFavorites = json_encode($currentFavorites);

    $query = "INSERT INTO favorites (user_id, favorites) VALUES (?, ?)
              ON DUPLICATE KEY UPDATE favorites = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute(array($userId, $updatedFavorites, $updatedFavorites));
}

function fetchFavorites($userId) {
    global $conn;
    $query = "SELECT favorites FROM favorites WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute(array($userId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['favorites'] : ''; // Return the JSON string directly
}

function changePassword($userId, $hashedPassword) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET password = :hashedPassword WHERE id = :userId");
    $stmt->execute(array(':hashedPassword' => $hashedPassword, ':userId' => $userId));

}

function updateLastLogon($userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET lastlogon = NOW(), lastactive = NOW() WHERE id = ?");
    $stmt->execute(array($userId));
}

function touchLastActive($userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET lastactive = NOW() WHERE id = ?");
    $stmt->execute(array($userId));
}

function incrementProfileViews($userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET views = views + 1 WHERE id = ?");
    $stmt->execute(array($userId));
}

/** Minuti di inattività oltre i quali un utente non è più considerato in linea. */
define('ANYSPACE_ONLINE_MINUTES', 5);

/**
 * True se l'utente risulta attivo di recente. Serviva: il riquadro
 * "IN LINEA!" era stampato incondizionatamente su ogni profilo, quindi
 * chiunque risultava sempre collegato, e il filtro "online" di browse.php
 * interrogava una colonna `online_status` che non esiste nello schema
 * (eccezione PDO non gestita, pagina troncata a metà).
 */
function isUserOnline($lastActive) {
    if (empty($lastActive)) {
        return false;
    }
    $ts = strtotime($lastActive);
    return $ts !== false && (time() - $ts) <= ANYSPACE_ONLINE_MINUTES * 60;
}

// Should probably get moved, no idea where to put it though
// $row opzionale: se il chiamante ha già la riga dell'utente (es. browse.php,
// che fa una sola SELECT per l'intero elenco) si evitano due query in più
// per persona solo per rileggere nome e foto.
function printPerson($userId, $row = null) {
    $username = ($row && isset($row['username'])) ? $row['username'] : fetchName($userId);
    $profilePicPath = ($row && isset($row['pfp'])) ? $row['pfp'] : fetchPFP($userId);

    $profilePicPath = htmlspecialchars('media/pfp/' . $profilePicPath);
    $profileLink = 'profile.php?id=' . (int) $userId;
    $username = htmlspecialchars($username);

    echo "<div class='person'>";
    echo "<a href='{$profileLink}'><p>{$username}</p></a>";
    echo "<a href='{$profileLink}'><img class='pfp-fallback' src='{$profilePicPath}' alt='Profile Picture' loading='lazy' style='width: 100%; max-height: 95px; aspect-ratio: 1/1;'></a>";
    echo "</div>";
}





?>