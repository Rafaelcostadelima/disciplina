<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já está logado, não precisa fazer nada
if (isset($_SESSION['usuario_id'])) {
    return;
}

// Se NÃO está logado, mas tem o cookie 'lembrar_token'
if (isset($_COOKIE['lembrar_token'])) {
    
    // Conexão (Copiada para garantir funcionamento isolado)
    $host = 'localhost'; $db = 'disciplina_db'; $user = 'root'; $pass = '';
    try {
        $pdo_login = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $pdo_login->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) { return; }

    // O cookie guarda "ID:TOKEN" (ex: 5:a7f8...)
    $dados_cookie = explode(':', $_COOKIE['lembrar_token']);
    
    if (count($dados_cookie) == 2) {
        $id_usuario = $dados_cookie[0];
        $token_cookie = $dados_cookie[1];

        // Busca o token salvo no banco para esse usuário
        $stmt = $pdo_login->prepare("SELECT id, nome, token_login FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id_usuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se o token do banco bate com o do cookie
        if ($usuario && $usuario['token_login'] === $token_cookie) {
            // SUCESSO! Recria a sessão automaticamente
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            
            // Renova o cookie por mais 30 dias (para não expirar logo)
            setcookie('lembrar_token', $id_usuario . ':' . $token_cookie, time() + (30 * 24 * 60 * 60), "/");
        }
    }
}
?>