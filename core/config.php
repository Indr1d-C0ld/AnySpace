<?php
// Shim: la configurazione vera vive fuori dal docroot, vedi /data/anyspace/config/.
// Questo file non contiene segreti e può restare nell'albero servito (bloccato
// comunque da Apache a livello di <Directory>, ma niente credenziali qui per
// difesa in profondità).
//
// Il percorso è definito UNA SOLA VOLTA qui: se sposti il file di
// configurazione, cambia solo la riga sotto (core/tools/install.php lo
// rilegge da questa stessa costante, non lo duplica).
if (!defined('ANYSPACE_CONFIG_FILE')) {
    define('ANYSPACE_CONFIG_FILE', '/data/anyspace/config/config.php');
}
extract(require ANYSPACE_CONFIG_FILE);
