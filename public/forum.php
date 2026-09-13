<?php
// Il Forum vive ora in forum/ (bacheche -> discussioni -> messaggi).
require("../core/conn.php");
require_once("../core/settings.php");

header("Location: forum/");
exit;
