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
    $authorId = $_GET['id'];
}

$userId = $_SESSION['userId'];

if (!checkFriend($userId, $authorId)) {
    if ($userId !== $authorId) {
        header("Location: index.php");
        exit;
    }
}

$bulletins = fetchBulletinByAuthor($authorId, $limit = null);
$userInfo = fetchUserInfo($authorId);
$statusInfo = fetchUserStatus($authorId);
$isUserAuthor = ($userId == $authorId);



?>
<?php require("bulletins-header.php"); ?>

<div class="row profile">
    <div class="col w-30 left">
        <h1>
            <?= $userInfo['username'] ?>
        </h1>
        <div class="general-about">
            <div class="profile-pic ">
                <img class="pfp-fallback" src="../media/pfp/<?= $userInfo['pfp'] ?>" alt="profile picture"
                    loading="lazy">
            </div>
            <div class="details below">
                <?php if (!empty($statusInfo['status'])): ?>
                    <p>"<?= $statusInfo['status'] ?>"
                    </p>
                <?php endif; ?>
                <?php if (!empty($statusInfo['you'])): ?>
                    <p><?= $statusInfo['you'] ?>
                    </p>
                <?php endif; ?>
                <p class="online"><img src="../static/img/green_person.png" aria-hidden="true" alt="Online icon"
                        loading="lazy"> IN LINEA!</p>
            </div>
        </div>
        <div class="mood">
            <p><b>Umore:</b> <?= $statusInfo['mood'] ?></p><br>
            <p>
                <b>Guarda il mio:
                    <a href="../profile.php?id=<?= $authorId ?>">Profilo</a> |
                    <a href="../blog/user.php?id=<?= $authorId ?>">Blog</a>
                </b>
            </p>
        </div>

        <div class="url-info view-full-profile">
            <p>
                <a href="../profile.php?id=<?= $authorId ?>">
                    <b>Vedi il Profilo Completo</b>
                </a>
            </p>
        </div>
    </div>
    <div class="col right">
        <div class="blog-preview">
            <h1>
                Bulletin di <?= htmlspecialchars($userInfo['username']) ?>
            </h1>
            <?php if ($isUserAuthor): ?>
            <h2>
                [<a href="createbulletin.php">Pubblica un nuovo Bulletin</a>]
                </h2>
            <?php endif; ?>
                <br>
                <?php if (empty($bulletins)): ?>
                    <p>Nessun bulletin trovato.</p>
                <?php else: ?>
                <table class="bulletin-table" cellspacing="0" cellpadding="3" bordercolor="000000" border="1">
        <thead>
          <tr>
            <th scope="col">Ora</th>
            <th scope="col">Oggetto</th>
            <th scope="col">Commenti</th>
          </tr>
        </thead>
        <tbody>
                <?php foreach ($bulletins as $entry): ?>
                <?php $countTotalComments = count(fetchBulletinComments($entry['id'])); ?>
                      <tr>
              <td>
                <p><time class="ago"> <?= time_elapsed_string($entry['date']) ?></time></p>
              </td>
              <td class="subject">
                <a href="bulletin.php?id=<?= $entry['id'] ?>">
                  <p><b><?= $entry['title'] ?><b></p>
                </a>
              </td>
              <td>
                <a href="bulletincomments.php?id=<?= $entry['id'] ?>"><p><?= $countTotalComments ?> Commenti</p></a>              </td>
            </tr>
                <?php endforeach; ?>
                  </tbody>
      </table>
           
            <div class="pagination">
                <!-- Pagination logic here -->
            </div>
                <?php endif; ?>
        </div>
    </div>

</div>
</div>

<?php require("../footer.php"); ?>