<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) { header("Location: dashboard.php"); exit; }
// Conexão padrão...
$host = 'localhost'; $db = 'disciplina_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $stmt = $pdo->prepare("DELETE FROM recompensas WHERE id = :id AND usuario_id = :uid");
    $stmt->execute([':id' => $_GET['id'], ':uid' => $_SESSION['usuario_id']]);
} catch (PDOException $e) {}
header("Location: dashboard.php");
?>