<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/bulletin.php");
require_once("../../core/site/comment.php");

login_check();

$userId = $_SESSION['userId'];
$pager = paginate(countAllFriendBulletins($userId));
$bulletins = fetchAllFriendBulletins($userId, $pager['per_page'], $pager['offset']);
$highlightedEntry = 1;

?>
<?php require("bulletins-header.php"); ?>

<div class="simple-container">
  <h1>Bacheca Bulletin</h1>

  <h3>[<a href="userbulletins.php?id=<?= $userId ?>">Vedi i tuoi Bulletin</a>]</h3>
  <h3>[<a href="createbulletin.php">Pubblica un nuovo Bulletin</a>]</h3>

  <p>Qui puoi vedere tutti i Bulletin pubblicati dai tuoi Amici. I Bulletin hanno una durata limitata tra 1 e 10 giorni, dopodiché spariscono definitivamente.</p>
  <?php if (!empty($bulletins)): ?>
  <table class="bulletin-table">
    <thead>
      <tr>
        <th scope="col">Da</th>
        <th scope="col" class="time-col">Ora</th>
        <th scope="col">Oggetto</th>
        <th scope="col" class="comment-col">Commenti</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bulletins as $entry): ?>
                <?php $countTotalComments = count(fetchBulletinComments($entry['id'])); ?>
              <tr>
          <td class="user-info ">
            <a href="../profile.php?id=<?= $entry['author'] ?>">
              <p><?= fetchName($entry['author']) ?></p>
            </a>
            <a href="../profile.php?id=<?= $entry['author'] ?>">
              <img class="pfp-fallback" src="../media/pfp/<?= fetchPFP($entry['author']) ?>" alt="profile picture" loading="lazy">
            </a>
          </td>
          <td class="time-col">
            <time class="ago"><?= time_elapsed_string($entry['date']) ?></time>
          </td>
          <td class="subject">
            <a href="bulletin.php?id=<?= $entry['id'] ?>">
              <b><?= $entry['title'] ?><b>
            </a>
          </td>
          <td class="comment-col">
            <a href="bulletincomments.php?id=<?= $entry['id'] ?>"><?= $countTotalComments ?> Commenti</a> </td>
        </tr>
      <?php endforeach; ?>


          </tbody>
  </table>
  <?= pagination_links($pager) ?>
    <div class="pagination">
      </div>
      <?php else: ?>
      <p> Non è stato pubblicato ancora nessun bulletin... </p>
      <?php endif; ?>
</div>



<?php require("../footer.php"); ?>