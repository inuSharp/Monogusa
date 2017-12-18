<?php
$migrationSQL = <<<'SQL'
    drop table if exists users;
    drop table if exists works;
    drop table if exists tags;
    drop table if exists work_tags;

    CREATE TABLE `users` (
      `id`             bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ユーザーID',
      `name`           varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表示名',
      `email`          varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
      `password`       varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
      `activated`      tinyint(1)                              NOT NULL COMMENT 'アクティベーション済',
      `created_at`     varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登録日時',
      `updated_at`     varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '更新日時',
      PRIMARY KEY (`id`),
      KEY `users_email_index` (`email`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    insert into users (name,     email,      password, activated, created_at, updated_at) values 
                     ('管理者', 'testuser', '$2y$10$Bz3C3q6ZcpB0\/qRwDK0RHuJ384JxICKg3T54KSRvZcidDec8EEzKm',       1,         now(),      now());

    CREATE TABLE `remember_tokens` (
      `user_id` bigint(20) unsigned                     NOT NULL COMMENT 'ユーザーID',
      `token`   varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ログインしたままトークン',
      PRIMARY KEY (`user_id`),
      KEY `remember_tokens_token_index` (`token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    insert into remember_tokens (user_id, token) values (1, '');

SQL;
function migrate($commandParam)
{
    global $migrationSQL;
    $db = getDBConnection();
    $db->execSQL($migrationSQL);
}
