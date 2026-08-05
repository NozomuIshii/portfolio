<?php 
// データベース接続ファイルを読み込みます
require_once 'includes/dbConnect.php'; 
include 'includes/header.php'; 

// データベースからすべての商品情報を取得するSQLを実行
try {
    $stmt = $pdo->query("SELECT * FROM products");
    $allProducts = $stmt->fetchAll();
} catch (PDOException $e) {
    exit('データ取得失敗: ' . $e->getMessage());
}

// 取得した商品をカテゴリーごとに振り分けるための配列を用意
$mainProducts = [];
$varietyProducts = [];

// 【修正箇所】DBにcategoryカラムがないため、商品ID（id）の範囲で振り分けます
// id が 1〜6 はメインメニュー、7以降はバラエティセット
foreach ($allProducts as $product) {
    if ($product['id'] <= 6) {
        $mainProducts[] = $product;
    } else {
        $varietyProducts[] = $product;
    }
}
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem" aria-current="page">商品一覧</li>
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

<!-- 3. メインコンテンツエリア -->
<main class="pProductPage lContainer">
  <!-- ページ大見出し -->
  <h1 class="pProductMainTitle">商品一覧</h1>

  <!-- メインメニュー セクション -->
  <section class="pProductSection">
    <h2 class="pProductSectionTitle">メインメニュー</h2>

    <div class="pProductGrid">
      <?php foreach ($mainProducts as $product): ?>
        <article class="pProductCard">
          <a href="productDetail.php?id=<?php echo $product['id']; ?>" class="pProductCardLink">
            <div class="pProductCardImg">
              <!-- 【修正箇所】DBにimgカラムがないため、id番の商品画像を表示するように修正 -->
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
  </section>

  <!-- バラエティセット セクション -->
  <section class="pProductSection">
    <h2 class="pProductSectionTitle">バラエティセット</h2>

    <div class="pProductGrid">
      <?php foreach ($varietyProducts as $product): ?>
        <article class="pProductCard">
          <a href="productDetail.php?id=<?php echo $product['id']; ?>" class="pProductCardLink">
            <div class="pProductCardImg">
              <!-- 【修正箇所】DBにimgカラムがないため、id番の商品画像を表示するように修正 -->
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
  </section>

</main>

<?php include 'includes/footer.php'; ?>