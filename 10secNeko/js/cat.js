/* ===========================================
   cat.js
   主人公の白猫。

   状態は 'idle'（待機） / 'run'（走る） / 'jump'（ジャンプ中）の3つ。
   被弾・無敵はフェーズ5以降で足す。
   =========================================== */

/*
  当たり判定の余白（px）。
  猫の見た目は 56x40、判定はその内側 30x26。
*/
const CAT_HITBOX = { left: 16, right: 10, top: 10, bottom: 4 };


class Cat {

  constructor(groundY) {
    // 横位置は固定。Chrome Dino と同じく画面左寄りに置く
    this.x = 40;

    /*
      見た目の大きさ。ドット絵の縦横比に合わせて横長にしてある。

      正方形にすると、ダッシュ絵の伸びたしっぽを収めるために
      猫全体を小さく縮めるしかなくなる。
      横長の枠にすることで、しっぽを切らずに猫を大きく見せられる。
    */
    this.width = 56;
    this.height = 40;

    // 地面の線の上に足が乗るようにY座標を決める
    this.groundY = groundY;
    this.y = groundY - this.height;

    this.state = 'idle';

    // 力尽きたら true。以降は動かず、被弾絵のまま止まる
    this.isDown = false;

    // --- ジャンプ用 ---
    // velocityY は「1秒あたり何ピクセル動くか」。
    // canvas はY軸が下向きなので、マイナス = 上に動く。
    this.velocityY = 0;
    this.isJumping = false;

    // --- 被弾表示用 ---
    // 0より大きい間、顔がバツ印の絵になる。時間で自然に戻る
    this.hitTimer = 0;

    /*
      --- 無敵表示用 ---
      無敵の管理そのものは Game 側が持っている。
      Cat は「あと何秒残っているか」を毎フレーム受け取って、
      見た目だけを決める。状態を二重に持たないための分担。
    */
    this.invincibleRemaining = 0;

    // --- アニメーション用 ---
    this.animTimer = 0;
    this.animFrame = 0;          // 0 か 1
    this.frameDuration = 0.1;    // 1コマ 0.1秒 = 秒間10コマ
  }

  /*
    ジャンプ開始。
    空中では受け付けない（＝2段ジャンプ禁止）。
  */
  jump() {
    if (this.isJumping) return;

    this.isJumping = true;
    this.velocityY = CONFIG.jumpVelocity;
    this.state = 'jump';
  }

  /*
    ボタンを離したときに呼ぶ。
    上昇中なら速度を弱めて、そこで上昇を打ち切る。これが可変ジャンプの正体。

    「velocityY < jumpCutVelocity」の判定は、
    まだ勢いよく上がっている最中だけ効かせるため。
    落下中（velocityY がプラス）や、すでに失速しているときは何もしない。
  */
  cutJump() {
    if (this.velocityY < CONFIG.jumpCutVelocity) {
      this.velocityY = CONFIG.jumpCutVelocity;
    }
  }

  /*
    障害物に当たったときに呼ぶ。
    体力の増減は Game 側の仕事なので、ここは見た目だけ担当する。
  */
  takeHit() {
    this.hitTimer = CONFIG.hitDisplayTime;
  }

  /*
    力尽きたときに呼ぶ。
    そこで完全に止める。

    これを入れないと、ジャンプ中に力尽きたとき着地処理が走り、
    state が 'run' に戻ってしまう。
    結果、ゲームオーバー画面の裏で猫が走り続けることになる。
  */
  fallDown() {
    this.isDown = true;
    this.velocityY = 0;
    this.isJumping = false;
  }

  update(dt) {
    // 力尽きたあとは一切動かさない
    if (this.isDown) return;

    // 被弾表示の残り時間を減らす。マイナスにならないよう下限を0にする
    if (this.hitTimer > 0) {
      this.hitTimer = Math.max(0, this.hitTimer - dt);
    }

    // --- ジャンプの物理計算 ---
    if (this.isJumping) {
      // 重力で速度を下向きに変化させる
      this.velocityY += CONFIG.gravity * dt;

      // その速度ぶん実際に動かす
      this.y += this.velocityY * dt;

      // 地面より下に行ったら着地させる
      const floorY = this.groundY - this.height;
      if (this.y >= floorY) {
        this.y = floorY;          // めり込んだぶんを戻す
        this.velocityY = 0;
        this.isJumping = false;
        this.state = 'run';
      }
    }

    /*
      --- コマ送り ---
      走っているときだけでなく、常に進めておく。

      無敵絵も被弾絵も2コマのアニメになったため、
      ジャンプ中（state が 'jump'）でもコマが動いてほしい。
      待機は1枚しかないので、進めても見た目は変わらない。
    */
    this.animTimer += dt;

    if (this.animTimer >= this.frameDuration) {
      this.animTimer -= this.frameDuration;
      this.animFrame = (this.animFrame + 1) % 2;
    }
  }

  draw(ctx) {
    /*
      ジャンプ中は待機絵を使う。
      Chrome Dino も同じで、空中で足がバタつかないほうが自然に見える。
      素材を増やさずに済むという実利もある。
    */
    const isInvincible = this.invincibleRemaining > 0;

    /*
      --- 切れる直前の点滅 ---
      残り3秒を切ったら、1秒あたり8回のペースで表示／非表示を切り替える。

      「残り時間 × 8」を整数に落として、偶数か奇数かで判定する。
      専用のタイマーを別に持たなくていいので、リセット漏れのバグが起きない。
    */
    if (isInvincible && this.invincibleRemaining <= CONFIG.invincibleBlinkStart) {
      const phase = Math.floor(this.invincibleRemaining * 8);
      if (phase % 2 === 0) return;   // このフレームは描かない＝点滅して見える
    }

    /*
      どの絵を使うか。上から順に優先する。

        1. 力尽きた      … catHit（1枚。倒れた絵）
        2. 被弾中        … catRunHit1 / 2（走りながらバツ目）
        3. 無敵中        … catStar1 / 2
        4. ジャンプ中    … catRun1（足を伸ばした絵。空中でバタつかせない）
        5. 走行中        … catRun1 / 2
        6. スタート待ち  … catStar1 / 2（目が星の絵で動かして、画面をにぎやかに）
    */
    const second = this.animFrame === 1;

    let key;
    if (this.isDown) {
      key = 'catHit';
    } else if (this.hitTimer > 0) {
      key = second ? 'catRunHit2' : 'catRunHit1';
    } else if (isInvincible) {
      key = second ? 'catStar2' : 'catStar1';
    } else if (this.state === 'jump') {
      key = 'catRun1';
    } else if (this.state === 'run') {
      key = second ? 'catRun2' : 'catRun1';
    } else {
      key = second ? 'catStar2' : 'catStar1';
    }

    const img = Assets.get(key);

    if (img) {
      ctx.drawImage(img, this.x, this.y, this.width, this.height);
    } else {
      // 仮描画
      ctx.fillStyle = '#222222';
      ctx.fillRect(this.x, this.y, this.width, this.height);

      ctx.fillStyle = '#ffffff';

      if (this.hitTimer > 0) {
        // 目をバツ印にする
        this.drawCross(ctx, this.x + 11, this.y + 13);
        this.drawCross(ctx, this.x + 27, this.y + 13);
      } else if (isInvincible) {
        // 目を星に、口を開ける
        ctx.save();
        ctx.font = '12px DotGothic16, monospace';
        ctx.textAlign = 'center';
        ctx.fillStyle = '#ffffff';
        ctx.fillText('✧', this.x + 12, this.y + 17);
        ctx.fillText('✧', this.x + 28, this.y + 17);
        ctx.restore();
        ctx.fillRect(this.x + 15, this.y + 22, 10, 7);
      } else {
        ctx.fillRect(this.x + 8, this.y + 10, 6, 6);
        ctx.fillRect(this.x + 24, this.y + 10, 6, 6);
      }

      if (this.isDown) {
        // 倒れている表現
        ctx.fillRect(this.x + 10, this.y + this.height - 4, this.width - 20, 4);
      } else if (this.state === 'run' || this.state === 'jump') {
        const legOffset = (this.animFrame === 0) ? 0 : 10;
        ctx.fillRect(this.x + 6 + legOffset, this.y + this.height - 6, 8, 4);
      }
    }
  }

  // 仮描画用のバツ印
  drawCross(ctx, cx, cy) {
    ctx.save();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(cx - 4, cy - 4);
    ctx.lineTo(cx + 4, cy + 4);
    ctx.moveTo(cx + 4, cy - 4);
    ctx.lineTo(cx - 4, cy + 4);
    ctx.stroke();
    ctx.restore();
  }

  /*
    当たり判定用の矩形。

    上下左右で余白の量が違うのは、猫が枠の中央にいないため。
    しっぽが左に長く伸びているぶん、胴体は右寄りに描かれている。
    左右同じ余白にすると、しっぽに当たっただけでダメージになってしまう。

    値は実際のドット絵から、どのポーズでも胴体が入る範囲を測って決めた。
  */
  getRect() {
    const p = CAT_HITBOX;
    return {
      x: this.x + p.left,
      y: this.y + p.top,
      w: this.width - p.left - p.right,
      h: this.height - p.top - p.bottom
    };
  }
}
