'use strict';

// ==========================================
// 1. ドロワーメニューの制御
// ==========================================
const hamburger = document.querySelector('.hamburgerJs');
const drawer = document.querySelector('.drawerJs');
const closeBtn = document.querySelector('.closeBtnJs');

if (hamburger && drawer && closeBtn) {
    // 【開く】
    hamburger.addEventListener('click', function () {
        drawer.classList.add('isOpen');
    });

    // 【閉じる】×ボタン
    closeBtn.addEventListener('click', function () {
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


// ==========================================
// 2. 営業時間モーダルの制御
// ------------------------------------------
// 「営業時間を見る」ボタン4つとモーダル4つを、
// ループ処理で一括して結びつけます。
// ==========================================

// 今開いているモーダルを覚えておく変数（Escキーで閉じるときに使う）
let openedModal = null;

// モーダルを開く
function openModal(modal) {
    modal.classList.add('isOpen');
    openedModal = modal;

    // 背景のページがスクロールしないように固定する
    document.body.style.overflow = 'hidden';

    // 閉じるボタンにフォーカスを移す（キーボード操作の人のため）
    const btn = modal.querySelector('.closeBtn');
    if (btn) {
        btn.focus();
    }
}

// モーダルを閉じる
function closeModal(modal) {
    modal.classList.remove('isOpen');
    openedModal = null;
    document.body.style.overflow = '';
}

for (let i = 1; i <= 4; i = i + 1) {
    const openBtn = document.getElementById('btnMuseum' + i);
    const modal = document.getElementById('modalMuseum' + i);

    // 両方そろっている場合だけ処理する（エラー防止）
    if (openBtn && modal) {
        const modalCloseBtn = modal.querySelector('.closeBtn');

        // 【開く】営業時間を見るボタン
        openBtn.addEventListener('click', function () {
            openModal(modal);
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
