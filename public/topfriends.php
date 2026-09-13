<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/user.php");
require_once("../core/site/friend.php");

login_check();

$userId = $_SESSION['userId'];
$saved = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();
    $positions = isset($_POST['position']) && is_array($_POST['position']) ? $_POST['position'] : array();

    // Ordina gli id per la posizione scelta (1-8); chi non ha una posizione
    // valida non entra nella lista esplicita e viene aggiunto in coda in
    // automatico da getOrderedTopFriends().
    $ranked = array();
    foreach ($positions as $friendId => $pos) {
        $pos = (int) $pos;
        if ($pos >= 1 && $pos <= 8) {
            $ranked[$pos] = $friendId;
        }
    }
    ksort($ranked);
    saveTopFriends($userId, array_values($ranked));
    $saved = true;
}

$accepted = fetchAcceptedFriendIds($userId);
$currentOrder = getOrderedTopFriends($userId, 8);
$positionOf = array_flip($currentOrder);
?>
<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Organizza il tuo Top 8</h1>
    <p><a href="profile.php?id=<?= $userId ?>">&laquo; Torna al tuo Profilo</a></p>
    <p>Scegli la posizione (da 1 a 8) per gli amici che vuoi in evidenza nel tuo "Spazio Amici". Chi non ha una posizione viene comunque mostrato dopo, in ordine qualsiasi, finché ci sono posti liberi.</p>

    <?php if ($saved): ?>
        <p style="color: green;">Top 8 aggiornato!</p>
    <?php endif; ?>

    <?php if (empty($accepted)): ?>
        <p><i>Non hai ancora amici da mettere in Top 8.</i></p>
    <?php else: ?>
        <form method="post">
            <?= csrf_field() ?>
            <table class="comments-table" cellspacing="0" cellpadding="3" bordercolor="ffffff" border="1">
                <thead>
                    <tr>
                        <th scope="col">Amico</th>
                        <th scope="col">Posizione (1-8)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accepted as $friendId): ?>
                        <tr>
                            <td>
                                <a href="profile.php?id=<?= $friendId ?>"><?= htmlspecialchars(fetchName($friendId)) ?></a>
                            </td>
                            <td>
                                <select name="position[<?= $friendId ?>]">
                                    <option value="0">—</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?= $i ?>" <?= (isset($positionOf[$friendId]) && $positionOf[$friendId] + 1 == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <button type="submit">Salva Top 8</button>
        </form>
    <?php endif; ?>
</div>

<?php require("footer.php"); ?>
