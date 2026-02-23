<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

// 1. Conexão
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

// 2. Dados do Usuário
$stmt = $pdo->prepare("SELECT nome, pontos, username, email, senha FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$dados_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$nome_usuario = $dados_usuario['nome'];
$pontos_totais = $dados_usuario['pontos'];
$username = $dados_usuario['username'];
$email = $dados_usuario['email'];
$senha = $dados_usuario['senha'];

// 3. Buscar Rotinas e Conclusões
$stmt_rotinas = $pdo->prepare("SELECT * FROM rotinas WHERE usuario_id = :uid ORDER BY id DESC");
$stmt_rotinas->execute([':uid' => $_SESSION['usuario_id']]);
$todas_rotinas = $stmt_rotinas->fetchAll(PDO::FETCH_ASSOC);

$stmt_conclusoes = $pdo->prepare("SELECT rotina_id, data_conclusao FROM conclusoes WHERE usuario_id = :uid");
$stmt_conclusoes->execute([':uid' => $_SESSION['usuario_id']]);
$todas_conclusoes = $stmt_conclusoes->fetchAll(PDO::FETCH_ASSOC);

$mapa_feitos = [];
foreach ($todas_conclusoes as $c) {
    $mapa_feitos[$c['rotina_id'] . '_' . $c['data_conclusao']] = true;
}

// Configurações de Data
$hoje_formatado = date('Y-m-d');
$dia_semana_hoje = date('N'); // 1 (Seg) a 7 (Dom)

// Mapeamento
$ordem_dias = ['Seg' => 1, 'Ter' => 2, 'Qua' => 3, 'Qui' => 4, 'Sex' => 5, 'Sab' => 6, 'Dom' => 7];
$nomes_dias_extenso = [
    'Seg' => 'Segunda-feira',
    'Ter' => 'Terça-feira',
    'Qua' => 'Quarta-feira',
    'Qui' => 'Quinta-feira',
    'Sex' => 'Sexta-feira',
    'Sab' => 'Sábado',
    'Dom' => 'Domingo'
];

// Sigla de hoje
$sigla_hoje = array_search($dia_semana_hoje, $ordem_dias);

// Filtrar HOJE
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
    <title>Minha Rotina - Disciplina Total</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="img/logodt.png" type="image/x-icon">
</head>

<body>

    <header>
        <nav>
            <a href="dashboard.php" class="logo"><img src="img/logodt.png" alt="Logo DT"></a>
            <ul class="nav-links">
                <li><a href="#" class="active">Minhas Rotina</a></li>
                <li><a href="#">Desempenho</a></li>
                <li class="dropdown">
                    <a href="javascript:void(0)" id="btnPerfil" class="dropbtn">Perfil <span class="arrow"></span></a>
                    <div id="myDropdown" class="dropdown-content">
                        <a href="#">Conta</a>
                        <a href="#">Configurações</a>
                        <hr style="border: 0; border-top: 1px solid #444; margin: 0;">

                        <a href="logout.php" class="logout-btn">
                            <!-- ÍCONE SVG DE SAIR (20px) -->
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M16 17L21 12L16 7" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            Sair
                        </a>
                    </div>
                </li>
            </ul>
            <div class="hamburger"><span class="bar"></span><span class="bar"></span><span class="bar"></span></div>
        </nav>
    </header>

    <main class="login-container" style="display: block; padding-top: 100px; height: auto; min-height: 100vh;">
        <div style="max-width: 800px; margin: 0 auto; padding: 20px;">

            <div class="header-dashboard">
                <div class="saudacao">
                    <h1 style="color: var(--primary-color);">Olá, <?= htmlspecialchars($username); ?>!</h1>
                    <p style="color: var(--text-muted);">Vamos manter o foco hoje?</p>
                </div>
                <div class="dt-points-badge">
                    <div class="coin-icon"></div>
                    <span class="points-value"><?= $pontos_totais ?></span>
                    <span class="points-label">DT</span>
                </div>
            </div>

            <!-- ABAS -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="abrirTab(event, 'tab-hoje')">Hoje</button>
                    <button class="tab-btn" onclick="abrirTab(event, 'tab-semana')">Semana</button>
                </div>

                <!-- ABA HOJE -->
                <div id="tab-hoje" class="tab-content" style="display: block;">
                    <div class="task-list">
                        <?php if (count($rotinas_hoje) > 0): ?>
                            <?php foreach ($rotinas_hoje as $rotina):
                                $chave_check = $rotina['id'] . '_' . $hoje_formatado;
                                $ja_fez_hoje = isset($mapa_feitos[$chave_check]);
                                ?>
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

                                    <div style="display: flex; align-items: center;">
                                        <?php if ($ja_fez_hoje): ?>
                                            <span class="status-done">✔ Feito</span>
                                        <?php else: ?>
                                            <button class="btn-check"
                                                onclick="abrirConfirmacao(<?= $rotina['id'] ?>, '<?= htmlspecialchars($rotina['tipo_meta']) ?>', <?= $rotina['duracao_minutos'] ?>, <?= $rotina['pontos_recompensa'] ?>, '<?= htmlspecialchars($rotina['descricao_personalizada'] ?? '') ?>')">
                                                ✔
                                            </button>
                                        <?php endif; ?>

                                        <!-- DIV DE BOTÕES (Substitua a parte do botão delete antigo por isso) -->
                                        <div class="action-buttons">
                                            <!-- BOTÃO EDITAR (LÁPIS) -->
                                            <!-- Passamos: ID, Tipo, Descrição, Duração, Dias -->
                                            <button class="btn-icon btn-edit" onclick="abrirModalEditar(
                                                <?= $rotina['id'] ?>, 
                                                '<?= htmlspecialchars($rotina['tipo_meta']) ?>', 
                                                '<?= htmlspecialchars($rotina['descricao_personalizada'] ?? '') ?>', 
                                                <?= $rotina['duracao_minutos'] ?>, 
                                                '<?= $rotina['dias_semana'] ?>'
                                            )" title="Editar">
                                                ✏️
                                            </button>

                                            <!-- BOTÃO DELETAR (LIXEIRA) -->
                                            <button class="btn-icon btn-delete" onclick="abrirModalDelete(<?= $rotina['id'] ?>)"
                                                title="Excluir">
                                                🗑️
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #777; padding: 20px; text-align: center;">Nada para hoje.</p>
                        <?php endif; ?>
                        <button id="btnNovaMeta" class="btn-add-task">+ Nova Rotina</button>
                    </div>
                </div>

                <!-- ABA SEMANA -->
                <div id="tab-semana" class="tab-content" style="display: none;">
                    <?php
                    $ano_atual = date('Y');
                    $semana_atual = date('W');

                    foreach ($ordem_dias as $sigla => $num_dia_iso):
                        // Data exata do dia da semana
                        $data_calculada = new DateTime();
                        $data_calculada->setISODate($ano_atual, $semana_atual, $num_dia_iso);
                        $data_string = $data_calculada->format('Y-m-d');

                        // Rotinas deste dia
                        $rotinas_deste_dia = [];
                        foreach ($todas_rotinas as $r) {
                            if (strpos($r['dias_semana'], $sigla) !== false) {
                                $rotinas_deste_dia[] = $r;
                            }
                        }

                        // Verificar status
                        $perdidas = 0;
                        $lista_renderizada = [];

                        foreach ($rotinas_deste_dia as $r) {
                            $chave = $r['id'] . '_' . $data_string;
                            $foi_feito = isset($mapa_feitos[$chave]);
                            $eh_passado = ($data_string < $hoje_formatado);

                            // NOVA LÓGICA: Verificar se a rotina já existia nesta data
                            // data_criacao vem do banco como "2023-10-24 15:30:00"
                            // Pegamos só a parte da data Y-m-d
                            $data_criacao_rotina = date('Y-m-d', strtotime($r['data_criacao']));

                            if ($foi_feito) {
                                $status = "feito";
                            } elseif ($eh_passado) {
                                // Se a data deste dia for MENOR que a data de criação, ela não existia ainda
                                if ($data_string < $data_criacao_rotina) {
                                    $status = "proxima_semana";
                                } else {
                                    $status = "perdido"; // Existia e não fez
                                    $perdidas++;
                                }
                            } else {
                                $status = "futuro";
                            }

                            $lista_renderizada[] = ['r' => $r, 'status' => $status];
                        }
                        ?>

                        <div class="day-group" id="day-<?= $sigla ?>">
                            <h3 class="day-header">
                                <?= $nomes_dias_extenso[$sigla] ?>
                                <span style="font-size: 0.8rem; color: #555;">(<?= $data_calculada->format('d/m') ?>)</span>
                            </h3>

                            <?php if ($perdidas > 0): ?>
                                <div class="missed-alert">⚠ <?= $perdidas ?> hábito(s) não feitos</div>
                            <?php endif; ?>

                            <?php if (count($lista_renderizada) > 0): ?>
                                <div class="task-list">
                                    <?php foreach ($lista_renderizada as $item):
                                        $rotina = $item['r'];
                                        $status = $item['status'];
                                        ?>
                                        <div class="task-card-mini"
                                            style="display: flex; justify-content: space-between; align-items: center; <?= $status == 'perdido' ? 'opacity: 0.5;' : '' ?>">
                                            <div>
                                                <span><?= htmlspecialchars($rotina['tipo_meta']) ?></span>
                                                <?php if ($rotina['descricao_personalizada']): ?>
                                                    <small
                                                        style="color:#666; display:block; font-size:0.75rem"><?= htmlspecialchars($rotina['descricao_personalizada']) ?></small>
                                                <?php endif; ?>
                                            </div>

                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <?php if ($status == 'feito'): ?>
                                                    <span style="color: #00e676; font-size: 0.8rem;">✔ Feito</span>
                                                <?php elseif ($status == 'perdido'): ?>
                                                    <span style="color: #ff5555; font-size: 0.8rem;">Não feito</span>
                                                <?php elseif ($status == 'proxima_semana'): ?>
                                                    <!-- NOVO STATUS -->
                                                    <span class="status-next-week">Próxima semana</span>
                                                <?php else: ?>
                                                    <span style="color: #888; font-size: 0.8rem;">Pendente</span>
                                                <?php endif; ?>

                                                <!-- DIV DE BOTÕES (Substitua a parte do botão delete antigo por isso) -->
                                                <div class="action-buttons">
                                                    <!-- BOTÃO EDITAR (LÁPIS) -->
                                                    <!-- Passamos: ID, Tipo, Descrição, Duração, Dias -->
                                                    <button class="btn-icon btn-edit" onclick="abrirModalEditar(
                                                        <?= $rotina['id'] ?>, 
                                                        '<?= htmlspecialchars($rotina['tipo_meta']) ?>', 
                                                        '<?= htmlspecialchars($rotina['descricao_personalizada'] ?? '') ?>', 
                                                        <?= $rotina['duracao_minutos'] ?>, 
                                                        '<?= $rotina['dias_semana'] ?>'
                                                    )" title="Editar">
                                                        ✏️
                                                    </button>

                                                    <!-- BOTÃO DELETAR (LIXEIRA) -->
                                                    <button class="btn-icon btn-delete"
                                                        onclick="abrirModalDelete(<?= $rotina['id'] ?>)" title="Excluir">
                                                        🗑️
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted-small">Nenhum hábito.</p>
                            <?php endif; ?>
                        </div>
                        <hr class="divider-week">
                    <?php endforeach; ?>
                </div>
            </div>

            <button class="btn-importante">Importante</button>
        </div>
    </main>

    <!-- MANTENHA AS MODAIS 1, 2 e 3 QUE JÁ EXISTIAM AQUI (Copiei do anterior para garantir) -->
    <div id="modalMeta" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>

            <!-- 1. ID ADICIONADO NO TÍTULO -->
            <h2 id="modalTitulo" style="color: var(--primary-color); margin-bottom: 5px;">Configurar Rotina</h2>

            <!-- 2. ID ADICIONADO NO FORM -->
            <form id="formRotina" class="modal-form" action="salvar_rotina.php" method="POST">

                <!-- 3. INPUT OCULTO ADICIONADO (Para guardar o ID na edição) -->
                <input type="hidden" name="id_rotina" id="inputIdRotina">

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
                <!-- Mantive o max="1440" que adicionamos antes -->
                <input type="number" name="duracao" id="inputMinutos" class="input-minutos" placeholder="00" min="1"
                    max="1440" required>

                <div
                    style="background-color: #2a2a2a; padding: 10px; border-radius: 5px; text-align: center; margin-top: 10px;">
                    <span style="color: #bbb; font-size: 0.9rem;">Valor desta rotina:</span><br>
                    <span id="pontosPreview" style="color: gold; font-weight: bold; font-size: 1.4rem;">0</span>
                    <span style="color: gold;">DT Points</span>
                </div>

                <p style="text-align: center; font-size: 0.7rem; color: #666; margin-top: 5px;">(1 minuto = 3 pontos)
                </p>

                <!-- 4. ID ADICIONADO NO BOTÃO -->
                <button type="submit" id="btnSalvarRotina" class="btn-submit" style="margin-top: 20px;">CRIAR
                    ROTINA</button>
            </form>
        </div>
    </div>

    <div id="modalImportante" class="modal">
        <div class="modal-content" style="text-align: center;">
            <span class="close-btn" id="fecharImportante">&times;</span>
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Importante</h2>
            <p style="font-size: 1.1rem; line-height: 1.6; color: #ddd;">
                É importante ter em mente que você deve se compromissar com o app.
            </p>
            <br>
            <p style="font-size: 1.1rem; line-height: 1.6; color: #ddd;">
                Somos apenas uma peça para que você possa organizar a sua rotina utilizando o método de
                <a href="gamificacao.php"
                    style="color: var(--primary-color); text-decoration: underline; font-weight: bold;">gameficação</a>.
            </p>
            <button type="button" class="btn-submit" id="btnEntendi"
                style="margin-top: 25px; background-color: #333; border: 1px solid #555;">Entendi</button>
        </div>
    </div>

    <div id="modalConfirmacao" class="modal">
        <div class="modal-content" style="text-align: center;">
            <span class="close-btn" id="fecharConfirmacao">&times;</span>
            <h2 style="color: var(--primary-color); margin-bottom: 15px;">Parabéns pelo foco!</h2>
            <p style="font-size: 1.1rem; margin-bottom: 20px;">Você tem certeza de que completou este hábito?</p>
            <div id="previewTaskConfirm" style="text-align: left; margin-bottom: 30px;"></div>
            <form action="concluir_tarefa.php" method="POST">
                <input type="hidden" name="rotina_id" id="inputRotinaId">
                <div style="display: flex; gap: 10px; flex-direction: column;">
                    <button type="submit" class="btn-submit" style="font-size: 1.1rem;">Sim, eu tenho!</button>
                    <button type="button" id="btnCancelarConfirmacao"
                        style="background: transparent; border: 1px solid #555; color: #888; padding: 10px; border-radius: 5px; cursor: pointer;">Não
                        completei :(</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: CONFIRMAR EXCLUSÃO (NOVA) -->
    <div id="modalDelete" class="modal">
        <div class="modal-content" style="text-align: center; max-width: 400px;">
            <span class="close-btn" id="fecharDelete">&times;</span>
            <h2 style="color: #ff4444; margin-bottom: 15px;">Apagar Rotina?</h2>
            <p style="color: #ddd; margin-bottom: 25px;"><strong>Esta ação permanente e vai apagar todo o histórico
                    desta tarefa.</strong> Tem certeza?</p>

            <a href="#" id="btnConfirmarDelete" class="btn-confirm-delete">Sim, apagar para sempre</a>

            <button type="button" id="btnCancelarDelete"
                style="background: transparent; border: 1px solid #555; color: #aaa; padding: 10px; border-radius: 5px; cursor: pointer; width: 100%;">Cancelar</button>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        const diasSiglaJS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
        const dataCelular = new Date();
        const diaIndex = dataCelular.getDay();
        const siglaCelular = diasSiglaJS[diaIndex];
        const divDiaCelular = document.getElementById('day-' + siglaCelular);
        if (divDiaCelular) {
            divDiaCelular.style.borderLeft = "5px solid var(--primary-color)";
            divDiaCelular.style.paddingLeft = "15px";
            const tituloDia = divDiaCelular.querySelector('.day-header');
            if (tituloDia) tituloDia.innerHTML += ' <span class="badge-hoje" style="margin-left:10px;">HOJE</span>';
        }
    </script>
</body>

</html>