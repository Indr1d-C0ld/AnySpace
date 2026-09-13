<?php
require("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/user.php");
require("../core/site/edit.php");


login_check();

$userId = $_SESSION['userId'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    csrf_verify();
    $status = isset($_POST['category']['status']) ? strip_tags($_POST['category']['status']) : '';
    $mood = isset($_POST['category']['mood']) ? strip_tags($_POST['category']['mood']) : '';
    $you = isset($_POST['category']['you']) ? strip_tags($_POST['category']['you']) : '';

    $statusArray = array(
        "status" => $status,
        "mood" => $mood,
        "you" => $you
    );

    $jsonStatus = json_encode($statusArray);
    updateUserStatus($userId, $jsonStatus);
}

$statusInfo = fetchUserStatus($userId) ?? array();

$status = $statusInfo['status'] ?? '';
$mood = $statusInfo['mood'] ?? '';
$you = $statusInfo['you'] ?? '';


?>
<?php require("header.php"); ?>

<div class="row edit-profile">
    <div class="col w-20 left">
        <!-- SIDEBAR CONTENT -->
    </div>
    <div class="col right">
        <h1>Modifica il tuo Stato</h1>
        <p>Tutti i campi sono opzionali e puoi lasciarli vuoti se vuoi.</p>
        <?php
        $moods = array(
            '😊 Felice', '😢 Triste', '😠 Arrabbiato/a', '😴 Stanco/a', '🤩 Entusiasta',
            '😌 Rilassato/a', '😰 Ansioso/a', '🥳 Festoso/a', '😑 Annoiato/a', '🤒 Malato/a',
            '😍 Innamorato/a', '🤔 Pensieroso/a', '😎 Fico/a', '🙃 Strano/a', '😤 Frustrato/a',
            '🥰 Coccoloso/a', '😇 Angelico/a', '🤪 Un po\' pazzo/a', '😭 Distrutto/a', '🤗 Grato/a',
        );
        if ($mood !== '' && !in_array($mood, $moods, true)) {
            array_unshift($moods, $mood);
        }
        ?>
        <form method="post" class="ctrl-enter-submit">
            <?= csrf_field() ?>
            <button type="submit" name="submit">Salva Tutto</button>
            <br>
            <label for="category_status">
                <h3>Stato:</h3>
            </label>
            <p>Cosa stai facendo in questo momento?</p><input type="text" maxlength="65" class="status_input"
                id="category_status" name="category[status]" value="<?= htmlspecialchars($status) ?>">
            <p><b>Esempi:</b> <i>sto leggendo, mi rilasso, studio, dormo, ...</i></p><br><label for="category_mood">
                <h3>Umore:</h3>
            </label>
            <p>Come ti senti in questo momento?</p>
            <select class="status_input" id="category_mood" name="category[mood]">
                <option value="" <?= $mood === '' ? 'selected' : '' ?>>—</option>
                <?php foreach ($moods as $option): ?>
                    <option value="<?= htmlspecialchars($option) ?>" <?= $mood === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
                <?php endforeach; ?>
            </select>
            <br><label for="category_you">
                <h3>Tu:</h3>
            </label>
            <p>Qualche parola su di te.</p><input type="text" maxlength="65" class="status_input"
                id="category_you" name="category[you]" value="<?= htmlspecialchars($you) ?>">
            <p><b>Esempi:</b> <i>la tua età, il tuo paese, ...</i></p><br> <br><br>
            <button type="submit" name="submit">Salva Tutto</button>
        </form>
    </div>
</div>


<?php require("footer.php"); ?>