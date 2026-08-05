<?php
require_once __DIR__ . '/connectDbData.php';

try {
    $pdo = new PDO(ACCESSDB, DBID, DBPW, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('データベースに接続できませんでした。');
}