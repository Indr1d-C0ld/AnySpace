<?php
require("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/friend.php");
require_once("../core/site/user.php");

login_check();

$user = $_SESSION['user'];
$userId = $_SESSION['userId'];

// Questa pagina mostrava tre pulsanti in <form method="post"> ("Accetta Tutte
// le Richieste", "Rifiuta", "Annulla Richiesta di Amicizia") ma non conteneva
// NESSUN gestore per il POST: il form si inviava su se stesso, la pagina si
// ricaricava identica e non succedeva niente.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $targetId = isset($_POST['target_id']) ? (int) $_POST['target_id'] : 0;

    if (isset($_POST['accept_all'])) {
        foreach (fetchFriends($conn, 'PENDING', 'receiver', $userId) as $req) {
            acceptFriend($req['sender'], $userId);
        }
    } elseif (isset($_POST['accept_one']) && $targetId > 0) {
        acceptFriend($targetId, $userId);
    } elseif ((isset($_POST['decline']) || isset($_POST['cancel_sent'])) && $targetId > 0) {
        revokeFriend($userId, $targetId);
    }

    header("Location: requests.php");
    exit;
}

// Fetch pending and accepted friends
$pendingReceived = fetchFriends($conn, 'PENDING', 'receiver', $userId);
$pendingSent = fetchFriends($conn, 'PENDING', 'sender', $userId);
$acceptedFriends = array_merge(
    fetchFriends($conn, 'ACCEPTED', 'receiver', $userId),
    fetchFriends($conn, 'ACCEPTED', 'sender', $userId)
);
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richieste |
        <?= (SITE_NAME) ?>
    </title>
    <link rel="stylesheet" href="static/css/header.css">
    <link rel="stylesheet" href="static/css/base.css">
    <link rel="stylesheet" href="static/css/my.css">
</head>

<body>
    <div class="master-container">
        <?php require("../core/components/navbar.php"); ?>
        <main>
            <div class="simple-container">
                <!-- RECEIVED -->
                <div class="friends">
                    <div class="heading">
                        <h1>Richieste di Amicizia</h1>
                    </div>
                    <div class="inner">
                        <br>
                        <p><b><span class="count">
                                    <?= count($pendingReceived); ?>
                                </span> Richieste di Amicizia in sospeso</b></p>
                        <?php if (!empty($pendingReceived)): ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <button type="submit" name="accept_all">Accetta Tutte le Richieste</button>
                        </form>
                        <?php endif; ?>
                        <br>
                        <table class="comments-table" cellspacing="0" cellpadding="3" bordercolor="ffffff" border="1">
                            <tbody>
                                <?php
                                if (empty($pendingReceived)) { // Make sure this matches the variable name used below
                                    echo "<div>Non hai richieste di amicizia in sospeso.</div>";
                                } else {
                                    foreach ($pendingReceived as $request): // Ensure this matches the variable checked above
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="profile.php?id=<?= $request['sender']; ?>">
                                                    <p>
                                                        <?= htmlspecialchars(fetchName($request['sender'])); ?>
                                                    </p>
                                                </a>
                                                <a href="profile.php?id=<?= $request['sender']; ?>">
                                                    <?php
                                                    // Since PHP 5.3 does not support the null coalescing operator, use a ternary operator as an alternative
                                                    $pfpPath = fetchPFP($request['sender']);
                                                    $pfpPath = $pfpPath ? $pfpPath : 'default.png';
                                                    ?>
                                                    <img class="pfp-fallback" src="media/pfp/<?= htmlspecialchars($pfpPath); ?>"
                                                        alt="profile picture" loading="lazy" width="50px">
                                                </a>
                                            </td>
                                            <td>
                                                <p><b>Richiesta di Amicizia</b></p>
                                                <form method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="target_id" value="<?= (int) $request['sender'] ?>">
                                                    <button type="submit" name="accept_one">Accetta</button>
                                                    <button type="submit" name="decline">Rifiuta</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php
                                    endforeach;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <br>
                    <hr>

                    <!-- SENT -->
                    <div class="heading">
                        <h1>Richieste Inviate</h1>
                    </div>
                    <div class="inner">
                        <br>
                        <p><b><span class="count">
                                    <?= count($pendingSent) ?>
                                </span> Richieste di Amicizia in sospeso</b></p>
                        <br>
                        <table class="comments-table" cellspacing="0" cellpadding="3" bordercolor="ffffff" border="1">
                            <tbody>
                                <?php
                                if (empty($pendingSent)) { // Make sure this matches the variable name used below
                                    echo "<div>Non hai richieste di amicizia inviate in sospeso.</div>";
                                } else {
                                    foreach ($pendingSent as $request):
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="profile.php?id=<?= $request['receiver']; ?>">
                                                    <p>
                                                        <?= htmlspecialchars(fetchName($request['receiver'])); ?>
                                                    </p>
                                                </a>
                                                <a href="profile.php?id=<?= $request['receiver']; ?>">
                                                    <?php
                                                    $pfpPath = fetchPFP($request['receiver']);
                                                    $pfpPath = $pfpPath ? $pfpPath : 'default.png';
                                                    ?>
                                                    <img class="pfp-fallback" src="media/pfp/<?= htmlspecialchars($pfpPath); ?>"
                                                        alt="profile picture" loading="lazy" width="50px">
                                                </a>
                                            </td>
                                            <td>
                                                <p><b>Richiesta di Amicizia Inviata</b></p>
                                                <form method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="target_id" value="<?= (int) $request['receiver'] ?>">
                                                    <button type="submit" name="cancel_sent">Annulla
                                                        Richiesta di Amicizia</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php
                                    endforeach;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <br>
                </div>

            </div>

    </div>
    </main>
    </div>
</body>

</html>