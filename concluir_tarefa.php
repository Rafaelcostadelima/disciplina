<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$host = 'localhost'; $db = 'disciplina_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

$rotina_id = $_POST['rotina_id'];
$usuario_id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');

try {
    // 1. Verificar a rotina e pegar os pontos
    $stmt = $pdo->prepare("SELECT pontos_recompensa FROM rotinas WHERE id = :rid AND usuario_id = :uid");
    $stmt->execute([':rid' => $rotina_id, ':uid' => $usuario_id]);
    $rotina = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($rotina) {
        $pontos_rotina = $rotina['pontos_recompensa'];

        // 2. Verificar se JÁ concluiu hoje
        $check = $pdo->prepare("SELECT id FROM conclusoes WHERE rotina_id = :rid AND data_conclusao = :data");
        $check->execute([':rid' => $rotina_id, ':data' => $hoje]);

        if ($check->rowCount() == 0) {
            
            $pdo->beginTransaction();

            // A. Registra a conclusão (INCLUINDO pontos_ganhos AGORA!)
            $ins = $pdo->prepare("INSERT INTO conclusoes (usuario_id, rotina_id, pontos_ganhos, data_conclusao) VALUES (:uid, :rid, :pts, :data)");
            $ins->execute([
                ':uid' => $usuario_id, 
                ':rid' => $rotina_id, 
                ':pts' => $pontos_rotina, // <--- Correção aqui
                ':data' => $hoje
            ]);

            // B. Atualiza saldo do usuário
            $upd = $pdo->prepare("UPDATE usuarios SET pontos = pontos + :pts WHERE id = :uid");
            $upd->execute([':pts' => $pontos_rotina, ':uid' => $usuario_id]);

            $pdo->commit();
        }
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

header("Location: dashboard.php");
exit;
?>