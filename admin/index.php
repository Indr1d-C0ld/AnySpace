<?php
require("../core/conn.php");
require_once("../core/settings.php");

admin_check();

// Percorso del file di configurazione VERO, fuori dal docroot: costante
// ANYSPACE_CONFIG_FILE definita in core/config.php (incluso da conn.php),
// mai duplicata qui. Prima della bonifica del 2026-09-13 questo endpoint
// riscriveva con una regex il sorgente PHP di core/config.php: un nome sito
// contenente un apice avrebbe potuto rompere la sintassi e iniettare codice
// PHP arbitrario eseguito ad ogni richiesta. Ora si riscrive in modo sicuro
// l'array di configurazione esterno con var_export().

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    csrf_verify();

    $newSiteName = trim((string) filter_input(INPUT_POST, 'category[status]', FILTER_UNSAFE_RAW));
    $newDomainName = trim((string) filter_input(INPUT_POST, 'category[mood]', FILTER_UNSAFE_RAW));
    $newAdminUser = filter_input(INPUT_POST, 'category[you]', FILTER_VALIDATE_INT);

    $currentConfig = require ANYSPACE_CONFIG_FILE;

    if ($newSiteName !== '') {
        $currentConfig['siteName'] = $newSiteName;
    }
    if ($newDomainName !== '') {
        $currentConfig['domainName'] = $newDomainName;
    }
    if ($newAdminUser !== false && $newAdminUser !== null) {
        $currentConfig['adminUser'] = $newAdminUser;
    }

    $exported = "<?php\n// AnySpace — configurazione runtime (FUORI dal docroot, non versionato).\nreturn "
        . var_export($currentConfig, true) . ";\n";
    file_put_contents(ANYSPACE_CONFIG_FILE, $exported, LOCK_EX);

    header("Location: index.php?status=success");
    exit;
}

require("../core/config.php");
?>

<?php require("header.php"); ?>

<div class="simple-container">
    <div class="row edit-profile">
    <div class="col w-20 left">
        <!-- SIDEBAR CONTENT -->
    </div>
    <div class="col right">
        <h1>Impostazioni Generali</h1>
        <p>Qui puoi modificare le impostazioni generali del sito</p>
        <form method="post" class="ctrl-enter-submit">
            <?= csrf_field() ?>
            <button type="submit" name="submit">Salva Tutto</button>
            <br>
            <label for="category_status">
                <h3>Nome del Sito:</h3>
            </label>
            <p>Verrà mostrato nei titoli, nelle intestazioni e nei box informativi</p><input type="text" maxlength="65" class="status_input"
                id="category_status" name="category[status]" value="<?= $siteName ?>">
                <h3>Nome del Dominio:</h3>
            </label>
            <p>nome di dominio completo</p><input type="text" maxlength="65" class="status_input"
                id="category_mood" name="category[mood]" value="<?= $domainName ?>">
            <p><b>Esempio:</b> <i>anyspace.3to.moe</i></p><br><label for="category_mood">
                <h3>ID Admin:</h3>
            </label>
            <p>solo l'ID dell'utente amministratore</p><input type="text" maxlength="65" class="status_input"
                id="category_you" name="category[you]" value="<?= $adminUser ?>">
                <p></p>
            <button type="submit" name="submit">Salva Tutto</button>
        </form>
    </div>
</div>
</div>

<?php require("../public/footer.php"); ?>