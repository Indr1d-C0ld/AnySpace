<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/bulletin.php");
require_once("../../core/site/comment.php");

login_check();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
} else {
    $bulletinId = $_GET['id'];
}


$bulletin = fetchBulletin($bulletinId);
$authorId = $bulletin['author'];

$userInfo = fetchUserInfo($authorId);

// included components expect specific variables
$userId = $_SESSION['userId'];
$toid = $bulletinId;
$isUserAuthor = ($userId == $authorId);

// COMMENTS
$limitedBulletinComments = fetchBulletinComments($bulletinId, 20);
$countComments = count($limitedBulletinComments);
$countTotalComments = countBulletinComments($bulletinId);

$comments = $limitedBulletinComments;
$commentType = 'bulletin';
?>
<?php require("bulletins-header.php"); ?>

<div class="row article blog-entry" itemscope itemtype="http://schema.org/Article">
    <div class="col w-20 left">
    <span itemprop="publisher" itemscope itemtype="http://schema.org/Organization">
      <meta itemprop="name" content="<?= SITE_NAME ?>">
      <meta itemprop="logo" content="https://3to.moe/a/corespace.png">
    </span>
        <div class="edit-info">
            <div class="profile-pic">
                <img class="pfp-fallback" src="../media/pfp/<?= $userInfo['pfp'] ?>"
                    alt="<?= htmlspecialchars($userInfo['username']) ?>'s profile picture" loading="lazy">
            </div>
            <div class="author-details">
                <h4>
                    Pubblicato da
                    <span itemprop="author" itemscope itemtype="http://schema.org/Person">
                        <meta itemprop="url" content="../profile.php?id=<?= $bulletin['author'] ?>">
                        <span itemprop="name">
                            <a href="userbulletins.php?id=<?= $bulletin['author'] ?>">
                                <?= htmlspecialchars($userInfo['username']) ?>
                            </a>
                        </span>
                    </span>
                </h4>
                <p class="publish-date">
                    pubblicato <time class="ago" itemprop="datePublished" content="<?= $bulletin['date'] ?>">
                        <?= time_elapsed_string($bulletin['date']) ?>
                    </time><br>
                </p>
                <p class="links">
                    <a href="userbulletins.php?id=<?= $authorId ?>">
                        <img src="../static/icons/text_list_bullets.png" class="icon" aria-hidden="true" loading="lazy" alt=""> <span
                            class="m-hide">Vedi i</span> Bulletin
                    </a>
                    <a href="../profile.php?id=<?= $authorId ?>">
                        <img src="../static/icons/user.png" class="icon" aria-hidden="true" loading="lazy" alt=""> <span
                            class="m-hide">Vedi il</span> Profilo
                    </a>

            </div>
        </div>
    </div>
    <div class="col right">
        <h1 class="title" itemprop="headline name">
            <?= htmlspecialchars($bulletin['title']) ?>
        </h1>
        <?php if ($isUserAuthor): ?>
            <p class="links">
                <a href="deletebulletin.php?id=<?= $bulletin['id'] ?>">[elimina]</a>
            </p>
        <?php endif; ?>
        <div class="content" itemprop="articleBody">
            <?= $bulletin['text'] ?>
        </div>
        <!-- Comments Section -->
        <br>
        <div class="comments" id="comments">
            <div class="heading">
                <h4>Commenti</h4>
            </div>
            <div class="inner">
                <meta itemprop="commentCount" content="0">
                <p>
                    <b>
                        Visualizzati <span class="count"><?= $countComments ?></span> di <span class="count"><?= $countTotalComments ?></span> commenti
                        ( <a href="bulletincomments.php?id=<?= $bulletin['id'] ?>">Vedi tutti</a> | <a href="addbulletincomment.php?id=<?= $bulletin['id'] ?>">Aggiungi Commento</a>
                        )
                    </b>
                </p>
                <?php include("../../core/components/comments_table.php")?>
                <a href="addcomment.php?id=<?= $bulletin['id'] ?>"><button style="margin: 14px 0;">Aggiungi un Commento</button></a>
            </div>
        </div>
    </div>
</div>

<?php require("../footer.php"); ?>