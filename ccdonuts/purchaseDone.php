<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================================================
// ① POSTアクセス時（purchaseFinalConfirm.phpのボタン押下時）
// ==================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/dbConnect.php';

    // カートが空の場合は処理しない
    if (empty($_SESSION['cart'])) {
        header('Location: cart.php');
        exit;
    }

    try {
        // トランザクション開始
        $pdo->beginTransaction();

        // ログインユーザーIDの取得（未ログイン・ゲストの場合は NULL）
        $customerId = !empty($_SESSION['userId']) ? $_SESSION['userId'] : null;

        // カート内商品の最新価格と合計金額の計算
        $productIds   = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $productsFromDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $productMap = [];
        foreach ($productsFromDb as $p) {
            $productMap[$p['id']] = (int)$p['price'];
        }

        $totalAmount = 0;
        $orderItems  = [];

        foreach ($_SESSION['cart'] as $pId => $qty) {
            if (isset($productMap[$pId])) {
                $price = $productMap[$pId];
                $totalAmount += $price * $qty;
                $orderItems[] = [
                    'product_id' => $pId,
                    'quantity'   => $qty,
                    'price'      => $price
                ];
            }
        }

        // ----------------------------------------------
        // 1. orders テーブルへ注文親データを保存
        // ----------------------------------------------
        $stmtOrder = $pdo->prepare("INSERT INTO orders (customer_id, total_amount) VALUES (?, ?)");
        $stmtOrder->execute([$customerId, $totalAmount]);

        // 発行された orders の id を取得
        $orderId = $pdo->lastInsertId();

        // ----------------------------------------------
        // 2. order_details テーブルへ注文明細データを保存
        // ----------------------------------------------
        $stmtDetail = $pdo->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");

        foreach ($orderItems as $item) {
            $stmtDetail->execute([
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);
        }

        // コミットして処理を確定
        $pdo->commit();

        // ----------------------------------------------
        // 3. セッション情報の破棄（カート・お届け先・カード）
        // ----------------------------------------------
        unset($_SESSION['cart']);          // カート内商品
        unset($_SESSION['guest_address']); // ゲストお届け先情報
        unset($_SESSION['guest_card']);    // ゲストカード情報

        // ----------------------------------------------
        // 4. 完了画面表示用のフラグをセット
        // ----------------------------------------------
        $_SESSION['purchase_completed'] = true;

        // ----------------------------------------------
        // 5. 自分自身のGETアクセスへリダイレクト（PRGパターン）
        // ----------------------------------------------
        header('Location: purchaseDone.php');
        exit;

    } catch (Exception $e) {
        // エラー発生時はロールバックして購入確認画面へ戻す
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['purchase_error'] = '注文処理中にエラーが発生しました。';
        header('Location: purchaseFinalConfirm.php');
        exit;
    }
}

// ==================================================
// ② GETアクセス時（画面表示処理）
// ==================================================

// ガード節：完了フラグがない直アクセスや再読み込み（F5）の場合はTOPへ弾く
if (empty($_SESSION['purchase_completed'])) {
    header('Location: index.php');
    exit;
}

// 一度表示したら完了フラグを消去（次回F5対策）
unset($_SESSION['purchase_completed']);

include 'includes/header.php'; 
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="cart.php">カート</a></li>
      <li class="pBreadcrumbItem"><a href="purchaseFinalConfirm.php">購入確認</a></li>
      <li class="pBreadcrumbItem" aria-current="page">購入完了</li>
    </ol>
  </div>
</nav>

<main class="lMain">
  <div class="pRegisterContainer mDoneContainer">
    <h1 class="cPageTitle">ご購入完了</h1>

    <!-- 完了メッセージの枠線ブロック -->
    <div class="pDoneMessageBox">
      <p class="pDoneMessageText">ご購入いただきありがとうございます。</p>
      <p class="pDoneMessageText">今後ともご愛顧の程、宜しくお願いいたします。</p>
    </div>

    <!-- TOPページへのリンクエリア -->
    <div class="pDoneLinkArea">
      <a href="index.php" class="pDoneLink">TOPページへすすむ</a>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>