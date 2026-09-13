<?php
// Sanitizzazione HTML/CSS e helper CSRF/sessione per AnySpace.
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Sanitizza contenuto "normale" generato dall'utente (bio, commenti, post di blog).
 * Ammette un set di tag in stile MySpace 2006 (marquee, blink, font) oltre al
 * set HTML sicuro standard di HTMLPurifier, incluso lo style attribute inline
 * e gli iframe da un elenco ristretto di provider (YouTube ecc.).
 */
function sanitize_html($html) {
    static $purifier = null;
    if ($purifier === null) {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', sys_get_temp_dir());
        // "class" è ammesso su gran parte dei tag: senza, i profili "hackerati" potevano
        // contare solo sul set ristretto di CSS.AllowedProperties in stile inline — troppo
        // poco per un vero layout custom (niente transform/animazioni/pseudo-elementi).
        // Le regole vere e proprie restano quelle dentro il blocco <style>, sanitizzate a
        // parte da sanitize_layout_html()/anyspace_sanitize_style_css() senza questa
        // restrizione: lì c'è già libertà CSS piena. "class" qui serve solo ad agganciarle.
        $config->set('HTML.Allowed', 'a[href|title|class],b[class],i[class],u[class],strong[class],em[class],'
            . 'p[class],br,hr[class],ul[class],ol[class],li[class],blockquote[class],code[class],pre[class],'
            . 'h1[class],h2[class],h3[class],h4[class],h5[class],h6[class],span[style|class],div[style|class],'
            . 'table[class],tr[class],td[class],th[class],tbody,thead,'
            . 'img[src|alt|width|height|class],center,sub,sup,small[class]');
        $config->set('CSS.AllowedProperties', 'color,background-color,font-size,font-family,font-weight,'
            . 'font-style,text-align,text-decoration,border,border-color,padding,margin,width,height');
        $config->set('Attr.AllowedFrameTargets', array('_blank'));
        $config->set('HTML.TargetBlank', true);
        $config->set('URI.SafeIframeRegexp', '%^https://(www\.youtube\.com/embed/|player\.vimeo\.com/video/|open\.spotify\.com/embed/|bandcamp\.com/EmbeddedPlayer/)%');
        $config->set('HTML.SafeIframe', true);

        // Tag "retro" MySpace-style non presenti nel set standard di HTMLPurifier.
        $def = $config->getHTMLDefinition(true);
        if ($def !== null) {
            $def->addElement('marquee', 'Inline', 'Flow', 'Common', array(
                'behavior' => 'Enum#scroll,slide,alternate',
                'direction' => 'Enum#left,right,up,down',
                'scrollamount' => 'Number',
            ));
            $def->addElement('blink', 'Inline', 'Inline', 'Common');
            $def->addElement('font', 'Inline', 'Inline', 'Common', array(
                'color' => 'Text',
                'face' => 'Text',
                'size' => 'Text',
            ));
            $def->addElement('iframe', 'Inline', 'Flow', 'Common', array(
                'src' => 'URI#embedded',
                'width' => 'Length',
                'height' => 'Length',
                'frameborder' => 'Number',
                'allowfullscreen' => 'Bool',
            ));
        }

        $purifier = new HTMLPurifier($config);
    }
    return $purifier->purify((string) $html);
}

/**
 * Sanitizza le regole CSS dentro un blocco <style>...</style>: niente breakout
 * verso markup (nessun '<' o '>' sopravvive), niente vettori storici
 * (expression(), -moz-binding, behavior:, javascript:), url() ristretto a
 * relativo/https/data-image.
 */
function anyspace_sanitize_style_css($css) {
    // Rimuove i commenti per primi: evita evasioni tipo /*x*/expression(...)
    $css = preg_replace('/\/\*.*?\*\//s', '', (string) $css);

    // Invariante di sicurezza principale: nessuna parentesi angolare sopravvive,
    // quindi è strutturalmente impossibile chiudere il tag <style> che lo ospita.
    $css = str_replace(array('<', '>'), '', $css);

    $dangerous = array(
        '/expression\s*\(/i',
        '/-moz-binding\s*:/i',
        '/behavior\s*:/i',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/@import\b/i',
    );
    $css = preg_replace($dangerous, '', $css);

    $css = preg_replace_callback('/url\s*\(\s*([\'"]?)(.*?)\1\s*\)/i', function ($m) {
        $target = trim($m[2]);
        if (preg_match('~^(https://|/|data:image/)~i', $target)) {
            return "url('" . str_replace("'", "", $target) . "')";
        }
        return "url('')";
    }, $css);

    return $css;
}

/**
 * Sanitizza il campo "layout" del profilo (il vecchio trucco MySpace: incollare
 * un blocco <style> intero per rifare la grafica del proprio profilo, più
 * qualche tag decorativo). I blocchi <style> vengono ripuliti a parte con il
 * sanitizzatore CSS; il resto passa da HTMLPurifier col set di tag retro.
 */
function sanitize_layout_html($raw) {
    $raw = (string) $raw;
    $placeholders = array();
    $i = 0;
    // Testo semplice (niente byte di controllo): HTMLPurifier rimuove i
    // caratteri non stampabili, quindi un placeholder tipo "\x01STYLE0\x01"
    // arriva mutilato e non trova più corrispondenza nello strtr() finale
    // (bug osservato in produzione: il letterale "STYLE0" restava visibile
    // al posto del CSS). Il nonce impedisce che qualcuno inserisca il
    // placeholder stesso come testo per dirottare la sostituzione.
    $nonce = bin2hex(random_bytes(8));

    $withPlaceholders = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/is', function ($m) use (&$placeholders, &$i, $nonce) {
        $key = "ZZSTYLEPLACEHOLDER{$nonce}{$i}ZZ";
        $placeholders[$key] = '<style>' . anyspace_sanitize_style_css($m[1]) . '</style>';
        $i++;
        return $key;
    }, $raw);

    $cleaned = sanitize_html($withPlaceholders);

    return strtr($cleaned, $placeholders);
}

/** Genera (o riusa) il token CSRF di sessione. */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Campo hidden pronto da inserire in un <form>. */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/** Verifica il token CSRF di una richiesta POST; termina la richiesta se assente/non valido. */
function csrf_verify() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Richiesta non valida (token di sicurezza mancante o scaduto). Torna indietro e riprova.');
    }
}

/** Da chiamare subito dopo un login/registrazione riuscita: previene la session fixation. */
function regenerate_session() {
    session_regenerate_id(true);
}
