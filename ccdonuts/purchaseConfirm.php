<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/dbConnect.php'; 

// ★ cart.php から送信された最新の数量をセッションに反映する処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['num'])) {
    foreach ($_POST['num'] as $productId => $qty) {
        $qtyInt = (int)$qty;
        if ($qtyInt > 0) {
            $_SESSION['cart'][$productId] = $qtyInt;
        } else {
            // 0以下の数値が入った場合はカートから削除
            unset($_SESSION['cart'][$productId]);
        }
    }
}

// ログイン状況の確認
$isLoggedIn = !empty($_SESSION['userId']);
$customerId = $_SESSION['userId'] ?? null;

// ==================================================
// 1. お届け先情報の取得（会員：DB / ゲスト：セッション）
// ==================================================
$homeAddress = null;       // ご自宅（customersテーブル）
$registeredAddresses = []; // 追加お届け先リスト（addressesテーブル）
$selectedAddress = 'home'; // デフォルト選択値（ご自宅）

if ($isLoggedIn) {
    // ① ご自宅住所を取得（customersテーブル）
    $stmt = $pdo->prepare("SELECT name, furigana, postcode_a, postcode_b, address FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $homeAddress = $stmt->fetch();

    // ② 追加のお届け先リストを取得（addressesテーブル）
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE customer_id = ? ORDER BY id ASC");
    $stmt->execute([$customerId]);
    $registeredAddresses = $stmt->fetchAll();

    if (!empty($_SESSION['selected_address_id'])) {
        $selectedAddress = $_SESSION['selected_address_id'];
    }
} else {
    // ゲストの場合
    $guestAddress = $_SESSION['guest_address'] ?? null;
}

// ==================================================
// 2. クレジットカード情報の取得
// ==================================================
$registeredCards = []; // カードリスト
$selectedCard = null;

if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT * FROM credit_cards WHERE customer_id = ? ORDER BY id ASC");
    $stmt->execute([$customerId]);
    $registeredCards = $stmt->fetchAll();

    if (!empty($registeredCards)) {
        $selectedCard = $_SESSION['selected_card_id'] ?? $registeredCards[0]['id'];
    }
} else {
    // ゲストの場合
    $guestCard = $_SESSION['guest_card'] ?? null;
}

// ==================================================
// 3. カード番号伏字用関数（下4桁以外を●に変換）
// ==================================================
function maskCardNumber($number) {
    $clean = preg_replace('/\D/', '', $number);
    $length = strlen($clean);
    if ($length <= 4) {
        return $number;
    }
    return str_repeat('●', $length - 4) . substr($clean, -4);
}

// ==================================================
// 4. カート内商品の計算
// ==================================================
$cartItems = [];
$totalPrice = 0;
$totalCount = 0;

if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
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
                $item = $products[$id];
                $priceInt = (int)$item['price'];
                $totalPrice += $priceInt * $qty;
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

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// 購入可能判定
$canPurchase = false;
if ($isLoggedIn) {
    $canPurchase = ($homeAddress || !empty($registeredAddresses)) && !empty($registeredCards);
} else {
    $canPurchase = !empty($guestAddress) && !empty($guestCard);
}

include 'includes/header.php'; 
?>

<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="cart.php">カート</a></li>
      <li class="pBreadcrumbItem" aria-current="page">購入確認</li>
    </ol>
  </div>
</nav>

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

    <form action="purchaseFinalConfirm.php" method="post" class="pPurchaseForm">

      <!-- 1. ご購入商品エリア -->
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

      <!-- 2. お届け先選択エリア -->
      <section class="pPurchaseSection">
        <h2 class="pPurchaseSubTitle">お届け先</h2>

        <?php if ($isLoggedIn): ?>
          <!-- ① ご自宅 (customersテーブル) -->
          <?php if ($homeAddress): ?>
            <div class="pPurchaseItem mRadioItem">
              <label class="pRadioLabel" style="display: block; margin-bottom: 10px; font-size: 1.1rem;">
                <input type="radio" name="selected_address" value="home" <?php echo ($selectedAddress === 'home') ? 'checked' : ''; ?>>
                <strong>ご自宅</strong>
              </label>
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">お名前</span>
                <div class="pPurchaseValue"><?php echo htmlspecialchars($homeAddress['name'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">郵便番号</span>
                <div class="pPurchaseValue">〒<?php echo htmlspecialchars($homeAddress['postcode_a'] . '-' . $homeAddress['postcode_b'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">住所</span>
                <div class="pPurchaseValue"><?php echo htmlspecialchars($homeAddress['address'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
          <?php endif; ?>

          <!-- ② 追加のお届け先 (addressesテーブル) -->
          <?php foreach ($registeredAddresses as $index => $addr): ?>
            <?php $addrNum = $index + 1; ?>
            <div class="pPurchaseItem mRadioItem">
              <label class="pRadioLabel" style="display: block; margin-bottom: 10px; font-size: 1.1rem;">
                <input type="radio" name="selected_address" value="<?php echo $addr['id']; ?>" <?php echo ($selectedAddress == $addr['id']) ? 'checked' : ''; ?>>
                <strong>お届け先<?php echo $addrNum; ?></strong>
              </label>
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">お名前</span>
                <div class="pPurchaseValue"><?php echo htmlspecialchars($addr['name'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">郵便番号</span>
                <div class="pPurchaseValue">〒<?php echo htmlspecialchars($addr['postcode_a'] . '-' . $addr['postcode_b'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">住所</span>
                <div class="pPurchaseValue"><?php echo htmlspecialchars($addr['address'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- お届け先追加ボタン -->
          <div class="pActionArea" style="margin-top: 15px;">
            <a href="addressRegister.php" class="cBtnSecondary">お届け先追加</a>
          </div>

        <?php else: ?>
          <!-- ゲスト表示 -->
          <?php if (!empty($guestAddress)): ?>
            <div class="pPurchaseItem">
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">お名前</span>
                <div class="pPurchaseValue"><?php echo htmlspecialchars($guestAddress['name'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">郵便番号</span>
                <div class="pPurchaseValue">〒<?php echo htmlspecialchars($guestAddress['zip1'] . '-' . $guestAddress['zip2'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">住所</span>
                <div class="pPurchaseValue"><?php echo htmlspecialchars($guestAddress['address'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
          <?php else: ?>
            <div class="pPaymentAction">
              <a href="addressRegister.php" class="cBtnPrimary">お届け先を入力する</a>
            </div>
          <?php endif; ?>
        <?php endif; ?>

      </section>

      <!-- 3. お支払い方法選択エリア -->
      <section class="pPurchaseSection mPaymentSection">
        <h2 class="pPurchaseSubTitle">お支払い方法</h2>

        <?php if ($isLoggedIn): ?>
          <?php if (!empty($registeredCards)): ?>
            <!-- クレジットカード一覧 -->
            <?php foreach ($registeredCards as $index => $card): ?>
              <div class="pPurchaseItem mRadioItem">
                <label class="pRadioLabel" style="display: block; margin-bottom: 10px; font-size: 1.1rem;">
                  <input type="radio" name="selected_card" value="<?php echo $card['id']; ?>" <?php echo ($selectedCard == $card['id']) ? 'checked' : ''; ?>>
                  <strong>クレジットカード <?php echo $index + 1; ?></strong>
                </label>
                <div class="pPurchaseRow">
                  <span class="pPurchaseLabel">お支払い</span>
                  <div class="pPurchaseValue">クレジットカード</div>
                </div>
                <div class="pPurchaseRow">
                  <span class="pPurchaseLabel">ブランド</span>
                  <div class="pPurchaseValue"><?php echo htmlspecialchars($card['card_type'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="pPurchaseRow">
                  <span class="pPurchaseLabel">カード番号</span>
                  <div class="pPurchaseValue"><?php echo htmlspecialchars(maskCardNumber($card['card_number']), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
            <?php endforeach; ?>

            <!-- クレジットカード追加ボタン -->
            <div class="pActionArea" style="margin-top: 15px;">
              <a href="creditCardRegister.php" class="cBtnSecondary">クレジットカード追加</a>
            </div>

          <?php else: ?>
            <!-- カード未登録の場合 -->
            <div class="pPaymentAction">
              <a href="creditCardRegister.php" class="cBtnPrimary">カード情報登録する</a>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <!-- ゲスト表示 -->
          <?php if (!empty($guestCard)): ?>
            <div class="pPurchaseItem">
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">お支払い</span>
                <div class="pPurchaseValue">クレジットカード</div>
              </div>
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">ブランド</span>
                <div class="pPurchaseValue"><?php echo htmlspecialchars($guestCard['card_type'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="pPurchaseRow">
                <span class="pPurchaseLabel">カード番号</span>
                <div class="pPurchaseValue"><?php echo htmlspecialchars(maskCardNumber($guestCard['card_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
          <?php else: ?>
            <div class="pPaymentAction">
              <a href="creditCardRegister.php" class="cBtnPrimary">カード情報登録する</a>
            </div>
          <?php endif; ?>
        <?php endif; ?>

      </section>

      <!-- 4. 購入確定ボタンエリア -->
      <?php if ($canPurchase): ?>
        <div class="pFormSubmit" style="margin-top: 30px;">
          <button type="submit" class="cBtnPrimary">購入を確定する</button>
        </div>
      <?php endif; ?>

    </form>

  </div>
</main>

<?php include 'includes/footer.php'; ?>