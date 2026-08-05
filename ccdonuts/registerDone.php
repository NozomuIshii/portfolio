<?php
// ==========================================
// 1. セッションの開始とガード節
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// POSTアクセス以外（直接URLを入力された場合など）は入力画面へ戻す
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// ==========================================
// 2. データベース接続ファイルの読み込み
// ==========================================
require_once 'includes/dbConnect.php';

// ==========================================
// 3. POSTデータの受け取り
// ==========================================
$name     = isset($_POST['name']) ? trim($_POST['name']) : '';
$kana     = isset($_POST['kana']) ? trim($_POST['kana']) : '';
$zip1     = isset($_POST['zip1']) ? trim($_POST['zip1']) : '';
$zip2     = isset($_POST['zip2']) ? trim($_POST['zip2']) : '';
$address  = isset($_POST['address']) ? trim($_POST['address']) : '';
$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// 【修正】郵便番号は結合せず、そのまま使用するので $zipcode の行は削除（またはコメントアウト）します
// $zipcode = $zip1 . '-' . $zip2;

// パスワードのハッシュ化
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// ==========================================
// 4. データベースへの挿入処理（INSERT）
// ==========================================
try {
    // 【修正】カラム名をデータベースの設計（furigana, postcode_a, postcode_b）に完全に合わせます
    $sql = 'INSERT INTO customers (name, furigana, postcode_a, postcode_b, address, mail, password) 
            VALUES (:name, :furigana, :postcode_a, :postcode_b, :address, :mail, :password)';
    
    $stmt = $pdo->prepare($sql);
    
    // 【修正】プレースホルダと変数を正しくマッピングして実行します
    $stmt->execute([
        ':name'       => $name,
        ':furigana'   => $kana,      // DBの furigana カラムに、画面からの $kana を入れる
        ':postcode_a' => $zip1,      // DBの postcode_a に 3桁の郵便番号を入れる
        ':postcode_b' => $zip2,      // DBの postcode_b に 4桁の郵便番号を入れる
        ':address'    => $address,
        ':mail'       => $email,
        ':password'   => $hashed_password
    ]);

    // ==========================================
    // 5. 登録成功時の処理
    // ==========================================
    $_SESSION['register_completed'] = true;
    $_SESSION['registered_name'] = $name;

    header('Location: registerSuccess.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['register_error'] = '登録処理中にエラーが発生しました。時間をおいてやり直してください。';
    header('Location: register.php');
    exit;
}