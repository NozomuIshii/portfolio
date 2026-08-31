/* ===========================================
   main.js
   ゲーム全体の司令塔。
   初期化 → ゲームループ → 各パーツの update / draw を呼ぶ。
   =========================================== */

// --- 設定値。数字はここに集めておくと後で調整しやすい ---
const CONFIG = {
  canvasWidth: 600,
  canvasHeight: 150,

  groundY: 128,        // 地面の表面のY座標

  baseSpeed: 300,      // 開始時のスクロール速度（1秒あたりのピクセル数）
  maxSpeed: 700,       // 上限
  speedUpRate: 10,      // 1秒ごとに速度がいくつ増えるか

  /*
    --- ジャンプの調整値 ---
    単位はすべて「1秒あたり」。物理の式で高さを逆算できる。

      最高到達点 = jumpVelocity の2乗 ÷ (2 × gravity)

    今の値だと約 60px。canvas の高さが 150px、地面が 128px なので、
    猫の頭が y=28 あたりまで上がる。ちょうど画面に収まる高さ。
  */
  gravity: 2000,           // 重力加速度。大きいほどキビキビする
  jumpVelocity: -490,      // 踏み切りの初速。マイナス＝上向き。約60px上がる
  jumpCutVelocity: -330,   // ボタンを離したときの速度。約28px上がる

  debug: false,            // true にするとジャンプの高さの目安線と当たり判定を表示する（調整用）

  /*
    --- 体力とスコア ---
    体力の単位は「秒」。10秒から始まり、実時間で減っていく。
  */
  maxHp: 10,               // 体力の上限。餌で回復してもここで頭打ち
  hitDisplayTime: 0.4,     // 被弾絵（バツ目）を出しておく秒数

  /*
    --- 無敵モード（キャンディー）---
    無敵時間は体力とは別のカウンタで持つ。
    体力の最大値と同じ10秒だが、意味がまったく違うので混ぜてはいけない。
  */
  invincibleDuration: 10,   // 無敵が続く秒数
  invincibleBlinkStart: 3,  // 残りこの秒数を切ったら猫が点滅する
  invincibleMultiplier: 10, // 無敵中のスコア倍率

  /*
    --- 障害物の出現間隔 ---
    「秒」ではなく「距離(px)」で管理する。
    秒で管理すると速度が上がったときに間隔が広がってしまい、逆に簡単になる。

    minGap は速度に比例させて、反応する時間を必ず確保する。
    speed × 0.75 = 「0.75秒ぶんの距離」という意味。
  */
  minGapBase: 230,         // 最低でもこれだけは離す
  minGapPerSpeed: 0.75,    // 速度に応じて足す係数（秒）
  gapRandomRange: 280,     // 上記に 0〜この値をランダムに足す

  /*
    --- アイテムの出現間隔 ---
    障害物より短めにして、回復のチャンスを多めにしてある。
    体力が毎秒1減るので、ここが渋いとゲームが成立しない。
  */
  itemGapBase: 150,
  itemGapPerSpeed: 0.5,
  itemGapRandomRange: 200,

  // 障害物とアイテムをこれ以上近づけない距離。
  // 近すぎると「避けながら取る」が物理的に不可能になる
  spawnClearance: 80
};


// --- ゲームの状態。文字列で管理すると読みやすい ---
const STATE = {
  READY: 'ready',      // スタート待ち
  PLAYING: 'playing',  // プレイ中
  GAMEOVER: 'gameover' // フェーズ8で使う
};


class Game {

  constructor(canvas) {
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');

    // ピクセル画像を拡大するときにブラウザがぼかすのを止める（CSS側とセットで効く）
    this.ctx.imageSmoothingEnabled = false;

    this.state = STATE.READY;

    // 部品を用意する
    this.background = new Background(CONFIG.canvasWidth);
    this.ground = new Ground(CONFIG.canvasWidth, CONFIG.groundY);
    this.cat = new Cat(CONFIG.groundY);

    this.speed = CONFIG.baseSpeed;

    // 画面に出ている障害物を入れておく配列
    this.obstacles = [];

    // 次の障害物までの残り距離(px)。0を切ったら1体出す
    this.spawnCountdown = 400;

    // アイテムも同じ仕組みで別に管理する
    this.items = [];
    this.itemSpawnCountdown = 250;

    // 「+1」などの浮かぶ文字
    this.popups = [];

    // 無敵の残り秒数。0より大きい間が無敵
    this.invincibleTimer = 0;

    // 無敵中に流れる★
    this.shootingStars = new ShootingStars(CONFIG.canvasWidth, CONFIG.canvasHeight);

    // 体力（秒）とスコア
    this.hp = CONFIG.maxHp;
    this.score = 0;
    this.scoreTimer = 0;   // 1秒たまるごとにスコアを加算するためのカウンタ

    // 経過時間。速度を上げるのに使う
    this.elapsed = 0;

    // 前フレームの時刻。差分（デルタタイム）を出すのに使う
    this.lastTime = 0;

    /*
      DOM側は GameUi に任せる。
      「リトライが押された」ときだけ、こちらに戻してもらう。
    */
    this.ui = new GameUi({
      onRetry: () => {
        this.reset();
        this.start();
      }
    });

    this.setupInput();
  }

  /*
    入力のセットアップ。
    キーボードとタッチの両方を同じ処理につなぐ。
  */
  setupInput() {
    // --- 押した瞬間 ---
    window.addEventListener('keydown', (e) => {
      /*
        名前を入力しているときは、スペースキーをジャンプに使わない。
        これを入れないと、名前に空白を入れようとしただけで
        ゲームが再開してしまう。
      */
      if (this.isTyping(e)) return;

      if (e.code !== 'Space' && e.code !== 'ArrowUp') return;

      e.preventDefault();   // スペースでページがスクロールするのを止める

      // 押しっぱなしにするとキーが連射される。それを無視する
      if (e.repeat) return;

      this.onPressStart();
    });

    // --- 離した瞬間 ---
    window.addEventListener('keyup', (e) => {
      if (this.isTyping(e)) return;
      if (e.code !== 'Space' && e.code !== 'ArrowUp') return;
      this.onPressEnd();
    });

    /*
      タップは canvas ではなく、その親（gameWrapper）で受ける。

      canvas に付けると、上に重なっているスタート画面のオーバーレイが
      タップを受け止めてしまい、canvas まで届かない。
      親で受ければ、canvas でもオーバーレイでも同じように反応する。

      click ではなく pointerdown を使うのは、反応が速いため。
    */
    const surface = this.canvas.parentElement;

    surface.addEventListener('pointerdown', (e) => {
      // ボタンや入力欄の操作は邪魔しない
      if (this.isTyping(e)) return;

      // 画面のスクロールや長押しメニューを止める
      e.preventDefault();
      this.onPressStart();
    });

    /*
      指を離す系のイベントは3種類そろえておく。
      pointerup     … 普通に離した
      pointercancel … 着信などでブラウザに割り込まれた
      pointerleave  … canvas の外に指が出た
      拾い漏らすと「離していないのに上昇し続ける」バグになる。
    */
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(type => {
      surface.addEventListener(type, (e) => {
        if (this.isTyping(e)) return;
        this.onPressEnd();
      });
    });
  }

  /*
    入力欄やボタンを操作している最中かどうか。
    そのときはゲームの操作として扱わない。
  */
  isTyping(e) {
    const tag = e.target && e.target.tagName;
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'BUTTON';
  }

  onPressStart() {
    if (this.state === STATE.READY) {
      /*
        Chrome Dino と同じで、開始の入力がそのまま最初のジャンプになる。
        押し始めた勢いを無駄にしないので、操作の感触がなめらかになる。
      */
      this.start();
      this.cat.jump();
      return;
    }

    /*
      ゲームオーバー中は何もしない。
      再開は「もういちど」ボタンからのみ。
      画面のどこを押しても再開してしまうと、
      名前を入力しようとしただけでやり直しになってしまう。
    */
    if (this.state === STATE.GAMEOVER) return;

    if (this.state === STATE.PLAYING) {
      this.cat.jump();
    }
  }

  onPressEnd() {
    if (this.state === STATE.PLAYING) {
      this.cat.cutJump();
    }
  }

  start() {
    this.state = STATE.PLAYING;
    this.cat.state = 'run';
    this.ui.hideStart();
  }

  /*
    最初の状態に戻す。リトライで呼ぶ。
    「増やした変数をここに書き足し忘れる」のがよくあるバグなので、
    フィールドを足したら必ずここも見直すこと。
  */
  reset() {
    this.obstacles = [];
    this.items = [];
    this.popups = [];
    this.spawnCountdown = 400;
    this.itemSpawnCountdown = 250;
    this.hp = CONFIG.maxHp;
    this.score = 0;
    this.scoreTimer = 0;
    this.elapsed = 0;
    this.speed = CONFIG.baseSpeed;
    this.invincibleTimer = 0;
    this.shootingStars.clear();
    this.cat = new Cat(CONFIG.groundY);
  }

  // 今このフレームで無敵かどうか。あちこちで使うのでまとめておく
  get isInvincible() {
    return this.invincibleTimer > 0;
  }

  /*
    ゲームループ本体。
    requestAnimationFrame が「次の描画タイミング」で呼び戻してくれる。
    timestamp はページを開いてからの経過ミリ秒。
  */
  loop(timestamp) {
    // 初回だけ lastTime を合わせる。これをしないと1フレーム目で巨大な dt が出る
    if (!this.lastTime) this.lastTime = timestamp;

    /*
      dt = 前フレームからの経過秒数。
      これを掛け算に使うと、60fps でも 120fps でもゲーム速度が同じになる。

      Math.min で上限をかけているのは、タブを裏に回して戻ってきたときに
      dt が数秒になり、猫が障害物を貫通する事故を防ぐため。
    */
    const dt = Math.min((timestamp - this.lastTime) / 1000, 0.05);
    this.lastTime = timestamp;

    this.update(dt);
    this.draw();

    requestAnimationFrame((t) => this.loop(t));
  }

  update(dt) {
    if (this.state !== STATE.PLAYING) {
      this.cat.update(dt);
      return;
    }

    this.elapsed += dt;

    // だんだん速くする。上限を超えないように Math.min で止める
    this.speed = Math.min(
      CONFIG.baseSpeed + this.elapsed * CONFIG.speedUpRate,
      CONFIG.maxSpeed
    );

    this.background.update(this.speed, dt);
    this.ground.update(this.speed, dt);
    this.cat.update(dt);

    // --- 無敵時間を減らす ---
    if (this.invincibleTimer > 0) {
      this.invincibleTimer = Math.max(0, this.invincibleTimer - dt);
    }

    // 猫には「残り秒数」だけ渡す。見た目の判断は猫側の仕事
    this.cat.invincibleRemaining = this.invincibleTimer;

    // 流れ星のオン／オフ
    this.shootingStars.setActive(this.isInvincible);
    this.shootingStars.update(dt);

    // --- 体力を減らす ---
    // 無敵中は減らない。ここが無敵の一番の効果
    if (!this.isInvincible) {
      this.hp -= dt;
    }

    // --- スコアを増やす ---
    // 1秒たまるごとに +1。while にしているのは、
    // 処理落ちで dt が大きくなったときに取りこぼさないため
    this.scoreTimer += dt;
    while (this.scoreTimer >= 1) {
      this.scoreTimer -= 1;
      this.score += this.scoreRate();
    }

    this.updateObstacles(dt);
    this.updateItems(dt);
    this.checkCollisions();
    this.checkPickups();

    // 浮かぶ文字。消えたものは捨てる
    this.popups.forEach(p => p.update(this.speed, dt));
    this.popups = this.popups.filter(p => !p.isDead);

    // --- ゲームオーバー判定 ---
    if (this.hp <= 0) {
      this.hp = 0;
      this.gameOver();
    }
  }

  /*
    障害物の出現・移動・退場をまとめて処理する
  */
  updateObstacles(dt) {
    // 進んだ距離ぶん、次の出現までのカウントを減らす
    this.spawnCountdown -= this.speed * dt;

    if (this.spawnCountdown <= 0) {
      this.spawnObstacle();
    }

    this.obstacles.forEach(o => o.update(this.speed, dt));

    // 画面外に出たものを配列から取り除く。
    // filter は「残すものだけの新しい配列」を作る
    this.obstacles = this.obstacles.filter(o => !o.isDead);
  }

  spawnObstacle() {
    const typeKey = pickObstacleType();

    // 画面の右端の少し外から出す
    this.obstacles.push(new Obstacle(typeKey, CONFIG.canvasWidth + 20, CONFIG.groundY));

    /*
      次までの距離を決める。
      速度が上がるほど間隔も広がるので、反応できる時間が一定に保たれる。
    */
    const minGap = CONFIG.minGapBase + this.speed * CONFIG.minGapPerSpeed;
    this.spawnCountdown = minGap + Math.random() * CONFIG.gapRandomRange;
  }

  /*
    猫と障害物の当たり判定
  */
  checkCollisions() {
    const catRect = this.cat.getRect();

    this.obstacles.forEach(o => {
      // すでに当たった相手からは二重にダメージを受けない
      if (o.isHit) return;

      if (!rectsOverlap(catRect, o.getRect())) return;

      if (this.isInvincible) {
        /*
          無敵中は逆に得をする。
          ダメージ量をそのまま「ごほうびの大きさ」として使い回している。
          ブルドッグ(2)のほうが鳥(1)より高得点になる。
        */
        o.knockOut();

        this.hp = Math.min(CONFIG.maxHp, this.hp + o.spec.damage);
        const gain = o.spec.damage * CONFIG.invincibleMultiplier;
        this.score += gain;

        this.popups.push(new Popup(`+${gain}`, o.x + o.width / 2, o.y));

      } else {
        o.isHit = true;
        this.hp -= o.spec.damage;
        this.cat.takeHit();
      }
    });
  }

  /*
    アイテムの出現・移動・退場
  */
  updateItems(dt) {
    this.itemSpawnCountdown -= this.speed * dt;

    if (this.itemSpawnCountdown <= 0) {
      this.spawnItem();
    }

    this.items.forEach(i => i.update(this.speed, dt));
    this.items = this.items.filter(i => !i.isDead && !i.isCollected);
  }

  spawnItem() {
    const spawnX = CONFIG.canvasWidth + 20;

    /*
      無敵中はキャンディーを出さない。
      出してしまうと、取るたびに無敵が延びて実質ずっと無敵になり、
      緊張感がなくなってしまう。
    */
    const exclude = this.isInvincible ? ['candy'] : [];
    const typeKey = pickItemType(exclude);

    if (!typeKey) return;

    const width = ITEM_TYPES[typeKey].width;

    /*
      障害物と重なる位置には置かない。
      重なると「避けたら取れない、取ったら当たる」という
      どうにもならない配置ができてしまう。

      置けないときは少しだけ待って、すぐにまた試す。
    */
    if (!this.isSpaceFree(spawnX, width)) {
      this.itemSpawnCountdown = 40;
      return;
    }

    this.items.push(new Item(typeKey, spawnX, CONFIG.groundY));

    const gap = CONFIG.itemGapBase + this.speed * CONFIG.itemGapPerSpeed;
    this.itemSpawnCountdown = gap + Math.random() * CONFIG.itemGapRandomRange;
  }

  /*
    x から x+width の範囲に、他のものが近すぎないか調べる。
    横方向だけ見ればよい（縦は関係なく、重なって見えるのが問題なので）。
  */
  isSpaceFree(x, width) {
    const clearance = CONFIG.spawnClearance;
    const left = x - clearance;
    const right = x + width + clearance;

    const others = [...this.obstacles, ...this.items];

    return !others.some(o => o.x < right && o.x + o.width > left);
  }

  /*
    アイテムを取ったときの処理
  */
  checkPickups() {
    const catRect = this.cat.getRect();

    this.items.forEach(item => {
      if (item.isCollected) return;
      if (!rectsOverlap(catRect, item.getRect())) return;

      item.isCollected = true;

      // --- キャンディーなら無敵開始 ---
      if (item.spec.effect === 'invincible') {
        /*
          すでに無敵ならなにも起きない。時間は延長しない。

          無敵中はキャンディーが出現しないようにしてあるが、
          「無敵になる直前にすでに画面に出ていた1個」を拾う可能性は残る。
          その取りこぼしをここで塞いでいる。
        */
        if (!this.isInvincible) {
          this.invincibleTimer = CONFIG.invincibleDuration;
          this.popups.push(new Popup('むてき!', item.x + item.width / 2, item.getY()));
        }
        return;
      }

      // --- 餌アイテム ---
      // 体力を回復。ただし上限を超えない
      this.hp = Math.min(CONFIG.maxHp, this.hp + item.spec.heal);

      const gain = item.spec.points * this.scoreRate();
      this.score += gain;

      // 取った位置に「+1」を出す
      this.popups.push(new Popup(
        `+${gain}`,
        item.x + item.width / 2,
        item.getY()
      ));
    });
  }

  // 今のスコア倍率。無敵中だけ10倍になる
  scoreRate() {
    return this.isInvincible ? CONFIG.invincibleMultiplier : 1;
  }

  gameOver() {
    this.state = STATE.GAMEOVER;

    // 猫をその場で止める。state を変えるだけだと、
    // 空中で力尽きたときに着地処理で走り出してしまう
    this.cat.fallDown();
    // elapsed は「実際に走っていた秒数」。サーバー側の不正チェックに使う
    this.ui.showGameOver(this.score, this.elapsed);
  }

  draw() {
    const ctx = this.ctx;

    // 毎フレーム、前の絵を消してから描き直す
    ctx.clearRect(0, 0, CONFIG.canvasWidth, CONFIG.canvasHeight);

    // 奥から手前の順に描く。順番を間違えると背景が猫を隠す
    this.background.draw(ctx);

    // 流れ星は雲や家より手前、地面より奥
    this.shootingStars.draw(ctx);

    this.ground.draw(ctx);

    // 障害物とアイテムの高さを決めるための目安線（CONFIG.debug で切る）
    if (CONFIG.debug) this.drawJumpGuide(ctx);

    this.items.forEach(i => i.draw(ctx));
    this.obstacles.forEach(o => o.draw(ctx));
    this.cat.draw(ctx);
    this.popups.forEach(p => p.draw(ctx));

    if (CONFIG.debug) this.drawHitboxes(ctx);

    this.drawHud(ctx);
  }

  /* -------------------------------------------
     HUD（体力バーとスコア）
     ------------------------------------------- */
  drawHud(ctx) {
    // --- 体力バー ---
    // 10マスの枠を描き、残り体力のぶんだけ黒く塗る
    const cellW = 10;
    const cellH = 8;
    const barX = 10;
    const barY = 10;

    ctx.save();
    ctx.strokeStyle = '#222222';
    ctx.fillStyle = '#222222';
    ctx.lineWidth = 1;

    for (let i = 0; i < CONFIG.maxHp; i++) {
      const x = barX + i * cellW;

      // 枠。0.5ずらすと線がボヤけない（canvas は線が座標の中央に描かれるため）
      ctx.strokeRect(x + 0.5, barY + 0.5, cellW - 1, cellH - 1);

      /*
        このマスの残量を 0〜1 で出す。
        hp が 7.4 なら、7マス目は 1.0、8マス目は 0.4、9マス目以降は 0。
      */
      const fill = Math.max(0, Math.min(1, this.hp - i));

      if (fill > 0) {
        ctx.fillRect(x + 2, barY + 2, (cellW - 4) * fill, cellH - 4);
      }
    }

    ctx.font = '10px DotGothic16, monospace';
    ctx.fillText('たいりょく', barX, barY + 22);

    /*
      --- 無敵ゲージ ---
      無敵中だけ体力バーの下に出す。
      残り時間が目で分かると「あと何体倒せるか」の判断ができる。
    */
    if (this.isInvincible) {
      const gaugeW = cellW * CONFIG.maxHp;
      const gaugeY = barY + 26;
      const ratio = this.invincibleTimer / CONFIG.invincibleDuration;

      ctx.strokeRect(barX + 0.5, gaugeY + 0.5, gaugeW - 1, 5);
      ctx.fillRect(barX + 1, gaugeY + 1, (gaugeW - 2) * Math.min(1, ratio), 3);

      ctx.fillText(`むてき ${this.invincibleTimer.toFixed(1)}`, barX + gaugeW + 6, gaugeY + 6);
    }

    // --- スコア ---
    // 4桁のゼロ埋め。padStart は文字列の先頭を埋めるメソッド
    const scoreText = `SCORE ${String(this.score).padStart(4, '0')}`;
    ctx.font = '12px DotGothic16, monospace';
    ctx.textAlign = 'right';
    ctx.fillText(scoreText, CONFIG.canvasWidth - 10, barY + 10);

    ctx.restore();
  }

  /* -------------------------------------------
     当たり判定の可視化（開発用）
     「当たってないのに当たる」ときはこれを見ると原因が分かる
     ------------------------------------------- */
  drawHitboxes(ctx) {
    ctx.save();
    ctx.strokeStyle = '#ff0000';
    ctx.lineWidth = 1;

    const rects = [
      this.cat.getRect(),
      ...this.obstacles.map(o => o.getRect()),
      ...this.items.map(i => i.getRect())
    ];
    rects.forEach(r => ctx.strokeRect(r.x, r.y, r.w, r.h));

    ctx.restore();
  }

  /*
    ジャンプで届く高さを線で表示する開発用の機能。
    障害物とアイテムをどの高さに置くか決めるための物差し。
    仕上げのときに CONFIG.debug を false にすれば消える。

    高さの計算は物理の公式そのまま:
      到達高さ = 初速の2乗 ÷ (2 × 重力)
  */
  drawJumpGuide(ctx) {
    const maxHeight = (CONFIG.jumpVelocity ** 2) / (2 * CONFIG.gravity);
    const minHeight = (CONFIG.jumpCutVelocity ** 2) / (2 * CONFIG.gravity);

    // 猫の「頭のてっぺん」が届く位置に線を引く
    const catTopOnGround = CONFIG.groundY - this.cat.height;

    const lines = [
      { y: catTopOnGround - maxHeight, label: `MAX ${Math.round(maxHeight)}px` },
      { y: catTopOnGround - minHeight, label: `MIN ${Math.round(minHeight)}px` }
    ];

    ctx.save();
    ctx.setLineDash([4, 4]);     // 破線にする
    ctx.strokeStyle = '#bbbbbb';
    ctx.lineWidth = 1;
    ctx.fillStyle = '#bbbbbb';
    ctx.font = '9px monospace';

    lines.forEach(line => {
      ctx.beginPath();
      ctx.moveTo(0, line.y);
      ctx.lineTo(CONFIG.canvasWidth, line.y);
      ctx.stroke();
      ctx.fillText(line.label, 4, line.y - 3);
    });

    ctx.restore();               // setLineDash などの設定を元に戻す
  }
}


/* -------------------------------------------
   ゲーム画面を画面内に収める

   縦横比が 4:1 固定なので、横幅を広げるほど縦も伸びる。
   上に広告が入ると、そのぶん使える高さが減って画面からはみ出す。

   広告の高さは事前に分からないうえ、読み込みのタイミングでも変わる。
   そこで「ゲーム画面の上端が今どこにあるか」を実測して、
   残りの高さから入る幅を逆算する。
   ------------------------------------------- */
function fitGameToViewport() {
  const wrapper = document.querySelector('.gameWrapper');
  if (!wrapper) return;

  // ページ先頭から数えた、ゲーム画面の上端の位置
  const top = wrapper.getBoundingClientRect().top + window.scrollY;

  // 残りの高さ。下に少し余白を残す
  const available = window.innerHeight - top - 12;

  // 高さ × 4 が、その高さに収まる最大の幅（600:150 = 4:1 のため）
  const maxWidth = Math.max(320, Math.floor(available * 4));

  /*
    同じ値なら書き換えない。

    max-width を変えるとゲーム画面の高さが変わり、
    それを監視している ResizeObserver がまた反応する。
    値が変わったときだけ書き込むことで、無限に往復するのを防ぐ。
  */
  const current = parseFloat(wrapper.style.maxWidth) || 0;
  if (Math.abs(current - maxWidth) > 1) {
    wrapper.style.maxWidth = `${maxWidth}px`;
  }
}


/* -------------------------------------------
   起動処理
   画像の読み込みが終わってからループを回し始める
   ------------------------------------------- */
window.addEventListener('load', async () => {
  fitGameToViewport();

  window.addEventListener('resize', fitGameToViewport);
  window.addEventListener('orientationchange', fitGameToViewport);

  /*
    広告は自分より後から読み込まれることがある。
    body の高さが変わったら測り直す。
  */
  if (window.ResizeObserver) {
    new ResizeObserver(fitGameToViewport).observe(document.body);
  }

  await Assets.loadAll();

  const canvas = document.getElementById('gameCanvas');
  const game = new Game(canvas);

  requestAnimationFrame((t) => game.loop(t));
});
