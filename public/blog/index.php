<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/blog.php");
require_once("../../core/site/comment.php");

$viewerId = isset($_SESSION["userId"]) ? $_SESSION["userId"] : 0;
$pager = paginate(countAllBlogEntries($viewerId));
$blogEntries = fetchAllBlogEntries($pager['per_page'], $viewerId, $pager['offset']);

// Il post "in evidenza" è il più recente, e si mostra solo sulla prima
// pagina. Veniva ripreso anche in cima all'elenco sottostante, quindi
// compariva due volte di fila su ogni pagina: qui viene tolto dall'elenco
// per non duplicarlo.
$higlighted = null;
$highlightedEntry = null;
if ($pager['page'] === 1 && !empty($blogEntries)) {
    $higlighted = array_shift($blogEntries);
    $highlightedEntry = $higlighted['id'];
}


  ?>
<?php require("blog-header.php"); ?>

<div class="row blog-category">
  <div class="col w-20 left">
    <div class="category-list">
      <b>Vedi:</b>
      <ul>
        <!--
        <li><b><a href="/"><img src="<?= BASE_PATH ?>/static/icons/asterisk_yellow.png" class="icon" aria-hidden="true" loading="lazy"
                alt=""> Top Entries</a></b></li>
-->
        <li><a href="<?= BASE_PATH ?>/blog/"><img src="<?= BASE_PATH ?>/static/icons/clock.png" class="icon" aria-hidden="true" loading="lazy" alt="">
            <b>Post Recenti</b></a></li>
            <!--
        <li><a href="<?= BASE_PATH ?>/subscriptions"><img src="<?= BASE_PATH ?>/static/icons/world.png" class="icon" aria-hidden="true" loading="lazy"
              alt=""> Subscriptions</a></li>
-->
      </ul>
      <b>Categorie:</b>
      <ul>
        <li><a href="category.php?id=1">Arte</a></li>
        <li><a href="category.php?id=2">Motori</a></li>
        <li><a href="category.php?id=3">Moda</a></li>
        <li><a href="category.php?id=4">Finanza</a></li>
        <li><a href="category.php?id=5">Cibo</a></li>
        <li><a href="category.php?id=6">Giochi</a></li>
        <li><a href="category.php?id=7">Vita</a></li>
        <li><a href="category.php?id=8">Letteratura</a></li>
        <li><a href="category.php?id=9">Scienza</a></li>
        <li><a href="category.php?id=10">Film e TV</a></li>
        <li><a href="category.php?id=11">Musica</a></li>
        <li><a href="category.php?id=12">Paranormale</a></li>
        <li><a href="category.php?id=13">Politica</a></li>
        <li><a href="category.php?id=14">Umanità</a></li>
        <li><a href="category.php?id=15">Amore</a></li>
        <li><a href="category.php?id=16">Sport</a></li>
        <li><a href="category.php?id=17">Tecnologia</a></li>
        <li><a href="category.php?id=18">Viaggi</a></li>
      </ul>
    </div>
  </div>
  <div class="col right">
    <h1>Blog</h1>
    <div class="blog-preview">
      <?php if (isset($_SESSION['userId'])): ?>
        <h3>[<a href="user.php?id=<?= (int) $_SESSION['userId'] ?>">Vedi il tuo Blog</a>]</h3>
        <h3>[<a href="newpost.php">Crea un nuovo Post</a>]</h3>
      <?php else: ?>
        <h3>[<a href="<?= BASE_PATH ?>/login.php">Accedi per scrivere sul tuo blog</a>]</h3>
      <?php endif; ?>
      <?php if ($higlighted): ?>
      <div class="blog-entries">
        <div class="entry">
          <div class="inner">
            <h3 class="title">
              <a href="entry.php?id=<?= $highlightedEntry ?>"><?= htmlspecialchars($higlighted['title']) ?></a>
            </h3>
            <p>
              <a href="entry.php?id=<?= $highlightedEntry ?>">&raquo; Leggi il Post</a>
            </p>
          </div>
        </div>
      </div>
      <hr>
      <?php endif; ?>
      <h3>Ultimi Post del Blog</h3>
      <div class="blog-entries">
        <?php foreach ($blogEntries as $entry): ?>
          <?php $countTotalComments = count(fetchBlogComments($entry['id'])); ?>
          <div class="entry">
             <p class="publish-date">
              <time class="ago"><?= time_elapsed_string($entry['date']) ?></time>
              &mdash; di <a href="user.php?id=<?= (int) $entry['author'] ?>"><?= htmlspecialchars(fetchName($entry['author'])) ?></a>
              &mdash; <a href="comments.php?id=<?= $entry['id'] ?>"><?= $countTotalComments ?> Commenti</a><!--&mdash; 0 Kudos -->           </p>
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
                echo strip_tags($previewText); ?>
                <a href="entry.php?id=<?= htmlspecialchars($entry['id']) ?>">&raquo; Continua a Leggere</a>
              </p>
              <a href="entry.php?id=<?= htmlspecialchars($entry['id']) ?>">&raquo; Vedi il Post</a>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($blogEntries)): ?>
          <p>Nessun post trovato.</p>
        <?php endif; ?>
      </div>
    </div>
    <?= pagination_links($pager) ?>
  </div>
</div>



<?php require("../footer.php"); ?>