<?php
// Forum: bacheche (forum_boards) -> discussioni (forum_threads) -> messaggi (forum_posts).

function fetchBoards() {
    global $conn;
    $stmt = $conn->query(
        "SELECT b.*, "
        . "(SELECT COUNT(*) FROM forum_threads t WHERE t.board_id = b.id) AS thread_count, "
        . "(SELECT COUNT(*) FROM forum_posts p JOIN forum_threads t ON p.thread_id = t.id WHERE t.board_id = b.id) AS post_count "
        . "FROM forum_boards b ORDER BY b.position ASC, b.id ASC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchBoard($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM forum_boards WHERE id = ?");
    $stmt->execute(array($id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateBoard($id, $name, $description, $position) {
    global $conn;
    $name = trim(strip_tags($name));
    if ($name === '') {
        return false;
    }
    $stmt = $conn->prepare("UPDATE forum_boards SET name = ?, description = ?, position = ? WHERE id = ?");
    return $stmt->execute(array($name, trim(strip_tags($description)), (int) $position, $id));
}

function deleteBoard($id) {
    global $conn;
    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("DELETE FROM forum_posts WHERE thread_id IN (SELECT id FROM (SELECT id FROM forum_threads WHERE board_id = ?) t)");
        $stmt->execute(array($id));
        $stmt = $conn->prepare("DELETE FROM forum_threads WHERE board_id = ?");
        $stmt->execute(array($id));
        $stmt = $conn->prepare("DELETE FROM forum_boards WHERE id = ?");
        $stmt->execute(array($id));
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log('deleteBoard fallita: ' . $e->getMessage());
        return false;
    }
}

function createBoard($name, $description) {
    global $conn;
    $name = trim(strip_tags($name));
    if ($name === '') {
        return false;
    }
    $nextPosition = (int) $conn->query("SELECT COALESCE(MAX(position), 0) + 1 FROM forum_boards")->fetchColumn();
    $stmt = $conn->prepare("INSERT INTO forum_boards (name, description, position) VALUES (?, ?, ?)");
    return $stmt->execute(array($name, trim(strip_tags($description)), $nextPosition));
}

function fetchThreadsByBoard($boardId, $limit = null, $offset = 0) {
    global $conn;
    $sql = "SELECT t.*, (SELECT COUNT(*) FROM forum_posts p WHERE p.thread_id = t.id) AS reply_count "
        . "FROM forum_threads t WHERE t.board_id = :board ORDER BY t.pinned DESC, t.last_post_at DESC";
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':board', (int) $boardId, PDO::PARAM_INT);
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countThreadsByBoard($boardId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM forum_threads WHERE board_id = ?");
    $stmt->execute(array($boardId));
    return (int) $stmt->fetchColumn();
}

function fetchThread($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM forum_threads WHERE id = ?");
    $stmt->execute(array($id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fetchPostsByThread($threadId, $limit = null, $offset = 0) {
    global $conn;
    $sql = "SELECT * FROM forum_posts WHERE thread_id = :thread ORDER BY date ASC";
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':thread', (int) $threadId, PDO::PARAM_INT);
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countPostsByThread($threadId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM forum_posts WHERE thread_id = ?");
    $stmt->execute(array($threadId));
    return (int) $stmt->fetchColumn();
}

function createThread($boardId, $authorId, $title, $firstPostText) {
    global $conn;
    $title = trim(strip_tags($title));
    $text = sanitize_html($firstPostText);
    if ($title === '' || trim(strip_tags($firstPostText)) === '') {
        return false;
    }

    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("INSERT INTO forum_threads (board_id, title, author, date, last_post_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute(array($boardId, $title, $authorId));
        $threadId = $conn->lastInsertId();

        $stmt = $conn->prepare("INSERT INTO forum_posts (thread_id, author, text, date) VALUES (?, ?, ?, NOW())");
        $stmt->execute(array($threadId, $authorId, $text));

        $conn->commit();
        return $threadId;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log('createThread fallita: ' . $e->getMessage());
        return false;
    }
}

function createPost($threadId, $authorId, $text) {
    global $conn;
    $text = sanitize_html($text);
    if (trim(strip_tags($text)) === '') {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO forum_posts (thread_id, author, text, date) VALUES (?, ?, ?, NOW())");
    $ok = $stmt->execute(array($threadId, $authorId, $text));

    if ($ok) {
        $stmt = $conn->prepare("UPDATE forum_threads SET last_post_at = NOW() WHERE id = ?");
        $stmt->execute(array($threadId));
    }

    return $ok;
}

/**
 * $actorId e' chi sta agendo: l'autore della discussione oppure
 * l'amministratore. Finora poteva cancellare solo l'autore, quindi un
 * messaggio di spam restava online finche' non lo rimuoveva chi l'aveva
 * scritto: il forum non aveva alcuna moderazione.
 */
function deleteThread($threadId, $actorId) {
    global $conn;
    $isAdmin = ((int) $actorId === (int) ADMIN_USER) ? 1 : 0;

    $stmt = $conn->prepare(
        "SELECT id FROM forum_threads WHERE id = :id AND (author = :actor OR :isAdmin = 1)"
    );
    $stmt->execute(array(':id' => $threadId, ':actor' => $actorId, ':isAdmin' => $isAdmin));
    if (!$stmt->fetch()) {
        return false;
    }

    $conn->prepare("DELETE FROM forum_posts WHERE thread_id = ?")->execute(array($threadId));
    return $conn->prepare("DELETE FROM forum_threads WHERE id = ?")->execute(array($threadId));
}

function deletePost($postId, $actorId) {
    global $conn;
    $isAdmin = ((int) $actorId === (int) ADMIN_USER) ? 1 : 0;
    $stmt = $conn->prepare(
        "DELETE FROM forum_posts WHERE id = :id AND (author = :actor OR :isAdmin = 1)"
    );
    $stmt->execute(array(':id' => $postId, ':actor' => $actorId, ':isAdmin' => $isAdmin));
    return $stmt->rowCount() > 0;
}
