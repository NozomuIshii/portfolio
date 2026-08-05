<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// データベース接続ファイルを読み込みます
require_once 'includes/dbConnect.php'; 

// カート内商品の計算用変数の初期化
$cartItems = [];
$totalPrice = 0;
$totalCount = 0;

// カートに商品が入っている場合のみデータベースを参照する
if (!empty($_SESSION['cart'])) {
    
    // カートに入っているすべての商品ID（配列のキー）を取得
    $productIds = array_keys($_SESSION['cart']);
    
    // SQLの IN 句につなげるプレースホルダを作成
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    try {
        // カート内のIDに一致する商品だけをDBから取得するSQL
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $productsFromDb = $stmt->fetchAll();
        
        // ループ処理しやすいように、取得したデータを商品IDをキーにした連想配列に再構成
        $products = [];
        foreach ($productsFromDb as $row) {
            $products[$row['id']] = $row;
        }
        
        // 各商品の合計金額や数量を計算
        foreach ($_SESSION['cart'] as $id => $qty) {
            if (isset($products[$id])) {
                $item = $products[$id];
                
                $priceInt = (int)$item['price'];
                $subTotal = $priceInt * $qty;
                
                $totalPrice += $subTotal;
                $totalCount += $qty;

                $cartItems[] = [
                    'id'       => $id,
                    'name'     => $item['name'],
                    'price'    => $priceInt,
                    'img'      => 'images/product' . $id . '.png',
                    'quantity' => $qty
                ];
            }
        }
        
    } catch (PDOException $e) {
        exit('データ取得失敗: ' . $e->getMessage());
    }
}

include 'includes/header.php'; 
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem" aria-current="page">カート</li>
    </ol>
  </div>
</nav>

<!-- 2. ようこそエリア -->
<div class="pWelcome">
  <div class="lContainer">
    <p class="pWelcomeText">
      ようこそ <span class="pWelcomeName"><?php echo !empty($_SESSION['userName']) ? htmlspecialchars($_SESSION['userName'], ENT_QUOTES, 'UTF-8') : 'ゲスト'; ?></span> 様
    </p>
  </div>
</div>

<!-- 3. メインコンテンツ -->
<main class="pCart lContainer">
  
  <?php if (empty($cartItems)): ?>
    <!-- カートが空の場合の表示 -->
    <div class="pCartEmptyBox" style="text-align: center; padding: 60px 0;">
      <p style="margin-bottom: 24px;">カートに商品は入っていません。</p>
      <div class="pCartContinueBlock">
        <a href="products.php" class="pCartContinueBtn">商品を見に行く</a>
      </div>
    </div>
  <?php else: ?>

    <!-- 全体をひとつのフォームにして purchaseConfirm.php へ送信 -->
    <form action="purchaseConfirm.php" method="post" id="cartForm">

      <!-- 上部注文合計エリア -->
      <div class="pCartSummaryBox">
        <p class="pCartSummaryCount">現在 商品<span id="summaryTotalCount"><?php echo $totalCount; ?></span>点</p>
        <p class="pCartSummaryTotal">ご注文小計：税込 <span class="pCartSummaryPrice" id="summaryTotalPrice">¥<?php echo number_format($totalPrice); ?></span></p>
        <button type="submit" class="pCartCheckoutBtn">購入確認へ進む</button>
      </div>

      <!-- カート商品リスト -->
      <div class="pCartList">
        <?php foreach ($cartItems as $item): ?>
          <div class="pCartItem" data-price="<?php echo $item['price']; ?>">
            <div class="pCartItemImg">
              <img src="<?php echo htmlspecialchars($item['img'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            
            <div class="pCartItemInfo">
              <h2 class="pCartItemName"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
              
              <div class="pCartItemRow">
                <p class="pCartItemPrice">税込 ¥<?php echo number_format($item['price']); ?></p>
                
                <div class="pCartItemControl">
                  <div class="pCartItemQtyRow">
                    <label for="qty-<?php echo $item['id']; ?>" class="pCartItemLabel">数量</label>
                    <!-- 配列形式 name="num[商品ID]" で送信 -->
                    <input type="number" id="qty-<?php echo $item['id']; ?>" name="num[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="pCartItemInput itemQtyInput">
                    <span class="pCartItemUnit">個</span>
                  </div>

                  <!-- JavaScript呼び出し用ボタン（type="button"） -->
                  <button type="button" class="pCartItemRecalcBtn js-recalc-btn">再計算</button>

                  <div class="pCartItemDeleteBlock">
                    <a href="cartDelete.php?id=<?php echo $item['id']; ?>" class="pCartItemDeleteLink">削除する</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- 下部注文合計エリア -->
      <div class="pCartSummaryBox mBottomBox">
        <p class="pCartSummaryCount">現在 商品<span id="summaryTotalCountBottom"><?php echo $totalCount; ?></span>点</p>
        <p class="pCartSummaryTotal">ご注文小計：税込 <span class="pCartSummaryPrice" id="summaryTotalPriceBottom">¥<?php echo number_format($totalPrice); ?></span></p>
        <button type="submit" class="pCartCheckoutBtn">購入確認へ進む</button>
      </div>

    </form>

    <!-- 買い物を続けるボタン -->
    <div class="pCartContinueBlock">
      <a href="products.php" class="pCartContinueBtn">買い物を続ける</a>
    </div>

  <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 画面上の小計・点数を再計算するJavaScript
    function recalculateCart() {
        let totalCount = 0;
        let totalPrice = 0;

        const items = document.querySelectorAll('.pCartItem');
        items.forEach(function(item) {
            const price = parseInt(item.getAttribute('data-price'), 10) || 0;
            const qtyInput = item.querySelector('.itemQtyInput');
            const qty = parseInt(qtyInput.value, 10) || 0;

            totalCount += qty;
            totalPrice += price * qty;
        });

        const formattedPrice = '¥' + totalPrice.toLocaleString();

        document.getElementById('summaryTotalCount').textContent = totalCount;
        document.getElementById('summaryTotalPrice').textContent = formattedPrice;
        document.getElementById('summaryTotalCountBottom').textContent = totalCount;
        document.getElementById('summaryTotalPriceBottom').textContent = formattedPrice;
    }

    // 「再計算」ボタンを押したときの処理
    const recalcBtns = document.querySelectorAll('.js-recalc-btn');
    recalcBtns.forEach(function(btn) {
        btn.addEventListener('click', recalculateCart);
    });
});
</script>