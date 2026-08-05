<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// データベース接続ファイルを読み込みます
require_once 'includes/dbConnect.php'; 

// 【変更後：売上数量が多い順に上位6件を自動取得】
$rankingProducts = [];

try {
    // LEFT JOINにすることで購入数が0件の商品も取得対象にし、6件未満になるのを防ぎます
    // IFNULL(SUM(od.quantity), 0) で売上がない商品は 0個 として扱います
    $sql = "SELECT p.*, IFNULL(SUM(od.quantity), 0) AS total_sold
            FROM products p
            LEFT JOIN order_details od ON p.id = od.product_id
            GROUP BY p.id
            ORDER BY total_sold DESC, p.id ASC
            LIMIT 6";
            
    $stmt = $pdo->query($sql);
    $rankingProducts = $stmt->fetchAll();

} catch (PDOException $e) {
    exit('データ取得失敗: ' . $e->getMessage());
}

// 共通のヘッダーファイルを読み込む
include 'includes/header.php'; 
?>

<main class="lMain">
  <!-- 2. ようこそエリア -->
  <div class="pWelcome">
    <div class="lContainer">
      <p class="pWelcomeText">
        ようこそ 
        <span class="pWelcomeName">
          <?php
          if (!empty($_SESSION['userName'])) {
              echo htmlspecialchars($_SESSION['userName'], ENT_QUOTES, 'UTF-8');
          } else {
              echo 'ゲスト';
          }
          ?>
        </span> 様
      </p>
    </div>
  </div>

  <!-- 3. ヒーロービジュアル（PC/SP画像出し分け） -->
  <div class="pHero">
      <picture class="pHeroImage">
        <source media="(min-width: 768px)" srcset="images/topHero01Pc.png">
        <img src="images/topHero01Sp.png" alt="ドーナッツをみんなで囲む風景">
      </picture>
  </div>

  <!-- 特設バナーグリッド -->
  <section class="pBannerSection">
    <div class="lContainer pBannerGrid">
      <!-- サマーストラス -->
      <a href="productDetail.php?id=5" class="pBannerItem">
        <img src="images/topNewItem01.png" alt="新商品 サマーストラス">
      </a>
      
      <!-- ドーナッツのある生活 -->
      <a href="products.php" class="pBannerItem">
        <img src="images/topNewItem02.png" alt="ドーナッツのある生活">
      </a>
      
      <!-- 商品一覧バナー -->
      <a href="products.php" class="pBannerItem pBannerItemFull">
        <img src="images/topNewItemList01.png" alt="商品一覧">
      </a>
    </div>
  </section>

  <!-- Philosophy（私たちの信念）セクション -->
  <section class="pPhilosophy">
    <div class="pPhilosophyBg">
      <img src="images/toppPhilosophy.png" alt="" role="presentation">
    </div>
    
    <div class="lContainer pPhilosophyBody">
      <h2 class="pPhilosophyTitle">Philosophy</h2>
      <p class="pPhilosophySubTitle">私たちの信念</p>
      <p class="pPhilosophyLead">"Creating Connections"</p>
      <p class="pPhilosophyText">「ドーナツでつながる」</p>
    </div>
  </section>

  <!-- 人気ランキングセクション -->
  <section class="pRanking">
    <div class="lContainer">
      <h2 class="pRankingSectionTitle">人気ランキング</h2>
      
      <div class="pRankingGrid">
        <?php 
        // ループ処理でランキングカードを動的出力（$index+1 が現在の順位）
        foreach ($rankingProducts as $index => $product): 
            $rank = $index + 1;
            // 1〜3位の時だけ固有のクラス（mRank1 など）を付与するための記述
            $rankClass = ($rank <= 3) ? ' mRank' . $rank : '';
        ?>
          <article class="pRankingCard<?php echo $rankClass; ?>">
            <span class="pRankingBadge"><?php echo $rank; ?></span>
            <div class="pRankingImage">
              <a href="productDetail.php?id=<?php echo $product['id']; ?>">
                <!-- 画像名はIDを元に動的生成 -->
                <img src="images/product<?php echo $product['id']; ?>.png" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
              </a>
            </div>
            <h3 class="pRankingTitle"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="pRankingPrice">税込&nbsp; ￥<?php echo number_format($product['price']); ?></p>
            <form action="cartAdd.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <button type="submit" class="pRankingBtn">カートに入れる</button>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>

<!-- フッターエリア -->
<footer class="pFooter">
  <div class="lContainer pFooterInner">
    <nav class="pFooterNav">
      <ul class="pFooterNavList">
        <li class="pFooterNavItem"><a href="#">よくある質問</a></li>
        <li class="pFooterNavItem"><a href="#">お問い合わせ</a></li>
        <li class="pFooterNavItem"><a href="#">当サイトのポリシー</a></li>
      </ul>
    </nav>

    <div class="pFooterLogo">
      <a href="index.php">
        <img src="images/commonLogo.png" alt="CCドーナッツ ロゴ">
      </a>
    </div>

    <ul class="pFooterSnsList">
      <li class="pFooterSnsItem"><a href="#" target="_blank" rel="noopener noreferrer"><img src="images/commonFaceBook.png" alt="Facebook"></a></li>
      <li class="pFooterSnsItem"><a href="#" target="_blank" rel="noopener noreferrer"><img src="images/commonInsta.png" alt="Instagram"></a></li>
      <li class="pFooterSnsItem"><a href="#" target="_blank" rel="noopener noreferrer"><img src="images/commonTwitter.png" alt="Twitter"></a></li>
    </ul>
  </div>

  <div class="pFooterCopyright">
    <small>Copyright (C) 2023 c.c.dounuts</small>
  </div>
</footer>

<!-- ドロワーメニュー -->
<div class="pDrawerMenu" id="drawerMenu">
    <div class="lContainer pDrawerMenuInner">
        <div class="pDrawerMenuHeader">
            <div class="pDrawerMenuLogo">
                <a href="index.php">
                    <img src="images/commonLogo.png" alt="CCドーナッツロゴ">
                </a>
            </div>
            <button class="pDrawerMenuCloseBtn" id="drawerClose" aria-label="メニューを閉じる"></button>
        </div>

        <nav class="pDrawerMenuNav">
            <ul class="pDrawerMenuNavList">
                <li class="pDrawerMenuNavItem"><a href="index.php">TOP</a></li>
                <li class="pDrawerMenuNavItem"><a href="products.php">商品一覧</a></li>
                <li class="pDrawerMenuNavItem"><a href="faq.php">よくある質問</a></li>
                <li class="pDrawerMenuNavItem"><a href="contact.php">問い合わせ</a></li>
                <li class="pDrawerMenuNavItem"><a href="policy.php">当サイトのポリシー</a></li>
            </ul>
        </nav>
    </div>
</div>

<script src="scripts/main.js"></script>
</body>
</html>