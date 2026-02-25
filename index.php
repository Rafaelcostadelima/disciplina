<?php
session_start();

// Configuração e Conexão
$host = 'localhost';
$db = 'disciplina_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Verifica se já está logado (Se estiver, manda direto pro Dashboard)
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Verifica se foi enviado o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];

    // Busca o usuário pelo email
    $sql = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    /* 
       AQUI ESTÁ O SEGREDO:
       Usamos password_verify para comparar a senha digitada com a criptografia do banco.
    */
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // --- LOGIN COM SUCESSO ---
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_id'] = $usuario['id'];

        // Redireciona para o Dashboard imediatamente
        header("Location: dashboard.php");
        exit;
    } else {
        // --- ERRO DE LOGIN ---
        $_SESSION['msg_erro'] = "Email ou senha incorretos.";
        header("Location: index.php"); // Recarrega para mostrar o erro
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
                <li class="dropdown">
                    <a href="javascript:void(0)" id="btnPerfil" class="dropbtn desativado">
                        Perfil
                    </a>
                </li>
                <li><a href="cadastro.php" class="btn-login-nav">Registre-se</a></li>
            </ul>

            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </nav>
    </header>

    <main class="login-container">
        <div class="login-card">
            <h2>Acesse sua conta</h2>

            <!-- Mensagem de Sucesso (vinda do cadastro) -->
            <?php if (isset($_SESSION['msg_sucesso'])): ?>
                <p style="color: green; margin-bottom: 10px;">
                    <?= $_SESSION['msg_sucesso']; ?>
                </p>
                <?php unset($_SESSION['msg_sucesso']); ?>
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

                <!-- Mensagem de Erro -->
                <?php if (isset($_SESSION['msg_erro'])): ?>
                    <p style="color: red; font-size: 0.9rem; margin-top: 5px; margin-bottom: 5px;">
                        <?= $_SESSION['msg_erro']; ?>
                    </p>
                    <?php unset($_SESSION['msg_erro']); ?>
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