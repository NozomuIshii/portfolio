<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/dbConnect.php'; 

// カード番号伏字用関数（下4桁以外を●に変換）
function maskCardNumber($number) {
    $clean = preg_replace('/\D/', '', $number); // ハイフンやスペースを除去
    $length = strlen($clean);
    if ($length <= 4) {
        return $number;
    }
    return str_repeat('●', $length - 4) . substr($clean, -4);
}

// ログイン状況の確認
$isLoggedIn = !empty($_SESSION['userId']);
$customerId = $_SESSION['userId'] ?? null;

$addressData = null;
$cardData    = null;

if ($isLoggedIn) {
    // ==========================================
    // ログインユーザーの場合
    // ==========================================
    
    // POSTで送信された選択肢を取得（またはデフォルト値）
    $selectedAddress = $_POST['selected_address'] ?? ($_SESSION['selected_address_id'] ?? 'home');
    $selectedCardId  = $_POST['selected_card'] ?? ($_SESSION['selected_card_id'] ?? null);

    // ① お届け先情報の取得
    if ($selectedAddress === 'home') {
        $stmt = $pdo->prepare("SELECT name, postcode_a AS zip1, postcode_b AS zip2, address FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $addressData = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT name, postcode_a AS zip1, postcode_b AS zip2, address FROM addresses WHERE id = ? AND customer_id = ?");
        $stmt->execute([$selectedAddress, $customerId]);
        $addressData = $stmt->fetch();
    }

    // ② カード情報の取得
    if ($selectedCardId) {
        $stmt = $pdo->prepare("SELECT * FROM credit_cards WHERE id = ? AND customer_id = ?");
        $stmt->execute([$selectedCardId, $customerId]);
        $cardData = $stmt->fetch();
    }

} else {
    // ==========================================
    // ゲストの場合
    // ==========================================
    $addressData = $_SESSION['guest_address'] ?? null;
    $cardData    = $_SESSION['guest_card'] ?? null;
}

// 住所・カードどちらかが存在しなければ purchaseConfirm.php へリダイレクト
if (empty($addressData) || empty($cardData)) {
    header('Location: purchaseConfirm.php');
    exit;
}

// 選択した住所・カード情報をセッションに保持（完了処理用）
$_SESSION['final_address'] = $addressData;
$_SESSION['final_card']    = $cardData;

// 2. カート内商品の動的計算
$cartItems  = [];
$totalPrice = 0;
$totalCount = 0;

if (!empty($_SESSION['cart'])) {
    $productIds   = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $productsFromDb = $stmt->fetchAll();
        
        $products = [];
        foreach ($productsFromDb as $row) {
            $products[$row['id']] = $row;
        }
        
        foreach ($_SESSION['cart'] as $id => $qty) {
            if (isset($products[$id])) {
                $item       = $products[$id];
                $priceInt   = (int)$item['price'];
                $subTotal   = $priceInt * $qty;
                
                $totalPrice += $subTotal;
                $totalCount += $qty;

                $cartItems[] = [
                    'id'       => $id,
                    'name'     => $item['name'],
                    'price'    => $priceInt,
                    'quantity' => $qty
                ];
            }
        }
    } catch (PDOException $e) {
        exit('データ取得失敗: ' . $e->getMessage());
    }
}

// 万が一カートが空の場合はカート画面へ
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

include 'includes/header.php'; 
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="cart.php">カート</a></li>
      <li class="pBreadcrumbItem" aria-current="page">購入確認</li>
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

<main class="lMain">
  <div class="pPurchaseContainer">
    <h1 class="cPageTitle">ご購入確認</h1>

    <!-- 1. ご購入商品セクション -->
    <section class="pPurchaseSection">
      <h2 class="pPurchaseSubTitle">ご購入商品</h2>
      
      <?php foreach ($cartItems as $item): ?>
        <div class="pPurchaseItem">
          <div class="pPurchaseRow">
            <span class="pPurchaseLabel">商品名</span>
            <div class="pPurchaseValue"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <div class="pPurchaseRow">
            <span class="pPurchaseLabel">数量</span>
            <div class="pPurchaseValue"><?php echo htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8'); ?>個</div>
          </div>
          <div class="pPurchaseRow">
            <span class="pPurchaseLabel">金額</span>
            <div class="pPurchaseValue">税込 ¥<?php echo number_format($item['price'] * $item['quantity']); ?></div>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- 合計数量・合計金額 -->
      <div class="pPurchaseTotal">
        <div class="pPurchaseRow">
          <span class="pPurchaseLabel" style="font-weight: bold;">合計数量</span>
          <div class="pPurchaseValue" style="font-weight: bold;"><?php echo $totalCount; ?>個</div>
        </div>
        <div class="pPurchaseRow">
          <span class="pPurchaseLabel" style="font-weight: bold;">合計金額</span>
          <div class="pPurchaseValue" style="font-weight: bold;">税込 ¥<?php echo number_format($totalPrice); ?></div>
        </div>
      </div>
    </section>

    <!-- 2. お届け先セクション -->
    <section class="pPurchaseSection">
      <h2 class="pPurchaseSubTitle">お届け先</h2>
      <div class="pPurchaseItem">
        <div class="pPurchaseRow">
          <span class="pPurchaseLabel">お名前</span>
          <div class="pPurchaseValue"><?php echo htmlspecialchars($addressData['name'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div class="pPurchaseRow">
          <span class="pPurchaseLabel">郵便番号</span>
          <div class="pPurchaseValue">〒<?php echo htmlspecialchars($addressData['zip1'] . '-' . $addressData['zip2'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div class="pPurchaseRow">
          <span class="pPurchaseLabel">住所</span>
          <div class="pPurchaseValue"><?php echo htmlspecialchars($addressData['address'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      </div>
    </section>

    <!-- 3. お支払い方法セクション -->
    <section class="pPurchaseSection">
      <h2 class="pPurchaseSubTitle">お支払い方法</h2>
      <div class="pPurchaseItem">
        <div class="pPurchaseRow">
          <span class="pPurchaseLabel">お支払い</span>
          <div class="pPurchaseValue">クレジットカード</div>
        </div>
        <div class="pPurchaseRow">
          <span class="pPurchaseLabel">ブランド</span>
          <div class="pPurchaseValue"><?php echo htmlspecialchars($cardData['card_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <!-- ★ 追加：カード番号表示エリア -->
        <?php if (!empty($cardData['card_number'])): ?>
          <div class="pPurchaseRow">
            <span class="pPurchaseLabel">カード番号</span>
            <div class="pPurchaseValue"><?php echo htmlspecialchars(maskCardNumber($cardData['card_number']), ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        <?php endif; ?>

      </div>
    </section>

    <!-- 最終確定ボタン（purchaseDone.php へ送信） -->
    <form action="purchaseDone.php" method="post" class="pPurchaseActionForm">
      <div class="pFormSubmit">
        <button type="submit" class="cBtnPrimary">購入を確定する</button>
      </div>
    </form>

  </div>
</main>

<?php include 'includes/footer.php'; ?>