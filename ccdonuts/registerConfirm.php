<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 1. ガード節：POSTアクセス以外は入力画面へ戻す
// ==========================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// ==========================================
// 2. データベース接続情報（メールアドレス重複チェック用）
// ==========================================
require_once 'includes/dbConnect.php';

// ==========================================
// 3. POSTデータの受け取り（HTML出力用にサニタイズする前の生データ）
// ==========================================
$name             = isset($_POST['name']) ? trim($_POST['name']) : '';
$kana             = isset($_POST['kana']) ? trim($_POST['kana']) : '';
$zip1             = isset($_POST['zip1']) ? trim($_POST['zip1']) : '';
$zip2             = isset($_POST['zip2']) ? trim($_POST['zip2']) : '';
$address          = isset($_POST['address']) ? trim($_POST['address']) : '';
$email            = isset($_POST['email']) ? trim($_POST['email']) : '';
$email_confirm    = isset($_POST['email_confirm']) ? trim($_POST['email_confirm']) : '';
$password         = isset($_POST['password']) ? $_POST['password'] : '';
$password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

// ==========================================
// 4. バリデーション（入力チェック）
// ==========================================
$error_msg = '';

// ① 必須項目の空チェック
if ($name === '' || $kana === '' || $zip1 === '' || $zip2 === '' || $address === '' || $email === '' || $password === '') {
    $error_msg = '必須項目が入力されていません。';
}
// ★ フリガナの全角カタカナチェック
elseif (!preg_match('/^[ァ-ヶー]+$/u', $kana)) {
    $error_msg = 'フリガナは全角カタカナのみで入力してください。';
}
// ② メールアドレスの形式チェック
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_msg = '正しいメールアドレスの形式で入力してください。';
}
// ③ メールアドレスの一致チェック
elseif ($email !== $email_confirm) {
    $error_msg = 'メールアドレスと確認用メールアドレスが一致しません。';
}
// ④ パスワードの文字数・文字種チェック（半角英数字8〜20文字）
elseif (!preg_match('/^[a-zA-Z0-9]{8,20}$/', $password)) {
    $error_msg = 'パスワードは半角英数字8文字以上20文字以内で入力してください（記号不可）。';
}
// ⑤ パスワードの一致チェック
elseif ($password !== $password_confirm) {
    $error_msg = 'パスワードと確認用パスワードが一致しません。';
}
// ⑥ 郵便番号の桁数チェック
elseif (!preg_match('/^[0-9]{3}$/', $zip1) || !preg_match('/^[0-9]{4}$/', $zip2)) {
    $error_msg = '郵便番号は前半3桁、後半4桁の半角数字で入力してください。';
}
// ⑦ メールアドレスの重複チェック（データベース照会）
else {
    try {
        $sql = 'SELECT COUNT(*) FROM customers WHERE mail = :mail'; 
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':mail' => $email]); 
        
        if ($stmt->fetchColumn() > 0) {
            $error_msg = 'このメールアドレスは既に登録されています。';
        }
    } catch (PDOException $e) {
        $error_msg = 'システムエラーが発生しました。時間をおいてやり直してください。';
    }
}

// エラーが1つでもあれば入力画面（register.php）にリダイレクト
if ($error_msg !== '') {
    $_SESSION['register_error'] = $error_msg;
    header('Location: register.php');
    exit;
}

// ==========================================
// 5. 表示用のサニタイズ（XSS対策）と伏字処理
// ==========================================
$h_name    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$h_kana    = htmlspecialchars($kana, ENT_QUOTES, 'UTF-8');
$h_zip1    = htmlspecialchars($zip1, ENT_QUOTES, 'UTF-8');
$h_zip2    = htmlspecialchars($zip2, ENT_QUOTES, 'UTF-8');
$h_address = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
$h_email   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

// 安全に文字数をカウントしてマスク処理
$maskedPassword = str_repeat('★', mb_strlen($password, 'UTF-8'));

include 'includes/header.php'; 
?>

<!-- 1. パンくずリスト -->
<nav class="pBreadcrumb" aria-label="パンくず">
  <div class="lContainer">
    <ol class="pBreadcrumbList">
      <li class="pBreadcrumbItem"><a href="index.php">TOP</a></li>
      <li class="pBreadcrumbItem"><a href="login.php">ログイン</a></li>
      <li class="pBreadcrumbItem"><a href="register.php">会員登録</a></li>
      <li class="pBreadcrumbItem" aria-current="page">入力情報確認</li>
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
    <h1 class="cPageTitle">入力情報確認</h1>

    <!-- 最終的に完了画面（registerDone.php）へデータを送信するフォーム -->
    <form action="registerDone.php" method="post" class="pForm">
      
      <!-- 次の画面へ値を引き継ぐための隠しフィールド(hidden) -->
      <input type="hidden" name="name" value="<?php echo $h_name; ?>">
      <input type="hidden" name="kana" value="<?php echo $h_kana; ?>">
      <input type="hidden" name="zip1" value="<?php echo $h_zip1; ?>">
      <input type="hidden" name="zip2" value="<?php echo $h_zip2; ?>">
      <input type="hidden" name="address" value="<?php echo $h_address; ?>">
      <input type="hidden" name="email" value="<?php echo $h_email; ?>">
      <input type="hidden" name="password" value="<?php echo htmlspecialchars($password, ENT_QUOTES, 'UTF-8'); ?>">

      <!-- お名前 -->
      <div class="pFormGroup mConfirmGroup">
        <span class="pFormLabel">お名前</span>
        <div class="pFormConfirmValue">
          <?php echo $h_name; ?>
        </div>
      </div>

      <!-- お名前（フリガナ） -->
      <div class="pFormGroup mConfirmGroup">
        <span class="pFormLabel">お名前（フリガナ）</span>
        <div class="pFormConfirmValue">
          <?php echo $h_kana; ?>
        </div>
      </div>

      <!-- 郵便番号 -->
      <div class="pFormGroup mConfirmGroup">
        <span class="pFormLabel">郵便番号</span>
        <div class="pFormConfirmValue">
          〒<?php echo $h_zip1; ?> - <?php echo $h_zip2; ?>
        </div>
      </div>

      <!-- 住所 -->
      <div class="pFormGroup mConfirmGroup">
        <span class="pFormLabel">住所</span>
        <div class="pFormConfirmValue">
          <?php echo $h_address; ?>
        </div>
      </div>

      <!-- メールアドレス -->
      <div class="pFormGroup mConfirmGroup">
        <span class="pFormLabel">メールアドレス</span>
        <div class="pFormConfirmValue">
          <?php echo $h_email; ?>
        </div>
      </div>

      <!-- パスワード -->
      <div class="pFormGroup mConfirmGroup">
        <span class="pFormLabel">パスワード</span>
        <div class="pFormConfirmValue">
          <?php echo $maskedPassword; ?>
        </div>
      </div>

      <!-- 送信ボタン -->
      <div class="pFormSubmit">
        <button type="submit" class="cBtnPrimary">登録する</button>
      </div>

    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>