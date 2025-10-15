<?php
$servername = "localhost";
$username = "rybkaau1_autoimport";
$password = "wa2udHHDcL5svfZTWbHC";
$dbname = "rybkaau1_autoimport";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Błąd połączenia z bazą: " . $conn->connect_error);
}
?>