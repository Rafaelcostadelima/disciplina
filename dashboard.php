<?php
session_start();
// Define fuso horário Brasil para cálculos de data no servidor
date_default_timezone_set('America/Sao_Paulo');

// 1. Conexão e Segurança
$host = 'localhost';
$db = 'disciplina_db';
$user = 'root';
$pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// 2. Buscar Dados do Usuário
$stmt = $pdo->prepare("SELECT nome, pontos FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$dados_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$nome_usuario = $dados_usuario['nome'];
$pontos_totais = $dados_usuario['pontos'];

// 3. Preparação dos Dias da Semana (Lógica PHP)
// Array ordenado: Segunda a Domingo
$ordem_dias = [
    'Seg' => 1,
    'Ter' => 2,
    'Qua' => 3,
    'Qui' => 4,
    'Sex' => 5,
    'Sab' => 6,
    'Dom' => 7
];

$nomes_dias_extenso = [
    'Seg' => 'Segunda-feira',
    'Ter' => 'Terça-feira',
    'Qua' => 'Quarta-feira',
    'Qui' => 'Quinta-feira',
    'Sex' => 'Sexta-feira',
    'Sab' => 'Sábado',
    'Dom' => 'Domingo'
];

// Descobrir dia de hoje numérico (1=Seg, 7=Dom) para comparar passado/futuro
$hoje_w = date('N'); // 'N' retorna 1 para Seg e 7 para Dom
// Descobrir a sigla de hoje (ex: 'Sex')
$dias_invertidos = array_flip($ordem_dias);
$sigla_hoje = $dias_invertidos[$hoje_w];

// 4. Buscar TODAS as rotinas do usuário
$stmt_rotinas = $pdo->prepare("SELECT * FROM rotinas WHERE usuario_id = :uid ORDER BY id DESC");
$stmt_rotinas->execute([':uid' => $_SESSION['usuario_id']]);
$todas_rotinas = $stmt_rotinas->fetchAll(PDO::FETCH_ASSOC);

// 5. Filtrar rotinas APENAS de HOJE (Para a primeira aba)
$rotinas_hoje = [];
foreach ($todas_rotinas as $rotina) {
    if (strpos($rotina['dias_semana'], $sigla_hoje) !== false) {
        $rotinas_hoje[] = $rotina;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus hábitos - Disciplina Total</title>
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
                <span class="bar"></span><span class="bar"></span><span class="bar"></span>
            </div>
        </nav>
    </header>

    <main class="login-container"
        style="display: block; padding-top: 100px; height: auto; min-height: 100vh; position: relative;">
        <div style="max-width: 800px; margin: 0 auto; padding: 20px;">

            <!-- CABEÇALHO: Saudação + Pontos -->
            <div class="header-dashboard">
                <div class="saudacao">
                    <h1 style="color: var(--primary-color);">Olá, <?= htmlspecialchars($nome_usuario); ?>!</h1>
                    <p style="color: var(--text-muted);">Vamos manter o foco hoje?</p>
                </div>

                <!-- MOSTRADOR DE PONTOS -->
                <div class="dt-points-badge">
                    <div class="coin-icon"></div> <!-- Configure a imagem no CSS .coin-icon -->
                    <span class="points-value"><?= $pontos_totais ?></span>
                    <span class="points-label">DT</span>
                </div>
            </div>

            <!-- SISTEMA DE ABAS (HOJE / SEMANA) -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="abrirTab(event, 'tab-hoje')">Hoje</button>
                    <button class="tab-btn" onclick="abrirTab(event, 'tab-semana')">Semana</button>
                </div>

                <!-- CONTEÚDO DA ABA: HOJE -->
                <div id="tab-hoje" class="tab-content" style="display: block;">
                    <div class="task-list">
                        <?php if (count($rotinas_hoje) > 0): ?>
                            <?php foreach ($rotinas_hoje as $rotina): ?>
                                <div class="task-card">
                                    <div class="task-info">
                                        <h4><?= htmlspecialchars(ucfirst($rotina['tipo_meta'])) ?></h4>
                                        <p><?= $rotina['duracao_minutos'] ?> min • <span
                                                style="color: gold">+<?= $rotina['pontos_recompensa'] ?> pts</span></p>
                                        <?php if ($rotina['descricao_personalizada']): ?>
                                            <small
                                                style="color: #888;">"<?= htmlspecialchars($rotina['descricao_personalizada']) ?>"</small>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn-check" onclick="abrirConfirmacao(
                                        <?= $rotina['id'] ?>, 
                                        '<?= htmlspecialchars($rotina['tipo_meta']) ?>', 
                                        <?= $rotina['duracao_minutos'] ?>, 
                                        <?= $rotina['pontos_recompensa'] ?>,
                                        '<?= htmlspecialchars($rotina['descricao_personalizada'] ?? '') ?>'
                                    )">
                                        ✔
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #777; padding: 20px; text-align: center;">Nenhuma tarefa agendada para hoje.
                                Aproveite ou adicione uma nova!</p>
                        <?php endif; ?>

                        <!-- Botão Nova Tarefa -->
                        <button id="btnNovaMeta" class="btn-add-task">+ Nova Rotina</button>
                    </div>
                </div>

                <!-- CONTEÚDO DA ABA: SEMANA (Segunda a Domingo) -->
                <div id="tab-semana" class="tab-content" style="display: none;">

                    <?php foreach ($ordem_dias as $sigla => $num_dia):

                        // 1. Filtrar rotinas para este dia específico do loop
                        $rotinas_deste_dia = [];
                        foreach ($todas_rotinas as $r) {
                            if (strpos($r['dias_semana'], $sigla) !== false) {
                                $rotinas_deste_dia[] = $r;
                            }
                        }

                        // 2. Verificar se é passado (Dia não feito)
                        // Se o número do dia (ex: 1/Seg) for menor que hoje (ex: 3/Qua), é passado.
                        $is_passado = ($num_dia < $hoje_w);
                        $habitos_perdidos = count($rotinas_deste_dia);
                        $mostrar_alerta = ($is_passado && $habitos_perdidos > 0);
                        ?>

                        <div class="day-group" id="day-<?= $sigla ?>">
                            <h3 class="day-header">
                                <?= $nomes_dias_extenso[$sigla] ?>
                            </h3>

                            <!-- CASO 1: Dia Passado e Tarefas não feitas -->
                            <?php if ($mostrar_alerta): ?>
                                <div class="missed-alert">
                                    ⚠ <?= $habitos_perdidos ?> hábitos não foram feitos na <?= $nomes_dias_extenso[$sigla] ?>
                                </div>
                                <!-- Lista apagadinha -->
                                <div class="task-list opacity-low">
                                    <?php foreach ($rotinas_deste_dia as $rotina): ?>
                                        <div class="task-card-mini">
                                            <span><?= htmlspecialchars($rotina['tipo_meta']) ?></span>
                                            <span style="color: #ff5555; font-size: 0.8rem;">Não feito</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- CASO 2: Dia normal com tarefas -->
                            <?php elseif (count($rotinas_deste_dia) > 0): ?>
                                <div class="task-list">
                                    <?php foreach ($rotinas_deste_dia as $rotina): ?>
                                        <div class="task-card faded">
                                            <div class="task-info">
                                                <h4><?= htmlspecialchars(ucfirst($rotina['tipo_meta'])) ?></h4>
                                                <p style="font-size: 0.8rem;"><?= $rotina['duracao_minutos'] ?> min</p>
                                            </div>
                                            <span
                                                style="color: gold; font-size: 0.9rem;">+<?= $rotina['pontos_recompensa'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- CASO 3: Dia vazio -->
                            <?php else: ?>
                                <p class="text-muted-small">Nenhum hábito programado para este dia.</p>
                            <?php endif; ?>
                        </div>

                        <hr class="divider-week">

                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Botão Importante -->
            <button class="btn-importante">Importante!</button>

        </div>
    </main>

    <!-- MODAL 1: NOVA ROTINA (Com Dias e Input Number) -->
    <div id="modalMeta" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 style="color: var(--primary-color); margin-bottom: 5px;">Configurar Rotina</h2>
            <p style="color: #888; font-size: 0.9rem; margin-bottom: 20px;">Defina o que você vai fazer e quando.</p>

            <form class="modal-form" action="salvar_rotina.php" method="POST">

                <label for="tipoMeta">Atividade</label>
                <select name="tipo_meta" id="tipoMeta" onchange="verificarOutro(this)">
                    <option value="estudar">Estudar</option>
                    <option value="treinar">Treinar (Academia)</option>
                    <option value="caminhar">Caminhar</option>
                    <option value="correr">Correr</option>
                    <option value="ler">Ler Livro</option>
                    <option value="meditar">Meditar</option>
                    <option value="outro">Outro (Personalizado)...</option>
                </select>
                <div id="inputPersonalizado" style="display: none;">
                    <input type="text" name="descricao_personalizada" id="metaTexto" placeholder="Nome da atividade...">
                </div>

                <label>Quais dias da semana?</label>
                <div class="dias-semana-wrapper">
                    <input type="checkbox" name="dias[]" value="Dom" id="dom"><label for="dom">D</label>
                    <input type="checkbox" name="dias[]" value="Seg" id="seg"><label for="seg">S</label>
                    <input type="checkbox" name="dias[]" value="Ter" id="ter"><label for="ter">T</label>
                    <input type="checkbox" name="dias[]" value="Qua" id="qua"><label for="qua">Q</label>
                    <input type="checkbox" name="dias[]" value="Qui" id="qui"><label for="qui">Q</label>
                    <input type="checkbox" name="dias[]" value="Sex" id="sex"><label for="sex">S</label>
                    <input type="checkbox" name="dias[]" value="Sab" id="sab"><label for="sab">S</label>
                </div>

                <label>Duração (Minutos)</label>
                <input type="number" name="duracao" id="inputMinutos" class="input-minutos" placeholder="00" min="1"
                    required>

                <div
                    style="background-color: #2a2a2a; padding: 10px; border-radius: 5px; text-align: center; margin-top: 10px;">
                    <span style="color: #bbb; font-size: 0.9rem;">Valor desta rotina:</span><br>
                    <span id="pontosPreview" style="color: gold; font-weight: bold; font-size: 1.4rem;">0</span>
                    <span style="color: gold;">DT Points</span>
                </div>
                <p style="text-align: center; font-size: 0.7rem; color: #666; margin-top: 5px;">(1 minuto = 3 pontos)
                </p>

                <button type="submit" class="btn-submit" style="margin-top: 20px;">CRIAR ROTINA</button>
            </form>
        </div>
    </div>

    <!-- MODAL 2: IMPORTANTE -->
    <div id="modalImportante" class="modal">
        <div class="modal-content" style="text-align: center;">
            <span class="close-btn" id="fecharImportante">&times;</span>
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Aviso</h2>
            <p style="font-size: 1.1rem; line-height: 1.6; color: #ddd;">
                É primordial realçar que você deve se compromissar com o app.
            </p>
            <br>
            <p style="font-size: 1.1rem; line-height: 1.6; color: #ddd;">
                Somos apenas uma engrenagem para que você possa organizar a sua rotina utilizando o método de
                <a href="gamificacao.php"
                    style="color: var(--primary-color); text-decoration: underline; font-weight: bold;">gameficação</a>.
            </p>
            <button type="button" class="btn-submit" id="btnEntendi"
                style="margin-top: 25px; background-color: #333; border: 1px solid #555;">Entendi</button>
        </div>
    </div>

    <!-- MODAL 3: CONFIRMAÇÃO DE CONCLUSÃO -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-content" style="text-align: center;">
            <span class="close-btn" id="fecharConfirmacao">&times;</span>

            <h2 style="color: var(--primary-color); margin-bottom: 15px;">Parabéns pelo foco!</h2>
            <p style="font-size: 1.1rem; margin-bottom: 20px;">Você tem certeza de que completou este hábito?</p>

            <!-- AQUI VAI APARECER O CARD DO HÁBITO (Preenchido via JS) -->
            <div id="previewTaskConfirm" style="text-align: left; margin-bottom: 30px;">
                <!-- O JS vai injetar o HTML aqui -->
            </div>

            <form action="concluir_tarefa.php" method="POST">
                <!-- Campo oculto para mandar o ID pro PHP -->
                <input type="hidden" name="rotina_id" id="inputRotinaId">

                <div style="display: flex; gap: 10px; flex-direction: column;">
                    <!-- Botão SIM (Estilo Login) -->
                    <button type="submit" class="btn-submit" style="font-size: 1.1rem;">Sim, eu tenho!</button>

                    <!-- Botão NÃO -->
                    <button type="button" id="btnCancelarConfirmacao"
                        style="background: transparent; border: 1px solid #555; color: #888; padding: 10px; border-radius: 5px; cursor: pointer;">
                        Não completei :(
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="script.js"></script>

    <!-- SCRIPT INLINE PARA DETECTAR O DIA DO CELULAR (UX) -->
    <script>
        // Array para mapear o .getDay() do JS (0=Dom, 1=Seg...) para nossas siglas
        const diasSiglaJS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
        const dataCelular = new Date();
        const diaIndex = dataCelular.getDay();
        const siglaCelular = diasSiglaJS[diaIndex];

        // Encontra a div do dia e destaca visualmente
        const divDiaCelular = document.getElementById('day-' + siglaCelular);
        if (divDiaCelular) {
            // Adiciona uma borda grossa laranja e escreve "HOJE" via JS
            divDiaCelular.style.borderLeft = "5px solid var(--primary-color)";
            divDiaCelular.style.paddingLeft = "15px";

            // Opcional: Adicionar um texto "HOJE (Celular)" no título
            const tituloDia = divDiaCelular.querySelector('.day-header');
            if (tituloDia) {
                tituloDia.innerHTML += ' <span class="badge-hoje" style="margin-left:10px;">HOJE</span>';
            }
        }
    </script>
</body>

</html>