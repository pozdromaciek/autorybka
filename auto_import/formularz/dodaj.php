<?php
// Włączamy raportowanie błędów
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('db.php');

$success_message = '';
$error_message = '';
$current_year = date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marka = $_POST['marka'] ?? '';
    $model = $_POST['model'] ?? '';
    $rok = (int)($_POST['rok_produkcji'] ?? 0);
    $przebieg = (int)($_POST['przebieg'] ?? 0);
    $moc = (int)($_POST['moc'] ?? 0);
    $pojemnosc = (int)($_POST['pojemnosc'] ?? 0);
    $rodzaj_paliwa = $_POST['rodzaj_paliwa'] ?? '';
    $nadwozie = $_POST['nadwozie'] ?? '';
    $skrzynia_biegow = $_POST['skrzynia_biegow'] ?? '';
    $wersja = $_POST['wersja'] ?? '';
    $generacja = $_POST['generacja'] ?? '';
    $liczba_drzwi = (int)($_POST['liczba_drzwi'] ?? 0);
    $naped = $_POST['napęd'] ?? '';
    $cena = (float)($_POST['cena'] ?? 0);
    $opis = $_POST['opis'] ?? '';

    // Wstaw auto do bazy
    $stmt = $conn->prepare("INSERT INTO auta (marka, model, rok_produkcji, przebieg, moc, pojemnosc, rodzaj_paliwa, nadwozie, skrzynia_biegow, wersja, generacja, liczba_drzwi, napęd, cena, opis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        $error_message = "Błąd przygotowania zapytania: " . $conn->error;
    } else {
        $stmt->bind_param("ssiiiissssssids", $marka, $model, $rok, $przebieg, $moc, $pojemnosc, $rodzaj_paliwa, $nadwozie, $skrzynia_biegow, $wersja, $generacja, $liczba_drzwi, $naped, $cena, $opis);
        if ($stmt->execute()) {
            $id_auta = $stmt->insert_id;
            $stmt->close();

            // Upload zdjęć z możliwością wyboru głównego
            if (!empty($_FILES['zdjecia']['name'][0])) {
                $upload_dir = 'uploads/'.uniqid().'/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                foreach ($_FILES['zdjecia']['tmp_name'] as $key => $tmp_name) {
                    $file_name = $_FILES['zdjecia']['name'][$key];
                    $file_tmp = $_FILES['zdjecia']['tmp_name'][$key];
                    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_name = uniqid() . "." . $ext;
                    $destination = $upload_dir . $new_name;

                    if (move_uploaded_file($file_tmp, $destination)) {
                        $glowne_val = (isset($_POST['glowne']) && $_POST['glowne'] == $key) ? 1 : 0;
                        $stmt2 = $conn->prepare("INSERT INTO zdjecia (id_auta, sciezka, glowne, data_dodania) VALUES (?, ?, ?, NOW())");
                        if ($stmt2) {
                            $stmt2->bind_param("isi", $id_auta, $destination, $glowne_val);
                            $stmt2->execute();
                            $stmt2->close();
                        }
                    }
                }
            }

            $success_message = "Auto zostało dodane!";
        } else {
            $error_message = "Błąd przy dodawaniu auta: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<link rel="icon" href="../img/ikona.ico?v=2" type="image/x-icon" />
<link rel="shortcut icon" href="../img/ikona.ico?v=2" type="image/x-icon" />  
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dodaj Auto</title>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Bowlby+One+SC&family=Caveat:wght@400..700&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Michroma&family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="dodaj.css">
</head>
<body>
<div class="container">
    <form method="post" enctype="multipart/form-data">
        <a href="/formularz" class="btn-add">Powrót do panelu</a>
        <h1>Dodaj Auto</h1>

        <?php if($success_message) echo "<p class='success'>{$success_message}</p>"; ?>
        <?php if($error_message) echo "<p class='error'>{$error_message}</p>"; ?>

        <div class="input-group">
            <input type="text" name="marka" placeholder="Marka" required>
        </div>
        <div class="input-group">
            <input type="text" name="model" placeholder="Model" required>
        </div>
        <div class="input-group">
            <input type="number" name="rok_produkcji" placeholder="Rok produkcji" min="1920" max="<?= $current_year ?>" required>
        </div>
        <div class="input-group">
            <input type="number" name="przebieg" placeholder="Przebieg" min="0" required>
        </div>
        <div class="input-group">
            <input type="number" name="moc" placeholder="Moc (KM)" min="0" required>
        </div>
        <div class="input-group">
            <input type="number" name="pojemnosc" placeholder="Pojemność skokowa (cm3)" min="0" required>
        </div>
        <div class="input-group">
            <select name="rodzaj_paliwa" required>
                <option value="">Rodzaj paliwa</option>
                <option value="benzyna">Benzyna</option>
                <option value="diesel">Diesel</option>
                <option value="benzyna+lpg">Benzyna+LPG</option>
                <option value="elektryczny">Elektryczny</option>
                <option value="hybryda">Hybryda</option>
            </select>
        </div>
        <div class="input-group">
            <select name="nadwozie" required>
                <option value="">Typ nadwozia</option>
                <option value="kombi">Kombi</option>
                <option value="sedan">Sedan</option>
                <option value="suv">SUV</option>
                <option value="coupe">Coupe</option>
                <option value="hatchback">Hatchback</option>
                <option value="compact">Compact</option>
            </select>
        </div>
        <div class="input-group">
            <select name="skrzynia_biegow" required>
                <option value="">Skrzynia biegów</option>
                <option value="automatyczna">Automatyczna</option>
                <option value="manualna">Manualna</option>
            </select>
        </div>
        <div class="input-group">
            <input type="text" name="wersja" placeholder="Wersja">
        </div>
        <div class="input-group">
            <input type="text" name="generacja" placeholder="Generacja">
        </div>
        <div class="input-group">
            <select name="liczba_drzwi" required>
                <option value="">Liczba drzwi</option>
                <option value="3">3</option>
                <option value="5">5</option>
            </select>
        </div>
        <div class="input-group">
            <select name="naped" required>
                <option value="">Napęd</option>
                <option value="na przód">Na przód</option>
                <option value="na tył">Na tył</option>
                <option value="4x4">4x4</option>
            </select>
        </div>
        <div class="input-group">
            <input type="number" name="cena" placeholder="Cena (PLN)" min="0" required>
        </div>
        <div class="input-group">
            <textarea name="opis" placeholder="Opis auta" required></textarea>
        </div>
     <div class="input-group">
            <label>Zdjęcia (zaznacz główne):</label>
            <div id="zdjecia-container">
                <input type="file" name="zdjecia[]" multiple accept="image/*" onchange="previewFiles(this)">
                <div id="preview"></div>
            </div>
        </div>

        <button type="submit">Dodaj auto</button>
    </form>

        <script>
function previewFiles(input) {
    const preview = document.getElementById('preview');
    preview.innerHTML = '';
    const files = input.files;
    for (let i = 0; i < files.length; i++) {
        const reader = new FileReader();
        const div = document.createElement('div');
        div.style.display = 'inline-block';
        div.style.margin = '5px';
        div.style.position = 'relative';

        const radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = 'glowne';
        radio.value = i;
        radio.style.position = 'absolute';
        radio.style.top = '5px';
        radio.style.left = '5px';
        radio.checked = i === 0; // domyślnie pierwsze zdjęcie główne

        const img = document.createElement('img');
        img.style.width = '120px';
        img.style.height = '90px';
        img.style.objectFit = 'cover';
        img.style.border = '1px solid #ccc';
        img.style.borderRadius = '4px';

        reader.onload = (e) => img.src = e.target.result;
        reader.readAsDataURL(files[i]);

        div.appendChild(radio);
        div.appendChild(img);
        preview.appendChild(div);
    }
}
</script>
</div>

</body>
</html>
