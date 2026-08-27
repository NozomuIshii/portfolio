'use strict';

/* ============================================================
   Cafe de Lumière — script.js
     01. ヘッダーの背景切り替え（スクロール）
     02. ハンバーガーメニューの開閉
     03. スクロールで要素をフェードイン
     04. ページトップボタンの表示切り替え
     05. ギャラリーのサムネイル切り替え（HOMEのみ）
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ==========================================================
     01. ヘッダーの背景切り替え
     100px 以上スクロールしたらヘッダーに .isFixed を付けて背景を出す
     ========================================================== */
  const siteHeader = document.querySelector('.siteHeader');
  const HEADER_SWITCH_POINT = 100;

  /* ==========================================================
     04. ページトップボタン
     こちらも同じスクロール量で判定するため、変数を先に取得しておく
     ========================================================== */
  const pageTopBtn = document.querySelector('.jsPageTop');
  const PAGETOP_SHOW_POINT = 400;

  // スクロールのたびに走る処理は1つの関数にまとめる（イベントを増やさないため）
  const onScroll = () => {
    const scrollY = window.scrollY;

    if (siteHeader) {
      siteHeader.classList.toggle('isFixed', scrollY > HEADER_SWITCH_POINT);
    }

    if (pageTopBtn) {
      pageTopBtn.classList.toggle('isVisible', scrollY > PAGETOP_SHOW_POINT);
    }
  };

  // passive: true を付けると、スクロール処理がブラウザの描画をブロックしなくなる
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll(); // 読み込み直後（途中から表示された場合）にも一度判定する

  // ページトップボタンを押したら先頭へ戻る
  if (pageTopBtn) {
    pageTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }


  /* ==========================================================
     02. ハンバーガーメニューの開閉
     ========================================================== */
  const menuToggle = document.querySelector('.jsMenuToggle');
  const drawerMenu = document.querySelector('.jsDrawer');

  if (menuToggle && drawerMenu) {

    // 開く／閉じるをまとめて扱う関数。isOpen が true なら開く
    const setDrawer = (isOpen) => {
      menuToggle.classList.toggle('isOpen', isOpen);
      drawerMenu.classList.toggle('isOpen', isOpen);
      document.body.classList.toggle('isNavOpen', isOpen); // 背後のスクロールを止める
      menuToggle.setAttribute('aria-expanded', String(isOpen)); // 支援技術に状態を伝える
      menuToggle.setAttribute('aria-label', isOpen ? 'メニューを閉じる' : 'メニューを開く');
    };

    menuToggle.addEventListener('click', () => {
      // 現在開いているかどうかを見て、反転させる
      const isOpen = drawerMenu.classList.contains('isOpen');
      setDrawer(!isOpen);
    });

    // ドロワー内のリンクを押したら閉じる（同一ページ内アンカーでも閉じるように）
    drawerMenu.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => setDrawer(false));
    });

    // Esc キーで閉じる
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && drawerMenu.classList.contains('isOpen')) {
        setDrawer(false);
        menuToggle.focus(); // フォーカスをボタンに戻す
      }
    });

    // 1024px 以上に広げた時、開いたままだと body のスクロールが止まったままになるので解除する
    const pcMediaQuery = window.matchMedia('(min-width: 1024px)');
    pcMediaQuery.addEventListener('change', (event) => {
      if (event.matches) setDrawer(false);
    });
  }


  /* ==========================================================
     03. スクロールで要素をフェードイン
     .fadeIn が画面に入ったら .isVisible を付ける。一度出したら監視を解除する
     ========================================================== */
  const fadeElements = document.querySelectorAll('.fadeIn');

  if (fadeElements.length > 0 && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('isVisible');
        obs.unobserve(entry.target); // 表示済みの要素は監視をやめる
      });
    }, {
      threshold: 0.15, // 要素の15%が見えたら発火
    });

    fadeElements.forEach((element) => observer.observe(element));
  } else {
    // IntersectionObserver が使えない環境では、最初から表示しておく
    fadeElements.forEach((element) => element.classList.add('isVisible'));
  }


  /* ==========================================================
     05. ギャラリーのサムネイル切り替え（HOMEのみ）
     サムネイルを押すと、メイン画像をそのサムネイルの拡大版に差し替える
     ========================================================== */
  const galleryMainImage = document.querySelector('.jsGalleryMain');
  const galleryThumbs = document.querySelectorAll('.jsGalleryThumb');

  if (galleryMainImage && galleryThumbs.length > 0) {
    galleryThumbs.forEach((thumb) => {
      thumb.addEventListener('click', () => {

        // data-large 属性に大きい画像のパス、data-alt に代替テキストを入れておく
        const largeSrc = thumb.dataset.large;
        const largeAlt = thumb.dataset.alt || '';
        if (!largeSrc) return;

        // 一度透明にしてから差し替えると、切り替わりが滑らかに見える
        galleryMainImage.classList.add('isSwitching');

        setTimeout(() => {
          galleryMainImage.src = largeSrc;
          galleryMainImage.alt = largeAlt;
          galleryMainImage.classList.remove('isSwitching');
        }, 300); // CSS の --transitionBase（0.3s）と同じ長さ

        // 選択中のサムネイルだけ .isActive にする
        galleryThumbs.forEach((item) => {
          item.classList.remove('isActive');
          item.setAttribute('aria-current', 'false');
        });
        thumb.classList.add('isActive');
        thumb.setAttribute('aria-current', 'true');
      });
    });
  }

  /* ==========================================================
     06. MENU：カテゴリータブ（MENUページのみ）
     タブを押すと、そのカテゴリーのセクションだけを表示する
     ========================================================== */
  const categoryTabs = document.querySelectorAll('.jsCategoryTab');
  const categoryPanels = document.querySelectorAll('.jsCategoryPanel');

  if (categoryTabs.length > 0 && categoryPanels.length > 0) {
    categoryTabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.category; // 'all' / 'coffee' / 'food' など

        // 押されたタブだけを選択状態にする
        categoryTabs.forEach((item) => {
          const isCurrent = item === tab;
          item.classList.toggle('isActive', isCurrent);
          item.setAttribute('aria-selected', String(isCurrent));
        });

        // 'all' のときは全部、それ以外は一致するセクションだけ表示
        // data-category は "sweets drink" のように複数持てる
        categoryPanels.forEach((panel) => {
          const categories = (panel.dataset.category || '').split(/\s+/);
          panel.hidden = !(target === 'all' || categories.includes(target));
        });
      });
    });
  }

  /* ==========================================================
     07. パララックス
     .jsParallax を、入れ物（親要素）の中でスクロール量に応じて
     上下にずらす。動かす量は「はみ出している分」までに制限するので、
     速度をいくつにしても隙間ができない
     ========================================================== */
  const parallaxElements = document.querySelectorAll('.jsParallax');
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (parallaxElements.length > 0 && !prefersReducedMotion) {
    let ticking = false;

    const updateParallax = () => {
      const viewportCenter = window.innerHeight / 2;

      parallaxElements.forEach((element) => {
        const holder = element.parentElement;
        const rect = holder.getBoundingClientRect();

        // 画面外のものは計算しない
        if (rect.bottom < 0 || rect.top > window.innerHeight) return;

        // 写真は画面より上下4remずつ大きい。そのはみ出し分が動かせる上限
        const limit = Math.max(0, (element.offsetHeight - window.innerHeight) / 2);
        const speed = parseFloat(element.dataset.speed) || 0.15;

        // 要素の中心が画面中央からどれだけ離れているか
        const distance = viewportCenter - (rect.top + rect.height / 2);
        const offset = Math.max(-limit, Math.min(limit, distance * speed));

        element.style.transform = `translate3d(0, ${offset}px, 0)`;
      });

      ticking = false;
    };

    // スクロールのたびに計算せず、次の描画タイミングにまとめる
    const onScrollParallax = () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(updateParallax);
    };

    window.addEventListener('scroll', onScrollParallax, { passive: true });
    window.addEventListener('resize', onScrollParallax);
    updateParallax();
  }

});
