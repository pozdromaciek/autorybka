<?php
// pojazd.php
include 'db.php';

// Sprawdzenie, czy ID auta jest przekazane
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Nieprawidłowe ID auta.");
}

$id_auta = intval($_GET['id']);

// Pobranie danych auta
$stmt = $conn->prepare("SELECT * FROM auta WHERE id_auta = ?");
$stmt->bind_param("i", $id_auta);
$stmt->execute();
$auto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$auto) {
    die("Nie znaleziono auta.");
}

// Pobranie wszystkich zdjęć auta
$stmt = $conn->prepare("SELECT sciezka FROM zdjecia WHERE id_auta = ? ORDER BY kolejnosc ASC, id_zdjecia ASC");
$stmt->bind_param("i", $id_auta);
$stmt->execute();
$zdjecia = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Przygotowanie absolutnego URL dla Open Graph
$base_url = "https://" . $_SERVER['HTTP_HOST'] . "/formularz/";
if (!empty($zdjecia)) {
    // szukamy zdjęcia głównego
    $glowne = null;
    foreach ($zdjecia as $z) {
        if (!empty($z['glowne']) && $z['glowne'] == 1) {
            $glowne = $z['sciezka'];
            break;
        }
    }

    // jeśli jest główne, używamy, jeśli nie - pierwsze
    $og_image = $base_url . ($glowne ?? $zdjecia[0]['sciezka']);
}

// Przykład meta tagów Open Graph
$og_title = htmlspecialchars( $auto['rok_produkcji']. ' ' .$auto['marka'] . ' ' . $auto['model'].' 💵 '.$auto['cena'].' PLN');
$og_description = htmlspecialchars(substr($auto['opis'], 0, 200)); // ograniczamy do 200 znaków
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Meta Open Graph -->
<meta property="og:title" content="<?= $og_title ?>" />
<meta property="og:description" content="<?= $og_description ?>" />
<meta property="og:image" content="<?= $og_image ?>" />
<meta property="og:type" content="website" />
<meta property="og:url" content="https://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>" />
<meta property="og:site_name" content="Ryba Auto Import" />

<!-- Opcjonalnie Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $og_title ?>">
<meta name="twitter:description" content="<?= $og_description ?>">
<meta name="twitter:image" content="<?= $og_image ?>">
<link rel="icon" href="../img/ikona.ico?v=2" type="image/x-icon" />
<link rel="shortcut icon" href="../img/ikona.ico?v=2" type="image/x-icon" /> 
<title><?php echo htmlspecialchars($auto['marka'] . ' ' . $auto['model']); ?></title>
    <link rel="stylesheet" href="pojazd.css">
    <link rel="stylesheet" href="../style/navbar.css">
    <link rel="stylesheet" href="../style/root.css">
            <link rel="icon" href="../img/ikona.ico?v=2" type="image/x-icon" />
        <link rel="shortcut icon" href="../img/ikona.ico?v=2" type="image/x-icon" />  
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bowlby+One+SC&family=Caveat:wght@400..700&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Michroma&family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="../script/navbar.js"></script>
</head>
<body>
    <nav class="navbar fade-in-3s"> 
            <div class="navbar-brand"><img src="../img/logo.png" alt=""></div>
            <div class="navbar-toggle" id="navbar-toggle">
                &#9776;
            </div>
            <ul class="navbar-menu" id="navbar-menu">
                <li><a href="../index.html">Strona Główna</a></li>
                <li><a href="../uslugi.html">Usługi</a></li>
                <li><a href="../ogloszenia/" class="navbar-active">Oferta</a></li>
                <li><a href="../onas.html">O nas</a></li>
                <li><a href="../kontakt.html">Kontakt</a></li>
            </ul>
        </nav>
<div class="container">

<div class="auto-glowne">
    <?php if (count($zdjecia) > 0): ?>
        <img id="glowne-zdjecie" src="../formularz/<?php echo htmlspecialchars($zdjecia[0]['sciezka']); ?>" alt="">
    <?php else: ?>
        <p>Brak zdjęć</p>
    <?php endif; ?>
</div>

<?php if (count($zdjecia) > 1): ?>
<div class="miniaturki">
    <?php foreach($zdjecia as $z): ?>
        <img src="../formularz/<?php echo htmlspecialchars($z['sciezka']); ?>" 
             onclick="document.getElementById('glowne-zdjecie').src=this.src" alt="">
    <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="najwazniejsze">
<?php
$parametry = [
    ['ikona'=>'speed', 'nazwa'=>'Przebieg', 'wartosc'=>number_format($auto['przebieg'],0,' ',' ') . ' km'],
    ['ikona'=>'local_gas_station', 'nazwa'=>'Rodzaj paliwa', 'wartosc'=>$auto['rodzaj_paliwa']],
    ['ikona'=>'settings', 'nazwa'=>'Skrzynia biegów', 'wartosc'=>$auto['skrzynia_biegow']],
    ['ikona'=>'directions_car', 'nazwa'=>'Typ nadwozia', 'wartosc'=>$auto['nadwozie']],
    ['ikona'=>'compress', 'nazwa'=>'Pojemność skokowa', 'wartosc'=>number_format($auto['pojemnosc'],0,' ',' ') . ' cm3'],
    ['ikona'=>'bolt', 'nazwa'=>'Moc', 'wartosc'=>$auto['moc'] . ' KM'],
];

foreach($parametry as $p):
?>
    <div class="param-box">
        <span class="material-icons ikona"><?= $p['ikona'] ?></span>
        <div class="nazwa"><?= htmlspecialchars($p['nazwa']) ?></div>
        <div class="wartosc"><?= htmlspecialchars($p['wartosc']) ?></div>
    </div>
<?php endforeach; ?>
</div>

<div class="auto-dane">
    <h1><?php echo htmlspecialchars($auto['marka'] . ' ' . $auto['model']); ?></h1>
    <p class="auto-cena"><?php echo number_format($auto['cena'], 0, ',', ' '); ?> PLN</p>
    <p><?php echo nl2br(htmlspecialchars($auto['opis'])); ?></p>
</div>

</div>
<footer class="footer">
    <div class="footer-content">
      <div class="footer-left">
        <h2>Rybka Auto Import</h2>
        <p>ul. Zwycięstwa 63, 44-230 Stanowice</p>
        <p>tel. <a href="tel:+48665466673">+48 665 466 673</a></p>
        <p>e-mail: <a href="mailto:kontakt@rybkaautohub.pl">kontakt@autorybka.pl</a></p>
      </div>
    <footer>
        <div class="footer-right">
        <a href="https://instagram.com/auto_import" target="_blank" aria-label="Instagram Auto Import">
          <!-- Ikona Instagram -->
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M7.75 2A5.75 5.75 0 0 0 2 7.75v8.5A5.75 5.75 0 0 0 7.75 22h8.5A5.75 5.75 0 0 0 22 16.25v-8.5A5.75 5.75 0 0 0 16.25 2h-8.5Zm0 1.5h8.5a4.25 4.25 0 0 1 4.25 4.25v8.5a4.25 4.25 0 0 1-4.25 4.25h-8.5A4.25 4.25 0 0 1 3.5 16.25v-8.5A4.25 4.25 0 0 1 7.75 3.5ZM12 7a5 5 0 1 0 0 10a5 5 0 0 0 0-10Zm0 1.5a3.5 3.5 0 1 1 0 7a3.5 3.5 0 0 1 0-7Zm5.25-.75a.75.75 0 1 1 0 1.5a.75.75 0 0 1 0-1.5Z"/></svg>
        </a>

        <a href="https://facebook.com/auto_import" target="_blank" aria-label="Facebook Auto Import">
          <!-- Ikona Facebook -->
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 9H16V6h-2.5c-1.933 0-3.5 1.567-3.5 3.5V12H8v3h2.5v7h3v-7H16l.5-3h-3V9.5c0-.276.224-.5.5-.5Z"/></svg>
        </a>
      </div>
    </div>
    <div class="footer-bottom">© 2025 AutoRybka. Wszystkie prawa zastrzeżone. | Design and develop <a href="https://pozdromaciek.github.io/_m.d_code/">_m.d_code</a> </div>
  </footer>
</body>
</html>
