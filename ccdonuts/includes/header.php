<?php
// すでに他でsession_start()が呼ばれていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <!-- 検索エンジンにインデックスさせない設定 -->
    <meta name="robots" content="noindex">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C.C.Donuts</title>

    <!-- ファビコン（追加） -->
    <link rel="icon" href="images/commonLogo.png" type="image/png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&display=swap" rel="stylesheet">
    <!-- リセットCSS -->
    <link rel="stylesheet" href="common/reset.css">
    <!-- 自作のCSS -->
    <link rel="stylesheet" href="styles/style.css">
    
    <!-- ログアウト確認用のJavaScript -->
    <script>
    function confirmLogout(event) {
        // ポップアップウィンドウを表示して「いいえ」を押した場合は遷移をキャンセルする
        if (!confirm('ログアウトしますか？')) {
            event.preventDefault();
        }
    }
    </script>
</head>
<body>
<!-- ヘッダーエリア -->
<header class="pHeader">
    <!-- ヘッダー上部 -->
     <div class="pHeaderMain">
        <div class="lContainer pHeaderMainInner">
            <!-- ハンバーガーメニューボタン -->
             <button class="pHeaderMenuBtn" id="drawerTrigger" aria-label="メニューを開く">
                <span></span>
                <span></span>
                <span></span>
             </button>
            <!-- ショップロゴ -->
             <div class="pHeaderLogo">
                <a href="index.php">
                    <img src="images/commonLogo.png" alt="CCドーナッツロゴ">
                </a>
             </div>
            <!-- ユーザーナビ（お気に入り、ログイン、カート） -->
             <nav class="pHeaderNav">
                <ul class="pHeaderNavList">
                    <!-- 【追加】お気に入りアイコン -->
                    <li class="pHeaderNavItem">
                        <a href="productFavo.php">
                            <img src="images/commonFavoIcon.png" alt="" class="pHeaderNavIcon">
                            <span>お気に入り</span>
                        </a>
                    </li>
                    <li class="pHeaderNavItem">
                        <?php if (!empty($_SESSION['userId'])): ?>
                            <!-- 【ログイン中】ログアウトアイコンを表示（クリック時にJSを実行） -->
                            <a href="logoutProcess.php" onclick="confirmLogout(event)">
                                <img src="images/commonLogoutIcon.png" alt="" class="pHeaderNavIcon">
                                <span>ログアウト</span>
                            </a>
                        <?php else: ?>
                            <!-- 【未ログイン】通常のログインアイコンを表示 -->
                            <a href="login.php">
                                <img src="images/commonLoginIcon.png" alt="" class="pHeaderNavIcon">
                                <span>ログイン</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    <li class="pHeaderNavItem">
                        <a href="cart.php">
                            <img src="images/commonCartIcon.png" alt="" class="pHeaderNavIcon">
                            <span>カート</span>
                        </a>
                    </li>
                </ul>
             </nav>
        </div>
     </div>
     <!-- 検索窓エリア -->
      <div class="pHeaderSearch">
        <div class="lContainer pHeaderSearchInner">
            <!-- action の送信先を productSearch.php に変更 -->
            <form action="productSearch.php" method="get" class="pHeaderSearchForm">
                <button type="submit" class="pHeaderSearchBtn" aria-label="検索"></button>
                <input type="search" name="keyword" class="pHeaderSearchInput" placeholder="キーワードを入力">
            </form>
        </div>
      </div>
</header>
<main class="lMain">