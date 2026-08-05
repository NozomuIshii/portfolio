<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/header.php'; 

$addressData = isset($_SESSION['guest_address']) ? $_SESSION['guest_address'] : [];
?>

<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="cart.php">カート</a></li>
      <li class="pBreadcrumbItem"><a href="purchaseConfirm.php">購入確認</a></li>
      <li class="pBreadcrumbItem" aria-current="page">お届け先入力</li>
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
    <h1 class="cPageTitle">お届け先入力</h1>

    <form action="addressConfirm.php" method="post" class="pForm">
      
      <!-- お名前 -->
      <div class="pFormGroup">
        <label for="name" class="pFormLabel">お名前 <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap">
          <input type="text" id="name" name="name" placeholder="ドーナツ太郎" value="<?php echo htmlspecialchars($addressData['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required class="cInputText">
        </div>
      </div>

      <!-- お名前（フリガナ） -->
      <div class="pFormGroup">
        <label for="kana" class="pFormLabel">お名前（フリガナ） <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap">
          <input type="text" id="kana" name="kana" placeholder="ドーナツタロウ" pattern="^[ァ-ヶー]+$" title="全角カタカナのみで入力してください" value="<?php echo htmlspecialchars($addressData['kana'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required class="cInputText">
        </div>
      </div>

      <!-- 郵便番号 -->
      <div class="pFormGroup">
        <label for="zip1" class="pFormLabel">郵便番号 <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap pFormInputWrap--zip">
          <input type="text" id="zip1" name="zip1" placeholder="123" maxlength="3" value="<?php echo htmlspecialchars($addressData['zip1'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required class="cInputText cInputText--zip1">
          <input type="text" id="zip2" name="zip2" placeholder="4567" maxlength="4" value="<?php echo htmlspecialchars($addressData['zip2'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required class="cInputText cInputText--zip2">
        </div>
      </div>

      <!-- 住所 -->
      <div class="pFormGroup">
        <label for="address" class="pFormLabel">住所 <span class="cRequired">（必須）</span></label>
        <div class="pFormInputWrap">
          <input type="text" id="address" name="address" placeholder="千葉県〇〇市中央1-1-1" value="<?php echo htmlspecialchars($addressData['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required class="cInputText">
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