-- ===========================================
-- 10秒で力尽きるネコ  ランキング用テーブル
--
-- phpMyAdmin の「SQL」タブに貼り付けて実行する。
-- 保存するのはプレイヤー名とスコアだけ。
-- ===========================================

CREATE TABLE IF NOT EXISTS `rankings` (

  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- 名前。日本語10文字を想定して、余裕をみて20文字ぶん確保
  `name` VARCHAR(20) NOT NULL,

  -- スコア。マイナスにはならないので UNSIGNED
  `score` INT UNSIGNED NOT NULL,

  -- 登録日時。同点のときの並び順を決めるのに使う
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- スコア順に並べ替えるので、索引を張っておく。
  -- 件数が増えたときの ORDER BY が速くなる
  KEY `idxScore` (`score`, `id`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- 文字コードについて
--
-- utf8 ではなく utf8mb4 にしているのが大事なところ。
-- MySQL の「utf8」は3バイトまでしか扱えず、
-- 絵文字（4バイト）を入れるとその場でエラーになる。
--
-- 名前欄に絵文字を入れる人は必ずいるので、utf8mb4 にしておく。
-- ===========================================
