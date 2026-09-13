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

function fetchThreadsByBoard($boardId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT t.*, (SELECT COUNT(*) FROM forum_posts p WHERE p.thread_id = t.id) AS reply_count "
        . "FROM forum_threads t WHERE t.board_id = ? ORDER BY t.pinned DESC, t.last_post_at DESC"
    );
    $stmt->execute(array($boardId));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchThread($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM forum_threads WHERE id = ?");
    $stmt->execute(array($id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fetchPostsByThread($threadId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM forum_posts WHERE thread_id = ? ORDER BY date ASC");
    $stmt->execute(array($threadId));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

function deleteThread($threadId, $authorId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM forum_posts WHERE thread_id = (SELECT id FROM (SELECT id FROM forum_threads WHERE id = ? AND author = ?) t)");
    $stmt->execute(array($threadId, $authorId));
    $stmt = $conn->prepare("DELETE FROM forum_threads WHERE id = ? AND author = ?");
    return $stmt->execute(array($threadId, $authorId));
}

function deletePost($postId, $authorId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM forum_posts WHERE id = ? AND author = ?");
    return $stmt->execute(array($postId, $authorId));
}
