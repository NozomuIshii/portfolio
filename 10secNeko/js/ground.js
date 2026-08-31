/* ===========================================
   ground.js
   地面と背景（雲・家）のスクロールを担当する。

   スクロールの考え方:
   画像そのものを動かすのではなく「描き始めのX座標」をズラしていく。
   左に流れて画面外に出たら、右端に戻す。これの繰り返しで無限に流れる。
   =========================================== */

/* -------------------------------------------
   地面
   ------------------------------------------- */
class Ground {

  constructor(canvasWidth, groundY) {
    this.canvasWidth = canvasWidth;

    // 地面の「表面」のY座標。猫はこの線の上に立つ
    this.y = groundY;

    // 描き始めのX座標。ここをマイナス方向に動かしてスクロールさせる
    this.offsetX = 0;

    // 地面画像1枚分の幅。画像が無いときの仮の値としても使う
    this.tileWidth = 600;
  }

  /*
    毎フレーム呼ばれる更新処理。
    speed … 1秒あたり何ピクセル流れるか
    dt    … 前のフレームからの経過秒数（0.016くらい）
  */
  update(speed, dt) {
    this.offsetX -= speed * dt;

    // 1枚分ぶん左に流れたら、位置を戻す。
    // 「-=」ではなく「%」を使うと、フレーム落ちで大きく飛んでも破綻しない
    if (this.offsetX <= -this.tileWidth) {
      this.offsetX %= this.tileWidth;
    }
  }

  draw(ctx) {
    const img = Assets.get('ground');

    if (img) {
      this.tileWidth = img.width;

      // 画面を埋めるのに必要な枚数ぶん、横に並べて描く
      // +2 は「左にはみ出す1枚」と「右にはみ出す1枚」の保険
      const count = Math.ceil(this.canvasWidth / this.tileWidth) + 2;

      for (let i = 0; i < count; i++) {
        ctx.drawImage(img, this.offsetX + i * this.tileWidth, this.y);
      }

    } else {
      // 画像が無いときの仮描画。ただの線と、流れていることが分かる点を打つ
      ctx.strokeStyle = '#222222';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(0, this.y);
      ctx.lineTo(this.canvasWidth, this.y);
      ctx.stroke();

      ctx.fillStyle = '#888888';
      for (let x = this.offsetX % 40; x < this.canvasWidth; x += 40) {
        ctx.fillRect(x, this.y + 6, 4, 2);
      }
    }
  }
}


/* -------------------------------------------
   背景（雲）
   地面より遅く流すと奥行きが出る。これを視差スクロールという。

   以前は家も並べていたが、プレイすると障害物と紛らわしく
   邪魔になったので外した。
   drawItems は種類を増やせる作りのままにしてあるので、
   別の背景素材を足したくなったら1行で戻せる。
   ------------------------------------------- */
class Background {

  constructor(canvasWidth) {
    this.canvasWidth = canvasWidth;

    // 雲をいくつか、バラバラの位置に置いておく
    this.clouds = [
      { x: 80,  y: 20 },
      { x: 300, y: 35 },
      { x: 500, y: 15 }
    ];
  }

  update(speed, dt) {
    // 雲は地面の 0.2 倍の速さ。ゆっくり流れる
    this.moveItems(this.clouds, speed * 0.2 * dt, 46);
  }

  /*
    配列の中身をまとめて左に動かし、画面外に出たら右端に戻す。
    width は「その素材のだいたいの幅」
  */
  moveItems(items, distance, width) {
    items.forEach(item => {
      item.x -= distance;

      if (item.x < -width) {
        // 右端の外に戻す。少しランダムにすると同じ間隔で並ばない
        item.x = this.canvasWidth + Math.random() * 150;
      }
    });
  }

  draw(ctx) {
    /*
      背景はアイテムや障害物と区別したいので薄くする。

      0.65 という値は実際の画面を見て決めたもの。
      雲はもともと明るいグレーで描かれているので、
      0.35 くらいまで落とすと消えてしまう。
    */
    this.drawItems(ctx, this.clouds, 'cloud', 46, 25, 0.65);
  }

  drawItems(ctx, items, assetKey, w, h, alpha) {
    const img = Assets.get(assetKey);

    /*
      globalAlpha を下げてから描き、描き終わったら必ず 1 に戻す。
      戻し忘れると、以降に描くもの（猫や障害物）が全部薄くなる。
    */
    ctx.globalAlpha = alpha;

    items.forEach(item => {
      if (img) {
        /*
          幅と高さを指定して描く。
          これを省くと画像の元サイズでそのまま描かれるので、
          素材を1024pxで書き出したときに画面いっぱいになってしまう。
          指定しておけば、元サイズが多少違っても崩れない。
        */
        ctx.drawImage(img, item.x, item.y, w, h);
      } else {
        // 仮描画。位置と大きさの当たりを付けるための灰色の枠
        ctx.strokeStyle = '#999999';
        ctx.lineWidth = 2;
        ctx.strokeRect(item.x, item.y, w, h);
      }
    });

    ctx.globalAlpha = 1;
  }
}


/* -------------------------------------------
   無敵中に流れる「★------」

   背景の一番奥に描く。
   無敵が始まったら流し始め、終わったら残りを流し切ってから止める。
   ------------------------------------------- */
class ShootingStars {

  constructor(canvasWidth, canvasHeight) {
    this.canvasWidth = canvasWidth;
    this.canvasHeight = canvasHeight;

    this.stars = [];
    this.active = false;

    // 次の1本を出すまでの残り秒数
    this.spawnTimer = 0;
  }

  setActive(flag) {
    this.active = flag;
  }

  update(dt) {
    if (this.active) {
      this.spawnTimer -= dt;

      if (this.spawnTimer <= 0) {
        this.spawn();
        // 0.08〜0.22秒おき。細かく出すと「流れている」感じが出る
        this.spawnTimer = 0.08 + Math.random() * 0.14;
      }
    }

    this.stars.forEach(s => {
      s.x -= s.speed * dt;
    });

    // 画面の左に消えたものを捨てる。-80 は文字列の長さぶんの余裕
    this.stars = this.stars.filter(s => s.x > -80);
  }

  spawn() {
    this.stars.push({
      x: this.canvasWidth + 20,
      // 地面より上の空の範囲にばらまく
      y: 12 + Math.random() * 90,
      // 速度をばらけさせると奥行きが出る
      speed: 400 + Math.random() * 500,
      // 濃さもばらけさせる
      alpha: 0.25 + Math.random() * 0.4
    });
  }

  draw(ctx) {
    if (this.stars.length === 0) return;

    ctx.save();
    ctx.fillStyle = '#888888';
    ctx.font = '10px DotGothic16, monospace';
    ctx.textAlign = 'left';

    this.stars.forEach(s => {
      ctx.globalAlpha = s.alpha;
      // 星が先頭、うしろに尾を引く形。進行方向は左なので尾は右側
      ctx.fillText('★------', s.x, s.y);
    });

    ctx.restore();
  }

  clear() {
    this.stars = [];
    this.active = false;
  }
}
