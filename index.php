<?php
// 1. Inclui o sistema de auto-login (se tiver cookie, já loga sozinho)
require_once 'autologin.php';

// Se o autologin funcionou, vai pro dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Configuração e Conexão
$host = 'localhost'; $db = 'disciplina_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

// Login Manual
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    // Verifica se marcou a caixinha
    $manter_conectado = isset($_POST['manter_conectado']); 

    $sql = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Sucesso na Sessão
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_id'] = $usuario['id'];

        // --- LÓGICA DO LEMBRAR DE MIM ---
        if ($manter_conectado) {
            // 1. Gera um token aleatório seguro
            $token = bin2hex(random_bytes(32)); // Ex: a7f89...
            
            // 2. Salva no banco
            $upd = $pdo->prepare("UPDATE usuarios SET token_login = :t WHERE id = :id");
            $upd->execute([':t' => $token, ':id' => $usuario['id']]);

            // 3. Cria o cookie no navegador (30 dias)
            // O valor é "ID:TOKEN"
            $cookie_valor = $usuario['id'] . ':' . $token;
            $expiracao = time() + (30 * 24 * 60 * 60); // 30 dias em segundos
            setcookie('lembrar_token', $cookie_valor, $expiracao, "/");
        }

        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['msg_erro'] = "Email ou senha incorretos.";
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Disciplina Total</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="img/logodt.png" type="image/x-icon">
</head>
<body>
    <header>
        <nav>
            <a href="dashboard.php" class="logo"><img src="img/logodt.png" alt="Logo DT"></a>
            <ul class="nav-links">
                <li><a href="#" class="desativado">Minhas Rotina</a></li>
                <li><a href="#" class="desativado">Desempenho</a></li>
                <li class="dropdown"><a href="#" class="desativado">Perfil</a></li>
                <li><a href="cadastro.php" class="btn-login-nav">Registre-se</a></li>
            </ul>
            <div class="hamburger"><span class="bar"></span><span class="bar"></span><span class="bar"></span></div>
        </nav>
    </header>

    <main class="login-container">
        <div class="login-card">
            <h2>Acesse sua conta</h2>

            <?php if (isset($_SESSION['msg_sucesso'])): ?>
                <p style="color: green; margin-bottom: 10px;"><?= $_SESSION['msg_sucesso']; unset($_SESSION['msg_sucesso']); ?></p>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" placeholder="seu@email.com" required>
                </div>
                <div class="input-group">
                    <label for="senha">Senha</label>
                    <input type="password" name="senha" id="senha" placeholder="********" required>
                </div>

                <!-- CHECKBOX NOVO -->
                <div style="text-align: left; margin-bottom: 15px; display: flex; align-items: center;">
                    <input type="checkbox" name="manter_conectado" id="manter" style="width: auto; margin-right: 8px;">
                    <label for="manter" style="margin: 0; cursor: pointer; color: #ccc;">Lembrar de mim por 30 dias</label>
                </div>

                <?php if (isset($_SESSION['msg_erro'])): ?>
                    <p style="color: red; font-size: 0.9rem; margin-top: 5px; margin-bottom: 5px;"><?= $_SESSION['msg_erro']; unset($_SESSION['msg_erro']); ?></p>
                <?php endif; ?>

                <button type="submit" class="btn-submit">ENTRAR</button>
            </form>

            <p style="margin-top: 15px; font-size: 0.8rem; color: #b0b0b0;">
                Ainda não tem conta? <a href="cadastro.php" style="color: var(--primary-color);">Registre-se</a>
            </p>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>