/* ===========================================
   item.js
   餌アイテム（お魚・鯛・キャンディー）と、取ったときの「+1」表示。

   構造は obstacle.js とほぼ同じ。
   違いは「当たると得をする」ことと、
   高さが複数の候補からランダムに選ばれること。
   =========================================== */

/*
  heights は「地面から何px上に出るか」の候補。
  出現するたびにこの中から1つ選ばれる。

  ジャンプの到達高さは MIN 28px / MAX 60px。
  ジャンプの途中でも通過するので、だいたい 80px までなら届く。

    0    … 走っているだけで取れる
    28   … 小ジャンプ
    48   … 中ジャンプ
    52以上 … 大ジャンプ必須
*/
/*
  幅と高さはドット絵の素材から逆算した値。

  お魚(26x12)と鯛(30x20)はどちらも魚なので、
  大きさと体形で見分けられるようにしてある。
  お魚は小さく細長く、鯛は大きく体高がある。
*/
const ITEM_TYPES = {

  // お魚。よく出る。控えめな回復
  fish: {
    width: 26,
    height: 12,
    heights: [0, 28, 48],
    heal: 1,          // 体力 +1秒
    points: 1,        // スコア +1
    assetKey: 'fish',
    padding: { left: 2, right: 2, top: 2, bottom: 2 },
    weight: 3
  },

  // 鯛。出にくいが回復量が大きい。高い位置にしか出ない
  tai: {
    width: 30,
    height: 20,
    heights: [52, 66],
    heal: 3,          // 体力 +3秒
    points: 3,        // スコア +3
    assetKey: 'tai',
    padding: { left: 2, right: 2, top: 2, bottom: 2 },
    weight: 1
  },

  /*
    キャンディー。10秒間の無敵になる。
    体力は回復しないが、無敵中に稼げるぶんで元が取れる設計。

    一番高い位置にしか出ないので、取りに行くと着地まで無防備になる。
    「リスクを負って取る」ものなので、出現率も低めにしてある。

    渦巻きの丸い形は、魚2種とも障害物とも似ていない。
    一目で「特別なもの」と分かることを優先している。
  */
  candy: {
    width: 26,
    height: 26,
    heights: [58, 70],
    heal: 0,
    points: 0,
    effect: 'invincible',     // これがある種類だけ特殊処理が走る
    assetKey: 'candy',
    padding: { left: 2, right: 2, top: 2, bottom: 2 },

    /*
      出現率 = 自分の weight ÷ 全部の weight の合計。

      weight 0.8 のときは 0.8 ÷ 4.8 = 16.7% だった。
      これを 10分の1（1.67%）にしたいが、weight を 0.1倍の 0.08 にしても
      合計のほうも小さくなるので 0.08 ÷ 4.08 = 1.96% にしかならない。
      正確に10分の1にするには 0.068 が必要。
    */
    weight: 0.068
  }
};


class Item {

  constructor(typeKey, startX, groundY) {
    this.typeKey = typeKey;
    this.spec = ITEM_TYPES[typeKey];

    this.x = startX;
    this.width = this.spec.width;
    this.height = this.spec.height;

    // 高さの候補からランダムに1つ選ぶ
    const heights = this.spec.heights;
    this.heightAboveGround = heights[Math.floor(Math.random() * heights.length)];
    this.baseY = groundY - this.heightAboveGround - this.height;

    this.isCollected = false;
    this.isDead = false;

    // ふわふわ上下させるための時間カウンタ
    this.floatTimer = Math.random() * Math.PI * 2;   // 個体ごとに位相をずらす
  }

  update(speed, dt) {
    this.x -= speed * dt;

    if (this.x + this.width < 0) {
      this.isDead = true;
    }

    this.floatTimer += dt * 4;
  }

  /*
    実際に描画・判定に使うY座標。
    地面に置いたものは揺らさない（浮いて見えるとおかしいので）。
  */
  getY() {
    if (this.heightAboveGround === 0) return this.baseY;

    // sin は -1〜1 を往復する。それを2px幅の上下の揺れに使う
    return this.baseY + Math.sin(this.floatTimer) * 2;
  }

  draw(ctx) {
    const img = Assets.get(this.spec.assetKey);
    const y = this.getY();

    if (img) {
      ctx.drawImage(img, this.x, y, this.width, this.height);
    } else {
      // 仮描画。障害物（黒っぽい塊）と区別できるよう白抜きにする
      ctx.save();
      ctx.fillStyle = '#ffffff';
      ctx.strokeStyle = '#222222';
      ctx.lineWidth = 2;

      if (this.typeKey === 'fish') {
        // 魚っぽい菱形＋尾びれ
        ctx.beginPath();
        ctx.moveTo(this.x, y + this.height / 2);
        ctx.lineTo(this.x + this.width - 6, y);
        ctx.lineTo(this.x + this.width - 6, y + this.height);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
        ctx.fillRect(this.x + this.width - 6, y + 4, 6, this.height - 8);
        ctx.strokeRect(this.x + this.width - 6, y + 4, 6, this.height - 8);
      } else if (this.typeKey === 'candy') {
        // 丸いアメと棒
        const r = this.width / 2 - 2;
        ctx.beginPath();
        ctx.arc(this.x + this.width / 2, y + r + 1, r, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(this.x + this.width / 2, y + r * 2);
        ctx.lineTo(this.x + this.width - 2, y + this.height - 1);
        ctx.stroke();
      } else {
        // 鯛。お魚より体高のある楕円
        ctx.beginPath();
        ctx.ellipse(this.x + this.width * 0.4, y + this.height / 2,
                    this.width * 0.4, this.height / 2 - 1, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(this.x + this.width * 0.78, y + this.height / 2);
        ctx.lineTo(this.x + this.width, y + 2);
        ctx.lineTo(this.x + this.width, y + this.height - 2);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
      }

      ctx.restore();
    }
  }

  // 当たり判定。書き方は obstacle.js とそろえてある
  getRect() {
    const p = this.spec.padding;
    const y = this.getY();
    return {
      x: this.x + p.left,
      y: y + p.top,
      w: this.width - p.left - p.right,
      h: this.height - p.top - p.bottom
    };
  }
}


/*
  weight に応じてアイテムの種類を選ぶ。

  excludeKeys に種類名を渡すと、その種類は候補から外れる。
  無敵中にキャンディーを出さないために使う。
*/
function pickItemType(excludeKeys = []) {
  const keys = Object.keys(ITEM_TYPES).filter(k => !excludeKeys.includes(k));

  // 全部除外されてしまった場合の保険
  if (keys.length === 0) return null;

  const total = keys.reduce((sum, k) => sum + ITEM_TYPES[k].weight, 0);

  let r = Math.random() * total;

  for (const key of keys) {
    r -= ITEM_TYPES[key].weight;
    if (r <= 0) return key;
  }

  return keys[0];
}


/* -------------------------------------------
   取ったときに浮かび上がる「+1」の表示

   ゲームでは「入力に対して何か反応が返る」ことが
   気持ちよさに直結する。地味だが効果は大きい。
   ------------------------------------------- */
class Popup {

  constructor(text, x, y) {
    this.text = text;
    this.x = x;
    this.y = y;
    this.life = 0.7;        // 表示され続ける秒数
    this.maxLife = 0.7;
    this.isDead = false;
  }

  update(speed, dt) {
    // 背景と一緒に流れないと、その場に取り残されて不自然になる
    this.x -= speed * dt;

    // ゆっくり上に浮かせる
    this.y -= 30 * dt;

    this.life -= dt;
    if (this.life <= 0) this.isDead = true;
  }

  draw(ctx) {
    ctx.save();

    // 残り時間に比例して薄くする（フェードアウト）
    ctx.globalAlpha = Math.max(0, this.life / this.maxLife);

    ctx.fillStyle = '#222222';
    ctx.font = '12px DotGothic16, monospace';
    ctx.textAlign = 'center';
    ctx.fillText(this.text, this.x, this.y);

    ctx.restore();
  }
}
