<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/user.php");

login_check();

$userId = $_SESSION['userId'];

// Cast a intero obbligatorio: prima $_GET['id'] finiva grezzo sia in
// addFavorite() sia dentro l'href in fondo alla pagina, rendendo possibile
// una XSS riflessa (?id="><script>...</script>).
$profileId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$target = $profileId > 0 ? fetchUserInfo($profileId) : false;

if (!$target || $profileId === (int) $userId) {
    header("Location: browse.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    csrf_verify();
    addFavorite($userId, $profileId);
    header("Location: favorites.php");
    exit;
}

?>
<?php require("header.php"); ?>
<div class="simple-container">
  <h1><img src="static/icons/award_star_add.png" class="icon" aria-hidden="true" loading="lazy" alt=""> Aggiungi ai Preferiti</h1>
  <p>Vuoi aggiungere <b><?= htmlspecialchars($target['username']) ?></b> ai tuoi Preferiti?</p>
  <form method="post">
    <?= csrf_field() ?>
    <button type="submit" name="submit">Aggiungi ai Preferiti</button>
    <a href="profile.php?id=<?= $profileId ?>"><button type="button">Torna Indietro</button></a>
  </form>
</div>

<?php require("footer.php"); ?>
