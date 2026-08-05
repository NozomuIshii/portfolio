<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 不正アクセス対策：ログイン処理を通っていない場合はログイン画面に戻す
if (!isset($_SESSION['login_completed']) || $_SESSION['login_completed'] !== true || empty($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

// ページが表示されたら完了フラグだけ落とす（リロード対策。userIdなどは消さない）
unset($_SESSION['login_completed']);

include 'includes/header.php'; 
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="login.php">ログイン</a></li>
      <li class="pBreadcrumbItem" aria-current="page">ログイン完了</li>
    </ol>
  </div>
</nav>

<!-- 2. ようこそエリア -->
<div class="pWelcome">
  <div class="lContainer">
    <p class="pWelcomeText">
      ようこそ <span class="pWelcomeName"><?php echo htmlspecialchars($_SESSION['userName'], ENT_QUOTES, 'UTF-8'); ?></span> 様
    </p>
  </div>
</div>

<main class="lMain">
  <div class="pRegisterContainer mDoneContainer">
    <h1 class="cPageTitle">ログイン完了</h1>

    <!-- 完了メッセージの枠線ブロック -->
    <div class="pDoneMessageBox">
      <p class="pDoneMessageText" style="font-weight: bold; margin-bottom: 15px; text-align: center;">
        ログインが完了しました。
      </p>
      <p class="pDoneMessageText" style="text-align: center;">
        引き続きお買い物やお手続きをお楽しみください。
      </p>
    </div>

    <!-- 共通のボタンデザインを適用して中央に配置 -->
    <div class="pFormSubmit" style="display: flex; flex-direction: column; align-items: center; gap: 15px; margin-top: 30px;">
      <a href="purchaseConfirm.php" class="cBtnPrimary">
        購入確認ページへすすむ
      </a>
      <a href="index.php" style="color: #6c5643; text-decoration: underline; font-size: 14px;">
        TOPページへもどる
      </a>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>