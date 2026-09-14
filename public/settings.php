<?php
require("../core/conn.php");
require_once("../core/settings.php");
require_once("../lib/password.php");
require("../core/site/user.php");
require("../core/site/edit.php");


login_check();

$userId = $_SESSION['userId'];

// Sessioni aperte prima che questa funzionalità esistesse non hanno ancora
// una riga in tabella: la creiamo al volo alla prima visita di questa pagina.
recordSession($userId, $_SESSION['user']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();
}

if (isset($_POST['revoke_session'])) {
    revokeSession($_POST['revoke_session'], $userId);
    header("Location: settings.php");
    exit;
} elseif (isset($_POST['revoke_others'])) {
    revokeOtherSessions($userId, session_id());
    header("Location: settings.php");
    exit;
}

// Messaggi mostrati dentro il layout (prima venivano stampati con echo PRIMA
// di header.php, quindi comparivano sopra la pagina, fuori dal tema).
$settingsMessages = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // --- E-mail -----------------------------------------------------------
    // Il campo esisteva nel form fin dall'inizio ma NESSUNO lo leggeva: si
    // poteva modificare l'indirizzo, premere "Salva Tutto" e ritrovarsi il
    // vecchio valore senza il minimo avviso.
    $newEmail = trim((string) ($_POST['email'] ?? ''));
    $currentEmail = fetchEmail($userId);
    if ($newEmail !== '' && $newEmail !== $currentEmail) {
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $settingsMessages[] = "L'indirizzo e-mail non è valido.";
        } else {
            $dup = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
            $dup->execute(array($newEmail, $userId));
            if ($dup->fetch()) {
                $settingsMessages[] = "Questo indirizzo e-mail è già associato a un altro account.";
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute(array($newEmail, $userId));
                $settingsMessages[] = "Indirizzo e-mail aggiornato.";
            }
        }
    }

    // --- Visibilità del profilo -------------------------------------------
    // Idem: la tendina prometteva "solo gli Amici possono vederne il
    // contenuto" ma il valore non veniva mai salvato e la colonna
    // users.private non era letta da nessuna parte del sito.
    $visibility = ($_POST['profile_visibility'] ?? 'public') === 'private' ? 1 : 0;
    $stmt = $conn->prepare("UPDATE users SET private = ? WHERE id = ?");
    $stmt->execute(array($visibility, $userId));
}

if (!empty($_POST['password-old']) || !empty($_POST['password-new']) || !empty($_POST['password-confirm'])) {
    $oldPassword = (string) ($_POST['password-old'] ?? '');
    $newPassword = (string) ($_POST['password-new'] ?? '');
    $confirmPassword = (string) ($_POST['password-confirm'] ?? '');

    $currentUserPassword = fetchUserPassword($userId);
    $currentUserPassword = $currentUserPassword['password'];

    if (!password_verify($oldPassword, $currentUserPassword)) {
        $settingsMessages[] = "La vecchia password non è corretta.";
    } elseif ($newPassword !== $confirmPassword) {
        $settingsMessages[] = "Le nuove password non coincidono.";
    } elseif (strlen($newPassword) < 8) {
        $settingsMessages[] = "La nuova password deve avere almeno 8 caratteri.";
    } else {
        changePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));

        // Si cambia password soprattutto quando si teme che qualcun altro sia
        // entrato: senza questa revoca le SUE sessioni restavano valide, cioè
        // esattamente ciò da cui ci si stava difendendo.
        revokeOtherSessions($userId, session_id());
        $settingsMessages[] = "Password aggiornata. Tutte le altre sessioni sono state terminate.";
    }
}

$currentPrivate = (int) ($conn->query("SELECT private FROM users WHERE id = " . (int) $userId)->fetchColumn());

?>
<?php require("header.php"); ?>

<div class="simple-container">
  <h1>Impostazioni Account</h1>
    <?php foreach ($settingsMessages as $m): ?>
      <p class="settings-notice"><b><?= htmlspecialchars($m) ?></b></p>
    <?php endforeach; ?>
    <form method="post" class="ctrl-enter-submit">
    <?= csrf_field() ?>
    <div class="setting-section">
      <div class="heading">
        <h4>Dati di Base</h4>
      </div>
      <div class="inner">
        <label for="id">ID Account:</label>
        <input type="text" id="id" value="<?= $userId ?>" readonly disabled>
        <br>
        <br>
        <label for="name">Indirizzo Email:</label>
        <input type="email" id="email" name="email" autocomplete="email" value="<?= fetchEmail($userId) ?>" required>
        <br>
        <br>
        <label for="name">Il tuo Nome:</label>
        <input type="text" id="name" value="<?= fetchName($userId) ?>" readonly disabled>
        <small>Modificabile nella pagina <a href="manage.php">Modifica Profilo</a></small>
        <!-- Currently Not Implemented
        <br>
        <br>
        <label for="username">Username: (optional)</label>
        <span class="username-box">
          https://<?= DOMAIN_NAME ?>/
          <input type="text" id="username" name="username" autocomplete="username" value="">
        </span>
        <p class="info">
          If you set a Username, you will get a custom URL for your Profile. Example: <b>https://<?= DOMAIN_NAME ?>/username</b><br><br>
          <b>Attention:</b> If you change your Username, your previous Profile URL won't work anymore and your Username will be available for other people again!
        </p>
          -->
      </div>
    </div>
        <div class="setting-section">
      <div class="heading">
        <h4>Cambia Password</h4>
      </div>
      <div class="inner">
      <label for="id">Vecchia Password:</label>
        <input type="password" id="id" value="" name="password-old" noautocomplete>
        <br>
        <br>
        <label for="name">Nuova Password:</label>
        <input type="password" id="id" value="" name="password-new" noautocomplete>
        <br>
        <br>
        <label for="name">Conferma Nuova Password:</label>
        <input type="password" id="id" value="" name="password-confirm" noautocomplete>
      </div>
    </div>
    <div class="setting-section">
      <div class="heading">
        <h4>Privacy</h4>
      </div>
      <div class="inner">
        <!-- real check doesn't exist yet
        <label for="show_online">Online Status:</label>
        <input type="checkbox" id="show_online" name="show_online" checked> Show Online Status on your Profile
                <br>
        <br>
    
        <label for="im_privacy">Who can start an IM conversation with you:</label>
        <select name="im_privacy" id="im_privacy" required>
          <option value="friends" selected>Your Friends</option>
          <option value="everyone" >Everyone</option>
          <option value="noone" >No one</option>
        </select>
        <br>
        <br>
          -->
        <label for="profile_visibility">Chi può vedere il tuo Profilo:</label>
        <select name="profile_visibility" id="profile_visibility" required>
          <option value="public" <?= $currentPrivate ? '' : 'selected' ?>>Tutti (Pubblico)</option>
          <option value="private" <?= $currentPrivate ? 'selected' : '' ?>>Solo Amici (Privato)</option>
        </select>
        <p class="info">Se il tuo Profilo è impostato come <b>privato</b>, solo gli Amici possono vederne il contenuto. Tutti gli altri contenuti che pubblichi resteranno pubblici.</p>

      </div>
    </div>

    <button type="submit" name="submit">Salva Tutto</button>
  </form>

  <div class="setting-section">
    <div class="heading">
      <h4>Sessioni Attive</h4>
    </div>
    <div class="inner">
      <?php $currentSessionId = session_id(); $sessions = fetchUserSessions($userId); ?>
      <table class="settings-sessions-table" border="1" cellspacing="0" cellpadding="3">
        <tr>
          <th>Dispositivo</th>
          <th>Ultima Attività</th>
          <th style="text-align:center;">Azione</th>
        </tr>
        <?php foreach ($sessions as $session): ?>
          <tr>
            <td><?= htmlspecialchars(parseUserAgent($session['user_agent'])) ?></td>
            <td><time class="ago"><?= time_elapsed_string($session['last_activity']) ?></time></td>
            <td style="text-align:center;">
              <?php if ($session['session_id'] === $currentSessionId): ?>
                <i>Questa sessione</i>
              <?php else: ?>
                <form method="post" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="revoke_session" value="<?= htmlspecialchars($session['session_id']) ?>">
                  <button type="submit">Termina</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
      <br>
      <?php if (count($sessions) > 1): ?>
        <form method="post">
          <?= csrf_field() ?>
          <button type="submit" name="revoke_others">Termina tutte le altre Sessioni</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <br>
  <br>
  <h4 style="margin-bottom: 5px;">Altre Opzioni</h4>
  <!--
  <ul>
    <li>Export your Account Data: <a href="/export" target="_blank">Download</a></li>
    <li>If you want to permanently delete your Account and all your data, please <a href="deleteaccount.php">click here</a></li>
  </ul>
          -->
</div>


<?php require("footer.php"); ?>