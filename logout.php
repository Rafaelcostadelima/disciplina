<?php
session_start();

// 1. Limpa a Sessão
session_unset();
session_destroy();

// 2. Destrói o Cookie (Colocando data no passado)
if (isset($_COOKIE['lembrar_token'])) {
    setcookie('lembrar_token', '', time() - 3600, "/");
}

// Opcional: Limpar token do banco também para segurança extra
// (Exige conexão com banco aqui, mas só deletar o cookie já resolve 99%)

header("Location: index.php");
exit;
?>