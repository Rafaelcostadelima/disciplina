<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$host = 'localhost'; $db = 'disciplina_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

$id_rotina = (int)$_GET['id'];
$usuario_id = $_SESSION['usuario_id'];

try {
    // 1. Apagar primeiro o histórico de conclusões dessa rotina (Chave Estrangeira)
    $stmt1 = $pdo->prepare("DELETE FROM conclusoes WHERE rotina_id = :rid AND usuario_id = :uid");
    $stmt1->execute([':rid' => $id_rotina, ':uid' => $usuario_id]);

    // 2. Apagar a rotina em si
    $stmt2 = $pdo->prepare("DELETE FROM rotinas WHERE id = :rid AND usuario_id = :uid");
    $stmt2->execute([':rid' => $id_rotina, ':uid' => $usuario_id]);

    header("Location: dashboard.php");
    exit;
} catch (PDOException $e) {
    echo "Erro ao excluir: " . $e->getMessage();
}
?>