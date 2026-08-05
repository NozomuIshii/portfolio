<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/dbConnect.php';

// 1. 登録確定処理（「登録する」ボタンが押された時）
if (isset($_POST['action']) && $_POST['action'] === 'save') {
    if (!empty($_SESSION['guest_address_temp'])) {
        $temp = $_SESSION['guest_address_temp'];
        
        // 会員ログイン状態なら DB に保存
        if (!empty($_SESSION['userId'])) {
            $stmt = $pdo->prepare("INSERT INTO addresses (customer_id, name, furigana, postcode_a, postcode_b, address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['userId'],
                $temp['name'],
                $temp['kana'],
                $temp['zip1'],
                $temp['zip2'],
                $temp['address']
            ]);
            // 追加した住所を選択状態にする
            $_SESSION['selected_address_id'] = $pdo->lastInsertId();
        } else {
            // ゲストの場合はセッションに一時保存
            $_SESSION['guest_address'] = $temp;
        }
        
        unset($_SESSION['guest_address_temp']);
    }
    
    // お届け先登録完了画面へ遷移
    header('Location: addressDone.php');
    exit;
}

// 2. addressRegister.php からの POST 送信
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['guest_address_temp'] = [
        'name'    => trim($_POST['name'] ?? ''),
        'kana'    => trim($_POST['kana'] ?? ''),
        'zip1'    => trim($_POST['zip1'] ?? ''),
        'zip2'    => trim($_POST['zip2'] ?? ''),
        'address' => trim($_POST['address'] ?? '')
    ];
}

$data = $_SESSION['guest_address_temp'] ?? $_SESSION['guest_address'] ?? [];

if (empty($data)) {
    header('Location: addressRegister.php');
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
      <li class="pBreadcrumbItem" aria-current="page">お届け先確認</li>
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
  <div class="pRegisterContainer">
    <h1 class="cPageTitle">お届け先確認</h1>

    <form action="addressConfirm.php" method="post" class="pForm">
      <input type="hidden" name="action" value="save">

      <div class="pFormGroup mConfirmGroup">
        <span class="pFormLabel">お名前</span>
        <div class="pFormConfirmValue"><?php echo htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <div class="pFormGroup mConfirmGroup">
        <span class="pFormLabel">お名前（フリガナ）</span>
        <div class="pFormConfirmValue"><?php echo htmlspecialchars($data['kana'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <div class="pFormGroup mConfirmGroup">
        <span class="pFormLabel">郵便番号</span>
        <div class="pFormConfirmValue">〒<?php echo htmlspecialchars($data['zip1'] . '-' . $data['zip2'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <div class="pFormGroup mConfirmGroup">
        <span class="pFormLabel">住所</span>
        <div class="pFormConfirmValue"><?php echo htmlspecialchars($data['address'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <div class="pFormSubmit">
        <button type="submit" class="cBtnPrimary">登録する</button>
      </div>
    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>