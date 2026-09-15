-- AnySpace — schema canonico (bonifica 2026-09-13).
-- Sostituisce le tre versioni divergenti che coesistevano nel repo upstream
-- (schema.sql, anyspace.sql, e la copia incorporata in public/install.php):
-- qui tutto è InnoDB/utf8mb4, con i default che mancavano nelle colonne
-- NOT NULL usate da INSERT parziali (altrimenti la registrazione fallisce in
-- modalità strict, come succedeva con `lastactive`/`lastlogon`).
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rank` tinyint(4) NOT NULL DEFAULT 0,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `bio` text NOT NULL DEFAULT '',
  `interests` text NOT NULL DEFAULT '',
  `css` mediumtext NOT NULL DEFAULT '',
  `music` varchar(255) NOT NULL DEFAULT 'default.mp3',
  `pfp` varchar(255) NOT NULL DEFAULT 'default.jpg',
  `currentgroup` varchar(255) NOT NULL DEFAULT 'None',
  `status` varchar(500) NOT NULL DEFAULT '',
  `private` tinyint(1) NOT NULL DEFAULT 0,
  `views` int(11) NOT NULL DEFAULT 0,
  `lastactive` datetime NULL DEFAULT NULL,
  `lastlogon` datetime NULL DEFAULT NULL,
  `email_verified_at` datetime NULL DEFAULT NULL,
  `verification_token` varchar(64) NULL DEFAULT NULL,
  `verification_sent_at` datetime NULL DEFAULT NULL,
  `reset_token` varchar(64) NULL DEFAULT NULL,
  `reset_expires` datetime NULL DEFAULT NULL,
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `top_friends` text NULL DEFAULT NULL,
  `who_meet` text NOT NULL DEFAULT '',
  -- Sorgente scritta dall'utente, separata dall'HTML reso in bio/who_meet:
  -- il modulo di modifica ripropone QUESTA, non la resa, altrimenti ogni
  -- salvataggio ri-scappa il risultato del precedente e il testo degrada.
  `bio_source` text NULL DEFAULT NULL,
  `who_meet_source` text NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `friends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender` varchar(255) NOT NULL,
  `receiver` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'PENDING',
  PRIMARY KEY (`id`),
  KEY `ix_friends_sender` (`sender`),
  KEY `ix_friends_receiver` (`receiver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `favorites` (
  `user_id` int(11) NOT NULL,
  `favorites` text,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `toid` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` varchar(500) NOT NULL,
  `date` datetime NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_comments_toid` (`toid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` int(11) NOT NULL DEFAULT 0,
  `privacy_level` int(11) NOT NULL DEFAULT 0,
  `pinned` tinyint(1) NOT NULL DEFAULT 0,
  `kudos` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_blogs_author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blogcomments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `toid` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` varchar(500) NOT NULL,
  `date` datetime NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_blogcomments_toid` (`toid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bulletins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `expires_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_bulletins_author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bulletincomments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `toid` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` varchar(500) NOT NULL,
  `date` datetime NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_bulletincomments_toid` (`toid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) NOT NULL,
  `author` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `members` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupcomments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `toid` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` varchar(500) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_groupcomments_toid` (`toid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `layouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `code` mediumtext NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `ix_layouts_author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `layoutcomments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `toid` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` varchar(500) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_layoutcomments_toid` (`toid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `toid` int(11) NOT NULL,
  `author` int(11) NOT NULL,
  `msg` text NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_messages_toid` (`toid`),
  KEY `ix_messages_author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `content_type` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `forum_boards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `forum_threads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `board_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `pinned` tinyint(1) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `last_post_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_forum_threads_board` (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `forum_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) NOT NULL,
  `author` int(11) NOT NULL,
  `text` text NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_forum_posts_thread` (`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user` varchar(50) NOT NULL,
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `last_logon` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sessions_session_id` (`session_id`),
  KEY `ix_sessions_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limitazione dei tentativi su login, registrazione e reset password.
-- La tabella viene creata automaticamente al primo utilizzo da
-- core/ratelimit.php: è qui per completezza dello schema.
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bucket` varchar(64) NOT NULL,
  `identifier` varchar(190) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `first_attempt` datetime NOT NULL,
  `blocked_until` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rate_limits` (`bucket`,`identifier`),
  KEY `ix_rate_limits_first` (`first_attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inviti alla registrazione (admin/invites.php). Come rate_limits, la tabella
-- viene creata automaticamente al primo utilizzo da core/site/invite.php:
-- è qui per completezza dello schema.
CREATE TABLE IF NOT EXISTS `invites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `note` varchar(255) NOT NULL DEFAULT '',
  `expires_at` datetime NULL DEFAULT NULL,
  `used_by` int(11) NULL DEFAULT NULL,
  `used_at` datetime NULL DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invites_code` (`code`),
  KEY `ix_invites_used_by` (`used_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
