<?php
require_once(__DIR__ . '/friend.php');

// Livelli di privacy dei post, come nel MySpace dell'epoca. Il modulo di
// pubblicazione li offriva già (radio "public/diary/friends/favorites/link")
// ma erano commentati nell'HTML e createBlogEntry() non leggeva mai il campo:
// la colonna blogs.privacy_level restava sempre 0 e nessuna query la
// consultava, quindi ogni post era di fatto pubblico.
define('BLOG_PUBLIC', 0);     // visibile a chiunque
define('BLOG_DIARY', 1);      // solo l'autore
define('BLOG_FRIENDS', 2);    // solo gli amici accettati
define('BLOG_FAVORITES', 3);  // solo chi è nei Preferiti dell'autore
define('BLOG_LINK', 4);       // solo con il link diretto: non compare in nessun elenco

function blogPrivacyFromForm($value) {
    $map = array(
        'public' => BLOG_PUBLIC,
        'diary' => BLOG_DIARY,
        'friends' => BLOG_FRIENDS,
        'favorites' => BLOG_FAVORITES,
        'link' => BLOG_LINK,
    );
    return isset($map[$value]) ? $map[$value] : BLOG_PUBLIC;
}

function blogPrivacyLabel($level) {
    $labels = array(
        BLOG_PUBLIC => 'Pubblico',
        BLOG_DIARY => 'Diario (solo io)',
        BLOG_FRIENDS => 'Solo Amici',
        BLOG_FAVORITES => 'Solo Preferiti',
        BLOG_LINK => 'Solo con il link',
    );
    return isset($labels[$level]) ? $labels[$level] : 'Pubblico';
}

/** Id degli utenti che hanno $userId fra i propri Preferiti. */
function fetchUsersWhoFavorited($userId) {
    global $conn;
    $ids = array();
    $rows = $conn->query("SELECT user_id, favorites FROM favorites")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $list = json_decode($row['favorites'], true);
        if (is_array($list) && in_array((string) $userId, array_map('strval', $list), true)) {
            $ids[] = (int) $row['user_id'];
        }
    }
    return $ids;
}

/**
 * Frammento SQL che limita una query su `blogs` ai post che $viewerId può
 * vedere. $includeLinkOnly resta false negli elenchi (i post "solo con il
 * link" non devono comparire) e true quando si apre un post specifico.
 * Gli id nelle liste IN vengono dal database e sono castati a intero.
 */
function blogVisibilitySql($viewerId, $includeLinkOnly = false) {
    $viewerId = (int) $viewerId;

    if ($viewerId > 0 && $viewerId === (int) ADMIN_USER) {
        return '1=1'; // l'amministratore vede tutto, per poter moderare
    }

    $clauses = array('privacy_level = ' . BLOG_PUBLIC);

    if ($includeLinkOnly) {
        $clauses[] = 'privacy_level = ' . BLOG_LINK;
    }

    if ($viewerId > 0) {
        $clauses[] = 'author = ' . $viewerId; // i propri post, a qualsiasi livello

        $friendIds = array_map('intval', fetchAcceptedFriendIds($viewerId));
        if ($friendIds) {
            $clauses[] = '(privacy_level = ' . BLOG_FRIENDS . ' AND author IN (' . implode(',', $friendIds) . '))';
        }

        $favoritedBy = fetchUsersWhoFavorited($viewerId);
        if ($favoritedBy) {
            $clauses[] = '(privacy_level = ' . BLOG_FAVORITES . ' AND author IN (' . implode(',', $favoritedBy) . '))';
        }
    }

    return '(' . implode(' OR ', $clauses) . ')';
}

/** Autorità unica per il singolo post (usata da entry.php, editpost, deletepost). */
function canViewBlogEntry($entry, $viewerId) {
    if (!$entry) {
        return false;
    }
    $viewerId = (int) $viewerId;
    $author = (int) $entry['author'];
    $level = (int) $entry['privacy_level'];

    if ($viewerId > 0 && ($viewerId === $author || $viewerId === (int) ADMIN_USER)) {
        return true;
    }
    if ($level === BLOG_PUBLIC || $level === BLOG_LINK) {
        return true;
    }
    if ($viewerId <= 0) {
        return false;
    }
    if ($level === BLOG_FRIENDS) {
        return checkFriend($viewerId, $author);
    }
    if ($level === BLOG_FAVORITES) {
        return in_array($author, fetchUsersWhoFavorited($viewerId), true);
    }
    return false; // BLOG_DIARY
}

function createBlogEntry($authorId, $postContent)
{
    global $conn;
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($postContent['submit'])) {
        $title = isset($postContent['subject']) ? $postContent['subject'] : '';
        $text = isset($postContent['content']) ? $postContent['content'] : '';
        $category = isset($postContent['category']) ? $postContent['category'] : 0;
        $privacy = blogPrivacyFromForm(isset($postContent['privacy']) ? $postContent['privacy'] : 'public');
        $author = $authorId;
        $date = date('Y-m-d H:i:s');

        $title = strip_tags($title);
        $text = validateContentHTML($text);

        try {
            $stmt = $conn->prepare("INSERT INTO blogs (title, text, author, category, date, privacy_level) VALUES (?, ?, ?, ?, ?, ?)");

            $stmt->bindParam(1, $title, PDO::PARAM_STR);
            $stmt->bindParam(2, $text, PDO::PARAM_STR);
            $stmt->bindParam(3, $author, PDO::PARAM_INT);
            $stmt->bindParam(4, $category, PDO::PARAM_INT);
            $stmt->bindParam(5, $date, PDO::PARAM_STR);
            $stmt->bindParam(6, $privacy, PDO::PARAM_INT);

            // Niente echo prima del redirect: l'output avviava l'invio degli
            // header e header("Location: ...") veniva semplicemente ignorato,
            // per cui dopo la pubblicazione si restava sul modulo vuoto.
            if ($stmt->execute()) {
                header("Location: user.php?id=" . (int) $authorId);
                exit;
            }
            return false;
        } catch (PDOException $e) {
            error_log('createBlogEntry fallita: ' . $e->getMessage());
            return false;
        }
    }
}

function updateBlogEntry($entryId, $authorId, $postContent) {
    global $conn;
    if (isset($postContent['submit'])) {
        // Retrieve form data
        $title = isset($postContent['subject']) ? $postContent['subject'] : '';
        $text = isset($postContent['content']) ? $postContent['content'] : '';
        $date = date('Y-m-d H:i:s'); 

        $title = strip_tags($title);
        $text = validateContentHTML($text);
        $privacy = blogPrivacyFromForm(isset($postContent['privacy']) ? $postContent['privacy'] : 'public');

        try {
            $stmt = $conn->prepare("UPDATE blogs SET title = ?, text = ?, date = ?, privacy_level = ? WHERE id = ? AND author = ?");

            $stmt->bindParam(1, $title, PDO::PARAM_STR);
            $stmt->bindParam(2, $text, PDO::PARAM_STR);
            $stmt->bindParam(3, $date, PDO::PARAM_STR);
            $stmt->bindParam(4, $privacy, PDO::PARAM_INT);
            $stmt->bindParam(5, $entryId, PDO::PARAM_INT);
            $stmt->bindParam(6, $authorId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('updateBlogEntry fallita: ' . $e->getMessage());
            return false;
        }
    }
}

function deleteBlogEntry($entryId, $authorId) {
    global $conn;
    try {
        $stmt = $conn->prepare("DELETE FROM blogs WHERE id = ? AND author = ?");
        
        $stmt->bindParam(1, $entryId, PDO::PARAM_INT);
        $stmt->bindParam(2, $authorId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "<p>Blog entry successfully deleted!</p>";
        } else {
            echo "<p>There was a problem deleting your entry.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
}

/**
 * $viewerId: chi sta guardando (0/null = visitatore anonimo). Gli elenchi
 * escludono sempre i post "solo con il link", che devono essere raggiungibili
 * unicamente conoscendone l'indirizzo.
 */
function fetchAllBlogEntries($limit = null, $viewerId = 0) {
    global $conn;
    $query = "SELECT * FROM `blogs` WHERE " . blogVisibilitySql($viewerId) . " ORDER BY date DESC";
    if ($limit !== null) {
        $query .= " LIMIT :limit";
    }
    $stmt = $conn->prepare($query);

    if ($limit !== null) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchBlogEntries($authorId, $limit = null, $viewerId = 0)
{
    global $conn;
    $query = "SELECT * FROM `blogs` WHERE author = :authorId AND " . blogVisibilitySql($viewerId)
        . " ORDER BY id DESC";
    if ($limit !== null) {
        $query .= " LIMIT :limit";
    }

    $stmt = $conn->prepare($query);

    $stmt->bindParam(':authorId', $authorId);
    if ($limit !== null) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchBlogEntry($entryId)
{
    global $conn;
    $query = "SELECT * FROM `blogs` WHERE id = :entryId";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(':entryId', $entryId);


    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getCategoryName($categoryId) {
    $categories = array(
        '0' => '',
        '1' => 'Arte',
        '2' => 'Motori',
        '3' => 'Moda',
        '4' => 'Finanza',
        '5' => 'Cibo',
        '6' => 'Giochi',
        // Era '777' => 'Vita': un refuso che lasciava scoperta la chiave '7',
        // per cui un post in quella categoria (offerta dal menu del modulo)
        // finiva su un indice inesistente.
        '7' => 'Vita',
        '8' => 'Letteratura',
        '9' => 'Scienza',
        '10' => 'Film e TV',
        '11' => 'Musica',
        '12' => 'Paranormale',
        '13' => 'Politica',
        '14' => 'Umanità',
        '15' => 'Amore',
        '16' => 'Sport',
        '17' => 'Tecnologia',
        '18' => 'Viaggi',
    );

    // Accesso difensivo: una categoria non in elenco emetteva un warning
    // "Undefined array key" invece di degradare a stringa vuota.
    return isset($categories[$categoryId]) ? $categories[$categoryId] : '';
}

function fetchBlogEntriesByCategory($categoryId, $limit = null, $viewerId = 0) {
    global $conn;
    // Update the query to filter by category
    $query = "SELECT * FROM `blogs` WHERE category = :categoryId AND " . blogVisibilitySql($viewerId)
        . " ORDER BY id DESC";
    if ($limit !== null) {
        $query .= " LIMIT :limit";
    }

    $stmt = $conn->prepare($query);

    // Bind the categoryId parameter
    $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
    if ($limit !== null) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


