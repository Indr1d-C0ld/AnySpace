<?php
// Gruppi: elenco (groups, con members = JSON di id utente) + bacheca di
// discussione (groupcomments, riusata come "muro" del gruppo).

function fetchAllGroups() {
    global $conn;
    $stmt = $conn->query("SELECT * FROM `groups` ORDER BY date DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchGroup($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM `groups` WHERE id = ?");
    $stmt->execute(array($id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fetchGroupMembers($groupOrId) {
    $group = is_array($groupOrId) ? $groupOrId : fetchGroup($groupOrId);
    if (!$group) {
        return array();
    }
    $members = json_decode($group['members'], true);
    return is_array($members) ? $members : array();
}

function isGroupMember($groupId, $userId) {
    $members = fetchGroupMembers($groupId);
    return in_array((string) $userId, array_map('strval', $members), true);
}

function createGroup($authorId, $name, $description) {
    global $conn;
    $name = trim(strip_tags($name));
    $description = trim(strip_tags($description));
    if ($name === '') {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO `groups` (name, description, author, date, members) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->execute(array($name, $description, $authorId, json_encode(array((string) $authorId))));
    return $conn->lastInsertId();
}

function joinGroup($groupId, $userId) {
    global $conn;
    $members = fetchGroupMembers($groupId);
    $userId = (string) $userId;
    if (!in_array($userId, $members, true)) {
        $members[] = $userId;
        $stmt = $conn->prepare("UPDATE `groups` SET members = ? WHERE id = ?");
        $stmt->execute(array(json_encode($members), $groupId));
    }
}

function leaveGroup($groupId, $userId) {
    global $conn;
    $members = fetchGroupMembers($groupId);
    $userId = (string) $userId;
    $members = array_values(array_filter($members, function ($id) use ($userId) {
        return (string) $id !== $userId;
    }));
    $stmt = $conn->prepare("UPDATE `groups` SET members = ? WHERE id = ?");
    $stmt->execute(array(json_encode($members), $groupId));
}

function fetchGroupWall($groupId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM groupcomments WHERE toid = ? ORDER BY date DESC");
    $stmt->execute(array($groupId));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function postToGroupWall($groupId, $authorId, $text) {
    global $conn;
    $text = strip_tags(trim($text));
    if ($text === '') {
        return false;
    }
    $stmt = $conn->prepare("INSERT INTO groupcomments (toid, author, text, date) VALUES (?, ?, ?, NOW())");
    return $stmt->execute(array($groupId, $authorId, $text));
}
