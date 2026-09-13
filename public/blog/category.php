<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/blog.php");
require_once("../../core/site/comment.php");

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$blogEntries = fetchBlogEntriesByCategory($categoryId);

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
        <li><a href="category.php?id=777">Vita</a></li>
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
    <h1>Categoria: <?= getCategoryName($categoryId) ?></h1>
    <div class="blog-preview">
      <h3>[<a href="user.php?id=<?= $_SESSION['userId'] ?>">Vedi il tuo Blog</a>]</h3>
      <h3>[<a href="newpost.php">Crea un nuovo Post</a>]</h3>
      <h3>Ultimi Post del Blog</h3>
      <div class="blog-entries">
        <?php foreach ($blogEntries as $entry): ?>
          <?php $countTotalComments = count(fetchBlogComments($entry['id'])); ?>
          <div class="entry">
             <p class="publish-date">
              <time class="ago"><?= time_elapsed_string($entry['date']) ?></time>
              &mdash; di <a href="user.php?id=<?= $entry['author'] ?>"><?= fetchName($entry['author']) ?></a>
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
    <div class="pagination">
      <a class="next" rel="next" href="/?page=2">
        <button>
          Pagina Successiva
        </button>
      </a>
    </div>
  </div>
</div>



<?php require("../footer.php"); ?>