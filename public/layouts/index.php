<?php
require("../../core/conn.php");
require_once("../../core/settings.php");
require_once("../../core/site/user.php");
require_once("../../core/site/layout.php");

login_check();

$userId = $_SESSION['userId'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();
    if (isset($_POST['submit_layout'])) {
        if (createLayout($userId, $_POST['title'] ?? '', $_POST['code'] ?? '')) {
            $message = 'Grafica pubblicata nella galleria!';
        } else {
            $message = 'Titolo e codice sono obbligatori.';
        }
    } elseif (isset($_POST['apply_layout'])) {
        applyLayoutToUser($userId, (int) $_POST['layout_id']);
        header("Location: ../manage.php");
        exit;
    }
}

$layouts = fetchAllLayouts();
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafiche | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/normalize.min.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/style.min.css">
</head>

<body>
    <div class="master-container">
        <?php require_once("../../core/components/navbar.php"); ?>
        <main>
            <div class="simple-container">
                <h1>Galleria Grafiche</h1>
                <p>Grafiche pronte da applicare al tuo profilo, condivise da altri utenti. Applicarne una sostituisce
                    la tua Grafica attuale (modificabile in qualsiasi momento da <a href="../manage.php">Modifica Profilo</a>).</p>

                <?php if ($message): ?>
                    <p><b><?= htmlspecialchars($message) ?></b></p>
                <?php endif; ?>

                <?php if (empty($layouts)): ?>
                    <p><i>Nessuna grafica pubblicata ancora. Sii il primo!</i></p>
                <?php else: ?>
                    <table class="bulletin-table">
                        <thead>
                            <tr>
                                <th scope="col">Titolo</th>
                                <th scope="col">Autore</th>
                                <th scope="col">Pubblicata</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($layouts as $layout): ?>
                                <tr>
                                    <td><?= htmlspecialchars($layout['title']) ?></td>
                                    <td><a href="../profile.php?id=<?= $layout['author'] ?>"><?= htmlspecialchars(fetchName($layout['author'])) ?></a></td>
                                    <td><time class="ago"><?= time_elapsed_string($layout['date']) ?></time></td>
                                    <td>
                                        <details>
                                            <summary>Vedi codice</summary>
                                            <pre style="white-space: pre-wrap; max-width: 400px; overflow-x: auto;"><?= htmlspecialchars($layout['code']) ?></pre>
                                        </details>
                                        <form method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="layout_id" value="<?= $layout['id'] ?>">
                                            <button type="submit" name="apply_layout">Applica al mio Profilo</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <hr>
                <h2>Pubblica la tua Grafica</h2>
                <p>Incolla qui il codice (HTML/CSS, incluso il tag <code>&lt;style&gt;</code>) della grafica che hai creato per il tuo profilo, così altri potranno usarla.</p>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="text" name="title" placeholder="Titolo della grafica" required style="width: 100%; max-width: 400px;"><br><br>
                    <textarea name="code" rows="10" cols="68" placeholder="Il tuo codice" required></textarea><br>
                    <button type="submit" name="submit_layout">Pubblica nella Galleria</button>
                </form>
            </div>
        </main>
        <?php require("../footer.php"); ?>
    </div>
</body>

</html>
