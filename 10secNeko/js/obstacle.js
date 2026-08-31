/* ===========================================
   obstacle.js
   障害物（ブルドッグ・鳥）と、当たり判定の道具。

   種類ごとの数値は OBSTACLE_TYPES にまとめてある。
   バランス調整はこの表だけをいじれば済むようにしてある。
   =========================================== */

/*
  y は「地面の線からどれだけ上か」で書く。
  0 なら地面に接地、40 なら地面から40px浮いている。

  ジャンプの到達高さ（MIN 28px / MAX 60px）と見比べて設計している。

  padding は当たり判定を見た目より小さくするための余白で、
  上下左右をそれぞれ指定する。

  上下左右をばらばらに指定できるようにしているのは、鳥のため。
  鳥は翼を上に伸ばすので枠の上側が大きく空く。
  ここを均等な余白にすると、翼の先に触れただけでダメージになってしまう。
  値は実際のドット絵から胴体の範囲を測って決めている。

  weight は出現しやすさ。数字が大きいほどよく出る。0 にすると出なくなる。
*/
const OBSTACLE_TYPES = {

  /*
    ブルドッグ。地面を走ってくる。小ジャンプで越えられる。

    54x32 という比率はドット絵の素材から逆算した値。
    実際のブルドッグは背が低く胴が長いので、横長になる。
  */
  dog: {
    width: 54,
    height: 32,
    heightAboveGround: 0,
    damage: 4,
    frames: ['dogBark', 'dogClose'],
    frameDuration: 0.18,
    padding: { left: 7, right: 7, top: 4, bottom: 4 },
    weight: 4
  },

  /*
    低い鳥。中くらいのジャンプが必要。

    枠は 36x32 だが、上の12pxは翼を振り上げたときの余地。
    heightAboveGround は「枠の下端」の高さなので、
    枠が高くなっても胴体の位置は変わらない。
  */
  birdLow: {
    width: 36,
    height: 32,
    heightAboveGround: 12,
    damage: 3,
    frames: ['birdUp', 'birdDown'],
    frameDuration: 0.12,
    padding: { left: 4, right: 4, top: 12, bottom: 4 },
    weight: 3
  },

  /*
    高い鳥。
    これだけ性質が違って「ジャンプせずに下をくぐる」障害物。
    ジャンプ操作しかないぶん、"跳ばない判断" を要求する変化球として入れている。

    理不尽に感じたら weight を 0 にすれば出なくなる。
  */
  birdHigh: {
    width: 36,
    height: 32,
    heightAboveGround: 54,
    damage: 3,
    frames: ['birdUp', 'birdDown'],
    frameDuration: 0.12,
    padding: { left: 4, right: 4, top: 12, bottom: 4 },
    weight: 1
  }
};


class Obstacle {

  constructor(typeKey, startX, groundY) {
    this.typeKey = typeKey;

    // 表から設定を引っぱってくる
    this.spec = OBSTACLE_TYPES[typeKey];

    this.x = startX;
    this.width = this.spec.width;
    this.height = this.spec.height;

    // 地面からの高さをY座標に変換する
    this.y = groundY - this.spec.heightAboveGround - this.height;

    // 一度当たったら、それ以上ダメージを与えない
    this.isHit = false;

    // 画面外に出たら true。main 側でまとめて捨てる
    this.isDead = false;

    // --- 無敵状態の猫に弾き飛ばされたとき用 ---
    this.isKnocked = false;
    this.knockVx = 0;        // 横の速度
    this.knockVy = 0;        // 縦の速度
    this.rotation = 0;       // 回転角（ラジアン）
    this.rotationSpeed = 0;  // 1秒あたり何ラジアン回るか

    this.animTimer = 0;
    this.animFrame = 0;
  }

  /*
    無敵の猫にぶつかられたときに呼ぶ。
    右斜め上に飛ばして、あとは重力に任せる。
  */
  knockOut() {
    this.isKnocked = true;
    this.isHit = true;

    // 少しランダムにすると、毎回同じ飛び方にならない
    this.knockVx = 220 + Math.random() * 120;
    this.knockVy = -300 - Math.random() * 120;

    // 回る向きもランダム。符号で時計回り／反時計回りが決まる
    this.rotationSpeed = (Math.random() < 0.5 ? -1 : 1) * (8 + Math.random() * 6);
  }

  update(speed, dt) {
    /*
      弾き飛ばされている最中は、地面のスクロールとは無関係に動く。
      自分の速度で飛び、重力で落ちて、くるくる回る。
    */
    if (this.isKnocked) {
      this.knockVy += CONFIG.gravity * dt;   // 猫のジャンプと同じ重力を使う

      this.x += this.knockVx * dt;
      this.y += this.knockVy * dt;
      this.rotation += this.rotationSpeed * dt;

      // 画面のどこかから出たら退場
      if (this.x > CONFIG.canvasWidth + 100 || this.y > CONFIG.canvasHeight + 100) {
        this.isDead = true;
      }
      return;
    }

    // 左に流す
    this.x -= speed * dt;

    // 完全に画面の左外に出たら退場フラグを立てる
    if (this.x + this.width < 0) {
      this.isDead = true;
    }

    // 羽ばたき・吠えのコマ送り
    this.animTimer += dt;
    if (this.animTimer >= this.spec.frameDuration) {
      this.animTimer -= this.spec.frameDuration;
      this.animFrame = (this.animFrame + 1) % this.spec.frames.length;
    }
  }

  draw(ctx) {
    /*
      回転させるときは canvas の座標系そのものを回す。
      手順は必ずこの順番:
        1. save() で今の状態を保存
        2. translate() で回転の中心を原点に持ってくる
        3. rotate() で回す
        4. 原点(0,0)を左上として描く
        5. restore() で元に戻す

      restore() を忘れると、以降の描画が全部傾く。
    */
    if (this.isKnocked) {
      ctx.save();
      ctx.translate(this.x + this.width / 2, this.y + this.height / 2);
      ctx.rotate(this.rotation);
      this.drawBody(ctx, -this.width / 2, -this.height / 2);
      ctx.restore();
      return;
    }

    this.drawBody(ctx, this.x, this.y);
  }

  // 実際の絵を描く部分。左上の座標を受け取る形にして、回転描画と共用する
  drawBody(ctx, x, y) {
    const key = this.spec.frames[this.animFrame];
    const img = Assets.get(key);

    if (img) {
      ctx.drawImage(img, x, y, this.width, this.height);
    } else {
      // 仮描画。種類が見分けられるように形を変えてある
      ctx.fillStyle = '#444444';

      if (this.typeKey === 'dog') {
        // 犬っぽい横長の塊
        ctx.fillRect(x, y + 8, this.width, this.height - 8);
        ctx.fillRect(x + this.width - 14, y, 14, 12);   // 頭
      } else {
        // 鳥。コマによって翼の位置を変える
        ctx.fillRect(x + 6, y + 8, this.width - 12, 8);
        const wingY = (this.animFrame === 0) ? y : y + 16;
        ctx.fillRect(x + 10, wingY, 16, 6);
      }
    }
  }

  /*
    当たり判定用の矩形。padding のぶん内側に縮めて返す。
  */
  getRect() {
    const p = this.spec.padding;
    return {
      x: this.x + p.left,
      y: this.y + p.top,
      w: this.width - p.left - p.right,
      h: this.height - p.top - p.bottom
    };
  }
}


/* -------------------------------------------
   矩形どうしが重なっているか調べる（AABB判定）

   「重なっていない条件」が4つあり、そのどれにも当てはまらなければ重なっている。
   この書き方が一番速くて間違いが少ない。
   ------------------------------------------- */
function rectsOverlap(a, b) {
  return a.x < b.x + b.w &&
         a.x + a.w > b.x &&
         a.y < b.y + b.h &&
         a.y + a.h > b.y;
}


/* -------------------------------------------
   weight に応じてランダムに種類を1つ選ぶ

   仕組み: weight の合計を出し、0〜合計 の乱数を引いて、
   先頭から weight を引いていく。0を下回ったところが当選。
   ------------------------------------------- */
function pickObstacleType() {
  const keys = Object.keys(OBSTACLE_TYPES);
  const total = keys.reduce((sum, k) => sum + OBSTACLE_TYPES[k].weight, 0);

  let r = Math.random() * total;

  for (const key of keys) {
    r -= OBSTACLE_TYPES[key].weight;
    if (r <= 0) return key;
  }

  return keys[0];   // 念のための保険
}
