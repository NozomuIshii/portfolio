<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. セッションの中身（ユーザーIDや名前など）をすべてクリアする
$_SESSION = [];

// 2. ログアウト完了画面用のフラグだけをセッションに書き込む
$_SESSION['logout_completed'] = true;

// 3. ログアウト完了画面へ遷移
header('Location: logoutDone.php');
exit;