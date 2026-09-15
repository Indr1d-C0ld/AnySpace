<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/blog.php");
require_once("../../core/site/comment.php");

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
} else {
    $blogEntryId = (int) $_GET['id'];
}

$blogEntry = fetchBlogEntry($blogEntryId);

if (!isset($_SESSION['userId'])) {
    $userId = null;
} else {
    $userId = $_SESSION['userId'];
}

// Post inesistente, oppure riservato a una cerchia di cui il lettore non fa
// parte (livelli in core/site/blog.php). I post "solo con il link" restano
// raggiungibili da qui: non compaiono negli elenchi, ma chi ha l'indirizzo
// li apre — è esattamente il loro scopo.
if (!$blogEntry || !canViewBlogEntry($blogEntry, (int) $userId)) {
    http_response_code(404);
    require("blog-header.php");
    echo '<div class="simple-container"><h1>Post non disponibile</h1>'
        . '<p>Questo post non esiste, oppure il suo autore ne ha limitato la visibilità.</p>'
        . '<p><a href="index.php">Torna al Blog</a></p></div>';
    require("../footer.php");
    exit;
}

$authorId = $blogEntry['author'];
$userInfo = fetchUserInfo($authorId);

$isUserAuthor = ($userId == $authorId);

// COMMENTS
$toid = $blogEntryId;
$limitedBlogComments = fetchBlogComments($blogEntryId, 20);
$countComments = count($limitedBlogComments);
$countTotalComments = count(fetchBlogComments($blogEntryId));
$commentType = 'blog';
?>
<?php require("blog-header.php"); ?>

<div class="row article blog-entry" itemscope itemtype="http://schema.org/Article">
    <div class="col w-20 left">
    <span itemprop="publisher" itemscope itemtype="http://schema.org/Organization">
      <meta itemprop="name" content="<?= SITE_NAME ?>">
      <meta itemprop="logo" content="https://3to.moe/a/corespace.png">
    </span>
        <!-- User Info Box -->
        <div class="edit-info">
            <div class="profile-pic">
                <img class="pfp-fallback" src="../media/pfp/<?= $userInfo['pfp'] ?>"
                    alt="<?= htmlspecialchars($userInfo['username']) ?>'s profile picture" loading="lazy">
            </div>
            <div class="author-details">
                <h4>
                    Pubblicato da
                    <span itemprop="author" itemscope itemtype="http://schema.org/Person">
                        <meta itemprop="url" content="../profile.php?id=<?= $blogEntry['author'] ?>">
                        <span itemprop="name">
                            <a href="user.php?id=<?= $blogEntry['author'] ?>">
                                <?= htmlspecialchars($userInfo['username']) ?>
                            </a>
                        </span>
                    </span>
                </h4>
                <p class="publish-date">
                    pubblicato <time class="ago" itemprop="datePublished" content="<?= $blogEntry['date'] ?>">
                        <?= time_elapsed_string($blogEntry['date']) ?>
                    </time><br>
                </p>
                <p class="category">
                  <!-- <b>Privacy:</b> <?= htmlspecialchars($blogEntry['privacy']) ?><br> !-->
                  <b>Categoria:</b> <a href="category.php?id=<?= $blogEntry['category'] ?>"><?= getCategoryName($blogEntry['category']) ?></a>
                </p>
                <p class="links">
                    <a href="user.php?id=<?= $authorId ?>">
                        <img src="../static/icons/script.png" class="icon" aria-hidden="true" loading="lazy" alt=""> <span
                            class="m-hide">Vedi il</span> Blog
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
            <?= htmlspecialchars($blogEntry['title']) ?>
        </h1>
        <?php if ($isUserAuthor): ?>
            <p class="links">
                <a href="editpost.php?id=<?= $blogEntry['id'] ?>">[modifica]</a>
                <a href="deletepost.php?id=<?= $blogEntry['id'] ?>">[elimina]</a>
                <a href="#/pin?id=<?= $blogEntry['id'] ?>">[fissa al blog]</a>
            </p>
        <?php endif; ?>
        <div class="content" itemprop="articleBody">
            <?= $blogEntry['text'] ?>
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
                        ( <a href="comments.php?id=<?= $blogEntry['id'] ?>">Vedi tutti</a> | <a href="addcomment.php?id=<?= $blogEntry['id'] ?>">Aggiungi Commento</a>
                        )
                    </b>
                </p>
                <table class="comments-table" cellspacing="0" cellpadding="3" bordercolor="ffffff" border="1">
                    <tbody>
                        <?php $comments = $limitedBlogComments ?>
                        <?php include("../../core/components/comments_block.php") ?>
                    </tbody>
                </table>
                <a href="addcomment.php?id=<?= $blogEntry['id'] ?>"><button style="margin: 14px 0;">Aggiungi un Commento</button></a>
            </div>
        </div>
    </div>
</div>

<?php require("../footer.php"); ?>