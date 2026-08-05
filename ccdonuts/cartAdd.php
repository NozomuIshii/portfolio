<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// カートのセッションがまだない場合は配列として初期化
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// POSTで商品IDが送られてきた場合のみ処理を行う
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']); // 安全のために整数化
    
    // フォームから送信された数量を取得（送られてこない場合は1にする）
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // 数量が0以下の不正な値だった場合の安全対策
    if ($quantity < 1) {
        $quantity = 1;
    }

    // すでにカートにそのIDの商品が入っているかチェック
    if (!isset($_SESSION['cart'][$product_id])) {
        // カートに入っていない場合は、指定された数量で新規追加
        $_SESSION['cart'][$product_id] = $quantity;
    } else {
        // すでにカートに入っている場合は、指定された数量をプラスする
        $_SESSION['cart'][$product_id] += $quantity;
    }
}

// 処理が終わったら cart.php にリダイレクト
header('Location: cart.php');
exit;