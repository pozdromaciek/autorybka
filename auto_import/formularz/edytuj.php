<?php
// 🔧 Raportowanie błędów
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('db.php');

// 🧩 Sprawdzenie połączenia z bazą
if ($conn->connect_error) {
    die("Błąd połączenia z bazą: " . $conn->connect_error);
}

// 🧩 Sprawdzenie ID auta
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Nieprawidłowe ID auta.");
}

$id_auta = (int)$_GET['id'];
$success_message = '';
$error_message = '';

// 🧹 Usuwanie zdjęcia
if (isset($_GET['delete_photo']) && is_numeric($_GET['delete_photo'])) {
    $id_zdjecia = (int)$_GET['delete_photo'];
    $result = $conn->query("SELECT sciezka FROM zdjecia WHERE id_zdjecia = $id_zdjecia AND id_auta = $id_auta");

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $sciezka = $row['sciezka'];

        if (file_exists($sciezka)) unlink($sciezka);

        $conn->query("DELETE FROM zdjecia WHERE id_zdjecia = $id_zdjecia");
        $success_message = "Zdjęcie zostało usunięte.";
    } else {
        $error_message = "Nie znaleziono zdjęcia.";
    }
}

// 🧩 Pobranie danych auta
$result = $conn->query("SELECT * FROM auta WHERE id_auta = $id_auta");
if ($result->num_rows === 0) die("Nie znaleziono auta o podanym ID.");
$auto = $result->fetch_assoc();

// 💾 Obsługa formularza edycji
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
    $generacja = $_POST['generacja'] ?? '';
    $wersja = $_POST['wersja'] ?? '';
    $liczba_drzwi = (int)($_POST['liczba_drzwi'] ?? 0);
    $naped = $_POST['naped'] ?? '';
    $cena = (int)($_POST['cena'] ?? 0);
    $opis = $_POST['opis'] ?? '';

    $stmt = $conn->prepare("UPDATE auta SET 
        marka=?, model=?, rok_produkcji=?, przebieg=?, moc=?, pojemnosc=?, 
        rodzaj_paliwa=?, nadwozie=?, skrzynia_biegow=?, generacja=?, wersja=?, 
        liczba_drzwi=?, napęd=?, cena=?, opis=? 
        WHERE id_auta=?"
    );

    if ($stmt) {
        $stmt->bind_param(
            "ssiiiissssssssss",
            $marka,
            $model,
            $rok,
            $przebieg,
            $moc,
            $pojemnosc,
            $rodzaj_paliwa,
            $nadwozie,
            $skrzynia_biegow,
            $generacja,
            $wersja,
            $liczba_drzwi,
            $naped,
            $cena,
            $opis,
            $id_auta
        );

        if ($stmt->execute()) {
            $success_message = "Dane auta zostały zaktualizowane.";

            // 📸 Upload nowych zdjęć
            if (!empty($_FILES['zdjecia']['name'][0])) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                foreach ($_FILES['zdjecia']['tmp_name'] as $key => $tmp_name) {
                    $file_name = $_FILES['zdjecia']['name'][$key];
                    $file_tmp = $_FILES['zdjecia']['tmp_name'][$key];
                    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_name = uniqid() . "." . $ext;
                    $destination = $upload_dir . $new_name;

                    if (move_uploaded_file($file_tmp, $destination)) {
                        $stmt2 = $conn->prepare("INSERT INTO zdjecia (id_auta, sciezka, data_dodania) VALUES (?, ?, NOW())");
                        if ($stmt2) {
                            $stmt2->bind_param("is", $id_auta, $destination);
                            $stmt2->execute();
                            $stmt2->close();
                        }
                    }
                }
            }

            // 🔹 Obsługa głównego zdjęcia
            if (!empty($_POST['glowne_zdjecie'])) {
                $glowne_id = (int)$_POST['glowne_zdjecie'];
                $conn->query("UPDATE zdjecia SET glowne=0 WHERE id_auta=$id_auta");
                $conn->query("UPDATE zdjecia SET glowne=1 WHERE id_zdjecia=$glowne_id");
            }

        } else {
            $error_message = "Błąd przy aktualizacji danych: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Błąd przygotowania zapytania: " . $conn->error;
    }

    // 🔄 Odśwież dane auta po aktualizacji
    $result = $conn->query("SELECT * FROM auta WHERE id_auta = $id_auta");
    $auto = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edytuj Auto</title>
<link rel="stylesheet" href="dodaj.css">
<style>
.zdjecia { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
.zdjecie { position: relative; text-align: center; }
.zdjecie img { width: 120px; height: 90px; object-fit: cover; border-radius: 6px; border: 1px solid #ccc; }
.zdjecie a.usun { position: absolute; top: 4px; right: 4px; background: #e74c3c; color: white; font-size: 12px; padding: 3px 6px; border-radius: 4px; text-decoration: none; }
.zdjecie a.usun:hover { background: #b4231f; }
</style>
</head>
<body>
<div class="container">
<form method="post" enctype="multipart/form-data">
    <a href="/formularz" class="btn-add">Powrót do panelu</a>
    <h1>Edytuj Auto</h1>

    <?php if ($success_message): ?><p class="success"><?= $success_message ?></p><?php endif; ?>
    <?php if ($error_message): ?><p class="error" style="color:#e74c3c; text-align:center;"><?= $error_message ?></p><?php endif; ?>

    <!-- Tutaj pola auta (marka, model, rok itd.) -->
    <div class="input-group">
        <label>Marka</label>
        <input type="text" name="marka" value="<?= htmlspecialchars($auto['marka']) ?>" required>
    </div>
    <div class="input-group">
        <label>Model</label>
        <input type="text" name="model" value="<?= htmlspecialchars($auto['model']) ?>" required>
    </div>
    <div class="input-group">
        <label>Rok produkcji</label>
        <input type="number" min="1920" max="<?= date('Y') ?>" name="rok_produkcji" value="<?= htmlspecialchars($auto['rok_produkcji']) ?>" required>
    </div>
    <div class="input-group">
        <label>Przebieg</label>
        <input type="number" name="przebieg" value="<?= htmlspecialchars($auto['przebieg']) ?>" required>
    </div>
    <div class="input-group">
        <label>Moc</label>
        <input type="number" name="moc" value="<?= htmlspecialchars($auto['moc']) ?>" required>
    </div>
    <div class="input-group">
        <label>Pojemność</label>
        <input type="number" name="pojemnosc" value="<?= htmlspecialchars($auto['pojemnosc']) ?>" required>
    </div>
    <div class="input-group">
        <label>Rodzaj paliwa</label>
        <select name="rodzaj_paliwa" required>
            <?php
            $opcje_paliwa = ['benzyna','diesel','benzyna+lpg','elektryczny','hybryda'];
            foreach ($opcje_paliwa as $p) {
                $sel = ($auto['rodzaj_paliwa']==$p) ? 'selected' : '';
                echo "<option value='$p' $sel>$p</option>";
            }
            ?>
        </select>
    </div>
    <div class="input-group">
        <label>Nadwozie</label>
        <select name="nadwozie" required>
            <?php
            $opcje_nadwozie = ['kombi','sedan','suv','coupe','hatchback','compact'];
            foreach ($opcje_nadwozie as $n) {
                $sel = ($auto['nadwozie']==$n) ? 'selected' : '';
                echo "<option value='$n' $sel>$n</option>";
            }
            ?>
        </select>
    </div>
    <div class="input-group">
        <label>Skrzynia biegów</label>
        <select name="skrzynia_biegow" required>
            <?php
            $opcje_skrzynia = ['automatyczna','manualna'];
            foreach ($opcje_skrzynia as $s) {
                $sel = ($auto['skrzynia_biegow']==$s) ? 'selected' : '';
                echo "<option value='$s' $sel>$s</option>";
            }
            ?>
        </select>
    </div>
    <div class="input-group">
        <label>Generacja</label>
        <input type="text" name="generacja" value="<?= htmlspecialchars($auto['generacja']) ?>" required>
    </div>
    <div class="input-group">
        <label>Wersja</label>
        <input type="text" name="wersja" value="<?= htmlspecialchars($auto['wersja']) ?>" required>
    </div>
    <div class="input-group">
        <label>Liczba drzwi</label>
        <select name="liczba_drzwi" required>
            <?php
            $opcje_drzwi = [3,5];
            foreach ($opcje_drzwi as $d) {
                $sel = ($auto['liczba_drzwi']==$d) ? 'selected' : '';
                echo "<option value='$d' $sel>$d</option>";
            }
            ?>
        </select>
    </div>
    <div class="input-group">
        <label>Napęd</label>
        <select name="naped" required>
            <?php
            $opcje_naped = ['na przód','na tył','4x4'];
            foreach ($opcje_naped as $n) {
                $sel = ($auto['napęd']==$n) ? 'selected' : '';
                echo "<option value='$n' $sel>$n</option>";
            }
            ?>
        </select>
    </div>
    <div class="input-group">
        <label>Cena</label>
        <input type="number" name="cena" value="<?= htmlspecialchars($auto['cena']) ?>" required>
    </div>
    <div class="input-group">
        <label>Opis</label>
        <textarea name="opis" required><?= htmlspecialchars($auto['opis']) ?></textarea>
    </div>

    <h3 style="color:#3498db;">Zdjęcia:</h3>
    <div class="zdjecia">
        <?php
        $result_zdjecia = $conn->query("SELECT id_zdjecia, sciezka, glowne FROM zdjecia WHERE id_auta = $id_auta ORDER BY id_zdjecia ASC");
        if ($result_zdjecia && $result_zdjecia->num_rows > 0) {
            while ($zdj = $result_zdjecia->fetch_assoc()) {
                $checked = $zdj['glowne'] ? 'checked' : '';
                echo "
                <div class='zdjecie'>
                    <img src='{$zdj['sciezka']}' alt='Zdjęcie auta'>
                    <div style='margin-top:5px;'>
                        <label>
                            <input type='radio' name='glowne_zdjecie' value='{$zdj['id_zdjecia']}' $checked> Główne
                        </label>
                    </div>
                    <a class='usun' href='?id=$id_auta&delete_photo={$zdj['id_zdjecia']}' onclick=\"return confirm('Na pewno usunąć to zdjęcie?');\">Usuń</a>
                </div>";
            }
        } else {
            echo "<p style='color:gray;'>Brak zdjęć.</p>";
        }
        ?>
    </div>

    <div class="input-group">
        <input type="file" name="zdjecia[]" multiple accept="image/*">
    </div>

    <button type="submit">Zapisz zmiany</button>
    <button type="button" onclick="window.location.href='/formularz';" style="margin-top:20px;">Anuluj</button>
</form>
</div>
</body>
</html>
