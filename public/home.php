<?php
require_once("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/user.php");
require("../core/site/friend.php");
require("../core/site/comment.php");
require("../core/site/blog.php");
require("../core/site/bulletin.php");

login_check();

// Fetch user information
$userInfo = fetchUserInfo($_SESSION['userId']);
$user = $userInfo ? $userInfo['username'] : '';
$userId = $userInfo['id'];

// Fetch blogs and friends using the user's username
$blogs = fetchUserBlogs($conn, $user);

// FRIENDS
$pendingRequests = fetchFriends($conn, 'PENDING', 'receiver', $userId);

$friends = array_merge(
    fetchFriends($conn, 'ACCEPTED', 'receiver', $userId),
    fetchFriends($conn, 'ACCEPTED', 'sender', $userId)
);

$friendCount = count($friends);


$dateJoined = new DateTime($userInfo['date']);
$formattedDate = $dateJoined->format('M j, Y'); // Formats the date as "Feb 6, 2024"

$sinceJoined = time_elapsed_string($userInfo['date']);

$profileViews = 0;

// Blogs & Bulletins
$blogEntries = fetchBlogEntries($userId, 5);
$bulletins = fetchAllFriendBulletins($userId, 5);

?>

<!DOCTYPE html>
<html>

<head>
    <title>Bacheca | <?= SITE_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="static/css/normalize.min.css">
    <link rel="stylesheet" href="static/css/header.min.css">
    <link rel="stylesheet" href="static/css/base.min.css">
    <link rel="stylesheet" href="static/css/my.min.css">

</head>

<body>
    <div class="master-container">
        <?php
        require("../core/components/navbar.php");
        ?>
        <main>
            <!-- Profile Box -->
            <div class="row profile user-home">
                <div class="col w-40 left">
                    <div class="general-about home-actions">
                        <div class="heading">
                            <h1 style='margin: 0px;'>Ciao,
                                <?= htmlspecialchars($userInfo['username']); ?>!
                            </h1>
                        </div>
                        <div class="inner">
                            <br>
                            <div class="profile-pic">

                                <img width='235px;' src='media/pfp/<?= htmlspecialchars($userInfo['pfp']); ?>'>
                            </div>
                            <div class="details">
                                <p><a href="manage.php">Modifica Profilo</a></p>
                                <p><a href="editstatus.php">Modifica Stato</a></p>
                                <p><a href="settings.php">Impostazioni Account</a></p>
                            </div>
                            <div class="more-options">
                                <p>Guarda il mio: <a href='profile.php?id=<?= $userId ?>'>Profilo</a> | <a
                                        <a href='blog/user.php?id=<?= $userId ?>'>Blog</a> | <a
                                        <a href='bulletins/userbulletins.php?id=<?= $userId ?>'>Bulletin</a> | <a
                                        href='friends.php?id=<?= $userId ?>'>Amici</a> | <a
                                        href='requests.php?id=<?= $userId ?>'>Richieste</a>
                                </p>
                                <p>Il mio URL: <a href='profile.php?id=<?= $userId ?>'>https://<?= DOMAIN_NAME ?>/profile.php?id=<?= $userId ?>
                                    </a></p>
                            </div>
                        </div>
                    </div>
                    <div class="url-info view-full-profile">
                        <p><a href="profile.php?id=<?= htmlspecialchars($userInfo['id']) ?>"><b>Vedi il Tuo
                                    Profilo</b></p>
                    </div>


                    <!-- sidebar -->
                    <?php include("../core/components/section_indie_box.php") ?>
                    <?php include("../core/components/section_announcements.php") ?>

                </div>
                
                <!-- Stats & Blog -->
                <div class="col right">
                    <div class="row top-row">
                        <div class="row top-row">
                            <div class="blog-preview col">
                                <h4>I Tuoi Ultimi Post del Blog [<a href="blog/newpost.php">Nuovo Post</a>]
                                </h4>
                                <?php if (empty($blogEntries)): ?>
                                    <p><i>Non ci sono ancora post nel blog.</i></p>
                                <?php else: ?>
                                    <?php foreach ($blogEntries as $entry): ?>
                                        <?php
                                        $maxTitleLength = 20;
                                        $title = $entry['title'];
                                        if (mb_strlen($title) > $maxTitleLength) {
                                            $title = mb_substr($title, 0, $maxTitleLength) . '...';
                                        }
                                        ?>
                                        <p>
                                            <?= htmlspecialchars($title) ?>
                                            (<a href="blog/entry.php?id=<?= $entry['id'] ?>">Vedi di più</a>)
                                        </p>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </div>
                            <div class="statistics col">
                                <div class="heading">
                                    <h4>
                                        Statistiche di <?= htmlspecialchars($userInfo['username']) ?>
                                    </h4><br>
                                    <h4>
                                        <?= htmlspecialchars($formattedDate) ?>
                                    </h4>
                                </div>
                                <div class="inner">
                                    <div class="m-row">
                                        <div class="m-col">
                                            <p>I Tuoi Amici: <br> <a
                                                    href="/friends.php?id=<?= htmlspecialchars($userId) ?>"><span
                                                        class="count">
                                                        <?= $friendCount ?>
                                                    </span></a></p>
                                        </div>
                                        <div class="m-col">
                                            <p>Visite al Profilo: <br> <span class="count">
                                                    <?= $profileViews ?>
                                                </span></p>
                                        </div>
                                        <div class="m-col">
                                            <p>Iscritto: <br> <span class="count"><i>
                                                        <?= $sinceJoined ?>
                                                    </i> </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>


                    <!-- NEW PEOPLE -->
                    <div class="new-people cool">
                        <div class="top">
                            <h4>Nuovi Utenti</h4>
                            <a class="more" href="browse.php">[vedi altri]</a>
                        </div>
                        <div class="inner">
                            <?php
                            $stmt = $conn->prepare("SELECT id FROM `users` ORDER BY date DESC LIMIT 4");
                            $stmt->execute();

                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                printPerson($row['id']);
                            }
                            ?>
                        </div>
                        <div class="view-more d-hide">
                            <a href="<?= BASE_PATH ?>/browse.php?view=new">
                                <p>[vedi altri]</p>
                            </a>
                        </div>
                    </div>


                    <!-- BULLETINS -->
                    <div class="bulletin-preview">
                        <div class="heading">
                            <h4>Bulletin dei Tuoi Amici</h4>
                            <a class="more" href="<?= BASE_PATH ?>/bulletins/">[vedi tutti]</a>
                        </div>
                        <?php if (!empty($bulletins)): ?>
                        <table class="bulletin-table preview">
                            <thead>
                                <tr>
                                    <th scope="col">Da</th>
                                    <th scope="col">Oggetto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bulletins as $entry): ?>
                                <tr>
                                    <td class="user-info">
                                        <a href="profile.php?id=<?= $entry['author'] ?>">
                                        <p><?= fetchName($entry['author']) ?></p>
                                        </a>
                                    </td>
                                    <td class="subject">
                                        <a href="<?= BASE_PATH ?>/bulletins/bulletin.php?id=<?= $entry['id'] ?>">
                                        <p><b><?= $entry['title'] ?></b></p>
                                        </a>
                                    </td>
                                </tr>
                                 <?php endforeach; ?>
                                <tr>
                                <td colspan="2">
                                <i><a href="<?= BASE_PATH ?>/bulletins/">Vedi tutti i Bulletin</a></i>            </td>
                            </tr>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>


                    <!-- FRIENDS -->
                    <div class="friends">
                        <div class="heading">
                            <h4>Richieste di Amicizia</h4>
                        </div>
                        <div class="inner">
                            <p><b><span class="count">
                                        <?= htmlspecialchars(count($pendingRequests)); ?>
                                    </span> Richieste di Amicizia in sospeso</b></p>
                            <a href="requests.php">
                                <button>Vedi Tutte le Richieste</button>
                            </a>
                            <br>
                            <table class="comments-table" cellspacing="0" cellpadding="3" bordercolor="ffffff"
                                border="1">
                                <tbody>
                                    <?php foreach ($pendingRequests as $request): ?>
                                        <tr>
                                            <td>
                                                <a href="profile.php?id=<?= $request['sender'] ?>">
                                                    <p>
                                                        <?= htmlspecialchars(fetchName($request['sender'])) ?>
                                                    </p>
                                                </a>
                                                <a href="profile.php?id=<?= $request['sender'] ?>">
                                                    <img class="pfp-fallback"
                                                        src="media/pfp/<?= fetchPFP($request['sender']) ?: 'default.png' ?>"
                                                        alt="profile picture" loading="lazy" width="50px">
                                                </a>
                                            </td>
                                            <td>
                                                <p><b>Richiesta di Amicizia</b></p>
                                                <form method="post">
                                                    <input type="hidden" name="type" value="friend-request">
                                                    <input type="hidden" name="request_id"
                                                        value="<?= htmlspecialchars($request['id']) ?>">
                                                    <button
                                                        onclick="location.href='friends.php?action=accept&id=<?= $request['sender'] ?>'"
                                                        name="decision" value="accept" type="button">Accetta</button>
                                                    <button
                                                        onclick="location.href='friends.php?action=revoke&id=<?= $request['sender'] ?>'"
                                                        type="button" name="decision" value="decline">Rifiuta</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php require_once("footer.php") ?>
