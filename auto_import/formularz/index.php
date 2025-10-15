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
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bowlby+One+SC&family=Caveat:wght@400..700&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Michroma&family=Oswald:wght@200..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
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
                    <p>Rok: <?php echo $row['rok_produkcji']; ?></p>
                    <p>Przebieg: <?php echo number_format($row['przebieg'],0,',',' '); ?> km</p>
                    <p>Cena: <?php echo number_format($row['cena'],0,',',' '); ?> PLN</p>
                    <div class="auto-actions">
                        <a href="edytuj.php?id=<?php echo $row['id_auta']; ?>" class="btn btn-edit">Edytuj</a>
                        <a href="?delete=<?php echo $row['id_auta']; ?>" class="btn btn-delete" onclick="return confirm('Na pewno usunąć?')">Usuń</a>
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
