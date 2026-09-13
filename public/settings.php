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

if (isset($_POST['password-old']) && isset($_POST['password-new']) && isset($_POST['password-confirm'])) {
    $oldPassword = $_POST['password-old'];
    $newPassword = $_POST['password-new'];
    $confirmPassword = $_POST['password-confirm'];

    $currentUserPassword = fetchUserPassword($userId); 
    $currentUserPassword = $currentUserPassword['password']; 

    if (password_verify($oldPassword, $currentUserPassword)) {
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            changePassword($userId, $hashedPassword); 

            echo "Password aggiornata con successo.";
        } else {
            echo "Le nuove password non coincidono.";
        }
    } else {
        echo "La vecchia password non è corretta.";
    }
}

?>
<?php require("header.php"); ?>

<div class="simple-container">
  <h1>Impostazioni Account</h1>
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
          <option value="public" selected>Tutti (Pubblico)</option>
          <option value="private" >Solo Amici (Privato)</option>
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