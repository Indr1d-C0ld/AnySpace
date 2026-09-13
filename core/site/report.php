<?php
// Sistema di segnalazioni. Codici content_type:
// 1 = utente, 2 = commento profilo, 3 = commento blog, 4 = commento bulletin
define('REPORT_TYPE_USER', 1);
define('REPORT_TYPE_COMMENT', 2);
define('REPORT_TYPE_BLOG_COMMENT', 3);
define('REPORT_TYPE_BULLETIN_COMMENT', 4);

function report_type_label($type) {
    $labels = array(
        REPORT_TYPE_USER => 'Utente',
        REPORT_TYPE_COMMENT => 'Commento profilo',
        REPORT_TYPE_BLOG_COMMENT => 'Commento blog',
        REPORT_TYPE_BULLETIN_COMMENT => 'Commento bulletin',
    );
    return $labels[$type] ?? 'Sconosciuto';
}

function createReport($userId, $creatorId, $contentType, $contentId) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO reports (user_id, creator_id, date, content_type, content_id) VALUES (?, ?, NOW(), ?, ?)");
    return $stmt->execute(array($userId, $creatorId, $contentType, $contentId));
}

function fetchReports() {
    global $conn;
    $stmt = $conn->query("SELECT * FROM reports ORDER BY date DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteReport($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
    return $stmt->execute(array($id));
}
