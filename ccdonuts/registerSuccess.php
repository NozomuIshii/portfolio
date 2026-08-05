<?php
// ==========================================
// 1. セッションの開始
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 2. 正規の登録処理を経由してきたかチェック（不正アクセス対策）
// ==========================================
if (!isset($_SESSION['register_completed']) || $_SESSION['register_completed'] !== true) {
    // フラグがない、または不正なアクセスの場合は登録入力画面へ強制移動
    header('Location: register.php');
    exit;
}

// ==========================================
// 3. 1回表示したらフラグを削除（ブラウザの更新・リロード対策）
// ==========================================
unset($_SESSION['register_completed']);

// 登録されたお名前をセッションから取得して、セッションからは消去する
$registered_name = isset($_SESSION['registered_name']) ? $_SESSION['registered_name'] : 'お客様';
unset($_SESSION['registered_name']);

// 共通ヘッダーの読み込み
include 'includes/header.php'; 
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="login.php">ログイン</a></li>
      <li class="pBreadcrumbItem"><a href="register.php">会員登録</a></li>
      <li class="pBreadcrumbItem" aria-current="page">登録完了</li>
    </ol>
  </div>
</nav>

<!-- 2. ようこそエリア -->
<div class="pWelcome">
  <div class="lContainer">
    <p class="pWelcomeText">
      ようこそ <span class="pWelcomeName"><?php echo htmlspecialchars($registered_name, ENT_QUOTES, 'UTF-8'); ?></span> 様
    </p>
  </div>
</div>

<!-- 3. メインコンテンツ（CSSの完了画面用マルチクラス .mDoneContainer を適用） -->
<main class="lMain">
  <div class="pRegisterContainer mDoneContainer">
    <!-- タイトル用の共通クラス -->
    <h1 class="cPageTitle">会員登録完了</h1>
    
    <!-- CSSのメッセージボックス用クラス .pDoneMessageBox を適用 -->
    <div class="pDoneMessageBox">
        <p class="pDoneMessageText" style="font-weight: bold; margin-bottom: 15px;">
            会員登録が正常に完了いたしました！
        </p>
        <p class="pDoneMessageText">
            ご登録いただき、誠にありがとうございました。<br>
            先ほどご登録いただいたメールアドレスとパスワードで、サイトにログインしていただけます。
        </p>
    </div>

    <!-- ログイン画面への移動ボタン（不要なインラインスタイルを排除し、cBtnPrimary本来のスタイルを適用） -->
    <div class="pFormSubmit">
        <a href="login.php" class="cBtnPrimary">
            ログイン画面へ進む
        </a>
    </div>
  </div>
</main>

<?php 
// 共通フッターの読み込み
include 'includes/footer.php'; 
?>