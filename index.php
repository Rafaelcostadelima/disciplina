<?php
session_start();

$host = 'localhost';
$db   = 'disciplina_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Verifica se foi enviado o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && $usuario['senha'] == $senha) {
        // Sucesso
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_id'] = $usuario['id'];
        
        // Redireciona para Dashboard (ainda não criamos, então vai pra ele mesmo com sucesso)
        // header("Location: dashboard.php");
        $_SESSION['msg_sucesso'] = "Login realizado com sucesso! Bem-vindo " . $usuario['nome'];
        header("Location: index.php"); // Recarrega para limpar o POST
        exit;
    } else {
        // Erro: Salva na sessão e recarrega a página
        $_SESSION['msg_erro'] = "Email ou senha incorretos.";
        header("Location: index.php"); // O segredo está aqui: recarregar a página limpa
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplina Total</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="img/logodt.png" type="image/x-icon">
</head>
<body>

    <header>
        <nav>
            <a href="#" class="logo"><img src="img/logodt.png" alt="Logo DT"></a>
            <ul class="nav-links">
                <li><a href="#">Início</a></li>
                <li><a href="#">Gráficos</a></li>
                <li><a href="#">Perfil</a></li>
                <li><a href="cadastro.php" class="btn-login-nav">Login</a></li>
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
            
            <!-- Mensagem de Sucesso (se houver) -->
            <?php if(isset($_SESSION['msg_sucesso'])): ?>
                <p style="color: green; margin-bottom: 10px;">
                    <?= $_SESSION['msg_sucesso']; ?>
                </p>
                <?php unset($_SESSION['msg_sucesso']); // Limpa a mensagem após exibir ?>
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

                <!-- MENSAGEM DE ERRO (Agora entre a senha e o botão) -->
                <?php if(isset($_SESSION['msg_erro'])): ?>
                    <p style="color: red; font-size: 0.9rem; margin-top: 5px; margin-bottom: 5px;">
                        <?= $_SESSION['msg_erro']; ?>
                    </p>
                    <?php unset($_SESSION['msg_erro']); // Limpa a mensagem para sumir no refresh ?>
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