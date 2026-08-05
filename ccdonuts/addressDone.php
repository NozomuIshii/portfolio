<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/header.php'; 
?>

<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="cart.php">カート</a></li>
      <li class="pBreadcrumbItem"><a href="purchaseConfirm.php">購入確認</a></li>
      <li class="pBreadcrumbItem"><a href="addressRegister.php">お届け先入力</a></li>
      <li class="pBreadcrumbItem"><a href="addressConfirm.php">お届け先確認</a></li>
      <li class="pBreadcrumbItem" aria-current="page">登録完了</li>
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
  <div class="pRegisterContainer mAddressDoneContainer">
    <h1 class="cPageTitle">お届け先登録完了</h1>

    <div class="pDoneMessageBox">
      <p class="pDoneMessageText">
        お届け先の登録が完了しました。<br>
        続けて購入確認ページへお進みください。
      </p>
    </div>

    <div class="pDoneLinkArea">
      <a href="purchaseConfirm.php" class="pDoneLink">購入確認ページへすすむ</a>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>