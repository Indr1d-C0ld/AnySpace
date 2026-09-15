<?php
// Moderazione dei post del blog. La pagina era in elenco nella navbar admin
// ma conteneva solo header e footer, e l'amministratore non aveva alcun modo
// di rimuovere un post altrui: solo l'autore poteva cancellare il proprio.
require("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/user.php");
require_once("../core/site/blog.php");

admin_check();

$adminId = $_SESSION['userId'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['delete_entry'])) {
        $entryId = (int) $_POST['delete_entry'];
        $stmt = $conn->prepare("DELETE FROM blogs WHERE id = ?");
        $stmt->execute(array($entryId));
        // I commenti al post non hanno vincoli di chiave esterna: senza
        // questa riga resterebbero orfani (vedi i controlli in database.php).
        $conn->prepare("DELETE FROM blogcomments WHERE toid = ?")->execute(array($entryId));
        $_SESSION['blog_message'] = "Post #$entryId eliminato, insieme ai suoi commenti.";
        header("Location: blog.php");
        exit;
    }
}

$message = '';
if (!empty($_SESSION['blog_message'])) {
    $message = $_SESSION['blog_message'];
    unset($_SESSION['blog_message']);
}

// blogVisibilitySql() restituisce 1=1 per l'amministratore: qui si vede tutto,
// compresi i post in modalità diario, che è il senso di una pagina di
// moderazione.
$pager = paginate(countAllBlogEntries($adminId), 30);
$entries = fetchAllBlogEntries($pager['per_page'], $adminId, $pager['offset']);
?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Blog</h1>
    <p>Tutti i post pubblicati sul sito, compresi quelli a visibilità ristretta.</p>

    <?php if ($message): ?>
        <p class="settings-notice"><b><?= htmlspecialchars($message) ?></b></p>
    <?php endif; ?>

    <?php if (empty($entries)): ?>
        <p><i>Non ci sono ancora post nel blog.</i></p>
    <?php else: ?>
        <table class="bulletin-table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Titolo</th>
                    <th scope="col">Autore</th>
                    <th scope="col">Data</th>
                    <th scope="col">Visibilità</th>
                    <th scope="col">Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= (int) $entry['id'] ?></td>
                        <td>
                            <a href="../public/blog/entry.php?id=<?= (int) $entry['id'] ?>">
                                <?= htmlspecialchars($entry['title']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="../public/profile.php?id=<?= (int) $entry['author'] ?>">
                                <?= htmlspecialchars(fetchName($entry['author'])) ?>
                            </a>
                        </td>
                        <td class="time-col"><time class="ago"><?= time_elapsed_string($entry['date']) ?></time></td>
                        <td><?= htmlspecialchars(blogPrivacyLabel((int) $entry['privacy_level'])) ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Eliminare definitivamente questo post e i suoi commenti?');">
                                <?= csrf_field() ?>
                                <button type="submit" name="delete_entry" value="<?= (int) $entry['id'] ?>">Elimina</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= pagination_links($pager) ?>
    <?php endif; ?>
</div>

<?php require("../public/footer.php"); ?>
