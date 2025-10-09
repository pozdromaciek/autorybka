document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.querySelector('.navbar');
    const navbarToggle = document.getElementById('navbar-toggle');
    const navbarMenu = document.getElementById('navbar-menu');
    const scrollThreshold = 50; // Po jakiej wartości przewinięcia zmienić tło

    function onScroll() {
        if (window.scrollY > scrollThreshold) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }

    function toggleMenu() {
        navbarMenu.classList.toggle('active');
        // Dodaj klasę scrolled, jeśli menu jest otwarte, lub usuń, jeśli zamknięte
        navbar.classList.toggle('scrolled', navbarMenu.classList.contains('active'));
    }

    function closeMenu() {
        navbarMenu.classList.remove('active');
        // Usuń klasę scrolled, gdy menu jest zamknięte
        navbar.classList.remove('scrolled');
    }

    // Nasłuchujemy na przewijanie strony
    window.addEventListener('scroll', onScroll);

    // Nasłuchujemy na kliknięcie w przycisk hamburgera
    navbarToggle.addEventListener('click', toggleMenu);

    // Nasłuchujemy na kliknięcia w linki w menu
    document.querySelectorAll('.navbar-menu a').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            // Sprawdź, czy link jest wewnętrzny (zaczyna się od #)
            if (this.getAttribute('href').startsWith('#')) {
                e.preventDefault(); // Blokujemy domyślne działanie tylko dla wewnętrznych linków
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);

                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - navbar.offsetHeight,
                        behavior: 'smooth'
                    });
                    closeMenu(); // Zamyka menu po kliknięciu w link
                }
            }
        });
    });

    // Wywołujemy funkcję onScroll na załadowanie strony, aby ustawić początkowy stan
    onScroll();
});