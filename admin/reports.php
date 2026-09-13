<?php
// Site functions
require("../core/conn.php");
require_once("../core/settings.php");

// Page functions
require("../core/site/user.php");
require("../core/site/report.php");

admin_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_id'])) {
    csrf_verify();
    deleteReport((int) $_POST['resolve_id']);
    header("Location: reports.php");
    exit;
}

$reports = fetchReports();
?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Segnalazioni</h1>
    <?php if (empty($reports)): ?>
        <p><i>Nessuna segnalazione al momento.</i></p>
    <?php else: ?>
        <table class="bulletin-table">
            <thead>
                <tr>
                    <th scope="col">Quando</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Contenuto</th>
                    <th scope="col">Segnalato da</th>
                    <th scope="col">Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><time class="ago"><?= time_elapsed_string($report['date']) ?></time></td>
                        <td><?= htmlspecialchars(report_type_label($report['content_type'])) ?></td>
                        <td>
                            <?php if ((int) $report['content_type'] === REPORT_TYPE_USER): ?>
                                <a href="../public/profile.php?id=<?= $report['content_id'] ?>">Profilo #<?= $report['content_id'] ?></a>
                            <?php else: ?>
                                ID contenuto: <?= $report['content_id'] ?>
                            <?php endif; ?>
                        </td>
                        <td><a href="../public/profile.php?id=<?= $report['creator_id'] ?>"><?= htmlspecialchars(fetchName($report['creator_id'])) ?></a></td>
                        <td>
                            <form method="post" action="">
                                <?= csrf_field() ?>
                                <input type="hidden" name="resolve_id" value="<?= $report['id'] ?>">
                                <button type="submit">Segna come Risolta</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require("../public/footer.php"); ?>
