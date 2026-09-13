<?php
// L'iscrizione a un gruppo si fa ora dalla pagina del gruppo stesso.
require("../../core/conn.php");
require_once("../../core/settings.php");
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
header("Location: viewgroup.php?id=$id");
exit;
