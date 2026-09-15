<?php
// Shim: la configurazione e-mail vera vive fuori dal docroot.
// Il percorso è la costante ANYSPACE_EMAIL_CONFIG_FILE, definita in
// core/config.php; qui c'è un fallback perché questo file può essere incluso
// da solo, senza che config.php sia già stato caricato.
if (!defined('ANYSPACE_EMAIL_CONFIG_FILE')) {
    require_once __DIR__ . '/config.php';
}
return require ANYSPACE_EMAIL_CONFIG_FILE;
