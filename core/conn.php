<?php
require_once("config.php");

try {
    // Nota: EMULATE_PREPARES resta sul default (true, emulati). Diverse query
    // esistenti (es. core/site/friend.php) riusano lo stesso placeholder
    // nominato più volte nella stessa istruzione, cosa che i prepared
    // statement nativi rifiutano ("Invalid parameter number"). Qui non
    // cambia il livello di sicurezza reale: le query restano sempre
    // parametrizzate e il charset è utf8mb4 ovunque.
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Il fuso della sessione MySQL DEVE coincidere con quello di PHP
    // (core/settings.php forza UTC). Il server MariaDB gira su ora locale
    // (@@time_zone = SYSTEM), quindi NOW() restituiva un orario 2 ore avanti
    // rispetto a time()/date() di PHP. Il codice mescola le due fonti — le
    // date si scrivono ora con NOW() ora con date('Y-m-d H:i:s') e si
    // confrontano in PHP — e lo scarto produceva errori silenziosi:
    //   - i bulletin scadevano 2 ore prima del previsto;
    //   - il limite "un'e-mail di verifica ogni 60 secondi" vedeva sempre un
    //     invio "nel futuro" e quindi non rispediva mai nulla;
    //   - ogni "quanto tempo fa" (19 punti nel sito) era sfalsato di 2 ore.
    $conn->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    error_log('AnySpace DB connection failed: ' . $e->getMessage());
    if (!empty($debug)) {
        echo "Connection failed: ", htmlspecialchars($e->getMessage());
    } else {
        http_response_code(500);
        echo "Servizio temporaneamente non disponibile.";
    }
    exit;
}
