<?php
require_once("../core/conn.php"); 
require_once("../lib/sqUID.php"); // php sqUID implementation. generalize long or stupid filenames

function updateInterests($userId, $interests) {
    global $conn;
    $jsonInterests = json_encode($interests);
    $stmt = $conn->prepare("UPDATE users SET interests = ? WHERE id = ?");
    $stmt->execute(array($jsonInterests, $userId));
}

function updateBio($userId, $bio) {
    global $conn;
    $bio = sanitize_html($bio);
    $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE id = ?");
    $stmt->execute(array($bio, $userId));
}

function updateWhoMeet($userId, $text) {
    global $conn;
    $text = sanitize_html($text);
    $stmt = $conn->prepare("UPDATE users SET who_meet = ? WHERE id = ?");
    $stmt->execute(array($text, $userId));
}

function updateUserStatus($userId, $jsonStatusInfo) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute(array($jsonStatusInfo, $userId));

        echo "<p>Status successfully updated!</p>";
    } catch (PDOException $e) {
        echo "<p>Error updating status: " . $e->getMessage() . "</p>";
    }
}

function updateCSS($userId, $css) {
    global $conn;
    $css = sanitize_layout_html($css);
    $stmt = $conn->prepare("UPDATE users SET css = ? WHERE id = ?");
    $stmt->execute(array($css, $userId));
}

/** Limite di dimensione per gli upload, coerente con quanto promette manage.php. */
define('ANYSPACE_MAX_UPLOAD_BYTES', 10 * 1024 * 1024);

/**
 * Salva un file caricato dall'utente.
 *
 * Prima veniva controllata SOLO l'estensione dichiarata nel nome del file:
 * nessuna verifica del contenuto reale, nessun limite di dimensione, e
 * l'esito di is_uploaded_file() era implicito. Ora l'estensione deve essere
 * nell'elenco ammesso E il contenuto deve corrispondere davvero a quel tipo
 * (getimagesize() per le immagini, firma dei byte iniziali per l'audio).
 */
function uploadFile($userId, $file, $targetDir, $validTypes) {
    global $conn;

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        echo 'Caricamento non valido.<hr>';
        return false;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        // UPLOAD_ERR_INI_SIZE/FORM_SIZE sono i casi ordinari di file troppo
        // grande: vanno spiegati, non mostrati come codice numerico.
        if (in_array($file['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true)) {
            echo 'Il file è troppo grande (massimo 10MB).<hr>';
        } else {
            echo 'Errore durante il caricamento del file.<hr>';
        }
        error_log('Upload fallito, codice ' . $file['error']);
        return false;
    }

    if ($file['size'] > ANYSPACE_MAX_UPLOAD_BYTES) {
        echo 'Il file è troppo grande (massimo 10MB).<hr>';
        return false;
    }

    $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    if (!in_array($fileType, $validTypes, true)) {
        error_log('Estensione non ammessa: ' . $fileType);
        echo 'Tipo di file non supportato.<hr>';
        return false;
    }

    $isImage = ($targetDir === "media/pfp/");

    if ($isImage) {
        // getimagesize() legge l'intestazione vera del file: un .php
        // rinominato .jpg, o un HTML con estensione immagine, non la superano.
        $info = @getimagesize($file['tmp_name']);
        $allowedImageTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
        if ($info === false || !in_array($info[2], $allowedImageTypes, true)) {
            error_log('Contenuto non riconosciuto come immagine: ' . $file['name']);
            echo 'Il file caricato non è un\'immagine valida (jpg, png o gif).<hr>';
            return false;
        }
        // Coerenza fra contenuto reale ed estensione salvata su disco.
        $fileType = ($info[2] === IMAGETYPE_JPEG) ? 'jpg' : (($info[2] === IMAGETYPE_PNG) ? 'png' : 'gif');
    } else {
        $handle = fopen($file['tmp_name'], 'rb');
        $head = $handle ? fread($handle, 12) : '';
        if ($handle) {
            fclose($handle);
        }
        // MP3: "ID3" oppure un frame sync 0xFF 0xEx/0xFx. OGG: "OggS".
        $isMp3 = (strncmp($head, "ID3", 3) === 0)
            || (strlen($head) > 1 && ord($head[0]) === 0xFF && (ord($head[1]) & 0xE0) === 0xE0);
        $isOgg = (strncmp($head, "OggS", 4) === 0);
        if (!$isMp3 && !$isOgg) {
            error_log('Contenuto non riconosciuto come audio: ' . $file['name']);
            echo 'Il file caricato non è un audio valido (mp3 o ogg).<hr>';
            return false;
        }
        $fileType = $isOgg ? 'ogg' : 'mp3';
    }

    $prefix = $isImage ? "pfp" : "mus";
    $uniqueId = generateUniqueId($prefix);
    $newFileName = $uniqueId . "." . $fileType;
    $targetFile = $targetDir . $newFileName;

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        @chmod($targetFile, 0644); // mai eseguibile
        $column = $isImage ? "pfp" : "music";
        $stmt = $conn->prepare("UPDATE users SET $column = ? WHERE id = ?");
        $stmt->execute(array($newFileName, $userId));
        $_SESSION['uploadStatus'] = 'File caricato correttamente.';
        echo 'File caricato correttamente.<hr>';
        error_log('Uploaded file: ' . $newFileName . ' Orig: ' . $file['name'] . ' User: ' . fetchName($userId) . '(' . $userId . ')');
        return true;
    }

    error_log('Failed to move uploaded file: ' . $file["name"]);
    echo 'Si è verificato un errore durante il caricamento.<hr>';
    return false;
}

