<?php
// Client SMTP minimale, senza dipendenze esterne (STARTTLS su 587, AUTH LOGIN,
// un solo destinatario, corpo testo o HTML semplice). Riusa l'account Brevo
// del forum phpBB — vedi /data/anyspace/config/email_config.php.

function anyspace_email_config() {
    static $config = null;
    if ($config === null) {
        $config = require '/data/anyspace/config/email_config.php';
    }
    return $config;
}

function send_mail($to, $subject, $body, $isHtml = true) {
    $config = anyspace_email_config();

    if (empty($config['smtp_host']) || empty($config['smtp_username'])) {
        error_log("AnySpace mail: configurazione SMTP mancante, invio a $to saltato.");
        return false;
    }

    $host = $config['smtp_host'];
    $port = (int) $config['smtp_port'];
    $fromEmail = $config['from_email'];
    $fromName = $config['from_name'] ?? 'AnySpace';

    $socket = @stream_socket_client(
        "tcp://$host:$port",
        $errno,
        $errstr,
        15,
        STREAM_CLIENT_CONNECT
    );
    if (!$socket) {
        error_log("AnySpace mail: connessione SMTP fallita ($errno $errstr)");
        return false;
    }
    stream_set_timeout($socket, 15);

    $read = function () use ($socket) {
        $data = '';
        while (($line = fgets($socket, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $write = function ($cmd) use ($socket) {
        fwrite($socket, $cmd . "\r\n");
    };
    $expect = function ($code) use ($read) {
        $resp = $read();
        return strpos($resp, (string) $code) === 0;
    };

    try {
        if (!$expect(220)) throw new Exception('nessun saluto dal server');

        $write("EHLO anyspace.local");
        $read();

        $write("STARTTLS");
        if (!$expect(220)) throw new Exception('STARTTLS rifiutato');

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception('impossibile avviare TLS');
        }

        $write("EHLO anyspace.local");
        $read();

        $write("AUTH LOGIN");
        if (!$expect(334)) throw new Exception('AUTH LOGIN rifiutato');

        $write(base64_encode($config['smtp_username']));
        if (!$expect(334)) throw new Exception('username SMTP rifiutato');

        $write(base64_encode($config['smtp_password']));
        if (!$expect(235)) throw new Exception('autenticazione SMTP fallita');

        $write("MAIL FROM:<$fromEmail>");
        if (!$expect(250)) throw new Exception('MAIL FROM rifiutato');

        $write("RCPT TO:<$to>");
        if (!$expect(250) && !$expect(251)) throw new Exception('RCPT TO rifiutato');

        $write("DATA");
        if (!$expect(354)) throw new Exception('DATA rifiutato');

        $contentType = $isHtml ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8';
        $headers = array(
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'To: <' . $to . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: ' . $contentType,
            'Date: ' . date('r'),
        );
        // Rimuove ogni riga che contenga solo "." per non troncare il DATA in anticipo.
        $escapedBody = preg_replace('/^\./m', '..', $body);
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $escapedBody . "\r\n.";
        $write($message);
        if (!$expect(250)) throw new Exception('invio del messaggio fallito');

        $write("QUIT");
        fclose($socket);
        return true;
    } catch (Exception $e) {
        error_log("AnySpace mail: invio a $to fallito — " . $e->getMessage());
        fclose($socket);
        return false;
    }
}
