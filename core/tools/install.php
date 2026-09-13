<?php
// Installer da riga di comando per AnySpace.
// Uso: php core/tools/install.php <admin_username> <admin_email> <admin_password>
//
// Crea (se assente) l'utente #1 "Tom" — l'amico automatico di ogni nuovo
// iscritto, per fedeltà al meccanismo originale di MySpace — e l'account
// amministratore vero e proprio, poi imposta adminUser nella configurazione
// esterna. Va lanciato dalla cartella core/tools/ (require relativi).
require("../conn.php");
require("../../lib/password.php");

function fail($msg) {
    fwrite(STDERR, $msg . "\n");
    exit(1);
}

if (php_sapi_name() !== 'cli') {
    fail("Questo script va eseguito da riga di comando.");
}

if ($argc < 4) {
    fail("Uso: php install.php <admin_username> <admin_email> <admin_password>");
}

list(, $adminUsername, $adminEmail, $adminPassword) = $argv;

$countStmt = $conn->query("SELECT COUNT(*) FROM users");
if ((int) $countStmt->fetchColumn() > 0) {
    fail("Il database contiene già degli utenti: l'installer si esegue una sola volta. Usa core/tools/resetAdminPass.php per reimpostare una password.");
}

$conn->beginTransaction();
try {
    // Utente #1: "Tom" — l'amico automatico che tutti ritrovano appena registrati.
    $tomBio = "<p>Ciao! Sono Tom, il tuo primo amico qui su " . htmlspecialchars($GLOBALS['siteName'] ?? 'AnySpace') . ".</p>"
        . "<p>Benvenuto/a — buon divertimento a personalizzare il tuo profilo!</p>";
    $tomInterests = json_encode(array(
        "General" => "Fare nuove amicizie",
        "Music" => "Di tutto un po'",
        "Movies" => "",
        "Television" => "",
        "Books" => "",
        "Heroes" => "Chi si mette in gioco",
    ));
    $tomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // account non pensato per il login diretto

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, date, bio, interests) VALUES (?, ?, ?, NOW(), ?, ?)");
    $stmt->execute(array('Tom', 'tom@' . ($GLOBALS['domainName'] ?? 'localhost'), $tomPassword, $tomBio, $tomInterests));
    $tomId = (int) $conn->lastInsertId();

    // Account amministratore reale.
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, date, interests) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->execute(array($adminUsername, $adminEmail, $hashedPassword, json_encode(array(
        "General" => "", "Music" => "", "Movies" => "", "Television" => "", "Books" => "", "Heroes" => "",
    ))));
    $adminId = (int) $conn->lastInsertId();

    // L'admin è subito amico di Tom (come ogni altro utente futuro, via autoAddFriend()).
    $stmt = $conn->prepare("INSERT INTO friends (sender, receiver, status) VALUES (?, ?, 'ACCEPTED')");
    $stmt->execute(array($tomId, $adminId));

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    fail("Installazione fallita: " . $e->getMessage());
}

// Aggiorna adminUser nella configurazione esterna (stesso percorso usato da
// core/config.php, già incluso da conn.php: definito lì come
// ANYSPACE_CONFIG_FILE, non va mai duplicato come stringa letterale).
$configFile = ANYSPACE_CONFIG_FILE;
$config = require $configFile;
$config['adminUser'] = $adminId;
$exported = "<?php\n// AnySpace — configurazione runtime (FUORI dal docroot, non versionato).\nreturn "
    . var_export($config, true) . ";\n";
file_put_contents($configFile, $exported, LOCK_EX);

echo "Installazione completata.\n";
echo "  Tom (mascotte, id {$tomId}): amico automatico di ogni nuovo iscritto.\n";
echo "  Admin '{$adminUsername}' (id {$adminId}): adminUser aggiornato in {$configFile}.\n";
