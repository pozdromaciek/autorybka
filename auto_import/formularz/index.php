<?php
include('db.php');

// Obsługa usuwania auta
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Usuń powiązane zdjęcia z serwera
    $stmt = $conn->prepare("SELECT sciezka FROM zdjecia WHERE id_auta=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) {
        if(file_exists($row['sciezka'])) unlink($row['sciezka']);
    }
    $stmt->close();

    // Usuń zdjęcia z bazy
    $stmt = $conn->prepare("DELETE FROM zdjecia WHERE id_auta=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Usuń auto
    $stmt = $conn->prepare("DELETE FROM auta WHERE id_auta=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}

// Pobranie wszystkich aut wraz z pierwszym zdjęciem
$sql = "
SELECT a.*, 
       (SELECT z.sciezka 
        FROM zdjecia z 
        WHERE z.id_auta = a.id_auta 
        ORDER BY COALESCE(z.kolejnosc, 0), z.id_zdjecia
        LIMIT 1) AS zdjecie_glowne
FROM auta a
ORDER BY a.data_dodania DESC
";
$result = $conn->query($sql);
if(!$result) die("Błąd SQL: " . $conn->error);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel aut - Admin</title>
<link rel="icon" href="../img/ikona.ico?v=2" type="image/x-icon" />
<link rel="shortcut icon" href="../img/ikona.ico?v=2" type="image/x-icon" /> 
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bowlby+One+SC&family=Caveat:wght@400..700&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Michroma&family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<div class="container">
    <h1>Panel zarządzania autami</h1>
    <a href="dodaj.php" class="btn btn-add">Dodaj auto</a>

    <?php if($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="auto-kafelek">
                <img src="<?php echo !empty($row['zdjecie_glowne']) ? $row['zdjecie_glowne'] : 'uploads/placeholder.jpg'; ?>" alt="">
                <div class="auto-dane">
                    <h2><?php echo htmlspecialchars($row['marka'] . ' ' . $row['model']); ?></h2>
                    <p>Cena: <?php echo number_format($row['cena'],0,',',' '); ?> PLN</p>
                    <p>Data dodania: <?php echo htmlspecialchars($row['data_dodania'],0,',',' '); ?></p>
                    <div class="auto-actions">
                        <a href="edytuj.php?id=<?php echo $row['id_auta']; ?>" class="btn btn-edit">
                            <i class="fas fa-edit"></i> Edytuj
                        </a>
                        <a href="?delete=<?php echo $row['id_auta']; ?>" class="btn btn-delete" onclick="return confirm('Na pewno usunąć?')">
                            <i class="fas fa-trash-alt"></i> Usuń
                        </a>
                        <a href="/ogloszenia/pojazd.php?id=<?php echo $row['id_auta']; ?>" class="btn btn-view" target="_blank">
                            <i class="fas fa-eye"></i> Zobacz ogłoszenie
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center;">Brak aut w bazie.</p>
    <?php endif; ?>
</div>

</body>
</html>
