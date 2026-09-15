<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/blog.php");

login_check();

$user = $_SESSION['user'];
$userId = $_SESSION['userId'];


$blogEntry = fetchBlogEntry($_GET['id']);
$authorId = $blogEntry['author'];

$isUserAuthor = ($userId == $authorId);

if (!$isUserAuthor) {
  header("Location: entry.php?id=" . $blogEntry['id']);
} else {
  if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
      csrf_verify();    $commentId = $_GET['id'];
    updateBlogEntry($commentId, $userId, $_POST);
    header("Location: entry.php?id=" . $commentId);
  }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modifica Post |
    <?= SITE_NAME ?>
  </title>
  <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/normalize.css">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/header.css">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/base.css">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/my.css">
  <link rel="stylesheet" href="editor/ui/trumbowyg.min.css">
  <link rel="stylesheet" href="editor/plugins/colors/ui/trumbowyg.colors.min.css">
  <link rel="stylesheet" href="editor/plugins/emoji/ui/trumbowyg.emoji.min.css">

  <style>
    .trumbowyg-button {
      width: 20px;
      height: 20px;
      background-size: 16px 16px;
    }

    .trumbowyg-toolbar {
      height: auto;
    }
  </style>
</head>

<body>
  <div class="master-container">
    <?php require_once("../../core/components/navbar.php"); ?>
    <main>


      <div class="row edit-blog-entry">
        <div class="col w-20 left">
          <div class="edit-info">
            <p>Usa l'editor visuale WYSIWYG per modificare il contenuto.</p>
          </div>
        </div>
        <div class="col right">
          <h1>Modifica Post del Blog</h1>
          <br>

          <form method="post" class="ctrl-enter-submit">
              <?= csrf_field() ?>
            <label for="subject">Oggetto:</label>
            <input type="text" id="subject" name="subject" autocomplete="off" value="<?= $blogEntry['title'] ?>"
              required>

            <!--
      <label for="category">Category:</label>
      <select name="category" id="category" required>
        <option value="" disabled selected>Choose a Category</option>
        <option value="24">Art and Photography</option><option value="16">Automotive</option><option value="1">Blogging</option><option value="27">Books and Stories</option><option value="10">Dreams and the Supernatural</option><option value="19">Fashion, Style, Shopping</option><option value="3">Food and Restaurants</option><option value="25">Friends</option><option value="8">Games</option><option value="13">Goals, Plans, Hopes</option><option value="5">Jobs, Work, Careers</option><option value="14">Life</option><option value="6">Movies, TV, Celebrities</option><option value="15">Music</option><option value="7">News and Politics</option><option value="17">Parties and Nightlife</option><option value="9">Pets and Animals</option><option value="2">Podcast</option><option value="21">Quiz/Survey</option><option value="11">Religion and Humanity</option><option value="20">Romance and Relationships</option><option value="23">School, College, University</option><option value="4">Sports</option><option value="18">Travel and Places</option><option value="12">Web, HTML, Tech</option><option value="22">Writing and Poetry</option>      </select>
     -->
            <br><br>

            <label for="wysiwyg">Contenuto:</label>
            <div>
              <textarea class="tb_wysiwyg" id="wysiwyg" name="content"><?= $blogEntry['text'] ?></textarea>
            </div>
            <label for="privacy"><u>Privacy:</u></label>
            <div id="privacy">
              <input type="radio" id="option1" name="privacy" value="public"<?= (int) $blogEntry['privacy_level'] === 0 ? ' checked="checked"' : '' ?>>
              <label for="option1">Pubblico</label>
              <p>Chiunque potra' leggere questo post.</p>

              <input type="radio" id="option2" name="privacy" value="diary"<?= (int) $blogEntry['privacy_level'] === 1 ? ' checked="checked"' : '' ?>>
              <label for="option2">Diario (privato)</label>
              <p>Solo tu potrai leggere questo post.</p>

              <input type="radio" id="option3" name="privacy" value="friends"<?= (int) $blogEntry['privacy_level'] === 2 ? ' checked="checked"' : '' ?>>
              <label for="option3">Solo Amici</label>
              <p>Solo i tuoi Amici potranno leggere questo post.</p>

              <input type="radio" id="option4" name="privacy" value="favorites"<?= (int) $blogEntry['privacy_level'] === 3 ? ' checked="checked"' : '' ?>>
              <label for="option4">Solo Preferiti</label>
              <p>Solo gli utenti che hai aggiunto ai tuoi Preferiti potranno leggere questo post.</p>

              <input type="radio" id="option5" name="privacy" value="link"<?= (int) $blogEntry['privacy_level'] === 4 ? ' checked="checked"' : '' ?>>
              <label for="option5">Solo con il link</label>
              <p>Il post non comparira' negli elenchi (Blog, categorie, il tuo profilo): lo leggera' solo chi conosce l'indirizzo diretto.</p>
            </div>
            <br>
            <!-- L'attivazione/disattivazione dei commenti resta commentata: a
                 differenza della privacy non ha una colonna che la sostenga nel
                 database, e un'opzione che non fa nulla e' peggio di
                 un'opzione assente.
      <label for="comments"><u>Commenti:</u></label>
      <div id="comments" class="comments">
        <input type="radio" id="enable_comments" name="comments" value="enabled" checked="checked">
        <label for="enable_comments">Abilita i commenti</label>

        <input type="radio" id="disable_comments" name="comments" value="disabled" >
        <label for="disable_comments">Disabilita i commenti</label>
      </div>
-->

            <div class="publish">
              <button type="submit" name="submit">
                Aggiorna Post </button>
            </div>
          </form>


        </div>
      </div>
    </main>
    <footer>
      <p>
        <a href="https://github.com/superswan/anyspace/" target="_blank" rel="noopener">Motore
          AnySpace</a>
      </p>
      <p> <i>Avviso: questo progetto non è affiliato con MySpace&reg; in alcun modo.</i>
      </p>
      <ul class="links">
        <li><a href="about.php">Chi siamo</a></li>
        <li><a href="rules.php">Regole</a></li>
        <li><a href="https://github.com/superswan/anyspace">Codice Sorgente</a></li>
      </ul>
      <p class="copyright">
        <a href="https://github.com/superswan/anyspace/">&copy;2024 Copyleft</a>
      </p>
    </footer>

    <!-- JQuery -->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/vendor/jquery-3.3.1.min.js"><\/script>')</script>

    <!-- WSYIWIG Editor -->
    <script src="editor/trumbowyg.min.js"></script>

    <!-- Editor Plugins and Injection -->
    <script src="editor/plugins/colors/trumbowyg.colors.js"></script>
    <script src="editor/plugins/emoji/trumbowyg.emoji.min.js"></script>
    <script src="editor/plugins/pasteimage/trumbowyg.pasteimage.min.js"></script>
    <script src="editor.js"></script>

</body>

</html>