<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. 直接アクセス（POST以外のアクセス）があった場合はログイン画面に戻す
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// 2. 【修正】共通のデータベース接続ファイルを読み込みます
require_once 'includes/dbConnect.php';

// 3. POSTデータの受け取り
$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// 4. バリデーション（空チェック）
if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'メールアドレスとパスワードを入力してください。';
    header('Location: login.php');
    exit;
}

try {
    // 【修正】テーブル名を「customers」に、カラム名を「mail」に変更
    $sql = 'SELECT * FROM customers WHERE mail = :mail';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':mail' => $email]);
    $user = $stmt->fetch();

    // 【修正】取得したデータ（$user）から正しくパスワードを検証
    if ($user && password_verify($password, $user['password'])) {
        
        // セッション固定攻撃対策：セッションIDを新しく生成する
        session_regenerate_id(true);

        // ログイン成功：セッションにユーザー情報を保存
        $_SESSION['userId'] = $user['id'];
        $_SESSION['userName'] = $user['name'];
        
        // ログイン完了フラグ（直リンクアクセス制限用）
        $_SESSION['login_completed'] = true;
        
        // ログイン完了画面へ遷移
        header('Location: loginDone.php');
        exit;
        
    } else {
        // ログイン失敗（ユーザーがいない、またはパスワード間違い）
        $_SESSION['login_error'] = 'メールアドレスまたはパスワードが間違っています。';
        header('Location: login.php');
        exit;
    }

} catch (PDOException $e) {
    // デバッグ用：一時的にエラー内容を確認したい場合は以下のコメントアウトを解除してください
    // exit('エラー詳細: ' . $e->getMessage());

    $_SESSION['login_error'] = 'システムエラーが発生しました。';
    header('Location: login.php');
    exit;
}