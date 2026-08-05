<?php 
// データベース接続ファイルを読み込みます
require_once 'includes/dbConnect.php'; 
include 'includes/header.php'; 

// 検索キーワードの取得と整形（前後の空白を削除）
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$searchResults = [];

try {
    if ($keyword !== '') {
        // 安全に部分一致検索を行うため、プレースホルダを使用（プレパードステートメント）
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :keyword");
        // キーワードの前後を % で挟むことで部分一致にする
        $stmt->execute([':keyword' => '%' . $keyword . '%']);
        $searchResults = $stmt->fetchAll();
    } else {
        // キーワードが空で検索された場合は、全件取得（またはお好みでエラー処理）
        $stmt = $pdo->query("SELECT * FROM products");
        $searchResults = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    exit('データ取得失敗: ' . $e->getMessage());
}
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem" aria-current="page">商品検索結果</li>
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

<!-- 3. メインコンテンツエリア（products.phpのレイアウトを継承） -->
<main class="pProductPage lContainer">
  <!-- ページ大見出し -->
  <h1 class="pProductMainTitle">
    <?php if ($keyword !== ''): ?>
      「<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>」の検索結果
    <?php else: ?>
      商品検索一覧
    <?php endif; ?>
  </h1>

  <section class="pProductSection">
    <!-- 検索ヒット件数の表示 -->
    <p class="pSearchCount" style="margin-bottom: 20px; color: #6c5643;">
      該当する商品が <strong><?php echo count($searchResults); ?></strong> 件見つかりました。
    </p>

    <?php if (count($searchResults) > 0): ?>
      <!-- 検索結果が存在する場合、products.php と同じグリッドを表示 -->
      <div class="pProductGrid">
        <?php foreach ($searchResults as $product): ?>
          <article class="pProductCard">
            <a href="productDetail.php?id=<?php echo $product['id']; ?>" class="pProductCardLink">
              <div class="pProductCardImg">
                <!-- 商品IDに基づいた画像パスを出力 -->
                <img src="images/product<?php echo $product['id']; ?>.png" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <h3 class="pProductCardName"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p class="pProductCardPrice"><span class="pProductCardPriceLabel">税込</span> ¥<?php echo number_format($product['price']); ?></p>
            </a>
            <form action="cartAdd.php" method="post" class="pProductCardForm">
              <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
              <button type="submit" class="pProductCardBtn">カートに入れる</button>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <!-- 検索結果が0件だった場合のメッセージブロック -->
      <div class="pDoneMessageBox" style="padding: 40px 20px; text-align: center; border: 1px solid #ddd; border-radius: 4px; background: #fafafa;">
        <p class="pDoneMessageText" style="font-weight: bold; color: #6c5643;">
          ご指定のキーワードに一致する商品が見つかりませんでした。
        </p>
        <p class="pDoneMessageText" style="margin-top: 10px; font-size: 14px;">
          キーワードを変えて再度お試しください。
        </p>
      </div>
      <div style="text-align: center; margin-top: 30px;">
        <a href="products.php" class="cBtnPrimary" style="display: inline-block; padding: 10px 30px; background: #6c5643; color: #fff; text-decoration: none; border-radius: 4px;">商品一覧へ戻る</a>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php include 'includes/footer.php'; ?>