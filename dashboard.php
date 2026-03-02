<?php
// Carrega o autologin (que já tem session_start)
require_once 'autologin.php';

date_default_timezone_set('America/Sao_Paulo');

// Se mesmo com autologin não tiver ID, aí sim expulsa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

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
$stmt = $pdo->prepare("SELECT nome, pontos, username, xp_total, nivel FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$dados_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$nome_usuario = $dados_usuario['nome'];
$username = $dados_usuario['username'];
$pontos_totais = $dados_usuario['pontos'];
$xp_total = $dados_usuario['xp_total'];
$nivel_atual = $dados_usuario['nivel'];

// --- CÁLCULOS DA BARRA DE PROGRESSO ---
// (A lógica visual continua a mesma para desenhar a barra)
$xp_base_nivel_atual = pow($nivel_atual - 1, 2) * 50;
$xp_para_proximo_nivel = pow($nivel_atual, 2) * 50;
$xp_no_nivel = $xp_total - $xp_base_nivel_atual;
$tamanho_do_nivel = $xp_para_proximo_nivel - $xp_base_nivel_atual;
$porcentagem_xp = 0;
if ($tamanho_do_nivel > 0) {
    $porcentagem_xp = ($xp_no_nivel / $tamanho_do_nivel) * 100;
}

// --- VERIFICA SE SUBIU DE NÍVEL (Sessão) ---
$exibir_modal_levelup = false;
$novo_nivel_conquistado = 0;

if (isset($_SESSION['level_up'])) {
    $exibir_modal_levelup = true;
    $novo_nivel_conquistado = $_SESSION['level_up'];
    unset($_SESSION['level_up']); // Limpa para não aparecer de novo ao recarregar
}

// --- SISTEMA DE NÍVEIS (RPG) ---
// Fórmula: Nível = RaizQuadrada(XP / 50) + 1
// Isso cria uma curva onde fica mais difícil subir conforme o nível aumenta.

$nivel_atual = floor(sqrt($xp_total / 50)) + 1;
if ($nivel_atual > 100)
    $nivel_atual = 100; // Trava no nível 100

// Cálculos para a Barra de Progresso
// Quanto XP precisava para chegar NESTE nível?
$xp_base_nivel_atual = pow($nivel_atual - 1, 2) * 50;

// Quanto XP precisa para o PRÓXIMO nível?
$xp_para_proximo_nivel = pow($nivel_atual, 2) * 50;

// Quanto XP eu tenho DENTRO deste nível?
$xp_no_nivel = $xp_total - $xp_base_nivel_atual;

// Qual o tamanho total deste nível (do início ao fim)?
$tamanho_do_nivel = $xp_para_proximo_nivel - $xp_base_nivel_atual;

// Porcentagem (0 a 100%)
$porcentagem_xp = 0;
if ($tamanho_do_nivel > 0) {
    $porcentagem_xp = ($xp_no_nivel / $tamanho_do_nivel) * 100;
}

// 3. Buscar Rotinas
$stmt_rotinas = $pdo->prepare("SELECT * FROM rotinas WHERE usuario_id = :uid ORDER BY id DESC");
$stmt_rotinas->execute([':uid' => $_SESSION['usuario_id']]);
$todas_rotinas = $stmt_rotinas->fetchAll(PDO::FETCH_ASSOC);

// 4. Buscar Conclusões
$stmt_conclusoes = $pdo->prepare("SELECT rotina_id, data_conclusao FROM conclusoes WHERE usuario_id = :uid");
$stmt_conclusoes->execute([':uid' => $_SESSION['usuario_id']]);
$todas_conclusoes = $stmt_conclusoes->fetchAll(PDO::FETCH_ASSOC);

$mapa_feitos = [];
foreach ($todas_conclusoes as $c) {
    $mapa_feitos[$c['rotina_id'] . '_' . $c['data_conclusao']] = true;
}

// 5. BUSCAR RECOMPENSAS (NOVO)
$stmt_recompensas = $pdo->prepare("SELECT * FROM recompensas WHERE usuario_id = :uid ORDER BY preco ASC");
$stmt_recompensas->execute([':uid' => $_SESSION['usuario_id']]);
$todas_recompensas = $stmt_recompensas->fetchAll(PDO::FETCH_ASSOC);

// 6. BUSCAR COMPRAS DE HOJE (NOVO)
// DATE(data_compra) pega só o dia (ignora hora), CURDATE() é o dia de hoje
$stmt_compras = $pdo->prepare("SELECT * FROM historico_compras WHERE usuario_id = :uid AND DATE(data_compra) = CURDATE() ORDER BY id DESC");
$stmt_compras->execute([':uid' => $_SESSION['usuario_id']]);
$compras_hoje = $stmt_compras->fetchAll(PDO::FETCH_ASSOC);

// Configs Data
$hoje_formatado = date('Y-m-d');
$dia_semana_hoje = date('N');
$ordem_dias = ['Seg' => 1, 'Ter' => 2, 'Qua' => 3, 'Qui' => 4, 'Sex' => 5, 'Sab' => 6, 'Dom' => 7];
$nomes_dias_extenso = ['Seg' => 'Segunda-feira', 'Ter' => 'Terça-feira', 'Qua' => 'Quarta-feira', 'Qui' => 'Quinta-feira', 'Sex' => 'Sexta-feira', 'Sab' => 'Sábado', 'Dom' => 'Domingo'];
$sigla_hoje = array_search($dia_semana_hoje, $ordem_dias);

// Filtrar Rotinas Hoje
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
                <li><a href="#" class="active">Minha Rotina</a></li>
                <li><a href="#">Desempenho</a></li>
                <li class="dropdown">
                    <a href="javascript:void(0)" id="btnPerfil" class="dropbtn">Perfil <span class="arrow"></span></a>
                    <div id="myDropdown" class="dropdown-content">
                        <a href="#">Conta</a>
                        <a href="#">Configurações</a>
                        <hr style="border: 0; border-top: 1px solid #444; margin: 0;">
                        <a href="logout.php" class="logout-btn">
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
                    <p style="color: var(--text-muted);">Você tem o poder de mudar sua vida.</p>
                </div>

                <!-- Container dos Badges -->
                <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">

                    <!-- Container dos Status (XP e DT) -->
                    <div class="stats-container">

                        <!-- BARRA DE NÍVEL (XP) -->
                        <div class="level-container">
                            <div class="level-info">
                                <span class="level-badge">LVL <?= $nivel_atual ?></span>
                                <span class="xp-text"><?= number_format($xp_no_nivel, 0, ',', '.') ?> /
                                    <?= number_format($tamanho_do_nivel, 0, ',', '.') ?> XP</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: <?= $porcentagem_xp ?>%;"></div>
                            </div>
                        </div>

                        <!-- Badge de DT Points (Dinheiro) -->
                        <div class="dt-points-badge">
                            <div class="coin-icon"></div>
                            <span class="points-value"><?= number_format($pontos_totais, 0, ',', '.') ?></span>
                            <span class="points-label">DT</span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ABAS -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="abrirTab(event, 'tab-hoje')">Hoje</button>
                    <button class="tab-btn" onclick="abrirTab(event, 'tab-semana')">Semana</button>
                    <button class="tab-btn" onclick="abrirTab(event, 'tab-loja')">Loja 🛒</button>
                    <button class="tab-btn" onclick="abrirTab(event, 'tab-premios')">Meus Prêmios 🎁</button>
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
                                                style="color: gold">+<?= $rotina['pontos_recompensa'] ?> DT</span></p>
                                        <?php if ($rotina['descricao_personalizada']): ?><small
                                                style="color: #888;">"<?= htmlspecialchars($rotina['descricao_personalizada']) ?>"</small><?php endif; ?>
                                    </div>
                                    <div style="display: flex; align-items: center;">
                                        <?php if ($ja_fez_hoje): ?><span class="status-done">✔ Feito</span>
                                        <?php else: ?>
                                            <button class="btn-check"
                                                onclick="abrirConfirmacao(<?= $rotina['id'] ?>, '<?= htmlspecialchars($rotina['tipo_meta']) ?>', <?= $rotina['duracao_minutos'] ?>, <?= $rotina['pontos_recompensa'] ?>, '<?= htmlspecialchars($rotina['descricao_personalizada'] ?? '') ?>')">✔</button>
                                        <?php endif; ?>

                                        <div class="action-buttons" style="display: flex; gap: 5px;">

                                            <!-- BOTÃO EDITAR (MANTIDO INTACTO) -->
                                            <button class="btn-icon btn-edit"
                                                onclick="abrirModalEditar(<?= $rotina['id'] ?>, '<?= htmlspecialchars($rotina['tipo_meta']) ?>', '<?= htmlspecialchars($rotina['descricao_personalizada'] ?? '') ?>', <?= $rotina['duracao_minutos'] ?>, '<?= $rotina['dias_semana'] ?>')">
                                                ✏️
                                            </button>

                                            <!-- NOVO BOTÃO DE LIXEIRA (Estilo Loja) -->
                                            <a href="excluir_rotina.php?id=<?= $rotina['id'] ?>" class="btn-trash-shop"
                                                onclick="return confirm('Tem certeza que deseja apagar esta rotina? Todo o histórico dela será perdido.')"
                                                style="width: 35px; height: 35px; margin: 0; padding: 0;" title="Apagar Rotina">

                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path
                                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                    </path>
                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                </svg>
                                            </a>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #777; padding: 20px; text-align: center;">Nada para hoje.</p>
                        <?php endif; ?>
                        <button id="btnAbrirModalCriar" class="btn-add-task">+ Novo Hábito</button>
                    </div>
                </div>

                <!-- ABA SEMANA -->
                <div id="tab-semana" class="tab-content" style="display: none;">
                    <?php
                    $ano_atual = date('Y');
                    $semana_atual = date('W');

                    foreach ($ordem_dias as $sigla => $num_dia_iso):
                        $data_calculada = new DateTime();
                        $data_calculada->setISODate($ano_atual, $semana_atual, $num_dia_iso);
                        $data_string = $data_calculada->format('Y-m-d');

                        // 1. Filtra rotinas deste dia específico
                        $rotinas_deste_dia = [];
                        foreach ($todas_rotinas as $r) {
                            if (strpos($r['dias_semana'], $sigla) !== false) {
                                $rotinas_deste_dia[] = $r;
                            }
                        }

                        // 2. Processa status e contagens
                        $perdidas = 0;
                        $concluidas_count = 0; // Contador de tarefas feitas
                        $lista_renderizada = [];

                        foreach ($rotinas_deste_dia as $r) {
                            $chave = $r['id'] . '_' . $data_string;
                            $foi_feito = isset($mapa_feitos[$chave]);
                            $eh_passado = ($data_string < $hoje_formatado);
                            $data_criacao_rotina = date('Y-m-d', strtotime($r['data_criacao']));

                            if ($foi_feito) {
                                $status = "feito";
                                $concluidas_count++; // Soma +1 concluída
                            } elseif ($eh_passado) {
                                if ($data_string < $data_criacao_rotina) {
                                    $status = "proxima_semana";
                                } else {
                                    $status = "perdido";
                                    $perdidas++;
                                }
                            } else {
                                $status = "futuro";
                            }
                            $lista_renderizada[] = ['r' => $r, 'status' => $status];
                        }

                        // Verifica se completou TODAS (Total > 0 e Concluídas == Total)
                        $dia_perfeito = (count($rotinas_deste_dia) > 0 && $concluidas_count == count($rotinas_deste_dia));
                        ?>

                        <div class="day-group" id="day-<?= $sigla ?>">
                            <h3 class="day-header">
                                <?= $nomes_dias_extenso[$sigla] ?>
                                <span style="font-size: 0.8rem; color: #555;">(<?= $data_calculada->format('d/m') ?>)</span>
                            </h3>

                            <!-- MENSAGEM DE 100% CONCLUÍDO (NOVO) -->
                            <?php if ($dia_perfeito): ?>
                                <div class="msg-dia-completo">
                                    <span>🏆</span> Dia dominado! Todas as tarefas concluídas.
                                </div>
                            <?php endif; ?>

                            <!-- Alerta de perdidas -->
                            <?php if ($perdidas > 0): ?>
                                <div class="missed-alert">⚠ <?= $perdidas ?> hábito(s) não feitos</div>
                            <?php endif; ?>

                            <!-- Lista de Tarefas -->
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
                                                    <span class="status-done">✔ Feito</span>
                                                <?php elseif ($status == 'perdido'): ?>
                                                    <span style="color: #ff5555; font-size: 0.8rem;">Não feito</span>
                                                <?php elseif ($status == 'proxima_semana'): ?>
                                                    <span class="status-next-week">Próxima semana</span>
                                                <?php else: ?>
                                                    <span style="color: #888; font-size: 0.8rem;">Pendente</span>
                                                <?php endif; ?>

                                                <div class="action-buttons" style="display: flex; gap: 5px;">

                                                    <!-- BOTÃO EDITAR (MANTIDO INTACTO) -->
                                                    <button class="btn-icon btn-edit"
                                                        onclick="abrirModalEditar(<?= $rotina['id'] ?>, '<?= htmlspecialchars($rotina['tipo_meta']) ?>', '<?= htmlspecialchars($rotina['descricao_personalizada'] ?? '') ?>', <?= $rotina['duracao_minutos'] ?>, '<?= $rotina['dias_semana'] ?>')">
                                                        ✏️
                                                    </button>

                                                    <!-- NOVO BOTÃO DE LIXEIRA (Estilo Loja) -->
                                                    <a href="excluir_rotina.php?id=<?= $rotina['id'] ?>" class="btn-trash-shop"
                                                        onclick="return confirm('Tem certeza que deseja apagar esta rotina?')"
                                                        style="width: 35px; height: 35px; margin: 0; padding: 0;"
                                                        title="Apagar Rotina">

                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="3 6 5 6 21 6"></polyline>
                                                            <path
                                                                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                            </path>
                                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                                        </svg>
                                                    </a>

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

                <!-- NOVA ABA: LOJA -->
                <div id="tab-loja" class="tab-content" style="display: none;">
                    <div class="task-list">
                        <?php if (count($todas_recompensas) > 0): ?>
                            <?php foreach ($todas_recompensas as $rec):
                                // Verifica se o usuário tem saldo suficiente
                                $pode_comprar = ($pontos_totais >= $rec['preco']);
                                ?>
                                <div class="task-card">
                                    <div class="task-info">
                                        <h4><?= htmlspecialchars($rec['nome']) ?></h4>
                                        <p>
                                            <?php if ($rec['tem_tempo']): ?>
                                                <?= $rec['duracao_minutos'] ?> min •
                                            <?php endif; ?>
                                            <span style="color: gold; font-weight: bold;">💎 <?= $rec['preco'] ?> DT</span>
                                        </p>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 10px;">

                                        <!-- Lógica do Botão Comprar -->
                                        <?php if ($pode_comprar): ?>
                                            <button class="btn-buy"
                                                onclick="abrirModalCompra(<?= $rec['id'] ?>, '<?= htmlspecialchars($rec['nome']) ?>', <?= $rec['preco'] ?>)">
                                                Comprar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-buy" disabled style="opacity: 0.5; cursor: not-allowed;">
                                                Faltam DT
                                            </button>
                                        <?php endif; ?>

                                        <!-- NOVO BOTÃO DE LIXEIRA (Com ícone e estilo novo) -->
                                        <a href="excluir_recompensa.php?id=<?= $rec['id'] ?>" class="btn-trash-shop"
                                            onclick="return confirm('Tem certeza que deseja excluir esta recompensa?')"
                                            title="Excluir item da loja">

                                            <!-- Ícone da Lixeira (SVG) -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                </path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </a>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #777; padding: 20px; text-align: center;">A loja está vazia. Crie sua primeira
                                recompensa!</p>
                        <?php endif; ?>

                        <button id="btnNovaRecompensa" class="btn-add-task">+ Uma Recompensa</button>
                    </div>
                </div>

                <!-- ABA MEUS PRÊMIOS (SÓ DE HOJE) -->
                <div id="tab-premios" class="tab-content" style="display: none;">
                    <div class="task-list">
                        <?php if (count($compras_hoje) > 0): ?>

                            <p style="text-align: center; color: #00e676; margin-bottom: 20px;">
                                🎉 Você conquistou isso hoje! Aproveite!
                            </p>

                            <?php foreach ($compras_hoje as $compra):
                                // Formatar a hora da compra (ex: 14:30)
                                $hora_compra = date('H:i', strtotime($compra['data_compra']));
                                ?>
                                <div class="task-card" style="border-left: 4px solid #00e676;">
                                    <div class="task-info">
                                        <!-- Nome do Prêmio -->
                                        <h4 style="color: #00e676;"><?= htmlspecialchars($compra['nome_recompensa']) ?></h4>

                                        <!-- Detalhes -->
                                        <p style="color: #aaa; font-size: 0.85rem;">
                                            Comprado às <?= $hora_compra ?>
                                        </p>
                                    </div>

                                    <!-- Valor Pago -->
                                    <div style="text-align: right;">
                                        <span style="display: block; color: gold; font-weight: bold; font-size: 0.9rem;">
                                            - <?= $compra['preco_pago'] ?> pts
                                        </span>
                                        <span style="font-size: 0.8rem; color: #555;">Pago</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <div style="text-align: center; padding: 40px 20px;">
                                <p style="font-size: 3rem; margin-bottom: 10px;">🎁</p>
                                <p style="color: #777;">Você ainda não comprou nenhuma recompensa hoje.</p>
                                <button class="btn-buy" onclick="abrirTab(event, 'tab-loja')" style="margin-top: 15px;">Ir
                                    para a Loja</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <button class="btn-importante">Importante</button>
        </div>
    </main>

    <!-- MODAL 1: CRIAR/EDITAR ROTINA -->
    <div id="modalMeta" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="modalTitulo" style="color: var(--primary-color); margin-bottom: 5px;">Configurar Rotina</h2>

            <form id="formRotina" class="modal-form" action="salvar_rotina.php" method="POST">
                <!-- INPUT OCULTO PARA EDIÇÃO -->
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
                <input type="number" name="duracao" id="inputMinutos" class="input-minutos" placeholder="00" min="1"
                    max="1440" required>

                <div
                    style="background-color: #2a2a2a; padding: 10px; border-radius: 5px; text-align: center; margin-top: 10px;">
                    <span style="color: #bbb; font-size: 0.9rem;">Valor desta rotina:</span><br>
                    <span id="pontosPreview" style="color: gold; font-weight: bold; font-size: 1.4rem;">0</span>
                    <span style="color: gold;">DT</span>
                </div>

                <p style="text-align: center; font-size: 0.7rem; color: #666; margin-top: 5px;">(1 minuto = 3 pontos)
                </p>

                <button type="submit" id="btnSalvarRotina" class="btn-submit" style="margin-top: 20px;">CRIAR
                    ROTINA</button>
            </form>
        </div>
    </div>

    <!-- MODAL 2: IMPORTANTE -->
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
                <a href="#" id="linkGamificacao"
                    style="color: var(--primary-color); text-decoration: underline; font-weight: bold;">gamificação</a>.
            </p>
            <button type="button" class="btn-submit" id="btnEntendi"
                style="margin-top: 25px; background-color: #333; border: 1px solid #555;">Entendi</button>
        </div>
    </div>

    <!-- MODAL 3: GAMIFICAÇÃO (INFO) -->
    <div id="modalGamificacao" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="fecharGamificacao">&times;</span>

            <h2 style="color: var(--primary-color); margin-bottom: 20px; text-align: center;">Sistema de Gamificação
            </h2>

            <!-- Área com scroll caso o texto seja grande em telas pequenas -->
            <div style="overflow-y: auto; max-height: 60vh; padding-right: 5px;">

                <p style="color: #ddd; line-height: 1.6; margin-bottom: 15px;">
                    A <strong>Gamificação</strong> transforma sua rotina em um RPG. Cada tarefa concluída te dá
                    experiência (DT Points).
                </p>

                <h3 style="color: white; margin-top: 20px; border-bottom: 1px solid #333; padding-bottom: 5px;">Como
                    ganhar pontos?</h3>
                <p style="color: #aaa; font-size: 0.9rem; margin-bottom: 10px;">A regra é simples: <strong>1 minuto de
                        foco = 3 DT Points</strong>.</p>

                <!-- Tabela de Exemplos -->
                <table style="width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 20px;">
                    <tr style="border-bottom: 1px solid #444;">
                        <th style="padding: 10px; color: var(--primary-color);">Atividade</th>
                        <th style="padding: 10px; color: var(--primary-color);">Tempo</th>
                        <th style="padding: 10px; color: gold;">Recompensa</th>
                    </tr>
                    <tr style="border-bottom: 1px solid #333;">
                        <td style="padding: 10px; color: #ccc;">Ler 10 págs</td>
                        <td style="padding: 10px; color: #ccc;">10 min</td>
                        <td style="padding: 10px; color: gold; font-weight: bold;">+30 pts</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #333;">
                        <td style="padding: 10px; color: #ccc;">Treino Rápido</td>
                        <td style="padding: 10px; color: #ccc;">30 min</td>
                        <td style="padding: 10px; color: gold; font-weight: bold;">+90 pts</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; color: #ccc;">Estudo Focado</td>
                        <td style="padding: 10px; color: #ccc;">1 hora</td>
                        <td style="padding: 10px; color: gold; font-weight: bold;">+180 pts</td>
                    </tr>
                </table>

                <h3 style="color: white; margin-top: 20px; border-bottom: 1px solid #333; padding-bottom: 5px;">Para que
                    servem?</h3>
                <p style="color: #ddd; line-height: 1.6;">
                    Use seus pontos na <strong>Loja</strong> para comprar recompensas que você mesmo define!
                </p>
            </div>

            <button type="button" id="btnVoltarImportante" class="btn-submit"
                style="margin-top: 20px; background-color: #333; border: 1px solid #555;">Voltar</button>
        </div>
    </div>

    <!-- MODAL 4: CONFIRMAÇÃO (CHECK) -->
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

    <!-- MODAL 5: CONFIRMAR EXCLUSÃO (DELETE) -->
    <div id="modalDelete" class="modal">
        <div class="modal-content" style="text-align: center; max-width: 400px;">
            <span class="close-btn" id="fecharDelete">&times;</span>
            <h2 style="color: #ff4444; margin-bottom: 15px;">Apagar Rotina?</h2>
            <p style="color: #ddd; margin-bottom: 25px;">Esta ação é permanente e vai apagar todo o histórico desta
                tarefa. Tem certeza?</p>
            <a href="#" id="btnConfirmarDelete" class="btn-confirm-delete">Sim, apagar para sempre</a>
            <button type="button" id="btnCancelarDelete"
                style="background: transparent; border: 1px solid #555; color: #aaa; padding: 10px; border-radius: 5px; cursor: pointer; width: 100%;">Cancelar</button>
        </div>
    </div>

    <!-- MODAL 5: NOVA RECOMPENSA (LOJA) -->
    <div id="modalRecompensa" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeRecompensa">&times;</span>
            <h2 style="color: #00e676; margin-bottom: 5px;">Nova Recompensa</h2>
            <form class="modal-form" action="salvar_recompensa.php" method="POST">

                <label>Escreva aqui a sua recompensa:</label>
                <input type="text" name="nome_recompensa" placeholder="Ex: Jogar Videogame, Comer Chocolate..."
                    required>

                <!-- Bloco do Checkbox com o Ícone -->
                <div style="display: flex; align-items: center; margin-top: 15px; margin-bottom: 10px;">

                    <!-- Checkbox e Texto -->
                    <label style="margin: 0; cursor: pointer; display: flex; align-items: center;">
                        <input type="checkbox" name="tem_tempo" id="checkTemTempo"
                            style="width: auto; margin-right: 8px;">
                        Possui tempo?
                    </label>

                    <!-- O ÍCONE DE INTERROGAÇÃO -->
                    <div class="help-icon" onclick="toggleTooltip(this)">
                        ?
                        <div class="help-tooltip">
                            Esta pergunta se refere a, por exemplo, jogar video game por <em>30 minutos</em>.<br><br>
                            Comer um doce, ou ações deste tipo, <strong>não precisam</strong> deste campo.
                        </div>
                    </div>

                </div>

                <div id="divTempoRecompensa" style="display: none;">
                    <label>Selecione, abaixo, o tempo desejado (em minutos):</label>
                    <input type="number" name="duracao_recompensa" class="input-minutos" placeholder="00" min="1"
                        max="1440" style="color: #00e676 !important;">
                </div>

                <label>Quantidade de pontos para comprar:</label>
                <input type="number" name="preco" class="input-minutos" placeholder="100" min="100" max="10000" required
                    style="color: gold !important;">
                <p style="text-align: center; font-size: 0.7rem; color: #666;">(Mín: 100 | Máx: 10.000)</p>

                <button type="submit" class="btn-submit"
                    style="margin-top: 20px; background-color: #00e676; color: black;">ADICIONAR À LOJA</button>
            </form>
        </div>
    </div>

    <!-- MODAL 6: CONFIRMAR COMPRA -->
    <div id="modalCompra" class="modal">
        <div class="modal-content" style="text-align: center;">
            <span class="close-btn" id="closeCompra">&times;</span>
            <h2 style="color: #00e676; margin-bottom: 15px;">Comprar Recompensa?</h2>
            <p id="textoConfirmacaoCompra" style="font-size: 1.1rem; color: #ddd; margin-bottom: 20px;"></p>

            <form action="comprar_recompensa.php" method="POST">
                <input type="hidden" name="id_recompensa" id="idRecompensaCompra">
                <button type="submit" class="btn-submit"
                    style="background-color: #00e676; color: black; font-weight: bold;">CONFIRMAR COMPRA</button>
                <button type="button" id="btnCancelarCompra"
                    style="margin-top: 10px; background: transparent; border: 1px solid #555; color: #aaa; padding: 10px; border-radius: 5px; cursor: pointer; width: 100%;">Cancelar</button>
            </form>
        </div>
    </div>

    <!-- MODAL LEVEL UP! 🎉 -->
    <?php if ($exibir_modal_levelup): ?>
        <div id="modalLevelUp" class="modal" style="display: block;">
            <div class="modal-content level-up-content">
                <span class="close-btn"
                    onclick="document.getElementById('modalLevelUp').style.display='none'">&times;</span>

                <div class="levelup-icon">⭐</div>

                <h2 class="levelup-title">LEVEL UP!</h2>
                <p style="color: #ddd; margin-bottom: 20px;">Você alcançou um novo patamar de disciplina.</p>

                <div class="levelup-badge">
                    NÍVEL <?= $novo_nivel_conquistado ?>
                </div>

                <p style="font-size: 0.9rem; color: #888; margin-top: 20px;">Continue focado. O próximo nível te espera.</p>

                <button class="btn-submit" onclick="document.getElementById('modalLevelUp').style.display='none'"
                    style="margin-top: 25px; background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%); border: none;">CONTINUAR</button>
            </div>
        </div>
        <!-- Som de Level Up (Opcional - Truque simples) -->
        <audio autoplay>
            <source src="https://assets.mixkit.co/active_storage/sfx/2013/2013-preview.mp3" type="audio/mpeg">
        </audio>
    <?php endif; ?>

    <script src="script.js"></script>
    <!-- Script Inline (Celular) -->
    <script>
        const diasSiglaJS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
        const dataCelular = new Date();
        const siglaCelular = diasSiglaJS[dataCelular.getDay()];
        const divDiaCelular = document.getElementById('day-' + siglaCelular);
        if (divDiaCelular) {
            divDiaCelular.style.borderLeft = "5px solid var(--primary-color)";
            divDiaCelular.style.paddingLeft = "15px";
            divDiaCelular.querySelector('.day-header').innerHTML += ' <span class="badge-hoje" style="margin-left:10px;">HOJE</span>';
        }
    </script>
</body>

</html>