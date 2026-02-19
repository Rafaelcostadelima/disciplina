<?php
session_start();

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$nome_usuario = $_SESSION['usuario_nome'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Metas - Disciplina Total</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="img/logodt.png" type="image/x-icon">
</head>

<body>

    <!-- NAVBAR -->
    <header>
        <nav>
            <a href="dashboard.php" class="logo"><img src="img/logodt.png" alt="Logo DT"></a>

            <ul class="nav-links">
                <li><a href="#" class="active">Minhas Metas</a></li>
                <li><a href="#">Desempenho</a></li>

                <!-- DROPDOWN PERFIL -->
                <li class="dropdown">
                    <!-- Adicionei a classe "arrow" aqui dentro -->
                    <a href="javascript:void(0)" id="btnPerfil" class="dropbtn">
                        Perfil <span class="arrow"></span>
                    </a>

                    <div id="myDropdown" class="dropdown-content">
                        <a href="#">Conta</a>
                        <a href="#">Configurações</a>
                        <hr style="border: 0; border-top: 1px solid #444; margin: 0;">
                        <a href="logout.php" style="color: #ff5555;">Sair</a>
                    </div>
                </li>
            </ul>

            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </nav>
    </header>

    <main class="login-container" style="display: block; padding-top: 100px; height: auto; min-height: 100vh;">
        <div style="max-width: 800px; margin: 0 auto; padding: 20px;">

            <h1 style="color: var(--primary-color);">Olá, <?= htmlspecialchars($nome_usuario); ?>!</h1>
            <p style="color: var(--text-muted);">Vamos manter o foco hoje?</p>

            <hr style="border-color: #333; margin: 20px 0;">

            <!-- Área de Tarefas -->
            <div
                style="background-color: var(--card-bg); padding: 20px; border-radius: 10px; border-left: 5px solid var(--primary-color);">
                <h3>Suas Tarefas de Hoje</h3>
                <p style="margin-top: 10px; color: #bbb;">Você ainda não cadastrou nenhuma tarefa.</p>

                <!-- Botão que abre o Modal -->
                <button id="btnNovaMeta"
                    style="margin-top: 15px; padding: 10px 20px; background: var(--primary-color); border: none; color: white; border-radius: 5px; cursor: pointer;">
                    + Nova Tarefa
                </button>
            </div>

            <!-- Cards de Estatística -->
            <div style="margin-top: 20px; display: flex; gap: 20px; flex-wrap: wrap;">
                <div
                    style="flex: 1; background-color: var(--card-bg); padding: 20px; border-radius: 10px; min-width: 200px;">
                    <h4 style="color: var(--text-muted);">Dias Consecutivos</h4>
                    <span style="font-size: 2rem; font-weight: bold;">0</span> dias
                </div>

                <div
                    style="flex: 1; background-color: var(--card-bg); padding: 20px; border-radius: 10px; min-width: 200px;">
                    <h4 style="color: var(--text-muted);">Tarefas Concluídas</h4>
                    <span style="font-size: 2rem; font-weight: bold;">0</span>
                </div>
            </div>

            <!-- BOTÃO IMPORTANTE (Laranja Claro no final) -->
            <button class="btn-importante">Importante</button>

        </div>
    </main>

    <!-- O MODAL (Janela escondida) -->
    <div id="modalMeta" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Nova Meta</h2>

            <form class="modal-form">
                <label for="tipoMeta">O que vamos fazer?</label>
                <select id="tipoMeta" onchange="verificarOutro(this)">
                    <option value="estudar">Estudar</option>
                    <option value="treinar">Treinar (Academia)</option>
                    <option value="caminhar">Caminhar</option>
                    <option value="correr">Correr</option>
                    <option value="ler">Ler Livro</option>
                    <option value="meditar">Meditar</option>
                    <option value="outro">Outro (Personalizado)...</option>
                </select>

                <div id="inputPersonalizado" style="display: none;">
                    <label for="metaTexto">Qual é a sua meta?</label>
                    <input type="text" id="metaTexto" placeholder="Digite aqui...">
                </div>

                <label>Duração ou Meta (Opcional)</label>
                <input type="text" placeholder="Ex: 30 minutos, 10 páginas...">

                <button type="button" class="btn-submit" style="margin-top: 25px;">SALVAR META</button>
            </form>
        </div>
    </div>

     <div id="modalImportante" class="modal">
        <div class="modal-content" style="text-align: center;">
            <span class="close-btn" id="fecharImportante">&times;</span>
            
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Importante</h2>
            
            <p style="font-size: 1.1rem; line-height: 1.6; color: #ddd;">
                É importante ter em mente que você deve se compromissar com o app. 
                De nada adianta você utilizar o app e não se comprometer com ele, 
                pois não iremos invadir o seu celular para proibir você de fazer as suas coisas.
            </p>
            
            <br>
            
            <p style="font-size: 1.1rem; line-height: 1.6; color: #ddd;">
                Somos apenas uma peça para que você possa organizar a sua rotina utilizando o método de 
                <a href="gamificacao.php" style="color: var(--primary-color); text-decoration: underline; font-weight: bold;">gameficação</a>.
            </p>

            <button type="button" class="btn-submit" id="btnEntendi" style="margin-top: 25px; background-color: #333; border: 1px solid #555;">Entendi</button>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>