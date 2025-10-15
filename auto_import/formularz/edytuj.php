<?php
// 🔧 Raportowanie błędów (pomocne przy debugowaniu)
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

        // Usuń plik z serwera
        if (file_exists($sciezka)) {
            unlink($sciezka);
        }

        // Usuń wpis z bazy
        $conn->query("DELETE FROM zdjecia WHERE id_zdjecia = $id_zdjecia");

        $success_message = "Zdjęcie zostało usunięte.";
    } else {
        $error_message = "Nie znaleziono zdjęcia.";
    }
}

// 🧩 Pobranie danych auta
$result = $conn->query("SELECT * FROM auta WHERE id_auta = $id_auta");
if ($result->num_rows === 0) {
    die("Nie znaleziono auta o podanym ID.");
}
$auto = $result->fetch_assoc();

// 💾 Obsługa formularza edycji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marka = $_POST['marka'] ?? '';
    $model = $_POST['model'] ?? '';
    $rok = (int)($_POST['rok_produkcji'] ?? 0);
    $przebieg = (int)($_POST['przebieg'] ?? 0);
    $cena = (int)($_POST['cena'] ?? 0);
    $opis = $_POST['opis'] ?? '';

    $stmt = $conn->prepare("UPDATE auta SET marka=?, model=?, rok_produkcji=?, przebieg=?, cena=?, opis=? WHERE id_auta=?");
    if ($stmt) {
        $stmt->bind_param("ssiiisi", $marka, $model, $rok, $przebieg, $cena, $opis, $id_auta);
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
    header("Location: /formularz");
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edytuj Auto</title>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bowlby+One+SC&family=Caveat:wght@400..700&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Michroma&family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="dodaj.css">
<style>
.zdjecia {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}
.zdjecie {
    position: relative;
}
.zdjecie img {
    width: 120px;
    height: 90px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--kolor-cieni);
}
.zdjecie a.usun {
    position: absolute;
    top: 4px;
    right: 4px;
    background: var(--kolor-akcentu);
    color: white;
    font-size: 12px;
    padding: 3px 6px;
    border-radius: 4px;
    text-decoration: none;
}
.zdjecie a.usun:hover {
    background: #b4231f;
}
</style>
</head>
<body>
<div class="container">
    <form method="post" enctype="multipart/form-data">
        <h1>Edytuj Auto</h1>

        <?php if ($success_message): ?>
            <p class="success"><?= $success_message ?></p>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <p class="error" style="color:#e74c3c; text-align:center;"><?= $error_message ?></p>
        <?php endif; ?>

        <div class="input-group">
            <input type="text" name="marka" value="<?= htmlspecialchars($auto['marka']) ?>" placeholder="Marka" required>
        </div>
        <div class="input-group">
            <input type="text" name="model" value="<?= htmlspecialchars($auto['model']) ?>" placeholder="Model" required>
        </div>
        <div class="input-group">
            <input type="number" name="rok_produkcji" value="<?= htmlspecialchars($auto['rok_produkcji']) ?>" placeholder="Rok produkcji" required>
        </div>
        <div class="input-group">
            <input type="number" name="przebieg" value="<?= htmlspecialchars($auto['przebieg']) ?>" placeholder="Przebieg" required>
        </div>
        <div class="input-group">
            <input type="number" name="cena" value="<?= htmlspecialchars($auto['cena']) ?>" placeholder="Cena" required>
        </div>
        <div class="input-group">
            <textarea name="opis" placeholder="Opis auta" required><?= htmlspecialchars($auto['opis']) ?></textarea>
        </div>

        <h3 style="color:var(--kolor-akcentu); font-family:'Michroma',sans-serif;">Zdjęcia:</h3>
        <div class="zdjecia">
            <?php
            $result_zdjecia = $conn->query("SELECT id_zdjecia, sciezka FROM zdjecia WHERE id_auta = $id_auta ORDER BY id_zdjecia ASC");
            if ($result_zdjecia && $result_zdjecia->num_rows > 0) {
                while ($zdj = $result_zdjecia->fetch_assoc()) {
                    echo "
                    <div class='zdjecie'>
                        <img src='{$zdj['sciezka']}' alt='Zdjęcie auta'>
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
