/* ===========================================
   assets.js
   画像をまとめて読み込む係。

   まだドット絵ができていないので、
   「ファイルが無くてもエラーで止まらず、代わりに四角を描く」
   という作りにしてある。
   img フォルダに絵を置けば自動的にそっちが使われる。
   =========================================== */

const Assets = {

  // 読み込んだ画像を入れておく箱。キー名で取り出す
  images: {},

  /*
    読み込みたい画像の一覧。
    key   … プログラムから呼ぶときの名前
    src   … ファイルの場所
    今はフェーズ1で使うものだけ。障害物やアイテムはフェーズ5以降で足す。
  */
  list: [
    { key: 'catIdle',  src: 'img/catIdle.png'  },
    { key: 'catRun1',  src: 'img/catRun1.png'  },
    { key: 'catRun2',  src: 'img/catRun2.png'  },
    { key: 'catHit',      src: 'img/catHit.png'      },
    { key: 'catRunHit1',  src: 'img/catRunHit1.png'  },
    { key: 'catRunHit2',  src: 'img/catRunHit2.png'  },
    { key: 'catStar1',    src: 'img/catStar1.png'    },
    { key: 'catStar2',    src: 'img/catStar2.png'    },
    { key: 'dogBark',  src: 'img/dogBark.png'  },
    { key: 'dogClose', src: 'img/dogClose.png' },
    { key: 'birdUp',   src: 'img/birdUp.png'   },
    { key: 'birdDown', src: 'img/birdDown.png' },
    { key: 'fish',     src: 'img/fish.png'     },
    { key: 'tai',      src: 'img/tai.png'      },
    { key: 'candy',    src: 'img/candy.png'    },
    { key: 'ground',   src: 'img/ground.png'   },
    { key: 'cloud',    src: 'img/cloud.png'    }
  ],

  /*
    全部の画像を読み込む。
    Promise を返すので、呼ぶ側は await で「読み込み終わるまで待つ」ができる。
  */
  loadAll() {
    // list の1件ずつを「1個の Promise」に変換する
    const jobs = this.list.map(item => {
      return new Promise(resolve => {
        const img = new Image();

        // 読み込み成功したら images に入れる
        img.onload = () => {
          this.images[item.key] = img;
          resolve();
        };

        // 失敗しても reject せず resolve する。
        // こうしないと画像が1枚無いだけでゲーム全体が起動しなくなる。
        img.onerror = () => {
          console.warn(`画像が見つかりません: ${item.src}（仮の四角で描画します）`);
          this.images[item.key] = null;
          resolve();
        };

        img.src = item.src;
      });
    });

    // 全部終わるまで待つ
    return Promise.all(jobs);
  },

  /*
    画像を取り出す。無ければ null が返る。
    呼ぶ側は null チェックをして仮描画に切り替える。
  */
  get(key) {
    return this.images[key] || null;
  }
};
