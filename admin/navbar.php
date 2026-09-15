<!-- BEGIN HEADER -->
<header class="main-header">
  <nav class="">
    <div class="top">
      <div class="left">
        <a href="index.php">
            <?= SITE_NAME ?>
          </a> | <a href="index.php">Pannello Admin</a>
      </ul>
    </div>
    <div class="center">

      <form>


        <label>
          <?= htmlspecialchars(SITE_NAME); ?>
        </label>

        <label>
          <input type="text" name="search">
        </label>

        <input class="submit-btn" type="submit" name="submit-button" value="Cerca">
      </form>
</div>
  <div class="right">
      <ul class="topnav signup">
        <?php if (isset($_SESSION['user'])): ?>
          <a href="docs/help.html">Aiuto</a> | <a href="logout.php">Esci</a>
        <?php else: ?>
          <a href="docs/help.html">Aiuto</a> |
          <a href="login.php">Accedi</a> |
          <a href="register.php">Iscriviti</a>
        <?php endif; ?>
      </ul>
        </div>
    </div>
    <ul class="links">
      <?php
      $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
      $currentPage = basename($currentUrl);

      $isHomePage = in_array($currentPage, array('index.php', 'home.php'));

      $navItems = array(
        'Generale' => 'index.php',
        'Utenti' => 'users.php',
        'Cerca' => 'search.php',
        'Segnalazioni' => 'reports.php',
        /*
        'Mail' => 'messages.php',
        'Blog' => 'blog/',
        'Bulletins' => 'bulletins/',
        'Forum' => 'forum.php',
        'Inviti' => 'invites.php',
        'Groups' => '#',
        'Layouts' => '#',
        */
        'Blog' => 'blog.php',
        // 'Preferiti' rimandava a una pagina vuota e non aveva un contenuto
        // sensato dal lato amministrazione: rimossa invece di inventarle uno
        // scopo. 'Forum' invece esisteva ed era funzionante, ma non era in
        // elenco: la gestione delle bacheche era raggiungibile solo a mano.
        'Forum' => 'forum.php',
        'Inviti' => 'invites.php',
        'Email' => 'email.php',
        'Database' => 'database.php',
        'Sorgente' => 'https://github.com/superswan/anyspace',
        'Aiuto' => 'docs/help.html',
        'Chi siamo' => 'about.php',
      );

      foreach ($navItems as $name => $page) {
        if ($name == 'Home' && $isHomePage) {
          $activeClass = 'class="active"';
        } else {
          $activeClass = ($currentPage == basename($page)) ? 'class="active"' : '';
        }
        echo "<li><a href=\"$page\" $activeClass>&nbsp;$name </a></li>";
      }
      ?>
    </ul>
  </nav>



</header>
<!-- END HEADER -->