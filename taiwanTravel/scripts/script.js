'use strict';

// ==========================================
// 1. HTMLから動かしたい要素（パーツ）を捕まえる
// ==========================================
const hamburger = document.querySelector('.hamburgerJs'); 
const drawer = document.querySelector('.drawerJs');       
const closeBtn = document.querySelector('.closeBtnJs');   

// ==========================================
// 2. ボタンを押したときの命令（イベント）を設定する
// ==========================================

// ドロワー関連の要素が揃っている場合のみ実行（エラー防止）
if (hamburger && drawer && closeBtn) {
    // 【開く処理】ハンバーガーボタンがクリックされたら実行
    hamburger.addEventListener('click', function() {
        drawer.classList.add('isOpen');
    });

    // 【閉じる処理】メニュー内の「×ボタン」がクリックされたら実行
    closeBtn.addEventListener('click', function() {
        drawer.classList.remove('isOpen');
    });

    // メニューの外側をタップしたときも閉じる仕組み
    document.addEventListener('click', function(event) {
        if (drawer.classList.contains('isOpen') && !drawer.contains(event.target) && !hamburger.contains(event.target)) {
            drawer.classList.remove('isOpen');
        }
    });
}


// ==========================================
// 3. ギャラリースライダーの仕組み
// ==========================================
const slideList = document.getElementById('slideContainer');
const dots = document.querySelectorAll('.dot');
const btnRight = document.getElementById('pageDotR');
const btnLeft = document.getElementById('pageDotL');

let currentIndex = 1;
const totalSlides = 5;
let isAnimating = false;
let sliderTimer;

// スライダーの必須要素がある場合のみ実行
if (slideList && dots.length > 0) {

    function updateDots() {
        for (let i = 0; i < dots.length; i = i + 1) {
            dots[i].classList.remove('active');
        }
        if (dots[currentIndex - 1]) {
            dots[currentIndex - 1].classList.add('active');
        }
    }

    function autoSlider() {
        if (isAnimating) return;
        isAnimating = true;

        const firstItem = slideList.querySelector('.sliderItem');
        if (!firstItem) return;
        const slideWidth = firstItem.offsetWidth;

        slideList.style.transition = 'transform 0.5s ease';
        slideList.style.transform = 'translateX(-' + slideWidth + 'px)';
        
        setTimeout(function() {
            slideList.appendChild(firstItem);
            slideList.style.transition = 'none';
            slideList.style.transform = 'translateX(0px)';
            isAnimating = false;
        }, 500); 

        if (currentIndex === totalSlides) {
            currentIndex = 1; 
        } else {
            currentIndex = currentIndex + 1;
        }
        
        updateDots();
    }

    // 最初のアニメーションタイマーを開始
    sliderTimer = setInterval(autoSlider, 3000);

    // 【右ボタン（次へ）】
    if (btnRight) {
        btnRight.addEventListener('click', function() {
            if (isAnimating) return;
            // タイマーを一度リセットして、クリック直後の猶予を作る
            clearInterval(sliderTimer);
            autoSlider();
            sliderTimer = setInterval(autoSlider, 3000);
        });
    }

    // 【左ボタン（前へ）】
    if (btnLeft) {
        btnLeft.addEventListener('click', function() {
            if (isAnimating) return;

            clearInterval(sliderTimer);
            isAnimating = true;

            const lastItem = slideList.lastElementChild;
            const firstItem = slideList.querySelector('.sliderItem');
            if (!lastItem || !firstItem) return;
            const slideWidth = firstItem.offsetWidth;

            slideList.style.transition = 'none';
            slideList.style.transform = 'translateX(-' + slideWidth + 'px)';
            slideList.insertBefore(lastItem, firstItem);

            setTimeout(function() {
                slideList.style.transition = 'transform 0.5s ease';
                slideList.style.transform = 'translateX(0px)';
                isAnimating = false;
            }, 50);

            if (currentIndex === 1) {
                currentIndex = totalSlides;
            } else {
                currentIndex = currentIndex - 1;
            }
            updateDots();

            sliderTimer = setInterval(autoSlider, 3000);
        });
    }
}