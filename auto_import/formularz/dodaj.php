<?php
// Włączamy raportowanie błędów (do debugowania)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('db.php');

// Sprawdzenie połączenia z bazą
if ($conn->connect_error) {
    die("Błąd połączenia z bazą: " . $conn->connect_error);
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pobieranie danych z formularza
    $marka = $_POST['marka'] ?? '';
    $model = $_POST['model'] ?? '';
    $rok = (int)($_POST['rok'] ?? 0);
    $przebieg = (int)($_POST['przebieg'] ?? 0);
    $cena = (float)($_POST['cena'] ?? 0);
    $opis = $_POST['opis'] ?? '';

    // Wstaw auto do bazy
    $stmt = $conn->prepare("INSERT INTO auta (marka, model, rok_produkcji, przebieg, cena, opis) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        $error_message = "Błąd przygotowania zapytania: " . $conn->error;
    } else {
        $stmt->bind_param("ssiiis", $marka, $model, $rok, $przebieg, $cena, $opis);
        if ($stmt->execute()) {
            $id_auta = $stmt->insert_id;
            $stmt->close();

            // Obsługa uploadu zdjęć
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
                        $stmt2 = $conn->prepare("INSERT INTO zdjecia (id_auta, sciezka) VALUES (?, ?)");
                        if ($stmt2) {
                            $stmt2->bind_param("is", $id_auta, $destination);
                            $stmt2->execute();
                            $stmt2->close();
                        } else {
                            echo "Błąd przygotowania zapytania dla zdjęcia: " . $conn->error;
                        }
                    } else {
                        echo "Nie udało się wgrać pliku: $file_name";
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

        <?php if(isset($success_message)) echo "<p class='success'>{$success_message}</p>"; ?>

        <div class="input-group">
            <input type="text" name="marka" placeholder="Marka" required>
        </div>
        <div class="input-group">
            <input type="text" name="model" placeholder="Model" required>
        </div>
        <div class="input-group">
            <input type="number" name="rok" placeholder="Rok produkcji" required>
        </div>
        <div class="input-group">
            <input type="number" name="przebieg" placeholder="Przebieg" required>
        </div>
        <div class="input-group">
            <input type="number" name="cena" placeholder="Cena" required>
        </div>
        <div class="input-group">
            <textarea name="opis" placeholder="Opis auta" required></textarea>
        </div>
        <div class="input-group">
            <input type="file" name="zdjecia[]" multiple accept="image/*">
        </div>
        <button type="submit">Dodaj auto</button>
    </form>
</div>

</body>
</html>
