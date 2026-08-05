document.addEventListener('DOMContentLoaded', () => {
  const drawerTrigger = document.getElementById('drawerTrigger');
  const drawerClose = document.getElementById('drawerClose');
  const drawerMenu = document.getElementById('drawerMenu');

  // 三本線ボタンをクリックしたらドロワーを開く
  if (drawerTrigger && drawerMenu) {
    drawerTrigger.addEventListener('click', () => {
      drawerMenu.classList.add('isOpen');
      // ドロワーが開いている間、背後の画面スクロールを止めたい場合は以下を有効に
      document.body.style.overflow = 'hidden';
    });
  }

  // バツ印ボタンをクリックしたらドロワーを閉じる
  if (drawerClose && drawerMenu) {
    drawerClose.addEventListener('click', () => {
      drawerMenu.classList.remove('isOpen');
      // スクロール制限を解除
      document.body.style.overflow = '';
    });
  }
});