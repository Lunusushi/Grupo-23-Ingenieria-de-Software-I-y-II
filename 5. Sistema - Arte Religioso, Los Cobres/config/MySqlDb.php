<?php
$host = "localhost";
$dbname = "loscobres_db";
$username = "root";
$password = ""; // o la clave que tengas en MySQL

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>
