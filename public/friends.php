<?php
require("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/friend.php");
require("../core/site/user.php");

login_check();

$user = $_SESSION['user'];
$userId = $_SESSION['userId'];

// Page is used as a single entry point for friends actions
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
$id = !empty($_GET['id']) ? (int) $_GET['id'] : $userId;


// Le azioni che modificano lo stato erano eseguite su GET. Poiché il tag <img>
// è ammesso nelle bio (HTMLPurifier), bastava che un utente mettesse nella
// propria bio
//     <img src=".../friends.php?action=remove&id=123">
// perché chiunque aprisse quel profilo cancellasse silenziosamente una
// propria amicizia. Ora servono POST + token CSRF.
if (!empty($action)) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Questa azione richiede l\'invio di un modulo (POST).');
    }
    csrf_verify();
    $targetId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    switch ($action) {
        case 'accept':
            acceptFriend($targetId, $userId);
            break;
        case 'add':
            addFriend($userId, $targetId);
            break;
        case 'revoke':
            revokeFriend($userId, $targetId);
            break;
        case 'remove':
            removeFriend($userId, $targetId);
            break;
        default:
            exit('Azione non valida.');
    }

    header("Location: " . BASE_PATH . "/friends.php?id=" . (int) $userId);
    exit;
}


// Fetch pending and accepted friends
$acceptedFriends = array_merge(
    fetchFriends($conn, 'ACCEPTED', 'receiver', $id),
    fetchFriends($conn, 'ACCEPTED', 'sender', $id)
);
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina Amici</title>
    <link rel="stylesheet" href="static/css/header.css">
    <link rel="stylesheet" href="static/css/base.css">
    <link rel="stylesheet" href="static/css/my.css">
</head>

<body>
    <div class="master-container">
        <?php require("../core/components/navbar.php"); ?>
        <main>
            <div class="simple-container">
                <h1>
                    Amici di <?= htmlspecialchars(fetchName($id)) ?>
                </h1>
                <p><a href="profile.php?id=<?= $id ?>">&laquo; Torna al Profilo di
                        <?= htmlspecialchars(fetchName($id)) ?>
                    </a></p>
                <br>
                <form metrhod="get">
                    <input type="hidden" name="id" value="">
                    <input type="text" name="q" value="" autocomplete="off" autofocus required>
                    <button type="submit">Cerca</button>
                </form>
                <br>
                <!-- ACCEPTED -->
                <div class="new-people">
                    <div class="top">
                        <h4>Amici</h4>
                    </div>
                    <div class="inner">
                        <?php
                        if (empty($acceptedFriends)) {
                            echo "<div>Questo utente non ha amici...</div>";
                        } else {
                            foreach ($acceptedFriends as $friend) {
                                $friendId = $id === $friend['sender'] ? $friend['receiver'] : $friend['sender'];
                                if ($friendId == $id) {
                                    $friendId = $friend['receiver'];
                                }
                                
                                printPerson($friendId);
                            }
                        }
                        ?>
                    </div>
                </div>

            </div>
    </div>
    </main>
    </div>

</body>

</html>