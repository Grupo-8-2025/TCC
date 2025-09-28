<?php
    session_start();

    $erros = isset($_SESSION['erros_login']) ? $_SESSION['erros_login'] : [];
    $email = isset($_SESSION['email_login']) ? $_SESSION['email_login'] : '';
    unset($_SESSION['erros_login']);
    unset($_SESSION['email_login']);

    if (isset($_COOKIE['user_login']) && empty($email)) {
        $email = $_COOKIE['user_login'];
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
    
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - MedInova</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
        <link rel="icon" type="image/png" href="./imgs/icon.png">
    </head>

    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="text-center">
                        <img src="imgs/logo.png" alt="Logo MedBrainScan" width="250" height="100.625" class="mb-3 mt-3">
                    </div>
                    <div class="card shadow bg-body-tertiary rounded" style="border-radius: 15px;">
                        <div class="card-header card-header-custom">
                            <h3 class="text-center">Faça seu login</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($erros)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($erros as $erro): ?>
                                            <li><?php echo htmlspecialchars($erro); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <form action="processar_login.php" method="POST">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="senha" class="form-label">Senha</label>
                                    <input type="password" class="form-control" id="senha" name="senha" required>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                                    <label class="form-check-label" for="rememberMe">Lembrar de mim</label>
                                </div>
                                <div class="row row-cols-2">
                                    <div class="col">
                                        <button type="reset" class="btn btn-custom-cinza w-100">Limpar Campos</button>
                                    </div>
                                    <div class="col">
                                        <button type="submit" class="btn btn-custom-azul w-100">Entrar</button>
                                    </div>
                                </div>
                            </form>
                            <div class="mt-3 text-center">
                                <a href="cadastro.php" class="text-decoration-none" style="color: #11314d;">Não possui uma conta? Cadastra-se</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    </body>

</html>