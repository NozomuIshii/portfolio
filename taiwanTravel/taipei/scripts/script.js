'use strict';

/* ==========================================
   1. ドロワーメニューの開閉処理
   ========================================== */
const hamburger = document.querySelector('.hamburgerJs');
const drawer = document.querySelector('.drawerJs');
const drawerCloseBtn = document.querySelector('.closeBtnJs');

// 要素がすべて揃っている場合のみ動かす（エラー防止）
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


/* ==========================================
   2. 夜市モーダルの制御
   ------------------------------------------
   4つ分を同じ内容で4回書いていたので、
   ループ処理でまとめました。
   ========================================== */

// 今開いているモーダルと、開いたボタンを覚えておく
let openedModal = null;
let lastOpener = null;

function openModal(modal, opener) {
    modal.classList.add('isOpen');
    openedModal = modal;
    lastOpener = opener;

    // 背景のページがスクロールしないように固定する
    document.body.style.overflow = 'hidden';

    // 閉じるボタンにフォーカスを移す（キーボード操作の人のため）
    const btn = modal.querySelector('.closeBtn');
    if (btn) {
        btn.focus();
    }
}

function closeModal(modal) {
    modal.classList.remove('isOpen');
    openedModal = null;
    document.body.style.overflow = '';

    if (lastOpener) {
        lastOpener.focus();
        lastOpener = null;
    }
}

for (let i = 1; i <= 4; i = i + 1) {
    const openBtn = document.querySelector('#btn-market' + i);
    const modal = document.querySelector('#modalMarket' + i);

    if (openBtn && modal) {
        const modalCloseBtn = modal.querySelector('.closeBtn');

        // 【開く】夜市名のボタン
        openBtn.addEventListener('click', function () {
            openModal(modal, openBtn);
        });

        // 【閉じる】×ボタン
        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', function () {
                closeModal(modal);
            });
        }

        // 【閉じる】黒い背景部分をクリック
        // event.target が modal 自身のときだけ＝中身をクリックしたときは閉じない
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    }
}

// 【閉じる】Escキー
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && openedModal) {
        closeModal(openedModal);
    }
});
