<?php
require("../core/conn.php");
require_once("../core/settings.php");
require("../core/site/user.php");

$view = isset($_GET['view']) ? $_GET['view'] : '';

function isFilterActive($filter, $friends = null) {
    $currentView = isset($_GET['view']) ? $_GET['view'] : '';
    $currentFriends = isset($_GET['friends']) ? $_GET['friends'] : null;

    if ($currentView === $filter && $friends === null) {
        return true; // Filter is active without considering friends
    }

    if ($currentView === $filter && $currentFriends === $friends) {
        return true; // Filter is active considering friends
    }

    if ($currentView === 'new' && $friends === '') {
        return true; 
    }

    return false;
}

?>

<?php require("header.php"); ?>

<div class="simple-container">
    <h1>Sfoglia Utenti</h1>
    <p>
    Filtra per:
    <a href="browse.php" class="<?= isFilterActive('') ? 'filter-active' : '' ?>">
    <?php if (isFilterActive('')): ?>
        <img src="static/icons/tick.png" class="icon" aria-hidden="true" loading="lazy" alt="">
    <?php endif; ?>
    Tutti gli Utenti
</a>
|
<a href="browse.php?view=new" class="<?= isFilterActive('new') ? 'filter-active' : '' ?>">
    <?php if (isFilterActive('new')): ?>
        <img src="static/icons/tick.png" class="icon" aria-hidden="true" loading="lazy" alt="">
    <?php endif; ?>
    Nuovi Utenti
</a>
<!--
<p>
    Friends:
    <a href="browse.php?view=active" class="<?= isFilterActive('') ? 'filter-active' : '' ?>">
        <?php if (!isset($_GET['friends'])): ?>
            <img src="static/icons/tick.png" class="icon" aria-hidden="true" loading="lazy" alt="">
        <?php endif; ?>
        Include Friends
    </a>
    |
    <a href="browse.php?view=active&friends=no" class="<?= isFilterActive('active', 'no') ? 'filter-active' : '' ?>">
        <?php if (isFilterActive('active', 'no')): ?>
            <img src="static/icons/tick.png" class="icon" aria-hidden="true" loading="lazy" alt="">
        <?php endif; ?>
        Exclude Friends
    </a>
</p>
        -->
    <div class="new-people">
        <div class="top">
            <h4>Utenti Attivi</h4>
            <a class="more" href="#">[casuale]</a>
        </div>
        <div class="inner">
            <?php
            // Il filtro "online" interrogava una colonna `online_status` che
            // non esiste nello schema: l'eccezione PDO non gestita troncava la
            // pagina a meta'. Lo stato si ricava da users.lastactive, che il
            // sito aggiorna gia' a ogni richiesta.
            $where = '';
            $order = ' ORDER BY id DESC';
            if ($view === 'online') {
                $where = ' WHERE lastactive IS NOT NULL AND lastactive >= DATE_SUB(NOW(), INTERVAL '
                    . (int) ANYSPACE_ONLINE_MINUTES . ' MINUTE)';
                $order = ' ORDER BY lastactive DESC';
            }

            $total = (int) $conn->query("SELECT COUNT(*) FROM `users`" . $where)->fetchColumn();
            $pager = paginate($total, 24);

            $stmt = $conn->prepare("SELECT id, username, pfp FROM `users`" . $where . $order
                . " LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', (int) $pager['per_page'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $pager['offset'], PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                echo '<p><i>Nessun utente da mostrare.</i></p>';
            }
            foreach ($rows as $row) {
                // La riga e' gia' in mano: passandola si evitano due query per
                // persona che rileggevano nome e foto uno alla volta.
                printPerson($row['id'], $row);
            }
            ?>
        </div>
    </div>
    <?= pagination_links($pager) ?>
</div>

<?php require("footer.php"); ?>