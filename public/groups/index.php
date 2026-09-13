<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/group.php");

login_check();

$userId = $_SESSION['userId'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_group'])) {
    csrf_verify();
    $groupId = createGroup($userId, $_POST['name'] ?? '', $_POST['description'] ?? '');
    if ($groupId) {
        header("Location: viewgroup.php?id=$groupId");
        exit;
    }
    $message = 'Il nome del gruppo è obbligatorio.';
}

$groups = fetchAllGroups();
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gruppi | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/normalize.min.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/style.min.css">
</head>

<body>
    <div class="master-container">
        <?php require_once("../../core/components/navbar.php"); ?>
        <main>
            <div class="simple-container">
                <h1>Gruppi</h1>

                <?php if ($message): ?>
                    <p style="color:red;"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>

                <?php if (empty($groups)): ?>
                    <p><i>Nessun gruppo ancora. Creane uno tu!</i></p>
                <?php else: ?>
                    <table class="bulletin-table">
                        <thead>
                            <tr>
                                <th scope="col">Nome</th>
                                <th scope="col">Descrizione</th>
                                <th scope="col">Membri</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $group): ?>
                                <tr>
                                    <td><a href="viewgroup.php?id=<?= $group['id'] ?>"><b><?= htmlspecialchars($group['name']) ?></b></a></td>
                                    <td><?= htmlspecialchars($group['description']) ?></td>
                                    <td><?= count(fetchGroupMembers($group)) ?></td>
                                    <td><a href="viewgroup.php?id=<?= $group['id'] ?>">Vedi</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <hr>
                <h2>Crea un nuovo Gruppo</h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="text" name="name" placeholder="Nome del gruppo" required style="width:100%; max-width:400px;"><br><br>
                    <textarea name="description" rows="4" cols="58" placeholder="Descrizione"></textarea><br>
                    <button type="submit" name="create_group">Crea Gruppo</button>
                </form>
            </div>
        </main>
        <?php require("../footer.php"); ?>
    </div>
</body>

</html>
