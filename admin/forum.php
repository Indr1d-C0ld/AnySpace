<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/forum.php");

admin_check();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['create_board'])) {
        if (createBoard($_POST['name'] ?? '', $_POST['description'] ?? '')) {
            $message = 'Bacheca creata.';
        } else {
            $message = 'Il nome della bacheca è obbligatorio.';
        }
    } elseif (isset($_POST['update_board'])) {
        $id = (int) $_POST['board_id'];
        updateBoard($id, $_POST['name'] ?? '', $_POST['description'] ?? '', $_POST['position'] ?? 0);
        $message = 'Bacheca aggiornata.';
    } elseif (isset($_POST['delete_board'])) {
        deleteBoard((int) $_POST['board_id']);
        $message = 'Bacheca eliminata, con tutte le discussioni al suo interno.';
    }
}

$boards = fetchBoards();
?>

<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Gestisci Bacheche del Forum</h1>

    <?php if ($message): ?>
        <p><b><?= htmlspecialchars($message) ?></b></p>
    <?php endif; ?>

    <?php if (empty($boards)): ?>
        <p><i>Nessuna bacheca ancora.</i></p>
    <?php else: ?>
        <table class="bulletin-table">
            <thead>
                <tr>
                    <th scope="col">Posizione</th>
                    <th scope="col">Nome</th>
                    <th scope="col">Descrizione</th>
                    <th scope="col">Discussioni</th>
                    <th scope="col">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($boards as $board): ?>
                    <tr>
                        <td colspan="5" style="padding:0;">
                        <form method="post" action="" onsubmit="if (event.submitter && event.submitter.name === 'delete_board') { return confirm('Eliminare questa bacheca e tutte le sue discussioni?'); }">
                            <?= csrf_field() ?>
                            <input type="hidden" name="board_id" value="<?= $board['id'] ?>">
                            <table style="width:100%;"><tr>
                                <td><input type="number" name="position" value="<?= (int) $board['position'] ?>" style="width:4em;"></td>
                                <td><input type="text" name="name" value="<?= htmlspecialchars($board['name']) ?>" required></td>
                                <td><input type="text" name="description" value="<?= htmlspecialchars($board['description']) ?>" style="width:100%;"></td>
                                <td><?= (int) $board['thread_count'] ?></td>
                                <td>
                                    <button type="submit" name="update_board">Salva</button>
                                    <button type="submit" name="delete_board">Elimina</button>
                                </td>
                            </tr></table>
                        </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <hr>
    <h2>Nuova Bacheca</h2>
    <form method="post" action="">
        <?= csrf_field() ?>
        <input type="text" name="name" placeholder="Nome" required style="width:100%; max-width:400px;"><br><br>
        <textarea name="description" rows="3" cols="58" placeholder="Descrizione"></textarea><br>
        <button type="submit" name="create_board">Crea Bacheca</button>
    </form>
</div>

<?php require("../public/footer.php"); ?>
