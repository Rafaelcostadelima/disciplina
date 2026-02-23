<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$host = 'localhost'; $db = 'disciplina_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

// Recebe os dados
$id_rotina = $_POST['id_rotina']; // O ID escondido
$tipo_meta = $_POST['tipo_meta'];
$desc_personalizada = $_POST['descricao_personalizada'] ?? '';
$duracao = (int) $_POST['duracao'];
$dias = isset($_POST['dias']) ? implode(',', $_POST['dias']) : '';

// Validações básicas
if ($duracao > 1440) $duracao = 1440;
if ($duracao <= 0) $duracao = 1;
if (empty($dias)) {
    // Se tirar todos os dias, não faz sentido, volta pro dashboard
    header("Location: dashboard.php");
    exit;
}

// Recalcula os pontos
$pontos_recompensa = $duracao * 3;

try {
    // ATUALIZA NO BANCO
    $sql = "UPDATE rotinas SET 
            tipo_meta = :tipo, 
            descricao_personalizada = :desc, 
            duracao_minutos = :duracao, 
            pontos_recompensa = :pontos, 
            dias_semana = :dias 
            WHERE id = :id AND usuario_id = :uid";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tipo' => $tipo_meta,
        ':desc' => $desc_personalizada,
        ':duracao' => $duracao,
        ':pontos' => $pontos_recompensa,
        ':dias' => $dias,
        ':id' => $id_rotina,
        ':uid' => $_SESSION['usuario_id']
    ]);

    header("Location: dashboard.php");
    exit;

} catch (PDOException $e) {
    echo "Erro ao atualizar: " . $e->getMessage();
}
?>