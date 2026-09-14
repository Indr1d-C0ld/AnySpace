<?php
// EDIT PROFILE page
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../core/site/user.php");
require_once("../core/site/edit.php");

login_check();

$userInfo = fetchUserInfo($_SESSION['userId']); // Assume this function exists and fetches user info
if ($userInfo) {
    $bio = $userInfo['bio'];
    $whoMeet = $userInfo['who_meet'];
    $css = $userInfo['css'];
    $userId = $userInfo['id'];
    $interests = json_decode($userInfo['interests'], true) ?: array("General" => "", "Music" => "", "Movies" => "", "Television" => "", "Books" => "", "Heroes" => "");
} else {
    echo "Utente non trovato.";
    exit;
}

$noticeMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();
    if (@$_POST['interestset']) {
        // Gli interessi sono salvati come testo grezzo e sempre stampati con
        // htmlspecialchars() in profile.php: nessuna sanitizzazione qui (il
        // vecchio commento "this is probably an XSS vuln" era infondato).
        $sanitizedInterests = array_map(function ($interest) {
            return is_string($interest) ? $interest : '';
        }, (array) $_POST['interests']);
        updateInterests($userId, $sanitizedInterests);
        header("Location: manage.php");
        exit;
    } elseif (isset($_POST['usernameset'])) {
        $newUsername = trim($_POST['newUsername']);
        // Lo stesso limite della registrazione (erano 21 lì e 50 qui) e un
        // controllo di unicità: senza, l'UPDATE violava l'indice UNIQUE e
        // l'eccezione PDO non gestita restituiva un errore 500 all'utente.
        if ($newUsername === '' || mb_strlen($newUsername) > 21) {
            $noticeMessage = 'Il nome utente deve avere da 1 a 21 caratteri.';
        } else {
            $dup = $conn->prepare("SELECT id FROM users WHERE username = ? AND id <> ?");
            $dup->execute(array($newUsername, $userId));
            if ($dup->fetch()) {
                $noticeMessage = 'Questo nome utente è già in uso.';
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->execute(array($newUsername, $userId));
                $_SESSION['user'] = $newUsername;
                header("Location: manage.php");
                exit;
            }
        }
    } else if (@$_POST['bioset']) {
        $unprocessedText = replaceBBcodes($_POST['bio']);
        $text = str_replace(PHP_EOL, "<br>", $unprocessedText);
        updateBio($userId, $text);
        header("Location: manage.php");
        exit;
    } else if (@$_POST['whomeetset']) {
        $unprocessedText = replaceBBcodes($_POST['who_meet']);
        $text = str_replace(PHP_EOL, "<br>", $unprocessedText);
        updateWhoMeet($userId, $text);
        header("Location: manage.php");
        exit;
    } else if (@$_POST['cssset']) {
        $validatedcss = validateLayoutHTML($_POST['css']);
        updateCSS($userId, $validatedcss);
        header("Location: manage.php");
        exit;
    } else if (@$_POST['submit']) {
        uploadFile($userId, $_FILES["fileToUpload"], "media/pfp/", array('jpg', 'png', 'jpeg', 'gif'));
    } elseif (isset($_POST['photoset'])) { // For music upload
        uploadFile($userId, $_FILES["fileToUpload"], "media/music/", array('mp3', 'ogg'));
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="static/css/header.css">
    <link rel="stylesheet" href="static/css/base.css">
    <link rel="stylesheet" href="static/css/my.css">
</head>

<body>
    <div class="master-container">
        <?php require("../core/components/navbar.php"); ?>
        <main>
            <div class="row edit-profile">
                <div class="col w-20 left"></div>
                <div class="col right">
                    <h1>Modifica Profilo</h1>
                    <p>Tutti i campi sono opzionali e possono essere lasciati vuoti</p>
                    <a href="profile.php?id=<?= $_SESSION['userId'] ?>">&laquo; Vedi Profilo</a>
                    <div class="profile-pic">
                        <?php
                        echo '<h1>' . htmlspecialchars($_SESSION['user']) . '</h1><br>' . '<img width="180px" height="auto" src="media/pfp/' . htmlspecialchars(fetchPFP($_SESSION['userId'])) . '"><br>';
                        ?>
                    </div>
                    <hr>
                    <h1>Cambia Nome:</h1>
                    <br>
                    <?php if ($noticeMessage !== ''): ?>
                        <p style="color:#CC0000;"><b><?= htmlspecialchars($noticeMessage) ?></b></p>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input size="77" maxlength="21" type="text" name="newUsername" placeholder="Nuovo Nome Utente"
                            value="<?php echo htmlspecialchars($_SESSION['user']); ?>"><br>
                        <input name="usernameset" type="submit" value="Cambia Nome" style="max-width: 100%;"> <small>massimo: 21
                            caratteri</small>
                    </form>
                    <br>


                    <br>
                    <h1>Foto Profilo e Canzone:</h1>
                    <br>
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <small>Scegli la foto:</small>
                        <input type="file" name="fileToUpload" id="fileToUpload">
                        <input type="submit" value="Carica Immagine" name="submit">
                    </form>
                    <small>Dimensione massima: 10MB (jpg/png/gif)</small>
                    <hr style="max-width: 80%;">
                    <br>
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <small>Scegli la canzone:</small>
                        <input type="file" name="fileToUpload" id="fileToUpload">
                        <input type="submit" value="Carica Canzone" name="photoset">
                    </form>
                    <small>Dimensione massima: 10MB (mp3/ogg)</small>
                    <hr style="max-width: 80%;">
                    <br>
                    <h1>Chi Sono:</h1>
                    <br>
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <textarea required cols="58" placeholder="Chi sono" name="bio"><?php echo htmlspecialchars($bio); ?></textarea><br>
                        <input name="bioset" type="submit" value="Imposta"> <small>limite massimo: 500 caratteri | supporta
                            bbcode</small>
                    </form>
                    <br>
                    <h1>Chi Vorrei Conoscere:</h1>
                    <br>
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <textarea cols="58" placeholder="Chi vorrei conoscere" name="who_meet"><?php echo htmlspecialchars($whoMeet); ?></textarea><br>
                        <input name="whomeetset" type="submit" value="Imposta"> <small>limite massimo: 500 caratteri | supporta
                            bbcode</small>
                    </form>
                    <br>
                    <h1>Interessi:</h1>
                    <br>
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <label for="general">Generali:</label>
                        <input type="text" id="general" name="interests[General]"
                            value="<?php echo htmlspecialchars($interests['General']); ?>">
                            <br>
                            <br>

                        <label for="music">Musica:</label>
                        <input type="text" id="music" name="interests[Music]"
                            value="<?php echo htmlspecialchars($interests['Music']); ?>"><br>
                            <br>

                        <label for="movies">Film:</label>
                        <input type="text" id="movies" name="interests[Movies]"
                            value="<?php echo htmlspecialchars($interests['Movies']); ?>"><br>
                            <br>

                        <label for="television">Televisione:</label>
                        <input type="text" id="television" name="interests[Television]"
                            value="<?php echo htmlspecialchars($interests['Television']); ?>"><br>
                            <br>

                        <label for="books">Libri:</label>
                        <input type="text" id="books" name="interests[Books]"
                            value="<?php echo htmlspecialchars($interests['Books']); ?>"><br>
                            <br>

                        <label for="heroes">Eroi:</label>
                        <input type="text" id="heroes" name="interests[Heroes]"
                            value="<?php echo htmlspecialchars($interests['Heroes']); ?>"><br>
                            <br>

                        <input name="interestset" type="submit" value="Imposta">
                        <small>limite massimo: 500 caratteri | supporta bbcode</small>
                    </form>

                    <br>
                    <h1>Grafica:</h1>
                    <small>quello che normalmente incolleresti nella sezione 'Biografia'. Puoi includere tag HTML.</small>
                    <br>
                    <form accept-charset="UTF-8" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <textarea required rows="15" cols="58" placeholder="Il tuo codice"
                            name="css"><?php echo htmlspecialchars($css); ?></textarea><br>
                        <input name="cssset" type="submit" value="Imposta"> <small>limite massimo: nessuno</small>
                    </form>
                    <br>

                </div>
            </div>
            <?php require_once("footer.php") ?>