<?php
/* ===========================================
   ranking.php
   ランキングの取得と登録を担当する API。

     GET  ranking.php        … 上位10件を返す
     POST ranking.php        … スコアを登録して、更新後の上位10件を返す

   やりとりはすべて JSON。
   =========================================== */

declare(strict_types=1);

// ブラウザに「これは JSON だ」と伝える
header('Content-Type: application/json; charset=utf-8');

// このAPIの応答をキャッシュされると、ランキングが更新されなくなる
header('Cache-Control: no-store');

$config = require __DIR__ . '/config.php';


/* -------------------------------------------
   共通の処理
   ------------------------------------------- */

/**
 * JSON を出力して終了する
 */
function respond(array $data, int $status = 200): void
{
    http_response_code($status);

    // JSON_UNESCAPED_UNICODE を付けると日本語が \u3042 形式にならず読みやすい
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * エラーを返して終了する
 *
 * 例外の中身をそのまま画面に出さないのが大事。
 * 接続情報やテーブル構造が漏れる手がかりになる。
 */
function fail(string $message, int $status = 400): void
{
    respond(['ok' => false, 'error' => $message], $status);
}

/**
 * 上位を取得する
 */
function fetchTop(PDO $pdo, int $limit): array
{
    /*
      LIMIT にはプレースホルダを使いにくい（文字列として渡ると構文エラーになる）ので、
      あらかじめ整数に確定させた値を埋め込む。
      $limit は config.php から来る固定値なので、外部入力ではない。
    */
    $limit = max(1, min(100, $limit));

    $sql = "SELECT `name`, `score`
              FROM `rankings`
          ORDER BY `score` DESC, `id` ASC
             LIMIT {$limit}";

    $stmt = $pdo->query($sql);

    // score は文字列で返ってくるので、数値に直しておく
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[] = [
            'name'  => $row['name'],
            'score' => (int) $row['score'],
        ];
    }

    return $rows;
}


/* -------------------------------------------
   データベースに接続する
   ------------------------------------------- */
try {
    $pdo = new PDO(
        $config['dsn'],
        $config['user'],
        $config['pass'],
        [
            // エラーを例外として投げる。黙って失敗するのを防ぐ
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // 連想配列で受け取る
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            /*
              ★SQLインジェクション対策の要★
              false にすると、PHP側で文字列を組み立てる「エミュレーション」をやめ、
              MySQL 本体のプリペアドステートメントを使う。
              値がSQL文として解釈される余地がなくなる。
            */
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // 本番では詳細を出さない。原因はサーバーのログで追う
    error_log('DB connect failed: ' . $e->getMessage());
    fail('データベースに接続できませんでした', 500);
}


/* -------------------------------------------
   GET … 上位を返すだけ
   ------------------------------------------- */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    try {
        respond(['ok' => true, 'ranking' => fetchTop($pdo, $config['limit'])]);
    } catch (PDOException $e) {
        error_log('DB select failed: ' . $e->getMessage());
        fail('ランキングを取得できませんでした', 500);
    }
}


/* -------------------------------------------
   POST … スコアを登録する
   ------------------------------------------- */
if ($method !== 'POST') {
    fail('許可されていないメソッドです', 405);
}

// JSON の本文を読む。フォーム送信ではないので $_POST には入らない
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input)) {
    fail('データの形式が正しくありません');
}


/* --- 名前の検証 ---------------------------- */
$name = isset($input['name']) && is_string($input['name']) ? $input['name'] : '';

/*
  壊れた UTF-8 が来ると preg_replace が null を返し、
  そのあとの trim() が PHP 8.1 以降で警告を出す。
  先に妥当性を確かめておく。
*/
if (!mb_check_encoding($name, 'UTF-8')) {
    $name = '';
}

// 制御文字（改行やタブ、見えない文字）を落とす
$name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
$name = trim($name);

// 10文字に切り詰める。mb_ 付きを使わないと日本語が途中で壊れる
$name = mb_substr($name, 0, 10, 'UTF-8');

if ($name === '') {
    $name = '野良ねこ';
}


/* --- スコアの検証 -------------------------- */
if (!isset($input['score']) || !is_numeric($input['score'])) {
    fail('スコアが正しくありません');
}

$score = (int) $input['score'];

if ($score < 0 || $score > 100000) {
    fail('スコアが正しくありません');
}


/* --- プレイ時間との整合性チェック ------------
   簡易的な不正対策。

   ブラウザから送られてくる値なので、その気になれば偽装できる。
   完全には防げないが、「開発者ツールでスコアだけ書き換える」程度は弾ける。

   このゲームで稼げる上限は、無敵中の10倍を考慮しても
   おおむね1秒あたり20点。余裕をみて 20倍 + 50 を上限とする。

   なお、プレイ時間は検証に使うだけで保存しない。
   仕様どおり、DBに入るのは名前とスコアだけ。
-------------------------------------------- */
$playTime = isset($input['playTime']) && is_numeric($input['playTime'])
    ? (float) $input['playTime']
    : 0.0;

if ($playTime < 0 || $playTime > 3600) {
    fail('プレイ時間が正しくありません');
}

if ($score > $playTime * 20 + 50) {
    fail('スコアが正しくありません');
}


/* --- 登録する ------------------------------ */
try {
    /*
      プレースホルダ（:name / :score）を使って値を渡す。
      文字列連結でSQLを組み立てないこと。これがSQLインジェクション対策の基本。
    */
    $stmt = $pdo->prepare(
        'INSERT INTO `rankings` (`name`, `score`) VALUES (:name, :score)'
    );

    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':score', $score, PDO::PARAM_INT);
    $stmt->execute();

    respond([
        'ok'      => true,
        'ranking' => fetchTop($pdo, $config['limit']),
    ]);

} catch (PDOException $e) {
    error_log('DB insert failed: ' . $e->getMessage());
    fail('スコアを登録できませんでした', 500);
}
