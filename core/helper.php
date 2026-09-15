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

    // Il ban veniva controllato SOLO al login: un utente già collegato restava
    // pienamente operativo finché non scadeva la sessione, quindi "Banna
    // Utente" non aveva alcun effetto su chi era online in quel momento —
    // proprio il caso in cui serve.
    if (isUserBanned($_SESSION['userId'] ?? 0)) {
        $_SESSION = array();
        session_destroy();
        header("Location: " . BASE_PATH . "/login.php");
        exit;
    }

    touchSessionActivity();
}

/** True se l'account risulta sospeso. */
function isUserBanned($userId) {
    global $conn;
    if (!$userId) {
        return false;
    }
    $stmt = $conn->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->execute(array($userId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && !empty($row['is_banned']);
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
//
// Nota importante: qui NON si applica più htmlspecialchars() all'intero
// testo. Lo faceva la versione precedente, e in combinazione con il fatto
// che il modulo di modifica riproponeva l'HTML *già reso* invece del testo
// scritto dall'utente, ogni salvataggio successivo ri-scappava il risultato
// del precedente: "<b>" diventava "&lt;b&gt;", poi "&amp;lt;b&amp;gt;", e la
// bio degradava a testo letterale a ogni modifica.
//
// Ora la sorgente scritta dall'utente si conserva a parte (users.bio_source)
// e questa funzione si limita a tradurre i bbcode. Il confine di sicurezza
// resta HTMLPurifier, chiamato subito dopo da renderUserMarkup(): è lo stesso
// trattamento già riservato ai post del blog, che l'HTML lo accettano da
// sempre. Le sostituzioni che finiscono dentro un attributo (colore,
// dimensione, url, immagine) restano comunque a set di caratteri ristretto.
function replaceBBcodes($text) {
    $find = array(
        '~\[b\](.*?)\[/b\]~s',
        '~\[i\](.*?)\[/i\]~s',
        '~\[u\](.*?)\[/u\]~s',
        '~\[quote\](.*?)\[/quote\]~s',
        '~\[size=(\d{1,3})\](.*?)\[/size\]~s',
        '~\[color=(#?[a-zA-Z0-9]{1,20})\](.*?)\[/color\]~s',
        '~\[url\]((?:ftp|https?)://[^"><\s]+?)\[/url\]~s',
        '~\[img\](https?://[^"><\s]+?\.(?:jpg|jpeg|gif|png|bmp))\[/img\]~s'
    );
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
    return preg_replace($find, $replace, $text);
}

/**
 * Trasforma il testo scritto dall'utente (bbcode e/o HTML d'epoca) nell'HTML
 * salvato in users.bio / users.who_meet e mostrato sul profilo.
 *
 * Deterministica e stabile: applicata al proprio risultato restituisce lo
 * stesso risultato, quindi ri-salvare una bio non la degrada più.
 */
function renderUserMarkup($source) {
    $html = replaceBBcodes((string) $source);
    $html = str_replace(array("\r\n", "\r", "\n"), '<br>', $html);
    return sanitize_html($html);
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