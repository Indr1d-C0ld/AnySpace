<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/blog.php");

login_check();

$user = $_SESSION['user'];
$userId = $_SESSION['userId'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
  createBlogEntry($userId, $_POST);
}




// Doesn't use normal header since it needs css and js for editor
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nuovo Post |
    <?= SITE_NAME ?>
  </title>
  <link rel="stylesheet" href="../static/css/normalize.css">
  <link rel="stylesheet" href="../static/css/header.css">
  <link rel="stylesheet" href="../static/css/base.css">
  <link rel="stylesheet" href="../static/css/my.css">
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
          <h1>Crea Post del Blog</h1>
          <br>

          <form method="post" class="ctrl-enter-submit">
            <label for="subject">Oggetto:</label>
            <input type="text" id="subject" name="subject" autocomplete="off" value="" required>

            <label for="category">Categoria:</label>
            <select name="category" id="category" required>
              <option value="" disabled selected>Scegli una Categoria</option>
              <option value="1">Arte</option>
              <option value="2">Motori</option>
              <option value="3">Moda</option>
              <option value="4">Finanza</option>
              <option value="5">Cibo</option>
              <option value="6">Giochi</option>
              <option value="777">Vita</option>
              <option value="8">Letteratura</option>
              <option value="9">Scienza</option>
              <option value="10">Film e TV</option>
              <option value="11">Musica</option>
              <option value="12">Paranormale</option>
              <option value="13">Politica</option>
              <option value="14">Umanità</option>
              <option value="15">Amore</option>
              <option value="16">Sport</option>
              <option value="17">Tecnologia</option>
              <option value="18">Viaggi</option>
            </select>
            <br><br>

            <label for="wysiwyg">Contenuto:</label>
            <div>
              <textarea class="tb_wysiwyg" id="wysiwyg" name="content"></textarea>
            </div>
            <!--
      <label for="privacy"><u>Privacy:</u></label>
      <div id="privacy">
        <input type="radio" id="option1" name="privacy" value="public" checked="checked">
        <label for="option1">Public</label>
        <p>Everyone will be able to see your Blog Entry.</p>

        <input type="radio" id="option2" name="privacy" value="diary" >
        <label for="option2">Diary (Private)</label>
        <p>Only you will be able to see your Blog Entry.</p>

        <input type="radio" id="option3" name="privacy" value="friends" >
        <label for="option3">Friends</label>
        <p>Only your Friends will be able to see your Blog Entry.</p>

        <input type="radio" id="option4" name="privacy" value="favorites" >
        <label for="option4">Favorites List</label>
        <p>Only the Users on your "Favorites" List will be able to see your Blog Entry.</p>

        <input type="radio" id="option5" name="privacy" value="link" >
        <label for="option5">Link-only</label>
        <p>Only People who know the Link to your Blog entry will be able to see it. It won't be listed on SITE_NAME or on the category pages and on your blog page.</p>
      </div>
      <br>
      <label for="comments"><u>Comments:</u></label>
      <div id="comments" class="comments">
        <input type="radio" id="enable_comments" name="comments" value="enabled" checked="checked">
        <label for="enable_comments">Enable Comments</label>

        <input type="radio" id="disable_comments" name="comments" value="disabled" >
        <label for="disable_comments">Disable Comments</label>
      </div>
-->

            <div class="publish">
              <button type="submit" name="submit">
                Pubblica Post </button>
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
    <script src="editor.js"></script>

</body>

</html>