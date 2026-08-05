<?php
session_start();

// 1. GET送信されたパラメータ名「id」から受信するよう変更
$product_id = isset($_GET['id']) ? $_GET['id'] : null;

// 2. カートから指定のアイテムを削除
if ($product_id !== null && isset($_SESSION['cart'][$product_id])) {
    // セッション配列から該当キーを削除
    unset($_SESSION['cart'][$product_id]);
}

// 3. カート画面にリダイレクトして戻す
header('Location: cart.php');
exit;