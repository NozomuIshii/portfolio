<?php
session_start();

// 1. データの受信（POSTを想定）
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

// 2. 最低限のバリデーション
if ($product_id !== null && isset($_SESSION['cart'][$product_id])) {
    
    if ($quantity > 0) {
        // --- パターンA: 単純に数量だけを管理している場合 ---
        $_SESSION['cart'][$product_id] = $quantity;
        
        // --- パターンB: 配列で詳細を管理している場合 (こちらならコメントアウトを解除) ---
        // $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        
    } else {
        // 数量が0以下ならカートから削除する
        unset($_SESSION['cart'][$product_id]);
    }
}

// 3. カート画面にリダイレクトして戻す
header('Location: cart.php');
exit;