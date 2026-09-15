<!-- BEGIN HEADER -->
<header class="main-header">
  <nav class="">
    <div class="top">
      <div class="left">
        <a href="index.php">
            <?= SITE_NAME ?>
          </a> | <a href="index.php">Home</a>
      </ul>
    </div>
    <div class="center">

      <?php
      // Il modulo di ricerca era inerte su TUTTO il sito: senza `action`
      // si inviava alla pagina corrente invece che a search.php, e il campo
      // si chiamava "search" mentre search.php legge $_GET['q']. Scrivere
      // qualcosa e premere "Cerca" si limitava a ricaricare la pagina.
      ?>
      <form action="<?= BASE_PATH ?>/search.php" method="get" role="search">
        <label for="nav-q">
          Cerca su <?= htmlspecialchars(SITE_NAME); ?>:
        </label>

        <div class="search-wrapper">
          <input id="nav-q" type="text" name="q" autocomplete="off"
                 value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
        </div>

        <input class="submit-btn" type="submit" value="Cerca">
      </form>
</div>
  <div class="right">
      <ul class="topnav signup">
        <?php if (isset($_SESSION['user'])): ?>
          <a href="<?= BASE_PATH ?>/docs/help.html">Aiuto</a> | <a href="logout.php">Esci</a>
        <?php else: ?>
          <a href="<?= BASE_PATH ?>/docs/help.html">Aiuto</a> |
          <a href="<?= BASE_PATH ?>/login.php">Accedi</a> |
          <a href="<?= BASE_PATH ?>/register.php">Iscriviti</a>
        <?php endif; ?>
      </ul>
        </div>
    </div>
    <ul class="links">
      <?php
      $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
      $currentPage = basename($currentUrl);

      $isHomePage = in_array($currentPage, array('index.php', 'home.php'));

      require_once(__DIR__ . '/../site/message.php');
      $unreadCount = isset($_SESSION['userId']) ? countUnreadMessages($_SESSION['userId']) : 0;

      $navItems = array(
        'Home' => BASE_PATH . '/index.php',
        'Esplora' => BASE_PATH . '/browse.php',
        'Cerca' => BASE_PATH . '/search.php',
        'Posta' . ($unreadCount > 0 ? " ($unreadCount)" : '') => BASE_PATH . '/messages.php',
        'Blog' => BASE_PATH . '/blog/',
        'Bulletin' => BASE_PATH . '/bulletins/',
        'Forum' => BASE_PATH . '/forum/',
        'Gruppi' => BASE_PATH . '/groups/',
        'Grafiche' => BASE_PATH . '/layouts/',
        'Preferiti' => BASE_PATH . '/favorites.php',
        'Sorgente' => 'https://github.com/superswan/anyspace',
        'Aiuto' => BASE_PATH . '/docs/help.html',
        'Chi siamo' => BASE_PATH . '/about.php',
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