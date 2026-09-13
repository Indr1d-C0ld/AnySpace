<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/user.php");

login_check();

$userId = $_SESSION['userId'];

$favoritesArray = fetchFavorites($userId);
$favorites =json_decode($favoritesArray, true);

?>
<?php require("header.php"); ?>
<div class="simple-container">
  <h1>I Tuoi Preferiti</h1>
  <p class="info">Clicca su <b><img src="static/icons/award_star_add.png" class="icon" aria-hidden="true" loading="lazy" alt=""> Aggiungi ai Preferiti</b> su un profilo qualsiasi per aggiungere un utente a questa lista.</p>
  <div class="new-people">
    <div class="top">
      <h4>Utenti Preferiti</h4>
      <!--
      <a class="more" href="#">View Favorite Layouts</a>
      -->
    </div>
    <div class="inner">
    <?php if ($favorites): ?>
        <?php
            foreach ($favorites as $favorite) {
                printPerson($favorite);
            }
        ?>
    <?php else: ?>
      <p><i>Non hai ancora aggiunto nessun Utente ai tuoi Preferiti.</i></p>    </div>
    <?php endif; ?>
  </div>
</div>

<?php require("footer.php"); ?>