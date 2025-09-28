<?php
    session_start();

    $erros = isset($_SESSION['erros_cadastro']) ? $_SESSION['erros_cadastro'] : [];
    $nome = isset($_SESSION['dados_cadastro']['nome']) ? $_SESSION['dados_cadastro']['nome'] : '';
    $email = isset($_SESSION['dados_cadastro']['email']) ? $_SESSION['dados_cadastro']['email'] : '';

    unset($_SESSION['erros_cadastro']);
    unset($_SESSION['dados_cadastro']);
?>

<!DOCTYPE html>
<html lang="pt-br">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cadastro de Médico</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="icon" type="image/png" href="./imgs/logo.png">
        <link rel="stylesheet" href="style.css">
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
                            <h3 class="text-center">Cadastro do Médico</h3>
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
                            <form action="processar_cadastro.php" method="POST">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome Completo</label>
                                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="crm" class="form-label">CRM</label>
                                    <input type="text" class="form-control" id="crm" name="crm" required>
                                    <small class="text-muted">Formato: CRM/UF 123456</small>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="senha" class="form-label">Senha</label>
                                    <input type="password" class="form-control" id="senha" name="senha" required minlength="6">
                                </div>
                                <div class="mb-3">
                                    <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                                </div>
                                <div class="row row-cols-2">
                                    <div class="col">
                                        <button type="reset" class="btn btn-custom-cinza w-100">Limpar Campos</button>
                                    </div>
                                    <div class="col">
                                        <button type="submit" class="btn btn-custom-azul w-100">Cadastrar</button>
                                    </div>
                                </div>
                            </form>
                            <div class="mt-3 text-center">
                                <a href="login.php" class="text-decoration-none" style="color: #11314d;">Já tem uma conta? Faça login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    </body>
    
</html>