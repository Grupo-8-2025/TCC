<?php

    session_start();

    require_once 'conexao.php';

    function testarCamposObrigatorios(&$erros, $nome, $email, $crm, $senha, $confirmar_senha){
        if (empty($nome) || empty($email) || empty($crm) || empty($senha) || empty($confirmar_senha)) {
            $erros[] = "Todos os campos são obrigatórios.";
        }
        if (!preg_match('/^CRM\/[A-Z]{2}\s\d+$/', $crm)) {
            $erros[] = "Formato de CRM inválido. Use: CRM/UF 123456";
        }
        if (strlen($senha) < 6) {
            $erros[] = "A senha deve ter pelo menos 6 caracteres.";
        }
        if ($senha !== $confirmar_senha) {
            $erros[] = "As senhas não coincidem.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Email inválido.";
        }
    }

    function verificarCadastroNoBanco($pdo, &$erros){
        if (empty($erros)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM medicos WHERE email = :email");
                $stmt->execute(['email' => $email]);
                
                if ($stmt->rowCount() > 0) {
                    $erros[] = "Este email já está cadastrado.";
                }
            } catch (PDOException $e) {
                $erros[] = "Erro ao verificar email: " . $e->getMessage();
            }
        }
    }

    function processarCadastro(&$erros, $pdo, $nome, $email, $crm, $senha){
        if (empty($erros)) {
            try {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO medicos (nome, crm, email, senha) VALUES (:nome, :crm, :email, :senha)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'nome' => $nome,
                    'email' => $email,
                    'crm' => $crm,
                    'senha' => $senha_hash
                ]);

                header("Location: login.php?sucesso=1");
                exit;
            } catch (PDOException $e) {
                $erros[] = "Erro ao cadastrar usuário: " . $e->getMessage();
            }
        }
    }

    function redirecionarParaCadastro($erros, $nome, $email){
        if (!empty($erros)) {
            $_SESSION['erros_cadastro'] = $erros;
            $_SESSION['dados_cadastro'] = [
                'nome' => $nome,
                'email' => $email
            ];
            header("Location: cadastro_sistema.php");
            exit;
        }
    }

    function main($pdo){
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $crm = trim($_POST['crm']);
            $senha = $_POST['senha'];
            $confirmar_senha = $_POST['confirmar_senha'];

            $erros = [];

            testarCamposObrigatorios($erros, $nome, $email, $crm, $senha, $confirmar_senha);
            verificarCadastroNoBanco($pdo, $erros);
            processarCadastro($erros, $pdo, $nome, $email, $crm, $senha);
            redirecionarParaCadastro($erros, $nome, $email);
        }
    }

    main($pdo);

?>