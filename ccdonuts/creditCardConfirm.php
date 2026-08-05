<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/dbConnect.php';

// 1. POST送信受け取り（入力値のチェックと一時保存）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $cardName     = trim($_POST['card_name'] ?? '');
    $cardNumber   = trim($_POST['card_number'] ?? '');
    $cardType     = trim($_POST['card_type'] ?? 'JCB');
    $expiryMonth  = trim($_POST['expiry_month'] ?? '');
    $expiryYear   = trim($_POST['expiry_year'] ?? '');
    $securityCode = trim($_POST['security_code'] ?? '');

    // PHP側でのバリデーションチェック
    $errorMsg = '';
    if ($cardName === '' || $cardNumber === '' || $expiryMonth === '' || $expiryYear === '' || $securityCode === '') {
        $errorMsg = '必須項目が入力されていません。';
    } elseif (!preg_match('/^[0-9]{16}$/', $cardNumber)) {
        $errorMsg = 'カード番号は半角数字16桁で入力してください。';
    } elseif (!preg_match('/^(0?[1-9]|1[0-2])$/', $expiryMonth)) {
        $errorMsg = '有効期限（月）は1〜12の範囲で入力してください。';
    } elseif (!preg_match('/^[0-9]{2}$/', $expiryYear)) {
        $errorMsg = '有効期限（年）は半角数字2桁で入力してください。';
    } elseif (!preg_match('/^[0-9]{3}$/', $securityCode)) {
        $errorMsg = 'セキュリティコードは半角数字3桁で入力してください。';
    }

    if ($errorMsg !== '') {
        $_SESSION['card_error'] = $errorMsg;
        header('Location: creditCardRegister.php');
        exit;
    }

    $_SESSION['guest_card_temp'] = [
        'card_name'     => $cardName,
        'card_number'   => $cardNumber,
        'card_type'     => $cardType,
        'expiry_month'  => $expiryMonth,
        'expiry_year'   => $expiryYear,
        'security_code' => $securityCode
    ];
}

// 2. 登録確定処理（「登録する」ボタン押下時）
if (isset($_POST['action']) && $_POST['action'] === 'save') {
    if (!empty($_SESSION['guest_card_temp'])) {
        $temp = $_SESSION['guest_card_temp'];
        
        // 会員ログイン状態なら DB に保存
        if (!empty($_SESSION['userId'])) {
            $stmt = $pdo->prepare("INSERT INTO credit_cards (customer_id, card_name, card_number, card_type, expiry_month, expiry_year, security_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['userId'],
                $temp['card_name'],
                $temp['card_number'],
                $temp['card_type'],
                $temp['expiry_month'],
                $temp['expiry_year'],
                $temp['security_code']
            ]);
            // 追加したカードを選択状態にする
            $_SESSION['selected_card_id'] = $pdo->lastInsertId();
        } else {
            // ゲストの場合はセッションに一時保存
            $_SESSION['guest_card'] = $temp;
        }
        
        unset($_SESSION['guest_card_temp']);
    }

    // カード登録完了画面へ遷移
    header('Location: creditCardDone.php');
    exit;
}

$card = $_SESSION['guest_card_temp'] ?? $_SESSION['guest_card'] ?? [];

if (empty($card)) {
    header('Location: creditCardRegister.php');
    exit;
}

include 'includes/header.php'; 
?>

<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="cart.php">カート</a></li>
      <li class="pBreadcrumbItem"><a href="purchaseConfirm.php">購入確認</a></li>
      <li class="pBreadcrumbItem"><a href="creditCardRegister.php">カード情報</a></li>
      <li class="pBreadcrumbItem" aria-current="page">情報確認</li>
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
  <div class="pCardConfirmContainer">
    <h1 class="cPageTitle">入力情報確認</h1>

    <div class="pPurchaseItem mCardConfirmList">
      <div class="pPurchaseRow">
        <span class="pPurchaseLabel">お名前</span>
        <div class="pPurchaseValue"><?php echo htmlspecialchars($card['card_name'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <div class="pPurchaseRow">
        <span class="pPurchaseLabel">カード番号</span>
        <div class="pPurchaseValue"><?php echo htmlspecialchars($card['card_number'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <div class="pPurchaseRow">
        <span class="pPurchaseLabel">カード会社</span>
        <div class="pPurchaseValue"><?php echo htmlspecialchars($card['card_type'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <div class="pPurchaseRow mExpiryRow">
        <span class="pPurchaseLabel">有効期限</span>
        <div class="pPurchaseValue">
          <span><?php echo htmlspecialchars($card['expiry_month'], ENT_QUOTES, 'UTF-8'); ?></span><span class="pCardConfirmUnit">月</span>
        </div>
      </div>

      <div class="pPurchaseRow mExpiryRow">
        <span class="pPurchaseLabel uHiddenLabel">有効期限（年）</span>
        <div class="pPurchaseValue">
          <span><?php echo htmlspecialchars($card['expiry_year'], ENT_QUOTES, 'UTF-8'); ?></span><span class="pCardConfirmUnit">年</span>
        </div>
      </div>

      <div class="pPurchaseRow">
        <span class="pPurchaseLabel">セキュリティコード</span>
        <div class="pPurchaseValue"><?php echo htmlspecialchars($card['security_code'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    </div>

    <form action="creditCardConfirm.php" method="post" class="pCardConfirmActionForm">
      <input type="hidden" name="action" value="save">
      <div class="pFormSubmit">
        <button type="submit" class="cBtnPrimary">登録する</button>
      </div>
    </form>

  </div>
</main>

<?php include 'includes/footer.php'; ?>