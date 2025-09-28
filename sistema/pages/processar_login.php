<?php

    session_start();
    require_once 'conexao.php';

    function testarCamposObrigatorios(&$erros, $email, $senha){
        if (empty($email) || empty($senha)) {
            $erros[] = "Todos os campos são obrigatórios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Email inválido.";
        }
    }

    function iniciarSessao($medico, $senha, &$erros){
        if ($medico && password_verify($senha, $medico['senha'])) {
            $_SESSION['usuario_logado'] = true;
            $_SESSION['usuario_id'] = $medico['id'];
            $_SESSION['usuario_nome'] = $medico['nome'];
            $_SESSION['usuario_email'] = $medico['email'];
            $_SESSION['usuario_crm'] = $medico['crm'];
            
            if (isset($_POST['rememberMe']) && $_POST['rememberMe'] == 'on') {
                $cookie_value = $medico['email'];
                $expiration = time() + (60 * 60 * 24 * 30);
                setcookie('user_login', $cookie_value, $expiration, '/', '', true, true);
            }
            
            header("Location: dashboard.php");
            exit;
        } else {
            $erros[] = "Email ou senha incorretos.";
        }
    }

    function processarLogin($pdo, &$erros, $email, $senha){
        if (empty($erros)) {
            try {
                $sql = "SELECT id, nome, crm, email, senha FROM medicos WHERE email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['email' => $email]);
                $medico = $stmt->fetch(PDO::FETCH_ASSOC);

                iniciarSessao($medico, $senha, $erros);
                
            } catch (PDOException $e) {
                $erros[] = "Erro ao verificar credenciais: " . $e->getMessage();
            }
        }
    }

    function redirecionarParaLogin($erros){
        if (!empty($erros)) {
            $_SESSION['erros_login'] = $erros;
            $_SESSION['email_login'] = $email;
            header("Location: login.php");
            exit;
        }
    }

    function main($pdo){
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $senha = $_POST['senha'];

            $erros = [];

            testarCamposObrigatorios($erros, $email, $senha);
            processarLogin($pdo, $erros, $email, $senha);
            redirecionarParaLogin($erros);
        } else {
            header("Location: login.php");
            exit;
        }
    }

    main($pdo);

?>