<?php
// Galleria layout (profili condivisi come blocchi di HTML/CSS pronti da applicare).

function fetchAllLayouts($limit = 50) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM layouts ORDER BY date DESC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchLayout($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM layouts WHERE id = ?");
    $stmt->execute(array($id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createLayout($authorId, $title, $code) {
    global $conn;
    $title = trim(strip_tags($title));
    $code = sanitize_layout_html($code);
    if ($title === '' || trim($code) === '') {
        return false;
    }
    $stmt = $conn->prepare("INSERT INTO layouts (title, author, date, text, code) VALUES (?, ?, NOW(), '', ?)");
    return $stmt->execute(array($title, $authorId, $code));
}

/** Applica un layout della galleria al campo css del profilo dell'utente. */
function applyLayoutToUser($userId, $layoutId) {
    $layout = fetchLayout($layoutId);
    if (!$layout) {
        return false;
    }
    global $conn;
    $code = sanitize_layout_html($layout['code']);
    $stmt = $conn->prepare("UPDATE users SET css = ? WHERE id = ?");
    return $stmt->execute(array($code, $userId));
}
