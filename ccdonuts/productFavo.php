<?php
// セッションの開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/dbConnect.php'; // DB接続処理（PDOインスタンス $pdo）

// --- 追加：POST受信時のお気に入り解除処理 ---
if (!isset($_SESSION['favo'])) {
    $_SESSION['favo'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_favorite') {
    $favoProductId = (int)$_POST['product_id'];
    
    // お気に入り配列から対象IDを削除
    if (in_array($favoProductId, $_SESSION['favo'], true)) {
        $_SESSION['favo'] = array_values(array_diff($_SESSION['favo'], [$favoProductId]));
    }

    // POST後にリロード時の二重送信を防ぐため自ページへリダイレクト
    header("Location: productFavo.php");
    exit;
}
// ------------------------------------------

// セッションからお気に入り商品IDの配列を取得（未設定の場合は空配列）
$favoIds = $_SESSION['favo'] ?? [];

$products = [];
if (!empty($favoIds)) {
    // プレースホルダーの生成 (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($favoIds), '?'));
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_values($favoIds));
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $products = [];
    }
}

include 'includes/header.php'; 
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="products.php">商品一覧</a></li>
      <li class="pBreadcrumbItem" aria-current="page">お気に入り</li>
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
<main class="lContainer" style="padding-top: 32px; padding-bottom: 60px;">
    <h1 class="pProductMainTitle" style="margin-bottom: 40px;">お気に入り一覧</h1>

    <?php if (!empty($products)): ?>
        <div style="display: flex; flex-direction: column; gap: 40px;">
            <?php foreach ($products as $product): ?>
                <?php $isFavorite = true; ?>
                <div class="pDetailLayout">
                    
                    <!-- 商品画像エリア -->
                    <div class="pDetailImgBlock">
                        <img src="images/product<?= $product['id']; ?>.png" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    
                    <!-- 商品情報エリア -->
                    <div class="pDetailInfoBlock">
                        <h2 class="pDetailTitle">
                            <?php if (isset($product['is_new']) && (int)$product['is_new'] === 1): ?>【新作】<?php endif; ?>
                            <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </h2>
                        
                        <p class="pDetailDescription">
                            <?= nl2br(htmlspecialchars($product['introduction'], ENT_QUOTES, 'UTF-8')); ?>
                        </p>
                        
                        <p class="pDetailPrice">
                            <span class="pDetailPriceLabel">税込</span> ¥<?= number_format($product['price']); ?>
                        </p>
                        
                        <div class="pDetailActionRow">
                            <!-- カート追加用フォーム -->
                            <form action="cartAdd.php" method="post" class="pDetailForm" style="display: flex; align-items: center; gap: 12px; flex-grow: 1; max-width: 240px;">
                                <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                
                                <div class="pDetailQuantityWrapper">
                                    <input type="number" name="quantity" value="1" min="1" class="pDetailInput">
                                    <span class="pDetailUnit">個</span>
                                </div>
                                
                                <button type="submit" class="pDetailCartBtn">カートに入れる</button>
                            </form>
                            
                            <!-- お気に入り登録/解除用フォーム（actionを自ページに変更） -->
                            <form action="productFavo.php" method="post">
                                <input type="hidden" name="action" value="toggle_favorite">
                                <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                <button type="submit" class="pDetailFavoriteBtn is-active" aria-label="お気に入りから削除">
                                    <svg viewBox="0 0 24 24" class="pDetailFavoriteIcon cIconHeart" aria-hidden="true" style="fill: #bf0000; stroke: #bf0000;">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                </button>
                            </form>
                        </div>

                    </div>
                    
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- お気に入りが空の場合 -->
        <div class="pFavoEmptyBlock">
            <div class="pFavoEmptyBox">
                <p class="pFavoEmptyText">現在、お気に入り商品は登録されておりません。</p>
            </div>
            <div class="pFavoEmptyAction">
                <a href="products.php" class="pFavoBtnProducts">商品一覧へ</a>
            </div>
            <div class="pFavoEmptyBack">
                <a href="index.php" class="pFavoLinkTop">TOPページへもどる</a>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>