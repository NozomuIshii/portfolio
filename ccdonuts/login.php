<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/header.php'; 

// セッションからエラーメッセージを取得して削除する
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem" aria-current="page">ログイン</li>
    </ol>
  </div>
</nav>

<main class="lMain">
  <div class="pLoginContainer">
    <h1 class="cPageTitle">ログイン</h1>

    <!-- エラーがあれば共通の枠線スタイルを使って綺麗に表示する -->
    <?php if (!empty($error)): ?>
      <div class="pDoneMessageBox" style="border-color: #f2d6d6; background-color: #fff9f9; margin-bottom: 25px;">
        <p class="pDoneMessageText" style="color: #b93a3a; text-align: center; font-weight: bold;">
          <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </p>
      </div>
    <?php endif; ?>

    <!-- ログインフォーム -->
    <div class="pLoginBox">
      <form action="loginProcess.php" method="post" class="pForm">
        
        <!-- メールアドレス -->
        <div class="pFormGroup mLoginGroup">
          <label for="email" class="pFormLabel">メールアドレス</label>
          <input type="email" id="email" name="email" class="cInputText" placeholder="123@gmail.com" required>
        </div>

        <!-- パスワード -->
        <div class="pFormGroup mLoginGroup">
          <label for="password" class="pFormLabel">パスワード</label>
          <input type="password" id="password" name="password" class="cInputText" placeholder="123456" required>
        </div>

        <!-- ログインボタン -->
        <div class="pFormSubmit mLoginSubmit">
          <button type="submit" class="cBtnPrimary">ログインする</button>
        </div>

      </form>
    </div>

    <!-- 会員登録へのリンクエリア -->
    <div class="pLoginLinkArea">
      <a href="register.php" class="pLoginLink">会員登録はこちら</a>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>