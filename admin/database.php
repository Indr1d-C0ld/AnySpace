<?php
// Stato del database: dimensioni delle tabelle, controlli di integrità e
// manutenzione. La pagina era in elenco nella navbar admin ma conteneva solo
// header e footer: cliccarla dava una schermata vuota.
require("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/user.php");

admin_check();

$config = require ANYSPACE_CONFIG_FILE;
$dbName = $config['dbname'];

// Dimensione per tabella da information_schema; le righe si contano a parte
// con un COUNT(*) vero perché per InnoDB information_schema.table_rows è una
// stima, e su un'istanza di queste dimensioni il conteggio esatto costa nulla.
$tables = array();
$stmt = $conn->prepare(
    "SELECT table_name AS tname, data_length, index_length FROM information_schema.tables "
    . "WHERE table_schema = ? ORDER BY table_name ASC"
);
$stmt->execute(array($dbName));
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $name = $row['tname'];
    $tables[] = array(
        'name' => $name,
        'rows' => (int) $conn->query("SELECT COUNT(*) FROM `" . str_replace('`', '', $name) . "`")->fetchColumn(),
        'bytes' => (int) $row['data_length'] + (int) $row['index_length'],
    );
}

$totalBytes = 0;
$totalRows = 0;
foreach ($tables as $t) {
    $totalBytes += $t['bytes'];
    $totalRows += $t['rows'];
}

function formatBytes($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

// Controlli di coerenza: il progetto non usa vincoli di chiave esterna,
// quindi cancellando un utente restano in giro commenti, messaggi e
// amicizie che puntano a un id inesistente.
$checks = array(
    'Commenti su profili inesistenti' =>
        "SELECT COUNT(*) FROM comments c LEFT JOIN users u ON u.id = c.toid WHERE u.id IS NULL",
    'Commenti di autori inesistenti' =>
        "SELECT COUNT(*) FROM comments c LEFT JOIN users u ON u.id = c.author WHERE u.id IS NULL",
    'Messaggi con mittente o destinatario inesistente' =>
        "SELECT COUNT(*) FROM messages m LEFT JOIN users a ON a.id = m.author LEFT JOIN users t ON t.id = m.toid WHERE a.id IS NULL OR t.id IS NULL",
    'Post di blog di autori inesistenti' =>
        "SELECT COUNT(*) FROM blogs b LEFT JOIN users u ON u.id = b.author WHERE u.id IS NULL",
    'Amicizie con utenti inesistenti' =>
        "SELECT COUNT(*) FROM friends f LEFT JOIN users s ON s.id = f.sender LEFT JOIN users r ON r.id = f.receiver WHERE s.id IS NULL OR r.id IS NULL",
    'Discussioni del forum in bacheche inesistenti' =>
        "SELECT COUNT(*) FROM forum_threads t LEFT JOIN forum_boards b ON b.id = t.board_id WHERE b.id IS NULL",
    'Messaggi del forum in discussioni inesistenti' =>
        "SELECT COUNT(*) FROM forum_posts p LEFT JOIN forum_threads t ON t.id = p.thread_id WHERE t.id IS NULL",
    'Bulletin scaduti ancora in archivio' =>
        "SELECT COUNT(*) FROM bulletins WHERE expires_at IS NOT NULL AND expires_at <= NOW()",
    'Sessioni revocate o inattive da oltre 30 giorni' =>
        "SELECT COUNT(*) FROM sessions WHERE active = 0 OR last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)",
);

$results = array();
foreach ($checks as $label => $sql) {
    try {
        $results[$label] = (int) $conn->query($sql)->fetchColumn();
    } catch (PDOException $e) {
        $results[$label] = null; // tabella assente: non è un errore da mostrare
    }
}

// Manutenzione: solo operazioni che rimuovono righe già scadute o
// inutilizzabili, mai contenuti ancora validi.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $done = '';

    if (isset($_POST['purge_sessions'])) {
        $n = $conn->exec("DELETE FROM sessions WHERE active = 0 OR last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $done = "Rimosse $n sessioni revocate o scadute.";
    } elseif (isset($_POST['purge_bulletins'])) {
        $n = $conn->exec("DELETE FROM bulletins WHERE expires_at IS NOT NULL AND expires_at <= NOW()");
        $done = "Rimossi $n bulletin scaduti.";
    } elseif (isset($_POST['purge_ratelimits'])) {
        $n = $conn->exec("DELETE FROM rate_limits WHERE blocked_until IS NULL OR blocked_until < NOW()");
        $done = "Azzerati $n contatori di tentativi ormai scaduti.";
    }

    if ($done !== '') {
        // Redirect dopo POST: ricaricare la pagina non deve ripetere l'azione.
        $_SESSION['db_message'] = $done;
        header("Location: database.php");
        exit;
    }
}

$maintenanceMessage = '';
if (!empty($_SESSION['db_message'])) {
    $maintenanceMessage = $_SESSION['db_message'];
    unset($_SESSION['db_message']);
}
?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Database</h1>
    <p>Stato di <b><?= htmlspecialchars($dbName) ?></b> &mdash;
        <?= count($tables) ?> tabelle, <?= number_format($totalRows) ?> righe,
        <?= formatBytes($totalBytes) ?> su disco.</p>

    <?php if ($maintenanceMessage): ?>
        <p class="settings-notice"><b><?= htmlspecialchars($maintenanceMessage) ?></b></p>
    <?php endif; ?>

    <h2>Tabelle</h2>
    <table class="bulletin-table">
        <thead>
            <tr>
                <th scope="col">Tabella</th>
                <th scope="col">Righe</th>
                <th scope="col">Dimensione</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tables as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['name']) ?></td>
                    <td><?= number_format($t['rows']) ?></td>
                    <td><?= formatBytes($t['bytes']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Controlli di integrità</h2>
    <p>Il progetto non usa vincoli di chiave esterna: cancellando un utente o un
        contenuto possono restare righe che puntano a qualcosa che non esiste più.</p>
    <table class="bulletin-table">
        <thead>
            <tr>
                <th scope="col">Controllo</th>
                <th scope="col">Righe</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $label => $count): ?>
                <tr>
                    <td><?= htmlspecialchars($label) ?></td>
                    <td>
                        <?php if ($count === null): ?>
                            <i>non applicabile</i>
                        <?php elseif ($count === 0): ?>
                            <span class="db-ok">nessuna</span>
                        <?php else: ?>
                            <b class="db-warn"><?= number_format($count) ?></b>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Manutenzione</h2>
    <p>Queste azioni rimuovono soltanto righe già scadute o inutilizzabili.</p>
    <form method="post">
        <?= csrf_field() ?>
        <p>
            <button type="submit" name="purge_sessions">Elimina le sessioni revocate e scadute</button><br>
            <small>Le sessioni ancora valide non vengono toccate: nessuno viene disconnesso.</small>
        </p>
        <p>
            <button type="submit" name="purge_bulletins">Elimina i bulletin scaduti</button><br>
            <small>Già invisibili sul sito, occupano solo spazio.</small>
        </p>
        <p>
            <button type="submit" name="purge_ratelimits">Azzera i contatori dei tentativi scaduti</button><br>
            <small>I blocchi ancora attivi restano in vigore.</small>
        </p>
    </form>

    <h2>Backup</h2>
    <p>Il backup non si fa da qui: si esegue dal server, così il file non passa
        mai dal browser e non finisce nei log del webserver.</p>
    <pre>mysqldump --single-transaction <?= htmlspecialchars($dbName) ?> &gt; anyspace-backup.sql</pre>
</div>

<?php require("../public/footer.php"); ?>
