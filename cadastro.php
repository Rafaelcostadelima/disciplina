<?php
// Configuração e Conexão
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

$mensagem = "";
$sucesso = false;

// Variáveis para guardar o que foi digitado (começam vazias)
$form_nome = "";
$form_user = "";
$form_email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pegamos os dados e salvamos nas variáveis para preencher o form de volta
    $form_nome = $_POST['nome'];
    $form_user = $_POST['username'];
    $form_email = $_POST['email'];
    
    $senha = $_POST['senha'];
    $confirmar = $_POST['confirmar_senha'];

    if ($senha !== $confirmar) {
        $mensagem = "Erro: As senhas não conferem!";
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :e OR username = :u");
            $check->execute([':e' => $form_email, ':u' => $form_user]);
            
            if ($check->rowCount() > 0) {
                $mensagem = "Erro: Email ou Username já estão em uso.";
            } else {
                // Inserção
                $sql = "INSERT INTO usuarios (nome, username, email, senha) VALUES (:nome, :username, :email, :senha)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $form_nome,
                    ':username' => $form_user,
                    ':email' => $form_email,
                    ':senha' => $senha // Lembre-se: em produção use password_hash()
                ]);

                $mensagem = "Conta criada com sucesso! Faça login.";
                $sucesso = true;
                
                // Se deu certo, limpamos as variáveis para o form ficar vazio
                $form_nome = ""; $form_user = ""; $form_email = "";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro no banco: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crie sua Conta - Disciplina Total</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="img/logodt.png" type="image/x-icon">
</head>
<body>

    <header>
        <nav>
            <a href="dashboard.php" class="logo"><img src="img/logodt.png" alt="Logo DT"></a>

            <ul class="nav-links">
                <li><a href="#" class="desativado">Minhas Metas</a></li>
                <li><a href="#" class="desativado">Desempenho</a></li>

                <!-- DROPDOWN PERFIL -->
                <li class="dropdown">
                    <!-- Adicionei a classe "arrow" aqui dentro -->
                    <a href="javascript:void(0)" id="btnPerfil" class="dropbtn desativado">
                        Perfil
                    </a>
                </li>
                <li><a href="index.php" class="btn-login-nav">Login</a></li>
            </ul>

            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </nav>
    </header>

    <main class="login-container">
        <div class="login-card" style="margin-top: 50px;">
            <h2>Crie sua conta</h2>

            <!-- Se for sucesso, mostra aqui em cima mesmo -->
            <?php if($sucesso): ?>
                <p style="color: green; margin-bottom: 15px; font-weight: bold;"><?= $mensagem ?></p>
            <?php endif; ?>

            <?php if(!$sucesso): ?>
            <form action="cadastro.php" method="POST">
                <!-- Nome (Com value preenchido pelo PHP) -->
                <div class="input-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" name="nome" id="nome" 
                           value="<?php echo htmlspecialchars($form_nome); ?>" 
                           placeholder="Ex: João Silva" required>
                </div>

                <!-- Username -->
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" 
                           value="<?php echo htmlspecialchars($form_user); ?>"
                           placeholder="@joaosilva" required>
                </div>

                <!-- Email -->
                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" 
                           value="<?php echo htmlspecialchars($form_email); ?>"
                           placeholder="seu@email.com" required>
                </div>

                <!-- Senhas (sem value, por segurança elas sempre limpam) -->
                <div class="input-group">
                    <label for="senha">Senha</label>
                    <input type="password" name="senha" id="senha" placeholder="Crie uma senha forte" required>
                </div>

                <div class="input-group">
                    <label for="confirmar_senha">Confirmar Senha</label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" placeholder="Repita a senha" required>
                </div>

                <!-- MENSAGEM DE ERRO AGORA FICA AQUI -->
                <?php if($mensagem && !$sucesso): ?>
                    <p style="color: red; font-size: 0.9rem; margin-bottom: 10px;">
                        <?= $mensagem ?>
                    </p>
                <?php endif; ?>

                <button type="submit" class="btn-submit">CADASTRAR</button>
            </form>
            <?php endif; ?>
            
            <p style="margin-top: 15px; font-size: 0.8rem; color: #b0b0b0;">
                Já tem uma conta? <a href="index.php" style="color: var(--primary-color);">Faça Login aqui</a>
            </p>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>