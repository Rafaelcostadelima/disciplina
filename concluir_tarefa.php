<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php"); exit;
}

// Se não usar arquivo externo, descomente abaixo:

$host = 'localhost'; $db = 'disciplina_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }


$rotina_id = $_POST['rotina_id'];
$usuario_id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');

try {
    // 1. Busca dados da rotina
    $stmt = $pdo->prepare("SELECT pontos_recompensa, duracao_minutos FROM rotinas WHERE id = :rid AND usuario_id = :uid");
    $stmt->execute([':rid' => $rotina_id, ':uid' => $usuario_id]);
    $rotina = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Busca dados ATUAIS do usuário (XP e Nível)
    $stmt_user = $pdo->prepare("SELECT xp_total, nivel FROM usuarios WHERE id = :uid");
    $stmt_user->execute([':uid' => $usuario_id]);
    $dados_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($rotina && $dados_user) {
        $pontos_dt = $rotina['pontos_recompensa'];
        $duracao = $rotina['duracao_minutos'];
        $xp_atual = $dados_user['xp_total'];
        $nivel_atual_banco = $dados_user['nivel'];

        // --- CÁLCULO DO XP ---
        $xp_ganho = 0;
        if ($duracao <= 25) $xp_ganho = 30;
        elseif ($duracao <= 45) $xp_ganho = 50;
        elseif ($duracao <= 60) $xp_ganho = 70;
        else {
            $horas = $duracao / 60;
            $xp_ganho = floor($horas * 70); 
        }

        // Verifica check duplicado
        $check = $pdo->prepare("SELECT id FROM conclusoes WHERE rotina_id = :rid AND data_conclusao = :data");
        $check->execute([':rid' => $rotina_id, ':data' => $hoje]);

        if ($check->rowCount() == 0) {
            $pdo->beginTransaction();

            // A. Registra conclusão
            $ins = $pdo->prepare("INSERT INTO conclusoes (usuario_id, rotina_id, pontos_ganhos, xp_ganho, data_conclusao) VALUES (:uid, :rid, :pts, :xp, :data)");
            $ins->execute([':uid' => $usuario_id, ':rid' => $rotina_id, ':pts' => $pontos_dt, ':xp' => $xp_ganho, ':data' => $hoje]);

            // B. Calcula NOVO Nível
            $novo_total_xp = $xp_atual + $xp_ganho;
            // Fórmula: Nível = Raiz(XP / 50) + 1
            $nivel_calculado = floor(sqrt($novo_total_xp / 50)) + 1;
            
            if ($nivel_calculado > 100) $nivel_calculado = 100;

            // Verifica se SUBIU DE NÍVEL
            if ($nivel_calculado > $nivel_atual_banco) {
                // SALVA NA SESSÃO PARA MOSTRAR A MODAL
                $_SESSION['level_up'] = $nivel_calculado;
            }

            // C. Atualiza Usuário (Pontos, XP e Nível)
            $upd = $pdo->prepare("UPDATE usuarios SET pontos = pontos + :pts, xp_total = xp_total + :xp, nivel = :lvl WHERE id = :uid");
            $upd->execute([
                ':pts' => $pontos_dt, 
                ':xp' => $xp_ganho,
                ':lvl' => $nivel_calculado, // Salva o novo nível no banco
                ':uid' => $usuario_id
            ]);

            $pdo->commit();
        }
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

header("Location: dashboard.php");
exit;
?>