-- FaceClone schema (MySQL 8 / MariaDB 10.4+, utf8mb4)
-- Drop order respects foreign keys.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notifications`, `messages`, `conversation_participants`, `conversations`,
  `story_views`, `stories`, `saved_posts`, `reactions`, `comments`, `post_media`, `posts`,
  `group_members`, `groups`, `event_attendees`, `events`, `listings`, `blocks`, `follows`,
  `friendships`, `user_settings`, `remember_tokens`, `password_resets`, `users`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name`    VARCHAR(60)  NOT NULL,
  `last_name`     VARCHAR(60)  NOT NULL,
  `username`      VARCHAR(40)  NOT NULL,
  `email`         VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `dob`           DATE         DEFAULT NULL,
  `gender`        ENUM('male','female','custom') DEFAULT 'custom',
  `avatar`        VARCHAR(255) DEFAULT NULL,
  `cover`         VARCHAR(255) DEFAULT NULL,
  `bio`           VARCHAR(255) DEFAULT NULL,
  `work`          VARCHAR(120) DEFAULT NULL,
  `education`     VARCHAR(120) DEFAULT NULL,
  `city`          VARCHAR(120) DEFAULT NULL,
  `hometown`      VARCHAR(120) DEFAULT NULL,
  `relationship`  ENUM('single','in_a_relationship','engaged','married','complicated','private') DEFAULT 'private',
  `website`       VARCHAR(190) DEFAULT NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `last_seen`     DATETIME     DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `ix_users_name` (`first_name`,`last_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_settings` (
  `user_id`             INT UNSIGNED NOT NULL,
  `theme`               ENUM('light','dark','system') NOT NULL DEFAULT 'light',
  `default_privacy`     ENUM('public','friends','only_me') NOT NULL DEFAULT 'friends',
  `who_can_friend`      ENUM('everyone','friends_of_friends') NOT NULL DEFAULT 'everyone',
  `who_can_message`     ENUM('everyone','friends') NOT NULL DEFAULT 'everyone',
  `show_online`         TINYINT(1) NOT NULL DEFAULT 1,
  `notify_reactions`    TINYINT(1) NOT NULL DEFAULT 1,
  `notify_comments`     TINYINT(1) NOT NULL DEFAULT 1,
  `notify_friends`      TINYINT(1) NOT NULL DEFAULT 1,
  `notify_messages`     TINYINT(1) NOT NULL DEFAULT 1,
  `notify_groups`       TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `remember_tokens` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `selector`   CHAR(24)     NOT NULL,
  `validator`  CHAR(64)     NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_selector` (`selector`),
  CONSTRAINT `fk_token_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reset_token` (`token_hash`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `friendships` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requester_id` INT UNSIGNED NOT NULL,
  `addressee_id` INT UNSIGNED NOT NULL,
  `status`       ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_friendship` (`requester_id`,`addressee_id`),
  KEY `ix_friend_addressee` (`addressee_id`,`status`),
  CONSTRAINT `fk_friend_req` FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_friend_add` FOREIGN KEY (`addressee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `follows` (
  `follower_id` INT UNSIGNED NOT NULL,
  `followee_id` INT UNSIGNED NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`follower_id`,`followee_id`),
  KEY `ix_follow_followee` (`followee_id`),
  CONSTRAINT `fk_follow_a` FOREIGN KEY (`follower_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_follow_b` FOREIGN KEY (`followee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blocks` (
  `user_id`    INT UNSIGNED NOT NULL,
  `blocked_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`blocked_id`),
  CONSTRAINT `fk_block_a` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_block_b` FOREIGN KEY (`blocked_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `groups` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120) NOT NULL,
  `slug`        VARCHAR(140) NOT NULL,
  `description` TEXT,
  `cover`       VARCHAR(255) DEFAULT NULL,
  `privacy`     ENUM('public','private') NOT NULL DEFAULT 'public',
  `creator_id`  INT UNSIGNED NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_group_slug` (`slug`),
  CONSTRAINT `fk_group_creator` FOREIGN KEY (`creator_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `group_members` (
  `group_id`  INT UNSIGNED NOT NULL,
  `user_id`   INT UNSIGNED NOT NULL,
  `role`      ENUM('admin','moderator','member') NOT NULL DEFAULT 'member',
  `status`    ENUM('member','pending','invited') NOT NULL DEFAULT 'member',
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`,`user_id`),
  KEY `ix_gm_user` (`user_id`,`status`),
  CONSTRAINT `fk_gm_group` FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gm_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `posts` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `group_id`       INT UNSIGNED DEFAULT NULL,
  `shared_post_id` INT UNSIGNED DEFAULT NULL,
  `content`        TEXT,
  `background`     VARCHAR(30)  DEFAULT NULL,
  `feeling`        VARCHAR(60)  DEFAULT NULL,
  `location`       VARCHAR(120) DEFAULT NULL,
  `privacy`        ENUM('public','friends','only_me') NOT NULL DEFAULT 'friends',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_at`      DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_posts_author_time` (`user_id`,`created_at`),
  KEY `ix_posts_group_time` (`group_id`,`created_at`),
  KEY `ix_posts_time` (`created_at`),
  CONSTRAINT `fk_post_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_post_group` FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_post_share` FOREIGN KEY (`shared_post_id`) REFERENCES `posts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_media` (
  `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `path`    VARCHAR(255) NOT NULL,
  `type`    ENUM('image','video') NOT NULL DEFAULT 'image',
  `sort`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_media_post` (`post_id`,`sort`),
  CONSTRAINT `fk_media_post` FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comments` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`    INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `parent_id`  INT UNSIGNED DEFAULT NULL,
  `content`    TEXT NOT NULL,
  `image`      VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_comment_post` (`post_id`,`created_at`),
  KEY `ix_comment_parent` (`parent_id`),
  CONSTRAINT `fk_comment_post`   FOREIGN KEY (`post_id`)   REFERENCES `posts`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reactions` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `target_type` ENUM('post','comment') NOT NULL,
  `target_id`   INT UNSIGNED NOT NULL,
  `type`        ENUM('like','love','care','haha','wow','sad','angry') NOT NULL DEFAULT 'like',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reaction` (`user_id`,`target_type`,`target_id`),
  KEY `ix_reaction_target` (`target_type`,`target_id`),
  CONSTRAINT `fk_reaction_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `saved_posts` (
  `user_id`    INT UNSIGNED NOT NULL,
  `post_id`    INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`post_id`),
  CONSTRAINT `fk_saved_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saved_post` FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `type`       ENUM('photo','text') NOT NULL DEFAULT 'photo',
  `media`      VARCHAR(255) DEFAULT NULL,
  `text`       VARCHAR(500) DEFAULT NULL,
  `background` VARCHAR(30)  DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_story_user_exp` (`user_id`,`expires_at`),
  CONSTRAINT `fk_story_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `story_views` (
  `story_id`  INT UNSIGNED NOT NULL,
  `user_id`   INT UNSIGNED NOT NULL,
  `viewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`story_id`,`user_id`),
  CONSTRAINT `fk_sv_story` FOREIGN KEY (`story_id`) REFERENCES `stories`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sv_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `conversations` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `is_group`   TINYINT(1) NOT NULL DEFAULT 0,
  `name`       VARCHAR(120) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_conv_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `conversation_participants` (
  `conversation_id` INT UNSIGNED NOT NULL,
  `user_id`         INT UNSIGNED NOT NULL,
  `last_read_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`conversation_id`,`user_id`),
  KEY `ix_cp_user` (`user_id`),
  CONSTRAINT `fk_cp_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_user` FOREIGN KEY (`user_id`)         REFERENCES `users`(`id`)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED NOT NULL,
  `sender_id`       INT UNSIGNED NOT NULL,
  `body`            TEXT,
  `attachment`      VARCHAR(255) DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_msg_conv` (`conversation_id`,`id`),
  CONSTRAINT `fk_msg_conv`   FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`)       REFERENCES `users`(`id`)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `actor_id`    INT UNSIGNED NOT NULL,
  `type`        VARCHAR(40) NOT NULL,
  `entity_type` VARCHAR(20) DEFAULT NULL,
  `entity_id`   INT UNSIGNED DEFAULT NULL,
  `url`         VARCHAR(255) DEFAULT NULL,
  `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_notif_user` (`user_id`,`is_read`,`created_at`),
  CONSTRAINT `fk_notif_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_actor` FOREIGN KEY (`actor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `events` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `host_id`     INT UNSIGNED NOT NULL,
  `title`       VARCHAR(150) NOT NULL,
  `description` TEXT,
  `cover`       VARCHAR(255) DEFAULT NULL,
  `location`    VARCHAR(190) DEFAULT NULL,
  `starts_at`   DATETIME NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_event_start` (`starts_at`),
  CONSTRAINT `fk_event_host` FOREIGN KEY (`host_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_attendees` (
  `event_id` INT UNSIGNED NOT NULL,
  `user_id`  INT UNSIGNED NOT NULL,
  `status`   ENUM('going','interested') NOT NULL DEFAULT 'going',
  PRIMARY KEY (`event_id`,`user_id`),
  CONSTRAINT `fk_ea_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ea_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `listings` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `seller_id`   INT UNSIGNED NOT NULL,
  `title`       VARCHAR(150) NOT NULL,
  `description` TEXT,
  `price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency`    CHAR(3) NOT NULL DEFAULT 'USD',
  `category`    VARCHAR(60) NOT NULL DEFAULT 'other',
  `location`    VARCHAR(120) DEFAULT NULL,
  `image`       VARCHAR(255) DEFAULT NULL,
  `is_sold`     TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_listing_cat` (`category`,`created_at`),
  CONSTRAINT `fk_listing_seller` FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
