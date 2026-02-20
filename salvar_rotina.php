<?php
session_start();

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

// Recebendo dados
$tipo_meta = $_POST['tipo_meta'];
$desc_personalizada = $_POST['descricao_personalizada'] ?? '';
$duracao = (int) $_POST['duracao'];
$dias = isset($_POST['dias']) ? implode(',', $_POST['dias']) : ''; // Transforma array em string "Seg,Ter"

// Validação simples
if ($duracao <= 0 || empty($dias)) {
    // Poderia redirecionar com erro, mas vamos só voltar por enquanto
    header("Location: dashboard.php");
    exit;
}

// Regra: 1 minuto = 3 pontos
$pontos_recompensa = $duracao * 3;

try {
    $sql = "INSERT INTO rotinas (usuario_id, tipo_meta, descricao_personalizada, duracao_minutos, pontos_recompensa, dias_semana) 
            VALUES (:uid, :tipo, :desc, :duracao, :pontos, :dias)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid' => $_SESSION['usuario_id'],
        ':tipo' => $tipo_meta,
        ':desc' => $desc_personalizada,
        ':duracao' => $duracao,
        ':pontos' => $pontos_recompensa,
        ':dias' => $dias
    ]);

    header("Location: dashboard.php");
    exit;

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>