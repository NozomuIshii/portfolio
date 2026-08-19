"use strict";

document.addEventListener('DOMContentLoaded', () => {
  const loader = document.getElementById('loading');
  if (!loader) return;
  
  // ロゴの演出1周分（3秒）が経過したら、フェードアウト用のクラスを付与
  setTimeout(() => {
    loader.classList.add('loaded'); // クラスを付与してCSSのフェードアウトを起動
    
    // 1秒かけてフワッと消えるのを待ってから、裏面に叩き落として操作を完全に通す
    setTimeout(() => {
      loader.style.display = 'none';
      loader.style.zIndex = '-99999';
      console.log("演出終了：ローディング画面を完全に排除しました。");
    }, 1000); // 1000ミリ秒 ＝ 1秒（フェードアウト時間）
    
  }, 3000); // 3000ミリ秒 ＝ 3秒（1周分の時間）
});