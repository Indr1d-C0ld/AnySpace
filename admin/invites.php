<?php
// Generazione e gestione degli inviti alla registrazione.
require("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/user.php");

admin_check();

$adminId = $_SESSION['userId'];
$justCreated = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['create'])) {
        $justCreated = invite_create(
            $adminId,
            isset($_POST['count']) ? (int) $_POST['count'] : 1,
            isset($_POST['note']) ? $_POST['note'] : '',
            isset($_POST['expires']) ? (int) $_POST['expires'] : 30
        );
        $_SESSION['invite_created'] = $justCreated;
        header("Location: invites.php");
        exit;
    }

    if (isset($_POST['revoke'])) {
        invite_revoke((int) $_POST['revoke']);
        $_SESSION['invite_message'] = 'Invito annullato.';
        header("Location: invites.php");
        exit;
    }

    if (isset($_POST['purge'])) {
        $n = invite_delete_spent();
        $_SESSION['invite_message'] = "Rimossi $n inviti annullati o scaduti.";
        header("Location: invites.php");
        exit;
    }

    if (isset($_POST['toggle_mode'])) {
        // Stesso meccanismo sicuro usato da admin/index.php per le altre
        // impostazioni: si riscrive l'array della configurazione esterna con
        // var_export, mai costruendo sorgente PHP a mano.
        $currentConfig = require ANYSPACE_CONFIG_FILE;
        $currentConfig['requireInvite'] = !REQUIRE_INVITE;
        $exported = "<?php\n// AnySpace — configurazione runtime (FUORI dal docroot, non versionato).\nreturn "
            . var_export($currentConfig, true) . ";\n";
        file_put_contents(ANYSPACE_CONFIG_FILE, $exported, LOCK_EX);

        $_SESSION['invite_message'] = $currentConfig['requireInvite']
            ? 'Registrazione su invito ATTIVATA: da ora serve un codice per iscriversi.'
            : 'Registrazione APERTA: chiunque può iscriversi senza codice.';
        header("Location: invites.php");
        exit;
    }
}

$message = '';
if (!empty($_SESSION['invite_message'])) {
    $message = $_SESSION['invite_message'];
    unset($_SESSION['invite_message']);
}
if (!empty($_SESSION['invite_created'])) {
    $justCreated = $_SESSION['invite_created'];
    unset($_SESSION['invite_created']);
}

$pager = paginate(invite_count(), 30);
$invites = invite_list($pager['per_page'], $pager['offset']);
?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Inviti</h1>

    <?php if ($message): ?>
        <p class="settings-notice"><b><?= htmlspecialchars($message) ?></b></p>
    <?php endif; ?>

    <div class="setting-section">
        <div class="heading">
            <h4>Modalità di iscrizione</h4>
        </div>
        <div class="inner">
            <?php if (REQUIRE_INVITE): ?>
                <p><b>Su invito.</b> Per iscriversi serve uno dei codici qui sotto.</p>
            <?php else: ?>
                <p><b>Aperta.</b> Chiunque raggiunga il sito può iscriversi: i codici generati
                    qui restano inutilizzati finché non attivi la modalità su invito.</p>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm(<?= REQUIRE_INVITE
                ? "'Riaprire la registrazione a chiunque?'"
                : "'Attivare la registrazione su invito? Da subito servirà un codice per iscriversi.'" ?>);">
                <?= csrf_field() ?>
                <button type="submit" name="toggle_mode">
                    <?= REQUIRE_INVITE ? 'Riapri la registrazione a tutti' : 'Attiva la registrazione su invito' ?>
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($justCreated)): ?>
        <div class="setting-section">
            <div class="heading">
                <h4><?= count($justCreated) ?> <?= count($justCreated) === 1 ? 'invito creato' : 'inviti creati' ?></h4>
            </div>
            <div class="inner">
                <p>Manda a ciascuna persona il suo indirizzo: il codice arriva già compilato.</p>
                <?php foreach ($justCreated as $code): ?>
                    <p>
                        <b><?= htmlspecialchars($code) ?></b><br>
                        <input type="text" readonly onclick="this.select();" size="70"
                               value="<?= htmlspecialchars(invite_url($code)) ?>">
                    </p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="setting-section">
        <div class="heading">
            <h4>Genera nuovi inviti</h4>
        </div>
        <div class="inner">
            <form method="post">
                <?= csrf_field() ?>
                <label for="count">Quanti:</label>
                <input type="number" id="count" name="count" value="1" min="1" max="50" style="width: 6em;">
                <br><br>
                <label for="note">Promemoria (facoltativo):</label><br>
                <input type="text" id="note" name="note" size="50" maxlength="255"
                       placeholder="per chi è questo invito, così lo riconosci nell'elenco">
                <br><br>
                <label for="expires">Scadenza:</label>
                <select id="expires" name="expires">
                    <option value="7">7 giorni</option>
                    <option value="30" selected>30 giorni</option>
                    <option value="90">90 giorni</option>
                    <option value="0">non scade</option>
                </select>
                <br><br>
                <button type="submit" name="create">Genera</button>
            </form>
        </div>
    </div>

    <h2>Inviti esistenti</h2>
    <?php if (empty($invites)): ?>
        <p><i>Nessun invito generato finora.</i></p>
    <?php else: ?>
        <table class="bulletin-table">
            <thead>
                <tr>
                    <th scope="col">Codice</th>
                    <th scope="col">Promemoria</th>
                    <th scope="col">Creato</th>
                    <th scope="col">Scadenza</th>
                    <th scope="col">Stato</th>
                    <th scope="col">Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invites as $inv): ?>
                    <?php $status = invite_status_label($inv); ?>
                    <tr>
                        <td><code><?= htmlspecialchars($inv['code']) ?></code></td>
                        <td><?= htmlspecialchars($inv['note']) ?></td>
                        <td class="time-col"><time class="ago"><?= time_elapsed_string($inv['created_at']) ?></time></td>
                        <td>
                            <?= empty($inv['expires_at']) ? '<i>non scade</i>' : htmlspecialchars($inv['expires_at']) ?>
                        </td>
                        <td>
                            <?php if ($status === 'usato'): ?>
                                usato da
                                <a href="../public/profile.php?id=<?= (int) $inv['used_by'] ?>">
                                    <?= htmlspecialchars(fetchName($inv['used_by'])) ?>
                                </a>
                            <?php elseif ($status === 'disponibile'): ?>
                                <span class="db-ok">disponibile</span>
                            <?php else: ?>
                                <span class="db-warn"><?= htmlspecialchars($status) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status === 'disponibile'): ?>
                                <form method="post" onsubmit="return confirm('Annullare questo invito?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" name="revoke" value="<?= (int) $inv['id'] ?>">Annulla</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= pagination_links($pager) ?>

        <form method="post" onsubmit="return confirm('Eliminare dall\'elenco gli inviti annullati e quelli scaduti mai usati?');">
            <?= csrf_field() ?>
            <button type="submit" name="purge">Ripulisci annullati e scaduti</button>
            <small>Gli inviti già usati restano: servono a sapere chi è entrato con quale codice.</small>
        </form>
    <?php endif; ?>
</div>

<?php require("../public/footer.php"); ?>
