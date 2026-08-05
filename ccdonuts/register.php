<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/header.php'; 

$error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : '';
unset($_SESSION['register_error']);
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="login.php">ログイン</a></li>
      <li class="pBreadcrumbItem" aria-current="page">会員登録</li>
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
  <div class="pRegisterContainer">
    <h1 class="cPageTitle">会員登録</h1>
    <?php if (!empty($error)): ?>
      
      <p style="color: red; text-align: center; margin-bottom: 20px; font-weight: bold;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <!-- フォームの送信先を registerConfirm.php に指定 -->
    <form action="registerConfirm.php" method="post" class="pForm">
      
      <!-- お名前 -->
      <div class="pFormGroup">
        <label for="name" class="pFormLabel">お名前 <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap">
          <input type="text" id="name" name="name" placeholder="ドーナツ太郎" required class="cInputText">
        </div>
      </div>

      <!-- お名前（フリガナ） -->
      <div class="pFormGroup">
        <label for="kana" class="pFormLabel">お名前（フリガナ） <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap">
          <input type="text" id="kana" name="kana" placeholder="ドーナツタロウ" pattern="^[ァ-ヶー]+$" title="全角カタカナのみで入力してください" required class="cInputText">
        </div>
      </div>

      <!-- 郵便番号 -->
      <div class="pFormGroup">
        <label for="zip1" class="pFormLabel">郵便番号 <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap pFormInputWrap--zip">
          <input type="text" id="zip1" name="zip1" placeholder="123" maxlength="3" required class="cInputText cInputText--zip1">
          <input type="text" id="zip2" name="zip2" placeholder="4567" maxlength="4" required class="cInputText cInputText--zip2">
        </div>
      </div>

      <!-- 住所 -->
      <div class="pFormGroup">
        <label for="address" class="pFormLabel">住所 <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap">
          <input type="text" id="address" name="address" placeholder="千葉県〇〇市中央1-1-1" required class="cInputText">
        </div>
      </div>

      <!-- メールアドレス -->
      <div class="pFormGroup">
        <label for="email" class="pFormLabel">メールアドレス <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap">
          <input type="email" id="email" name="email" placeholder="123@gmail.com" required class="cInputText">
        </div>
      </div>

      <!-- メールアドレス確認用 -->
      <div class="pFormGroup">
        <label for="email_confirm" class="pFormLabel">メールアドレス確認用 <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap">
          <input type="email" id="email_confirm" name="email_confirm" placeholder="123@gmail.com" required class="cInputText">
        </div>
      </div>

      <!-- パスワード -->
      <div class="pFormGroup">
        <label for="password" class="pFormLabel">
          パスワード <span class="cRequired">（必須）</span>
          <span class="pFormLabelNote">半角英数字8文字以上20文字以内で入力してください。※記号の使用はできません</span>
        </label>
        <div class="pFormInputWrap">
          <input type="password" id="password" name="password" placeholder="123456abcd" required class="cInputText">
        </div>
      </div>

      <!-- パスワード確認用 -->
      <div class="pFormGroup">
        <label for="password_confirm" class="pFormLabel">パスワード確認用 <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap">
          <input type="password" id="password_confirm" name="password_confirm" placeholder="123456abcd" required class="cInputText">
        </div>
      </div>

      <!-- 送信ボタン -->
      <div class="pFormSubmit">
        <button type="submit" class="cBtnPrimary">入力確認する</button>
      </div>

    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>