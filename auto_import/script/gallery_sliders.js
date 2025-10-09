document.querySelectorAll('.slider').forEach(slider => {
    let currentIndex = 0;
    let autoScrollInterval = null; // Przechowuje ID interwału do zatrzymania

    // Przycisk nawigacyjny lewy
    const prevButton = slider.querySelector('.slider-nav.left');
    // Przycisk nawigacyjny prawy
    const nextButton = slider.querySelector('.slider-nav.right');
    // Przycisk play/pause (ikona)
    const playButton = slider.querySelector('.slide');

    // Kontener, który zawiera wszystkie slajdy
    const sliderContainer = slider.querySelector('.slider-container');
    // Wszystkie slajdy w tym kontenerze
    const slides = slider.querySelectorAll('.slide');
    
    const totalSlides = slides.length;

    // Funkcja do przeskakiwania slajdów
    function moveSlide(direction) {
        currentIndex += direction;

        // Zapobiegamy przejściu poniżej pierwszego slajdu (lewy przycisk)
        if (currentIndex < 0) {
            currentIndex = totalSlides - 1; // Przejście na ostatni slajd
        }

        // Zapobiegamy przejściu poza ostatni slajd (prawy przycisk)
        if (currentIndex >= totalSlides -1) {
            currentIndex = 0; // Przejście na pierwszy slajd
            stopAutoScroll()
        }

        // Obliczamy przesunięcie kontenera na podstawie szerokości slajdów
        const slideWidth = slides[0].offsetWidth; // Szerokość jednego slajdu
        const newTransformValue = -currentIndex * slideWidth;
        sliderContainer.style.transform = `translateX(${newTransformValue}px)`;
    }

    // Funkcja do automatycznego przewijania
    function startAutoScroll() {
        autoScrollInterval = setInterval(() => {
            moveSlide(1); // Przewijamy slajdy w prawo
        }, 3000); // Co 1 sekundę
    }

    // Funkcja do zatrzymywania automatycznego przewijania
    function stopAutoScroll() {
        clearInterval(autoScrollInterval); // Zatrzymujemy interwał
        autoScrollInterval = null;
    }

    // Funkcja do przełączania między startem a stopem autoscroll
    function toggleAutoScroll() {
        if (autoScrollInterval) {
            stopAutoScroll(); // Jeśli interwał działa, zatrzymujemy go
            //playButton.innerHTML = '<i class="fas fa-play"></i>'; // Ikona play
        } else {
            moveSlide(1); // Przesuwamy o jeden slajd natychmiast
            startAutoScroll(); // Zaczynamy autoscroll
            //playButton.innerHTML = '<i class="fas fa-pause"></i>'; // Ikona pause
        }
    }

    // Nasłuchiwanie kliknięć na przyciski nawigacyjne
    prevButton.addEventListener('click', () => moveSlide(-1));
    nextButton.addEventListener('click', () => moveSlide(1));

    // Nasłuchiwanie kliknięcia na przycisk play/pause
    playButton.addEventListener('click', toggleAutoScroll);
});
