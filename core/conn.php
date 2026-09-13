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
