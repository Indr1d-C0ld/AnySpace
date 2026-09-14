<?php
require("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/user.php");
require("../core/site/comment.php");
require("../core/site/friend.php");
require("../core/site/blog.php");


if(isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId'];
} else {
    $userId = null;
}

// Profilo inesistente: prima si proseguiva comunque con $userInfo === false,
// e ogni accesso successivo ($userInfo['id'], ['interests'], ...) emetteva un
// warning finendo per servire una pagina profilo vuota e rotta con HTTP 200.
$requestedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$userInfo = $requestedId > 0 ? fetchUserInfo($requestedId) : false;

if (!$userInfo) {
    http_response_code(404);
    $pageTitle = 'Profilo non trovato';
    require("header.php");
    echo '<div class="simple-container"><h1>Profilo non trovato</h1>'
        . '<p>Questo utente non esiste (o è stato rimosso).</p>'
        . '<p><a href="' . BASE_PATH . '/browse.php">Esplora gli altri profili</a></p></div>';
    require("footer.php");
    exit;
}

$user = $userInfo['username'];
$profileId = $userInfo['id'];

$userInterests = $userInfo['interests'];
$interests = json_decode($userInterests, true);

// Fetch blogs and friends using the user's username
$blogs = fetchUserBlogs($conn, $user);

$friends = array_merge(
    fetchFriends($conn, 'ACCEPTED', 'receiver', $profileId),
    fetchFriends($conn, 'ACCEPTED', 'sender', $profileId)
);
$friendsTopEight = getOrderedTopFriends($profileId, 8);

$isFriend = false;
$isPendingFriend = false;

if ($userId !== null) {
    // Check if users are friends
    $isFriend = checkFriend($userId, $profileId);

    if (!$isFriend) {
        // If they are not friends, check for pending friend requests
        $isPendingFriend = checkFriendPending($userId, $profileId);
    }
}

// Profilo privato (Impostazioni -> Privacy): visibile solo a se stessi, agli
// amici accettati e all'amministratore. Finora la tendina in Impostazioni
// prometteva questo comportamento ma la colonna users.private non veniva
// letta da nessuna parte: i profili "privati" restavano pubblici a chiunque.
$isOwnProfile = ($userId !== null && (int) $userId === (int) $profileId);
$isAdminViewer = ($userId !== null && (int) $userId === (int) ADMIN_USER);
if (!empty($userInfo['private']) && !$isOwnProfile && !$isFriend && !$isAdminViewer) {
    require("header.php");
    ?>
    <div class="simple-container">
        <h1><?= htmlspecialchars($user) ?></h1>
        <div class="box standalone">
            <p><b>Questo profilo è privato.</b></p>
            <p>Solo gli amici di <?= htmlspecialchars($user) ?> possono vederne il contenuto.</p>
            <?php if ($userId === null): ?>
                <p><a href="<?= BASE_PATH ?>/login.php">Accedi</a> se siete già amici.</p>
            <?php elseif (!$isPendingFriend): ?>
                <form method="post" action="<?= BASE_PATH ?>/friends.php?action=add">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $profileId ?>">
                    <button type="submit">Invia una richiesta di amicizia</button>
                </form>
            <?php else: ?>
                <p><i>Hai già una richiesta di amicizia in sospeso.</i></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    require("footer.php");
    exit;
}

// Fetch comments
$toid = $profileId;
$comments = fetchComments($profileId, 20);
$countComments = count($comments);
$countTotalComments = count(fetchComments($profileId));

$blogEntries = fetchBlogEntries($profileId, 4);
$statusInfo = fetchUserStatus($profileId);

if ($userId != $profileId) {
    incrementProfileViews($profileId);
    $userInfo['views']++;
}
if ($userId !== null) {
    touchLastActive($userId);
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo di <?= htmlspecialchars($user) ?> | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="static/css/normalize.css">
    <link rel="stylesheet" href="static/css/base.css"> 
    <link rel="stylesheet" href="static/css/my.css">
<!-- Doesn't seem to work on profile page
        <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            overflow-x: hidden; 
        }

        @media screen and (max-width: 768px) {
            .row.home {
                display: flex;
                flex-direction: column;
            }

            .col {
                width: 100%;
            }

            .col.right {
                width: 60%);
                margin: 0 auto; 
            }

             .col.w-60 {
                width: 100%;
            }

            .master-container {
                width: 100%;
            }
        }
    </style>
    -->
    <style>
    .profile-info {
        height: 82px;
    }

    #music {
        position: fixed;
        bottom: 10px;
        left: 10px;
        width: 80px;
        transition: 0.5s width;
    }

    #music:hover {
        width: 360px;
    }
    </style>
</head>

<body>

<div class="container">
  <nav class="">
    <div class="top">
        <div class="left">
        <a href="index.php">
            <?= SITE_NAME ?>
            </a> | <a href="index.php">Home</a>
        </div>
        <div class="center">

            <form>
                <label for="q">
                    Cerca su <?= htmlspecialchars(SITE_NAME); ?>:
                </label>
                <div class="search-wrapper">
                    <input id="q" type="text" name="q" autocomplete="off">
                </div>
                <button type="submit">Cerca</button>
            </form>
        </div>
        <div class="right">
            <ul class="topnav signup">
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="docs/help.html">Aiuto</a> | <a href="logout.php">Esci</a>
                <?php else: ?>
                    <a href="docs/help.html">Aiuto</a> |
                    <a href="docs/help.html">Accedi</a> |
                    <a href="register.php">Iscriviti</a>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <ul class="links">
        <?php
        $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $currentPage = basename($currentUrl);

        $isHomePage = in_array($currentPage, array('index.php', 'home.php'));

        require_once("../core/site/message.php");
        $unreadCount = $userId !== null ? countUnreadMessages($userId) : 0;

        $navItems = array(
            'Home' => 'index.php',
            'Esplora' => 'browse.php',
            'Cerca' => 'search.php',
            'Posta' . ($unreadCount > 0 ? " ($unreadCount)" : '') => 'messages.php',
            'Blog' => 'blog/',
            'Bulletin' => 'bulletins/',
            'Forum' => 'forum/',
            'Gruppi' => 'groups/',
            'Grafiche' => 'layouts/',
            'Preferiti' => 'favorites.php',
            'Sorgente' => 'https://github.com/superswan/anyspace',
            'Aiuto' => 'docs/help.html',
            'Chi siamo' => 'about.php',
          );

        foreach ($navItems as $name => $page) {
            if ($name == 'Home' && $isHomePage) {
                $activeClass = 'class="active"';
            } else {
                $activeClass = ($currentPage == basename($page)) ? 'class="active"' : '';
            }
            echo "<li><a href=\"$page\" $activeClass>&nbsp;$name </a></li>";
        }
        ?>
    </ul>
  </nav>



        <main>
            <!-- USER PROFILE -->
            <div class="row profile" itemscope itemtype="https://schema.org/Person">
                <meta itemprop="url"
                    content="https://<?= htmlspecialchars(DOMAIN_NAME); ?><?= BASE_PATH ?>/profile.php?id=<?= htmlspecialchars($profileId); ?>">
                <meta itemprop="identifier" content="<?= htmlspecialchars($user); ?>">
            <!-- LEFT COLUMN -->
                <div class="col w-40 left">
                    <span itemprop="name" style="margin-top: 0;">
                        <?php if ($userInfo): ?>
                            <h1 style='margin: 0px;'>
                                <?= htmlspecialchars($userInfo['username']); ?>
                            </h1>
                        </span>
            <!-- PROFILE PICTURE BOX -->
                       <div class="general-about">
                            <div class="profile-pic">
                                <img class='pfp-fallback' style="width: 235px; height: auto; aspect-ratio: 1/1;" alt="user pfp" src='media/pfp/<?= htmlspecialchars($userInfo['pfp']); ?>'>
                            </div>
                            <div class="details">
                                <?php if (!empty($statusInfo['status'])): ?>
                                        <p>"<?= htmlspecialchars($statusInfo['status']) ?>"
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($statusInfo['you'])): ?>
                                        <p><?= htmlspecialchars($statusInfo['you']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="online"><img src="static/img/green_person.png" aria-hidden="true" alt="Online icon" loading="lazy">
                                        IN LINEA!</p>
                                </div>
                            </div>
            <!-- AUDIO -->
                            <audio controls autoplay loop id="music">
                                <source src="media/music/<?= htmlspecialchars($userInfo['music']); ?>" type="audio/ogg">
                        </audio> 
            <!-- MOOD -->

                        <div class="mood">
                            <p>
                                <b>Umore: </b>
                                <?= htmlspecialchars($statusInfo['mood'] ?? ''); ?>
                            </p>
                            <p>
                                <b>Guarda il mio:
                                    <a href="blog/user.php?id=<?= $userInfo['id'] ?>">Blog</a>
                                    <?php if ($isFriend || $userId == $profileId): ?>
                                    |
                                    <a href="bulletins/userbulletins.php?id=<?= $userInfo['id'] ?>">Bulletin</a>
                                    <?php endif; ?>
                                </b>
                            </p>
                            <p class="views">
                                <b>Visualizzazioni Profilo: </b><?= (int) $userInfo['views']; ?>
                            </p>
                            <?php if (!empty($userInfo['lastlogon'])): ?>
                            <p class="last-logon">
                                <b>Ultimo Accesso: </b><?= time_elapsed_string($userInfo['lastlogon']); ?>
                            </p>
                            <?php endif; ?>
                        </div>




                        <!-- CONTACT BOX -->
                        <div class="contact">
                            <div class="heading">
                                <h4>Contatta
                                    <?= htmlspecialchars($user); ?>
                                </h4>
                            </div>
                            <div class="inner">
                                <div class="f-row">
                                    <div class="f-col">
                                        <?php if ($isFriend): ?>
                                        <a href="unfriend.php?action=add&id=<?= htmlspecialchars($profileId); ?>"
                                            rel="nofollow">
                                            <img src="static/icons/delete.png" class="icon" aria-hidden="true" loading="lazy"
                                                alt=""> Rimuovi Amico
                                        </a>
                                        <?php elseif ($isPendingFriend): ?>
                                        <a href="requests.php"
                                            rel="nofollow">
                                            <img src="static/icons/hourglass.png" class="icon" aria-hidden="true" loading="lazy"
                                                alt=""> Richiesta in sospeso
                                        </a>
                                        <?php else: ?>
                                        <form method="post" action="friends.php?action=add" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $profileId ?>">
                                            <button type="submit" class="link-button">
                                                <img src="static/icons/add.png" class="icon" aria-hidden="true" loading="lazy"
                                                    alt=""> Aggiungi agli Amici
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <div class="f-col">
                                        <a href="addfavorite.php?id=<?= $profileId ?>" rel="nofollow">
                                            <img src="static/icons/award_star_add.png" class="icon" aria-hidden="true"
                                                loading="lazy" alt=""> Aggiungi ai Preferiti
                                        </a>
                                    </div>
                                </div>
                                <div class="f-row">
                                    <div class="f-col">
                                        <a href="conversation.php?id=<?= htmlspecialchars($profileId); ?>" rel="nofollow">
                                            <img src="static/icons/comment.png" class="icon" aria-hidden="true"
                                                loading="lazy" alt=""> Invia Messaggio
                                        </a>
                                    </div>
                                    <div class="f-col">
                                        <a href="#" rel="nofollow">
                                            <img src="static/icons/arrow_right.png" class="icon" aria-hidden="true"
                                                loading="lazy" alt=""> Inoltra a un Amico
                                        </a>
                                    </div>
                                </div>
                                <div class="f-row">
                                    <div class="f-col">
                                        <a href="#" rel="nofollow">
                                            <img src="static/icons/email.png" class="icon" aria-hidden="true" loading="lazy"
                                                alt=""> Messaggio Istantaneo
                                        </a>
                                    </div>
                                    <div class="f-col">
                                        <a href="#" rel="nofollow">
                                            <img src="static/icons/exclamation.png" class="icon" aria-hidden="true"
                                                loading="lazy" alt=""> Blocca Utente
                                        </a>
                                    </div>
                                </div>
                                <div class="f-row">
                                    <div class="f-col">
                                        <a href="groups/">
                                            <img src="static/icons/group_add.png" class="icon" aria-hidden="true"
                                                loading="lazy" alt=""> Aggiungi al Gruppo
                                        </a>
                                    </div>
                                    <div class="f-col">
                                        <a href="report.php?type=user&id=<?= htmlspecialchars($profileId); ?>" rel="nofollow">
                                            <img src="static/icons/flag_red.png" class="icon" aria-hidden="true"
                                                loading="lazy" alt=""> Segnala Utente
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>




                        <!-- URL BOX -->
                        <div class="url-info">
                            <p><b>
                                    URL di <?= htmlspecialchars(SITE_NAME); ?>:
                                </b></p>
                            <p>https://<?= htmlspecialchars(DOMAIN_NAME); ?><?= BASE_PATH ?>/profile.php?id=<?= htmlspecialchars($profileId); ?></p>
                        </div>





                        <!-- INTERESTS -->
                        <div class="table-section">
                            <div class="heading">
                                <h4>
                                    Interessi di <?= htmlspecialchars($userInfo['username']); ?>
                                </h4>
                            </div>
                            <div class="inner">
                                <table class="details-table" cellspacing="3" cellpadding="3">
                                    <tbody>
                                        <tr>
                                            <td>
                                                <p>Generali</p>
                                            </td>
                                            <td>
                                                <p>
                                                    <?= htmlspecialchars($interests['General'] ?? ''); ?>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <p>Musica</p>
                                            </td>
                                            <td>
                                                <p>
                                                    <?= htmlspecialchars($interests['Music'] ?? ''); ?>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <p>Film</p>
                                            </td>
                                            <td>
                                                <p>
                                                    <?= htmlspecialchars($interests['Movies'] ?? ''); ?>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <p>Televisione</p>
                                            </td>
                                            <td>
                                                <p>
                                                    <?= htmlspecialchars($interests['Television'] ?? ''); ?>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <p>Libri</p>
                                            </td>
                                            <td>
                                                <p>
                                                    <?= htmlspecialchars($interests['Books'] ?? ''); ?>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <p>
                                                    Eroi
                                                </p>
                                            </td>
                                            <td>
                                                <p>
                                                    <?= htmlspecialchars($interests['Heroes'] ?? ''); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>Utente non trovato.</p>
                        <?php endif; ?>
                    </div>
                </div>




                <!-- RIGHT COLUMN -->
                <div class="col right">

                <!-- UGLY BLOCK -->
                    <?php if($isFriend): ?>
                    <div class="profile-info">
                        <div class="inner">
                            <h3><?= htmlspecialchars($user) ?> è tuo Amico.</h3>
                        </div>
                    </div>
                    <?php elseif ($userId == $profileId): ?>
                    <div class="profile-info">
                        <div class="inner">
                            <h3><a href="manage.php">Modifica il tuo Profilo</a></h3>
                        </div>
                    </div>
                    <?php endif; ?>



                <!-- BLOG -->
                    <div class="blog-preview">
                        <h4>
                            Ultimi Post del Blog di <?= htmlspecialchars($userInfo['username']); ?> [<a href="blog/user.php?id=<?= $userInfo['id'] ?>">Vedi
                                il Blog</a>]
                        </h4>
                        <?php if (empty($blogEntries)): ?>
                                    <p><i>Non ci sono ancora post nel blog.</i></p>
                                <?php else: ?>
                                    <?php foreach ($blogEntries as $entry): ?>
                                        <?php
                                        $maxTitleLength = 25;
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




                    <!-- BLURBS -->
                    <div class="blurbs">
                        <div class="heading">
                            <h4>
                                Chi Sono — <?= htmlspecialchars($userInfo['username']); ?>
                            </h4>
                        </div>
                        <div class="inner">
                            <div class="section">
                                <p itemprop="description">
                                    <?= $userInfo['bio']; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($userInfo['who_meet'])): ?>
                    <div class="blurbs">
                        <div class="heading">
                            <h4>
                                Chi Vorrei Conoscere
                            </h4>
                        </div>
                        <div class="inner">
                            <div class="section">
                                <p><?= $userInfo['who_meet']; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- USER STYLES -->
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM `users` WHERE id = :id");
                    $stmt->execute(array(':id' => $_GET['id']));

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo $row['css'];
                    }
                    ?>



                    <!-- TOP 8 FRIENDS -->
                    <div class="friends">
                        <div class="heading">
                            <h4>
                                Spazio Amici di <?= htmlspecialchars($userInfo['username']); ?>
                            </h4>
                            <a class="more" href="friends.php?id=<?= $profileId ?>">[vedi tutti]</a>
                        </div>
                        <div class="inner">
                            <p><b>
                                    <?= htmlspecialchars($userInfo['username']); ?> ha <span class="count">
                                        <?= htmlspecialchars(count($friends)); ?>
                                    </span> amici.
                                </b>
                                <?php if ($userId == $profileId): ?>
                                    (<a href="topfriends.php">organizza il tuo Top 8</a>)
                                <?php endif; ?>
                            </p>
                            <div class="friends-grid">
                                <?php
                                foreach ($friendsTopEight as $friendId) {
                                    printPerson($friendId);
                                }
                                ?>
                            </div>
                        </div>
                    </div>




                    <!-- COMMENTS -->
                    <div class="friends" id="comments">
                        <div class="heading">
                            <h4>
                                Commenti degli Amici di <?= htmlspecialchars($userInfo['username']); ?>
                            </h4>
                        </div>
                        <div class="inner">
                            <p>
                                <b>
                                    Visualizzati <span class="count"><?= $countComments ?></span> di <span class="count"><?= $countTotalComments ?></span> commenti
                                    ( <a href="comments.php?id=<?= $userInfo['id'] ?>">Vedi tutti</a> | <a
                                        href="addcomment.php?id=<?= $userInfo['id'] ?>">Aggiungi
                                        Commento</a> )
                                </b>
                            </p>
                            <table class="comments-table" cellspacing="0" cellpadding="3" bordercolor="ffffff"
                                border="1">
                                <tbody>
                                    <?php include("../core/components/comments_block.php") ?> 
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </main>
        <footer>
        <p>
                <a href="https://github.com/superswan/anyspace/" target="_blank" rel="noopener">Motore AnySpace</a>
        </p>
        <p> <i>Avviso: questo progetto non è affiliato con MySpace&reg; in alcun modo.</i>
        </p>
        <ul class="links">
                <li><a href="about.php">Chi siamo</a></li>
                <li><a href="rules.php">Regole</a></li>
                <li><a href="<?= BASE_PATH ?>/docs/help.html">Aiuto</a></li>
                <li><a href="https://github.com/superswan/anyspace">Codice Sorgente</a></li>
        </ul>
        <p class="copyright">
                <a href="https://github.com/superswan/anyspace/">&copy;2024 Copyleft</a>
        </p>
</footer>
</div>

</body>

</html>
