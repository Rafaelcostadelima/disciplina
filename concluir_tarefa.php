<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Conexão
$host = 'localhost'; $db = 'disciplina_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

$rotina_id = $_POST['rotina_id'];
$usuario_id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');

// 1. Verificar se já completou essa rotina HOJE
$stmt_check = $pdo->prepare("SELECT id FROM conclusoes WHERE usuario_id = :uid AND rotina_id = :rid AND data_conclusao = :data");
$stmt_check->execute([':uid' => $usuario_id, ':rid' => $rotina_id, ':data' => $hoje]);

if ($stmt_check->rowCount() > 0) {
    // Já fez hoje! Não dá pontos de novo.
    header("Location: dashboard.php");
    exit;
}

// 2. Buscar quantos pontos essa rotina vale
$stmt_rotina = $pdo->prepare("SELECT pontos_recompensa FROM rotinas WHERE id = :rid");
$stmt_rotina->execute([':rid' => $rotina_id]);
$rotina = $stmt_rotina->fetch(PDO::FETCH_ASSOC);
$pontos = $rotina['pontos_recompensa'];

try {
    $pdo->beginTransaction();

    // 3. Registrar a conclusão
    $stmt_insert = $pdo->prepare("INSERT INTO conclusoes (usuario_id, rotina_id, pontos_ganhos, data_conclusao) VALUES (:uid, :rid, :pontos, :data)");
    $stmt_insert->execute([':uid' => $usuario_id, ':rid' => $rotina_id, ':pontos' => $pontos, ':data' => $hoje]);

    // 4. Dar os pontos ao usuário
    $stmt_update = $pdo->prepare("UPDATE usuarios SET pontos = pontos + :pontos WHERE id = :uid");
    $stmt_update->execute([':pontos' => $pontos, ':uid' => $usuario_id]);

    $pdo->commit();
    
    // Sucesso!
    header("Location: dashboard.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Erro: " . $e->getMessage();
}
?>