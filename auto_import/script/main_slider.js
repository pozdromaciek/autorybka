document.addEventListener('DOMContentLoaded', () => {
    const main_sliderContainer = document.querySelector('.main_slider-container');
    const main_slides = document.querySelectorAll('.main_slide');
    let currentIndex = 0;

    function moveMainSlider() {
        currentIndex++;
        if (currentIndex >= main_slides.length - 2) {
            currentIndex = 0; // Wracamy na początek
        }
        const offset = -currentIndex * main_slides[0].offsetWidth;
        main_sliderContainer.style.transform = `translateX(${offset}px)`;
    }
    moveMainSlider()
    setInterval(moveMainSlider, 10000); // Przesuwamy co 3 sekundy
});