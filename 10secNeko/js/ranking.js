/* ===========================================
   ranking.js
   ランキングのデータを出し入れする係。

   PHP の API（api/ranking.php）とやりとりする。
   ui.js からの呼ばれ方は仮実装のときとまったく同じで、
   fetchTop() と submit() が Promise を返す。
   =========================================== */

const Ranking = {

  // APIの場所。index.html から見た相対パス
  endpoint: 'api/ranking.php',

  /*
    上位を取ってくる。
    戻り値は [{ name, score }, ...]
  */
  /*
    fetch を呼んで、通信そのものが失敗したときだけ
    日本語のメッセージに置き換える。

    サーバーに届かなかった場合、fetch は TypeError を投げる。
    そのまま画面に出すと「Failed to fetch」と英語で表示されてしまう。
  */
  async request(options) {
    try {
      return await fetch(this.endpoint, options);
    } catch (err) {
      throw new Error('サーバーに つながりませんでした');
    }
  },

  async fetchTop() {
    const res = await this.request({
      method: 'GET',
      headers: { 'Accept': 'application/json' }
    });

    /*
      fetch は「サーバーに届いたけどエラーが返ってきた」場合、
      例外を投げずに res.ok === false になる。
      ここで自分で確認しないと、エラーを成功として扱ってしまう。
      これは fetch で一番はまりやすいところ。
    */
    if (!res.ok) {
      throw new Error(`取得に失敗しました (HTTP ${res.status})`);
    }

    const data = await res.json();

    if (!data.ok) {
      throw new Error(data.error || '取得に失敗しました');
    }

    return data.ranking;
  },

  /*
    スコアを登録して、更新後の上位を受け取る。

    playTime はサーバー側の不正チェックに使うだけで、保存はされない。
  */
  async submit(name, score, playTime) {
    const res = await this.request({
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, score, playTime })
    });

    if (!res.ok) {
      // サーバーが理由を返していれば、それを拾って表示に使う
      let message = `登録に失敗しました (HTTP ${res.status})`;
      try {
        const errData = await res.json();
        if (errData.error) message = errData.error;
      } catch (e) {
        // JSON でない応答（PHPのエラー画面など）のときはここに来る
      }
      throw new Error(message);
    }

    const data = await res.json();

    if (!data.ok) {
      throw new Error(data.error || '登録に失敗しました');
    }

    return data.ranking;
  }
};
