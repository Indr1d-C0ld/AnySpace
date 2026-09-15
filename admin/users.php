<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/user.php");

admin_check();

$actionMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    csrf_verify();
    $targetId = (int) $_POST['user_id'];
    if (isset($_POST['ban_user'])) {
        $stmt = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
        $stmt->execute(array($targetId));
        $actionMessage = "Utente bannato.";
    } elseif (isset($_POST['unban_user'])) {
        $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
        $stmt->execute(array($targetId));
        $actionMessage = "Utente sbannato.";
    }
}

$pager = paginate(countUsers(), 30);
$users = fetchUsers($pager['per_page'], $pager['offset']);
?>


<?php require("header.php"); ?>

<style>
.simple-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.bulletin-table {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

@media screen and (max-width: 768px) {
    .bulletin-table {
        font-size: 14px;
    }
}
</style>

<div class="simple-container">
    <?php if (isset($actionMessage)): ?>
        <div class="alert"><?php echo $actionMessage; ?></div>
    <?php endif; ?>
    <div class="row edit-profile">
    <!--
    <div class="col w-20 left">
    </div>
    -->
    <div class="col right">
        <h1>Gestisci Utenti</h1>
        <p>Banna, Promuovi, Reimposta Password, ecc.</p>

        <table class="bulletin-table">
    <thead>
      <tr>
        <th scope="col">ID</th>
        <th scope="col">Nome Utente</th>
        <th scope="col">Email</th>
        <th scope="col">Data Creazione</th>
        <th scope="col">Modifica</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <?php
            $userId = $user['id'];
            $username = $user['username'];
            $email = $user['email'];
            $dateCreated = $user['date'];
            $isBanned = isset($user['is_banned']) && $user['is_banned'] == 1;
        ?>
              <tr<?php echo $isBanned ? ' class="banned"' : ''; ?>>
         <!-- USED ID -->
          <td>
              <p><?= $userId ?></p>

          </td>
         <!-- Username -->
          <td>
            <a href="../public/profile.php?id=<?= (int) $userId ?>">
            <?= htmlspecialchars($username) ?>
      </a>
          </td>
         <!-- Email -->
          <td>
              <p><?= htmlspecialchars($email) ?></p>
          </td>
         <!--Date Created -->
         <td class="time-col">
            <time class="ago"><?= time_elapsed_string($user['date']) ?></time>
          </td>
          <td> 
            <form method="post" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                <?php if (!$isBanned): ?>
                    <button type="submit" name="ban_user">Banna</button>
                <?php else: ?>
                    <button type="submit" name="unban_user">Sbanna</button>
                <?php endif; ?>
            </form>
            <a href="modify_user.php?id=<?php echo $userId; ?>">
              <button type="button">Modifica</button>
            </a>
          </td>

        </tr>
      <?php endforeach; ?>


          </tbody>
  </table>
        <?= pagination_links($pager) ?>
</div>

    </div>
</div>
</div>

<?php require("../public/footer.php"); ?>