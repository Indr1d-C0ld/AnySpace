<?php
// Paginazione condivisa. Finora nessun elenco del sito ne aveva: blog,
// forum, commenti, messaggi e utenti venivano caricati interamente a ogni
// richiesta, il che regge con pochi contenuti ma degrada in fretta — e la
// pagina di una discussione lunga diventa illeggibile prima ancora di
// diventare lenta.

define('ANYSPACE_PER_PAGE', 20);

/**
 * Calcola lo stato della paginazione a partire dal totale degli elementi.
 * Il numero di pagina arriva dalla query string e viene sempre riportato
 * dentro l'intervallo valido: ?p=0, ?p=-5 e ?p=9999 non devono produrre
 * una pagina vuota o un OFFSET negativo.
 */
function paginate($totalItems, $perPage = ANYSPACE_PER_PAGE, $param = 'p') {
    $totalItems = max(0, (int) $totalItems);
    $perPage = max(1, (int) $perPage);
    $totalPages = max(1, (int) ceil($totalItems / $perPage));

    $page = isset($_GET[$param]) ? (int) $_GET[$param] : 1;
    $page = max(1, min($page, $totalPages));

    return array(
        'page' => $page,
        'per_page' => $perPage,
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
        'param' => $param,
    );
}

/**
 * Barra di navigazione fra le pagine. $baseUrl può già contenere una query
 * string: i parametri esistenti vengono conservati (serve per pagine come
 * blog/category.php?id=3 o profile comments?id=7).
 */
function pagination_links(array $pager, $baseUrl = '') {
    if ($pager['total_pages'] <= 1) {
        return '';
    }

    if ($baseUrl === '') {
        $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
    }

    // Conserva gli altri parametri della richiesta, tranne quello di pagina.
    $query = $_GET;
    unset($query[$pager['param']]);
    $prefix = $baseUrl . (strpos($baseUrl, '?') === false ? '?' : '&');
    if (!empty($query)) {
        $prefix .= http_build_query($query) . '&';
    }

    $page = $pager['page'];
    $last = $pager['total_pages'];
    $link = function ($n, $label = null) use ($prefix, $pager, $page) {
        $label = ($label === null) ? $n : $label;
        if ($n == $page) {
            return '<span class="pg-current">' . htmlspecialchars($label) . '</span>';
        }
        return '<a href="' . htmlspecialchars($prefix . $pager['param'] . '=' . $n) . '">'
            . htmlspecialchars($label) . '</a>';
    };

    $out = '<div class="pagination">';
    $out .= ($page > 1) ? $link($page - 1, '« Precedente') : '<span class="pg-disabled">« Precedente</span>';

    // Finestra di pagine attorno a quella corrente, con i salti indicati da "…".
    $window = 2;
    $shown = array(1, $last);
    for ($i = $page - $window; $i <= $page + $window; $i++) {
        if ($i >= 1 && $i <= $last) {
            $shown[] = $i;
        }
    }
    $shown = array_values(array_unique($shown));
    sort($shown);

    $previous = 0;
    foreach ($shown as $n) {
        if ($previous && $n > $previous + 1) {
            $out .= ' <span class="pg-gap">…</span> ';
        }
        $out .= ' ' . $link($n) . ' ';
        $previous = $n;
    }

    $out .= ($page < $last) ? $link($page + 1, 'Successiva »') : '<span class="pg-disabled">Successiva »</span>';
    $out .= '<div class="pg-info">Pagina ' . (int) $page . ' di ' . (int) $last
        . ' (' . (int) $pager['total_items'] . ' in totale)</div>';
    $out .= '</div>';

    return $out;
}
