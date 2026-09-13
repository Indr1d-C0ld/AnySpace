<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/bulletin.php");

login_check();

$user = $_SESSION['user'];
$userId = $_SESSION['userId'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    createBulletin($userId, $_POST);
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuovo Bulletin | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../static/css/normalize.css">
    <link rel="stylesheet" href="../static/css/header.css">
    <link rel="stylesheet" href="../static/css/base.css">
    <link rel="stylesheet" href="../static/css/my.css">
    <link rel="stylesheet" href="../blog/editor/ui/trumbowyg.min.css">
    <link rel="stylesheet" href="../blog/editor/plugins/colors/ui/trumbowyg.colors.min.css">
    <link rel="stylesheet" href="../blog/editor/plugins/emoji/ui/trumbowyg.emoji.min.css">

    <style>
.trumbowyg-toolbar {
    height: auto; 
}
</style>
</head>

<body>
    <div class="master-container">
        <?php require_once("bulletins-navbar.php"); ?>
        <main>


<div class="row edit-blog-entry">
  <div class="col w-20 left">
    <div class="edit-info">
      <p>Usa l'editor visuale WYSIWYG per modificare il contenuto.</p>
    </div>
  </div>
  <div class="col right">
    <h1>Crea Bulletin</h1>
    <br>

    <form method="post" class="ctrl-enter-submit">
      <label for="subject">Oggetto:</label>
      <input type="text" id="subject" name="subject" autocomplete="off" value="" required>

      <br><br>

      <label for="wysiwyg">Contenuto:</label>
      <div>
        <textarea class="tb_wysiwyg" id="wysiwyg" name="content"></textarea>
      </div>

      <br>
      <label for="duration">Durata:</label>
      <select name="duration" id="duration">
          <option value="1">1 giorno</option>
          <option value="3">3 giorni</option>
          <option value="5">5 giorni</option>
          <option value="7">7 giorni</option>
          <option value="10" selected>10 giorni</option>
      </select>

      <div class="publish">
        <button type="submit" name="submit">
          Pubblica Bulletin        </button>
      </div>
    </form>

    
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
<script src="../blog/editor/trumbowyg.min.js" ></script>

<!-- Editor Plugins and Injection -->
<script src="../blog/editor/plugins/colors/trumbowyg.colors.js"></script>
<script src="../blog/editor/plugins/emoji/trumbowyg.emoji.min.js"></script>
<script src="../blog/editor.js"></script>

</body>

</html>