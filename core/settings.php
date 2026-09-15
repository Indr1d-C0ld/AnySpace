<?php
// Don't delete this

// Cookie di sessione rinforzato — va impostato PRIMA di session_start().
session_set_cookie_params(array(
    'lifetime' => 0,
    'path'     => (isset($basePath) ? $basePath : '') . '/',
    'domain'   => '',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
));
session_start();

define("DEBUG", !empty($debug));

if (DEBUG === true) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

// Site localization settings
define("SITE_NAME", $siteName);
define("DOMAIN_NAME", $domainName);
define("ADMIN_USER", $adminUser);
// Percorso base per l'hosting in sottocartella (es. "/anyspace"). Stringa vuota se in root.
define("BASE_PATH", isset($basePath) ? rtrim($basePath, '/') : '');

// Set TimeZone (Time elapse will not display right if this doesn't match server)
date_default_timezone_set('UTC');

// sorry >.< , will eventually combine includes into single file
include("helper.php");
include("security.php");
include("site/session.php");
include("ratelimit.php");
include("pagination.php");
