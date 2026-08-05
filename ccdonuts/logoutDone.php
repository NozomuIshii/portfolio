<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 不正アクセス対策：ログアウト処理を通っていない直リンクの場合はTOP画面に戻す
if (!isset($_SESSION['logout_completed']) || $_SESSION['logout_completed'] !== true) {
    header('Location: index.php');
    exit;
}

// リロード対策：完了フラグだけ落とす
unset($_SESSION['logout_completed']);

include 'includes/header.php'; 
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem" aria-current="page">ログアウト完了</li>
    </ol>
  </div>
</nav>

<!-- 2. ようこそエリア -->
<div class="pWelcome">
  <div class="lContainer">
    <p class="pWelcomeText">
      ようこそ <span class="pWelcomeName">ゲスト</span> 様
    </p>
  </div>
</div>

<main class="lMain">
  <div class="pRegisterContainer mDoneContainer">
    <h1 class="cPageTitle">ログアウト完了</h1>

    <!-- 完了メッセージの枠線ブロック -->
    <div class="pDoneMessageBox">
      <p class="pDoneMessageText" style="font-weight: bold; margin-bottom: 15px; text-align: center;">
        ログアウトが完了しました。
      </p>
      <p class="pDoneMessageText" style="text-align: center;">
        またのご利用を心よりお待ちしております。
      </p>
    </div>

    <!-- 共通のボタンデザインを適用して中央に配置 -->
    <div class="pFormSubmit" style="display: flex; flex-direction: column; align-items: center; gap: 15px; margin-top: 30px;">
      <a href="index.php" class="cBtnPrimary">
        TOPページへもどる
      </a>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>