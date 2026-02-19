<?php
session_start();
session_destroy(); // Destroi todas as variáveis de sessão (nome, id, etc)
header("Location: index.php"); // Manda de volta pra tela de login
exit;
?>