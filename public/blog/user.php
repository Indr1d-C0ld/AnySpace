<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/blog.php");

login_check();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
} else {
    $authorId = $_GET['id'];
}

$userId = $_SESSION['userId'];

$viewerId = isset($_SESSION["userId"]) ? $_SESSION["userId"] : 0;
$pager = paginate(countBlogEntries($authorId, $viewerId));
$blogEntries = fetchBlogEntries($authorId, $pager['per_page'], $viewerId, $pager['offset']);
$userInfo = fetchUserInfo($authorId);
$statusInfo = fetchUserStatus($authorId);
$isUserAuthor = ($userId == $authorId);

?>
<?php require("blog-header.php"); ?>

<div class="row profile">
    <div class="col w-30 left">
        <h1>
            <?= htmlspecialchars($userInfo['username']) ?>
        </h1>
        <div class="general-about">
            <div class="profile-pic ">
                <img class="pfp-fallback" src="../media/pfp/<?= htmlspecialchars($userInfo['pfp']) ?>" alt="profile picture"
                    loading="lazy">
            </div>
            <div class="details below">
                <?php if (!empty($statusInfo['status'])): ?>
                    <p>"<?= htmlspecialchars($statusInfo['status']) ?>"
                    </p>
                <?php endif; ?>
                <?php if (!empty($statusInfo['you'])): ?>
                    <p><?= htmlspecialchars($statusInfo['you']) ?>
                    </p>
                <?php endif; ?>
<?php if (isUserOnline($userInfo['lastactive'])): ?>
                <p class="online"><img src="../static/img/green_person.png" aria-hidden="true" alt="Online icon"
                        loading="lazy"> IN LINEA!</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="mood">
            <p><b>Umore:</b> <?= htmlspecialchars($statusInfo['mood']) ?></p><br>
            <p>
                <b>Guarda il mio:
                    <a href="../profile.php?id=<?= $authorId ?>">Profilo</a>
                </b>
            </p>
        </div>

        <div class="url-info">
            <p><b>
                    URL del Blog su <?= SITE_NAME ?>:
                </b></p>
            <p>https://<?= DOMAIN_NAME ?><?= BASE_PATH ?>/blog/user.php?id=<?= $authorId ?>
            </p>
        </div>
        <div class="url-info view-full-profile">
            <p>
                <a href="../profile.php?id=<?= $authorId ?>">
                    <b>Vedi il Profilo Completo</b>
                </a>
            </p>
        </div>
    </div>
    <div class="col right">
        <div class="blog-preview">
            <h1>
                Post del Blog di <?= htmlspecialchars($userInfo['username']) ?>
            </h1>
            <?php if ($isUserAuthor): ?>
            <h2>
                [<a href="newpost.php">Crea un nuovo Post</a>]
                </h2>
            <?php endif; ?>
                <br>
            <div class="blog-entries">
                <?php foreach ($blogEntries as $entry): ?>
                    <div class="entry">
                        <p class="publish-date">
                            <time class="ago">
                                <?= time_elapsed_string($entry['date']) ?>
                            </time>
                            </a>
                        </p>
                        <div class="inner">
                            <h3 class="title">
                                <a href="entry.php?id=<?= htmlspecialchars($entry['id']) ?>">
                                    <?= htmlspecialchars($entry['title']) ?>
                                </a>
                            </h3>
                            <p>
                                <?php
                                $maxLength = 500;
                                $previewText = $entry['text'];
                                if (mb_strlen($previewText) > $maxLength) {
                                    $previewText = mb_substr($previewText, 0, $maxLength) . '...';
                                }
                                echo strip_tags($previewText) ?>
                                <a href="entry.php?id=<?= htmlspecialchars($entry['id']) ?>">&raquo; Continua a Leggere</a>
                            </p>
                            <br>
                            <p>
                                <a href="entry.php?id=<?= htmlspecialchars($entry['id']) ?>">&raquo; Vedi il Post</a>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($blogEntries)): ?>
                    <p>Nessun post trovato.</p>
                <?php endif; ?>
            </div>
            <?= pagination_links($pager) ?>
        </div>
    </div>

</div>
</div>

<?php require("../footer.php"); ?>