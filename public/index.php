<?php
if (!file_exists("../core/config.php")) {
    header("Location: install.php");
    exit;
}

require("../core/conn.php");
require("../core/settings.php");
require("../core/site/user.php");
require("../lib/password.php");

if (isset($_SESSION['user'])) {
    header("Location: home.php");
    exit;
}

// NOTA: qui esisteva una SECONDA implementazione del login, copia sbiadita di
// quella in login.php, che saltava ogni controllo aggiunto nel frattempo:
// niente token CSRF, niente session_regenerate_id() (session fixation),
// nessun controllo sull'account bannato, nessun controllo sulla verifica
// dell'e-mail e nessuna registrazione in `sessions` (quindi la sessione non
// compariva in "Sessioni Attive" e non era revocabile). Bastava inviare il
// form a index.php invece che a login.php per aggirare ban e verifica.
// Il form di questa pagina ora punta a login.php: un solo percorso di
// autenticazione, quello irrobustito.

?>
<!DOCTYPE html>
<html>

<head>
    <title>
        <?= SITE_NAME ?> | Uno Spazio per Tutti
    </title>
    <link rel="icon" href="static/favicon.ico" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <meta name="description" content="Un social network Open Source">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/normalize.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/static/css/style.min.css">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?= DOMAIN_NAME ?>/">
    <meta property="og:title" content="<?= SITE_NAME ?>">
    <meta property="og:description" content="Uno spazio per tutti.">
    <meta property="og:image" content="https://3to.moe/a/corespace.png">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://<?= DOMAIN_NAME ?>/">
    <meta name="twitter:title" content="<?= SITE_NAME ?>">
    <meta name="twitter:description" content="<?= SITE_NAME ?>. Uno spazio per tutti">
    <meta name="twitter:image" content="https://3to.moe/a/corespace.png">

    <!-- here for responsiveness, not in css yet -->
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            width: 100%;
            overflow-x: hidden;
        }

        @media screen and (max-width: 768px) {
            .row.home {
                display: flex;
                flex-direction: column;
            }

            .col {
                width: 100%;
            }

            .col.right {
                width: 60%);
                margin: 0 auto;
            }

            .col.w-60 {
                width: 100%;
            }

            .master-container {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="master-container">
        <?php require("../core/components/navbar.php"); ?>
        <main>
            <div class="row home">
                <div class="col w-60 left">
                    <!-- Cool New People Section -->
                    <div class="new-people cool">
                        <div class="top">
                            <h4>Nuovi Utenti</h4>
                        </div>
                        <div class="inner">
                            <?php
                            $stmt = $conn->prepare("SELECT id FROM `users` ORDER BY date DESC LIMIT 4");
                            $stmt->execute();

                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                printPerson($row['id']);
                            }
                            ?>
                        </div>
                    </div>
                    <!-- Music section -->
                    <?php include("../core/components/section_music.php") ?>

                    <!-- Announcements Section -->
                    <?php include("../core/components/section_announcements.php") ?>
                </div>
                <!-- MOTD -->
                <div class="col right">
                    <div class="welcome">
                        <p>Lo sapevi? AnySpace è OpenSource!</p>
                    </div>
                    <div class="box">
                        <!-- Login/Signup Form -->
                        <h4>Accesso / Registrazione</h4>
                        <form action="<?= BASE_PATH ?>/login.php" method="post" name="theForm" id="theForm">
                            <?= csrf_field() ?>
                            <input name="client_id" type="hidden" value="web">
                            <table>
                                <tbody>
                                    <tr class="email">
                                        <td class="label"><label for="email">E-Mail:</label></td>
                                        <td class="input"><input type="email" name="email" id="email"
                                                autocomplete="email" value="" required></td>
                                    </tr>
                                    <tr class="password">
                                        <td class="label"><label for="password">Password:</label></td>
                                        <td class="input"><input name="password" type="password" id="password"
                                                autocomplete="current-password" required></td>
                                    </tr>
                                    <tr class="remember">
                                        <td></td>
                                        <td>
                                            <input type="checkbox" name="remember" value="yes" id="checkbox">
                                            <label for="checkbox">Ricorda la mia E-mail</label>
                                        </td>
                                    </tr>
                                    <tr class="buttons">
                                        <td></td>
                                        <td>
                                            <button type="submit" class="login_btn" name="action"
                                                value="login">Accedi</button>
                                            <button type="button" class="signup_btn"
                                                onclick="location.href='register.php'" name="action" value="signup">Iscriviti</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </form>
                        <a class="forgot" href="<?= BASE_PATH ?>/reset.php">Hai dimenticato la password?</a>
                    </div>

                    <div class="value-info">
                        <!-- Value proposition or other info -->
                    </div>

                    <!-- Indie Box / Donation CTA -->
                    <?php include("../core/components/section_indie_box.php") ?>

                </div>
            </div>

            <div class="row info-area">
                <div class="col info-box">
                    <h3>Social Vintage</h3>
                    <p>Sono tornate tutte le cose che ti mancavano dei vecchi Social Network: Bulletin, Blog, Forum e
                        molto altro!</p>
                    <p class="link">&raquo; <a href="register.php"
                            title="Iscriviti a <?= htmlspecialchars(SITE_NAME); ?> Oggi">Iscriviti Oggi</a></p>
                </div>
                <div class="col info-box">
                    <h3>Rispetto della Privacy</h3>
                    <p>Niente algoritmi, niente tracciamento, niente pubblicità personalizzate - solo uno spazio
                        sicuro per te e i tuoi amici!</p>
                    <p class="link">&raquo; <a href="browse.php"
                            title="Sfoglia i Profili di <?= htmlspecialchars(SITE_NAME); ?>">Sfoglia i Profili</a></p>
                </div>
                <div class="col info-box">
                    <h3>Completamente Personalizzabile</h3>
                    <p>HTML e CSS personalizzati per darti tutta la libertà di rendere il tuo Profilo davvero
                        <i>il tuo</i> Spazio sul web!
                    </p>
                    <p class="link">&raquo; <a href="layouts/"
                            title="Scopri le Grafiche personalizzate di <?= htmlspecialchars(SITE_NAME) ?>">Scopri le Grafiche</a></p>
                </div>
                <div class="col info-box">
                    <h3>Iscriviti Oggi!</h3>
                    <p>Ritrova i tuoi amici sul web o conoscine di nuovi.</p>
                    <p class="link">&raquo; <a href="register.php"
                            title="Iscriviti a <?= htmlspecialchars(SITE_NAME); ?>">Iscriviti Ora</a></p>
                </div>
            </div>


            <?php require_once("footer.php") ?>