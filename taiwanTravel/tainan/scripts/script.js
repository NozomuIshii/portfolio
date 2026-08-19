'use strict';

/* ==========================================
   1. グルメ画像モーダル
   ------------------------------------------
   ボタンごとに同じ処理を4回書いていたのを、
   「ボタンID → 画像 + 説明」の一覧にまとめて
   ループで登録する形に整理しました。
   ========================================== */
const modal = document.querySelector('#modal');
const modalCloseBtn = document.querySelector('#closeBtn');
const modalImage = document.querySelector('#modalImage');

// 表示する画像の一覧。品目を増やすときはここに1行足すだけで済みます
const gourmetList = [
    { btnId: 'btnTanman', src: 'images/tnModal01.png', alt: '担仔麺' },
    { btnId: 'btnEbi',    src: 'images/tnModal02.png', alt: '蝦仁飯' },
    { btnId: 'btnShoron', src: 'images/tnModal03.png', alt: '小籠包' },
    { btnId: 'btnYaro',   src: 'images/tnModal04.png', alt: '鴨肉飯' }
];

// 「どのボタンから開いたか」を覚えておく（閉じたときにフォーカスを戻すため）
let lastOpener = null;

function openModal(src, alt, opener) {
    modalImage.src = src;
    modalImage.alt = alt;
    modal.classList.add('isOpen');

    // 背景のページがスクロールしないように固定する
    document.body.style.overflow = 'hidden';

    lastOpener = opener;

    if (modalCloseBtn) {
        modalCloseBtn.focus();
    }
}

function closeModal() {
    modal.classList.remove('isOpen');

    // 'auto' ではなく空文字にして、元の指定に戻す
    document.body.style.overflow = '';

    if (lastOpener) {
        lastOpener.focus();
        lastOpener = null;
    }
}

// 必要な要素が揃っている場合のみ実行（エラー防止）
if (modal && modalImage) {

    for (let i = 0; i < gourmetList.length; i = i + 1) {
        const item = gourmetList[i];
        const btn = document.querySelector('#' + item.btnId);

        if (btn) {
            btn.addEventListener('click', function () {
                openModal(item.src, item.alt, btn);
            });
        }
    }

    // 【閉じる】×ボタン
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }

    // 【閉じる】黒い背景部分をクリック
    // event.target が modal 自身のときだけ＝画像をクリックしたときは閉じない
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // 【閉じる】Escキー
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('isOpen')) {
            closeModal();
        }
    });
}


/* ==========================================
   2. ドロワーメニューの開閉処理
   ========================================== */
const hamburger = document.querySelector('.hamburgerJs');
const drawer = document.querySelector('.drawerJs');
const drawerCloseBtn = document.querySelector('.closeBtnJs');

if (hamburger && drawer && drawerCloseBtn) {

    // 【開く】ハンバーガーボタン
    hamburger.addEventListener('click', function () {
        drawer.classList.add('isOpen');
    });

    // 【閉じる】ドロワー内の×ボタン
    drawerCloseBtn.addEventListener('click', function () {
        drawer.classList.remove('isOpen');
    });

    // 【閉じる】メニューの外側をタップ
    document.addEventListener('click', function (event) {
        if (drawer.classList.contains('isOpen')
            && !drawer.contains(event.target)
            && !hamburger.contains(event.target)) {
            drawer.classList.remove('isOpen');
        }
    });
}
