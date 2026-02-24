<?php
session_start();
date_default_timezone_set('America/Sao_Paulo'); // Garante que o "Hoje" seja hoje mesmo

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php"); exit;
}

$host = 'localhost'; $db = 'disciplina_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

$id_recompensa = $_POST['id_recompensa'];
$uid = $_SESSION['usuario_id'];

// 1. Busca dados do item
$stmt = $pdo->prepare("SELECT nome, preco FROM recompensas WHERE id = :rid AND usuario_id = :uid");
$stmt->execute([':rid' => $id_recompensa, ':uid' => $uid]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) { header("Location: dashboard.php"); exit; }

$preco = $item['preco'];
$nome_item = $item['nome'];

// 2. Verifica saldo
$stmt_user = $pdo->prepare("SELECT pontos FROM usuarios WHERE id = :uid");
$stmt_user->execute([':uid' => $uid]);
$saldo = $stmt_user->fetchColumn();

if ($saldo >= $preco) {
    try {
        $pdo->beginTransaction();

        // A. Desconta pontos
        $novo_saldo = $saldo - $preco;
        $stmt_update = $pdo->prepare("UPDATE usuarios SET pontos = :novo WHERE id = :uid");
        $stmt_update->execute([':novo' => $novo_saldo, ':uid' => $uid]);

        // B. SALVA NO HISTÓRICO (O RECIBO)
        $stmt_hist = $pdo->prepare("INSERT INTO historico_compras (usuario_id, nome_recompensa, preco_pago, data_compra) VALUES (:uid, :nome, :preco, NOW())");
        $stmt_hist->execute([':uid' => $uid, ':nome' => $nome_item, ':preco' => $preco]);
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
}

header("Location: dashboard.php");
exit;
?>