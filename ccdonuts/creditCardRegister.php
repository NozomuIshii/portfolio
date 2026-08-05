<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/header.php'; 

$cardData = isset($_SESSION['guest_card']) ? $_SESSION['guest_card'] : [];
$error = isset($_SESSION['card_error']) ? $_SESSION['card_error'] : '';
unset($_SESSION['card_error']);
?>

<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="cart.php">カート</a></li>
      <li class="pBreadcrumbItem"><a href="purchaseConfirm.php">購入確認</a></li>
      <li class="pBreadcrumbItem" aria-current="page">カード情報</li>
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
  <div class="pCardRegisterContainer">
    
    <div class="cAlertWarningBox">
      当サイトは模擬サイトですので、実際の<br class="uSpOnly">クレジットカード情報は登録しないでください
    </div>

    <h1 class="cPageTitle">カード情報登録</h1>

    <?php if (!empty($error)): ?>
      <p style="color: red; text-align: center; margin-bottom: 20px; font-weight: bold;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form action="creditCardConfirm.php" method="post" class="pForm">
      
      <!-- お名前 -->
      <div class="pFormGroup">
        <label for="cardName" class="pFormLabel">お名前 <span class="cFormRequired">(必須)</span></label>
        <input type="text" id="cardName" name="card_name" class="cInputText" placeholder="ドーナツ太郎" value="<?php echo htmlspecialchars($cardData['card_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>

      <!-- カード番号 -->
      <div class="pFormGroup">
        <label for="cardNumber" class="pFormLabel">カード番号 <span class="cFormRequired">(必須)</span></label>
        <input type="text" id="cardNumber" name="card_number" class="cInputText" placeholder="1234567890123456" pattern="^[0-9]{16}$" maxlength="16" title="半角数字14桁〜16桁で入力してください" value="<?php echo htmlspecialchars($cardData['card_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>

      <!-- カード会社 -->
      <div class="pFormGroup">
        <span class="pFormLabel">カード会社 <span class="cFormRequired">(必須)</span></span>
        <div class="pFormRadioGroup">
          <?php $currentType = $cardData['card_type'] ?? 'JCB'; ?>
          <label class="cFormRadioLabel">
            <input type="radio" name="card_type" value="JCB" <?php echo $currentType === 'JCB' ? 'checked' : ''; ?>> JCB
          </label>
          <label class="cFormRadioLabel">
            <input type="radio" name="card_type" value="Visa" <?php echo $currentType === 'Visa' ? 'checked' : ''; ?>> Visa
          </label>
          <label class="cFormRadioLabel">
            <input type="radio" name="card_type" value="Mastercard" <?php echo $currentType === 'Mastercard' ? 'checked' : ''; ?>> Mastercard
          </label>
        </div>
      </div>

      <!-- 有効期限 -->
      <div class="pFormGroup">
        <span class="pFormLabel">有効期限 <span class="cFormRequired">(必須)</span></span>
        <div class="pFormExpiryGroup">
          <div class="pFormExpiryInputWrap">
            <input type="text" name="expiry_month" class="cInputText mInputShort" placeholder="04" pattern="^(0?[1-9]|1[0-2])$" maxlength="2" title="月を1〜12で入力してください" value="<?php echo htmlspecialchars($cardData['expiry_month'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            <span class="pFormExpiryUnit">月</span>
          </div>
          <div class="pFormExpiryInputWrap">
            <input type="text" name="expiry_year" class="cInputText mInputShort" placeholder="28" pattern="^[0-9]{2}$" maxlength="2" title="年を半角数字2桁で入力してください" value="<?php echo htmlspecialchars($cardData['expiry_year'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            <span class="pFormExpiryUnit">年</span>
          </div>
        </div>
      </div>

      <!-- セキュリティコード -->
      <div class="pFormGroup">
        <label for="securityCode" class="pFormLabel">セキュリティコード <span class="cFormRequired">(必須)</span></label>
        <input type="text" id="securityCode" name="security_code" class="cInputText" placeholder="123" pattern="^[0-9]{3}$" maxlength="4" title="半角数字3桁または4桁で入力してください" value="<?php echo htmlspecialchars($cardData['security_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>

      <div class="pFormSubmit">
        <button type="submit" class="cBtnPrimary">入力確認する</button>
      </div>

    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>