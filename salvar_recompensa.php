<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php"); exit;
}

$host = 'localhost'; $db = 'disciplina_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

$nome = $_POST['nome_recompensa'];
$tem_tempo = isset($_POST['tem_tempo']) ? 1 : 0;
$duracao = $tem_tempo ? (int)$_POST['duracao_recompensa'] : 0;
$preco = (int)$_POST['preco'];

// Validações
if ($preco < 100) $preco = 100;
if ($preco > 10000) $preco = 10000;
if ($tem_tempo && $duracao > 1440) $duracao = 1440;

$stmt = $pdo->prepare("INSERT INTO recompensas (usuario_id, nome, tem_tempo, duracao_minutos, preco) VALUES (:uid, :nome, :tem, :dur, :preco)");
$stmt->execute([
    ':uid' => $_SESSION['usuario_id'],
    ':nome' => $nome,
    ':tem' => $tem_tempo,
    ':dur' => $duracao,
    ':preco' => $preco
]);

header("Location: dashboard.php");
exit;
?>