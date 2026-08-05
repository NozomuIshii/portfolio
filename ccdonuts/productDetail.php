<?php 
// セッションの開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// データベース接続ファイルを読み込みます
require_once 'includes/dbConnect.php'; 

// 1. URLのパラメータからIDを取得（数値型にキャスト）
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// --- お気に入り処理（POST受信時のトグル処理） ---
if (!isset($_SESSION['favo'])) {
    $_SESSION['favo'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_favorite') {
    $favoProductId = (int)$_POST['product_id'];
    
    // すでに登録されている場合は配列から削除（解除）、未登録の場合は追加
    if (in_array($favoProductId, $_SESSION['favo'], true)) {
        $_SESSION['favo'] = array_values(array_diff($_SESSION['favo'], [$favoProductId]));
    } else {
        $_SESSION['favo'][] = $favoProductId;
    }

    // POST後にリロード時の二重送信を防ぐためリダイレクト
    header("Location: productDetail.php?id=" . $id);
    exit;
}

// 現在の商品がお気に入りに登録されているかチェック
$isFavorite = in_array($id, $_SESSION['favo'], true);

try {
    // 2. 該当するIDの商品データをデータベースから取得（SQLインジェクション対策）
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $currentProduct = $stmt->fetch();

    // 安全対策：もし該当するIDの商品が存在しない場合は、ID:1の商品を強制取得する
    if (!$currentProduct) {
        $id = 1;
        $stmt->execute([':id' => $id]);
        $currentProduct = $stmt->fetch();
        $isFavorite = in_array($id, $_SESSION['favo'], true);
    }
} catch (PDOException $e) {
    exit('データ取得失敗: ' . $e->getMessage());
}

include 'includes/header.php'; 
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="products.php">商品一覧</a></li>
      <li class="pBreadcrumbItem" aria-current="page"><?php echo htmlspecialchars($currentProduct['name'], ENT_QUOTES, 'UTF-8'); ?></li>
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
<main class="pDetail lContainer">
  <div class="pDetailLayout">
    
    <!-- 商品画像エリア -->
    <div class="pDetailImgBlock">
      <!-- データベースのidに基づいた画像パスを出力 -->
      <img src="images/product<?php echo $currentProduct['id']; ?>.png" alt="<?php echo htmlspecialchars($currentProduct['name'], ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    
    <!-- 商品情報エリア -->
    <div class="pDetailInfoBlock">
      <h1 class="pDetailTitle">
        <!-- is_newが1の場合は【新作】マークを動的に付与 -->
        <?php if ((int)$currentProduct['is_new'] === 1): ?>【新作】<?php endif; ?><?php echo htmlspecialchars($currentProduct['name'], ENT_QUOTES, 'UTF-8'); ?>
      </h1>
      
      <p class="pDetailDescription">
        <!-- カラム名を description から introduction に変更 -->
        <?php echo nl2br(htmlspecialchars($currentProduct['introduction'], ENT_QUOTES, 'UTF-8')); ?>
      </p>
      
      <p class="pDetailPrice">
        <!-- データベースの数値型の価格を number_format でカンマ区切りにする -->
        <span class="pDetailPriceLabel">税込</span> ¥<?php echo number_format($currentProduct['price']); ?>
      </p>
      
      <div class="pDetailActionRow">
        <!-- カート追加用フォーム -->
        <form action="cartAdd.php" method="post" class="pDetailForm" style="display: flex; align-items: center; gap: 12px; flex-grow: 1; max-width: 240px;">
          <input type="hidden" name="product_id" value="<?php echo $currentProduct['id']; ?>">
          
          <div class="pDetailQuantityWrapper">
            <input type="number" name="quantity" value="1" min="1" class="pDetailInput">
            <span class="pDetailUnit">個</span>
          </div>
          
          <button type="submit" class="pDetailCartBtn">カートに入れる</button>
        </form>
        
        <!-- お気に入り登録/解除用フォーム -->
        <form action="productDetail.php?id=<?php echo $currentProduct['id']; ?>" method="post">
          <input type="hidden" name="action" value="toggle_favorite">
          <input type="hidden" name="product_id" value="<?php echo $currentProduct['id']; ?>">
          <button type="submit" class="pDetailFavoriteBtn <?php echo $isFavorite ? 'is-active' : ''; ?>" aria-label="<?php echo $isFavorite ? 'お気に入りから削除' : 'お気に入りに追加'; ?>">
            <svg viewBox="0 0 24 24" class="pDetailFavoriteIcon cIconHeart" aria-hidden="true" style="<?php echo $isFavorite ? 'fill: #bf0000; stroke: #bf0000;' : 'fill: #ffffff; stroke: #bf0000;'; ?>">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.5 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
          </button>
        </form>
      </div>

    </div>
    
  </div>
</main>

<?php include 'includes/footer.php'; ?>