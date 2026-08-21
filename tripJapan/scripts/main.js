/* ==========================================
   FOODSセクションの画像スライダー（トップページ）
   ========================================== */
document.addEventListener('DOMContentLoaded', () => {
  const mainImages = document.querySelectorAll('.foods__mainItem');
  const thumbItems = document.querySelectorAll('.foods__thumbItem');

  // スライダーが無いページでは何もしない
  if (mainImages.length === 0) return;

  let currentIndex = 0;
  let slideTimer = null;
  const intervalTime = 4000; // 自動切り替えの間隔（4秒）

  // 指定インデックスのスライドに切り替える関数
  function switchSlide(index) {
    mainImages.forEach(img => img.classList.remove('isActive'));
    thumbItems.forEach(thumb => thumb.classList.remove('isActive'));

    mainImages[index].classList.add('isActive');
    thumbItems[index].classList.add('isActive');
    currentIndex = index;
  }

  // 次のスライドへ進む（ループ）
  function nextSlide() {
    const nextIndex = (currentIndex + 1) % mainImages.length;
    switchSlide(nextIndex);
  }

  // タイマーを開始
  function startTimer() {
    stopTimer();
    slideTimer = setInterval(nextSlide, intervalTime);
  }

  // タイマーを停止
  function stopTimer() {
    if (slideTimer) {
      clearInterval(slideTimer);
    }
  }

  // サムネイルクリックイベントの設定
  thumbItems.forEach(thumb => {
    thumb.addEventListener('click', (e) => {
      const index = parseInt(e.currentTarget.dataset.index, 10);
      switchSlide(index);
      startTimer(); // クリック後はタイマーをリセットして再スタート
    });
  });

  // 初期タイマー起動
  startTimer();
});

/* ==========================================
   ドロワーメニューの開閉（全ページ共通）
   ========================================== */
document.addEventListener('DOMContentLoaded', () => {
  const drawer = document.querySelector('.jsDrawer');
  const toggleBtns = document.querySelectorAll('.jsDrawerToggle');
  const drawerLinks = document.querySelectorAll('.drawer__link');

  // メニュー開閉切替
  const toggleDrawer = () => {
    const isOpen = drawer.classList.toggle('isOpen');
    drawer.setAttribute('aria-hidden', !isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : ''; // 背景スクロール固定
  };

  toggleBtns.forEach(btn => {
    btn.addEventListener('click', toggleDrawer);
  });

  // ページ内リンククリック時にメニューを閉じる
  drawerLinks.forEach(link => {
    link.addEventListener('click', () => {
      if (drawer.classList.contains('isOpen')) {
        toggleDrawer();
      }
    });
  });
});

/* ==========================================
   アクティビティページのタブ切り替え
   ========================================== */
document.addEventListener('DOMContentLoaded', () => {
  const tabItems = document.querySelectorAll('.actTabs__navItem');
  const tabPanels = document.querySelectorAll('.actTabs__panel');

  if (tabItems.length > 0) {
    tabItems.forEach(item => {
      item.addEventListener('click', () => {
        const targetTab = item.dataset.tab;

        // タブボタンのアクティブ切り替え
        tabItems.forEach(tab => tab.classList.remove('isActive'));
        item.classList.add('isActive');

        // パネルの非表示とアクティブ切り替え（CSSの opacity トランジションと連動）
        tabPanels.forEach(panel => {
          panel.classList.remove('isActive');
          if (panel.id === `tab-${targetTab}`) {
            panel.classList.add('isActive');
          }
        });
      });
    });
  }
});

/* ==========================================
   画像モーダル（FOODSページ）
   ========================================== */
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.querySelector('.jsModal');

  // モーダルが無いページでは何もしない
  if (!modal) return;

  const modalImg = modal.querySelector('.jsModalImg');
  const openTriggers = document.querySelectorAll('.jsModalOpen');
  const closeTriggers = modal.querySelectorAll('.jsModalClose');

  // モーダルを開く
  const openModal = (trigger) => {
    const src = trigger.dataset.modalSrc; // data-modal-src 属性から拡大表示する画像パスを取得
    const innerImg = trigger.querySelector('img');

    modalImg.src = src;
    modalImg.alt = innerImg ? innerImg.alt : '';

    modal.classList.add('isOpen');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';  // 背景のスクロールを止める
  };

  // モーダルを閉じる
  const closeModal = () => {
    modal.classList.remove('isOpen');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  // 各画像にクリックイベントを設定
  openTriggers.forEach((trigger) => {
    trigger.addEventListener('click', () => {
      openModal(trigger);
    });
  });

  // 閉じるボタンとオーバーレイのクリックで閉じる
  closeTriggers.forEach((btn) => {
    btn.addEventListener('click', closeModal);
  });

  // Escキーでも閉じる
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeModal();
    }
  });
});

/* ==========================================
   スクロール連動フェードイン（IntersectionObserver）
   ------------------------------------------
   .fadeIn / .fadeUp / .fadeDown / .fadeLeft / .fadeRight
   が付いた要素が画面に入ったら isShown を付与します。
========================================== */
(function () {
  var targets = document.querySelectorAll(
    '.fadeIn, .fadeUp, .fadeDown, .fadeLeft, .fadeRight'
  );
  if (!targets.length) return;

  // IntersectionObserver 非対応環境では最初から表示します
  if (!('IntersectionObserver' in window)) {
    targets.forEach(function (el) {
      el.classList.add('isShown');
    });
    return;
  }

  var observer = new IntersectionObserver(
    function (entries, obs) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('isShown');
        obs.unobserve(entry.target); // 一度表示したら監視を解除します
      });
    },
    {
      root: null,
      rootMargin: '0px 0px -15% 0px', // 画面下から15%入った時点で発火します
      threshold: 0
    }
  );

  targets.forEach(function (el) {
    observer.observe(el);
  });
})();