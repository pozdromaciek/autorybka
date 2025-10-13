  document.addEventListener("DOMContentLoaded", () => {
    const tiles = document.querySelectorAll('.service-tile');

    tiles.forEach(tile => {
      tile.addEventListener('click', () => {
        // Zamykamy inne kafelki
        tiles.forEach(t => { if(t !== tile) t.classList.remove('active'); });
        // Przełączamy ten kafelek
        tile.classList.toggle('active');
      });
    });
  });