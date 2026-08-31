/* ===========================================
   ui.js
   HTML側（DOM）の担当。

   canvas の中の描画は Game、
   画面の外側のボタンや入力欄は GameUi、と役割を分けている。
   こうしておくと、あとでUIを作り替えてもゲーム本体を触らずに済む。
   =========================================== */

// 名前を入れなかったときの既定値
const DEFAULT_PLAYER_NAME = '野良ねこ';

// 名前を覚えておくためのキー。次に遊ぶとき入力し直さなくて済む
const NAME_STORAGE_KEY = 'tenSecNekoPlayerName';


class GameUi {

  /*
    callbacks には { onRetry } を渡す。
    UI 側は「リトライが押された」と伝えるだけで、
    実際にゲームを作り直すのは Game の仕事。
  */
  constructor(callbacks) {
    this.callbacks = callbacks;

    this.startOverlay    = document.getElementById('startOverlay');
    this.gameOverOverlay = document.getElementById('gameOverOverlay');

    // ゲームオーバー画面の中身は2つあり、切り替えて使う
    this.submitView      = document.getElementById('submitView');   // スコアと名前の入力
    this.rankingView     = document.getElementById('rankingView');  // 登録後のランキング

    this.finalScore      = document.getElementById('finalScore');
    this.nameInput       = document.getElementById('playerName');
    this.submitButton    = document.getElementById('submitButton');
    this.submitStatus    = document.getElementById('submitStatus');
    this.rankingBody     = document.getElementById('rankingBody');

    // 「もういちど」は2つの表示それぞれにあるので、まとめて取得する
    this.retryButtons    = document.querySelectorAll('[data-retry]');

    // 自分の行に印をつけるために覚えておく
    this.lastEntry = null;

    // 今回のスコアとプレイ時間。登録ボタンを押したときに使う
    this.currentScore = 0;
    this.currentPlayTime = 0;

    this.restoreName();
    this.bindEvents();
  }

  bindEvents() {
    this.submitButton.addEventListener('click', () => this.handleSubmit());

    this.retryButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        this.hideGameOver();
        this.callbacks.onRetry();
      });
    });

    /*
      入力欄でEnterを押したら登録。
      キーイベントが window まで上がるとジャンプ扱いされるので、
      stopPropagation() でここで止める。
    */
    this.nameInput.addEventListener('keydown', (e) => {
      e.stopPropagation();
      if (e.key === 'Enter') this.handleSubmit();
    });
  }

  // 前回入力した名前を復元する。無ければ既定値のまま
  restoreName() {
    try {
      const saved = localStorage.getItem(NAME_STORAGE_KEY);
      if (saved) this.nameInput.value = saved;
    } catch (err) {
      // プライベートモードなどで使えないことがある。使えなくても困らない
    }
  }

  /*
    入力された名前を整えて返す。
    空欄や空白だけのときは「野良ねこ」にする。
  */
  getPlayerName() {
    const raw = this.nameInput.value.trim();
    return raw === '' ? DEFAULT_PLAYER_NAME : raw;
  }

  hideStart() {
    this.startOverlay.classList.add('hidden');
  }

  showGameOver(score, playTime) {
    this.currentScore = score;
    this.currentPlayTime = playTime;
    this.finalScore.textContent = score;

    // 前回の状態が残らないように毎回リセットする
    this.submitButton.disabled = false;
    this.submitButton.textContent = 'ランキングに のこす';
    this.submitStatus.textContent = '';

    // 必ず「入力」の表示から始める
    this.submitView.classList.remove('hidden');
    this.rankingView.classList.add('hidden');

    this.gameOverOverlay.classList.remove('hidden');
  }

  hideGameOver() {
    this.gameOverOverlay.classList.add('hidden');
  }

  /*
    スコアを送る。
    通信は失敗しうるので、押した直後にボタンを止めて二重送信を防ぐ。
  */
  async handleSubmit() {
    if (this.submitButton.disabled) return;

    const name = this.getPlayerName();

    this.submitButton.disabled = true;
    this.submitButton.textContent = 'おくっています...';
    this.submitStatus.textContent = '';

    try {
      localStorage.setItem(NAME_STORAGE_KEY, name);
    } catch (err) {
      // 保存できなくても登録自体は続ける
    }

    try {
      const list = await Ranking.submit(name, this.currentScore, this.currentPlayTime);

      // 今回の記録。ランキング表で自分の行に印をつけるのに使う
      this.lastEntry = { name, score: this.currentScore };

      this.renderRanking(list);

      // 入力からランキングへ表示を切り替える
      this.submitView.classList.add('hidden');
      this.rankingView.classList.remove('hidden');

    } catch (err) {
      console.error(err);

      // 失敗したらボタンを戻して、もう一度押せるようにする
      this.submitButton.disabled = false;
      this.submitButton.textContent = 'もういちど おくる';

      // サーバーが理由を返していれば、それをそのまま見せる
      this.submitStatus.textContent = err.message || 'そうしんに しっぱいしました';
    }
  }

  /*
    ランキング表を描き直す。

    textContent で入れているのが大事なところ。
    innerHTML に名前を直接入れると、名前にHTMLタグを仕込まれたときに
    そのまま実行されてしまう（XSS）。textContent なら文字として扱われる。
  */
  renderRanking(list) {
    this.rankingBody.innerHTML = '';

    if (!list || list.length === 0) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = 3;
      td.className = 'rankingEmpty';
      td.textContent = 'まだ きろくが ありません';
      tr.appendChild(td);
      this.rankingBody.appendChild(tr);
      return;
    }

    // 同じ名前・同じスコアの行を1つだけ光らせるためのフラグ
    let markedMine = false;

    list.forEach((entry, index) => {
      const tr = document.createElement('tr');

      if (!markedMine && this.lastEntry &&
          entry.name === this.lastEntry.name &&
          entry.score === this.lastEntry.score) {
        tr.className = 'isMine';
        markedMine = true;
      }

      const rankTd = document.createElement('td');
      rankTd.textContent = index + 1;

      const nameTd = document.createElement('td');
      nameTd.textContent = entry.name;

      const scoreTd = document.createElement('td');
      scoreTd.textContent = entry.score;

      tr.append(rankTd, nameTd, scoreTd);
      this.rankingBody.appendChild(tr);
    });
  }
}
