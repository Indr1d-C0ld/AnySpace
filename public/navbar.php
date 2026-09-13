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

      require_once(__DIR__ . '/../core/site/message.php');
      $unreadCount = isset($_SESSION['userId']) ? countUnreadMessages($_SESSION['userId']) : 0;

      $navItems = array(
        'Home' => 'index.php',
        'Esplora' => 'browse.php',
        'Cerca' => 'search.php',
        'Bacheca' => 'futaba.php',
        'Posta' . ($unreadCount > 0 ? " ($unreadCount)" : '') => 'messages.php',
        'Blog' => 'blog/',
        'Bulletin' => 'bulletins/',
        'Forum' => 'forum/',
        'Gruppi' => 'groups/',
        'Grafiche' => 'layouts/',
        'Preferiti' => 'favorites.php',
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