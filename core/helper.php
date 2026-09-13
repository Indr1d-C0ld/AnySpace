<?php
function login_check() {
    if (!isset($_SESSION['user'])) {
        header("Location: " . BASE_PATH . "/login.php");
        exit;
    }

    // Se questa sessione è stata terminata da remoto (vedi Impostazioni ->
    // Sessioni Attive), sloggare subito invece di lasciarla operativa.
    if (!isCurrentSessionActive()) {
        $_SESSION = array();
        session_destroy();
        header("Location: " . BASE_PATH . "/login.php");
        exit;
    }

    touchSessionActivity();
}

// Va chiamata da OGNI pagina sotto admin/ (tranne login.php/logout.php).
// login_check() da sola verifica solo che qualcuno sia loggato, non che sia
// l'amministratore: prima della bonifica del 2026-09-13 questo permetteva a
// qualsiasi utente registrato di raggiungere l'intero pannello admin.
function admin_check() {
    login_check();
    if ((int) ($_SESSION['userId'] ?? 0) !== (int) ADMIN_USER) {
        http_response_code(403);
        exit('Accesso riservato all\'amministratore.');
    }
}

// Nota: la vecchia implementazione a base di strip_tags() lasciava passare
// attributi pericolosi (onerror=, href="javascript:...") su qualunque tag
// ammesso. Ora delegano a HTMLPurifier via core/security.php.
function validateContentHTML($validate) {
    return sanitize_html($validate);
}

function validateLayoutHTML($validate) {
    return sanitize_layout_html($validate);
}

// thanks dzhaugasharov https://gist.github.com/afsalrahim/bc8caf497a4b54c5d75d
function replaceBBcodes($text) {
    $text = htmlspecialchars($text);
    // BBcode array
    $find = array(
        '~\[b\](.*?)\[/b\]~s',
        '~\[i\](.*?)\[/i\]~s',
        '~\[u\](.*?)\[/u\]~s',
        '~\[quote\]([^"><]*?)\[/quote\]~s',
        '~\[size=([^"><]*?)\](.*?)\[/size\]~s',
        '~\[color=([^"><]*?)\](.*?)\[/color\]~s',
        '~\[url\]((?:ftp|https?)://[^"><]*?)\[/url\]~s',
        '~\[img\](https?://[^"><]*?\.(?:jpg|jpeg|gif|png|bmp))\[/img\]~s'
    );
    // HTML tags to replace BBcode
    $replace = array(
        '<b>$1</b>',
        '<i>$1</i>',
        '<span style="text-decoration:underline;">$1</span>',
        '<pre>$1</pre>',
        '<span style="font-size:$1px;">$2</span>',
        '<span style="color:$1;">$2</span>',
        '<a href="$1">$1</a>',
        '<img src="$1" alt="" />'
    );
    // Replacing the BBcodes with corresponding HTML tags
    return preg_replace($find, $replace, $text);
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks as a separate variable using days
    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    if ($weeks) {
        $string = array_merge(array('w' => 'week'), $string); 
    }

    foreach ($string as $k => &$v) {
        $value = ($k === 'w') ? $weeks : $diff->$k; 
        if ($value) {
            $v = $value . ' ' . $v . ($value > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}