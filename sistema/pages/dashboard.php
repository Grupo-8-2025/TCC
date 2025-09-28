<?php
    session_start();

    if (!isset($_SESSION['usuario_logado'])) {
        header("Location: login.php");
        exit;
    }

    $erros = isset($_SESSION['erros_exame']) ? $_SESSION['erros_exame'] : [];
    $nome = isset($_SESSION['dados_exame']['nomePaciente']) ? $_SESSION['dados_exame']['nomePaciente'] : '';
    $email = isset($_SESSION['dados_exame']['cpfPaciente']) ? $_SESSION['dados_exame']['cpfPaciente'] : '';

    unset($_SESSION['erros_exame']);
    unset($_SESSION['dados_exame']);

    require_once 'retornar_quantitativos.php';

    require_once 'retornar_paciente.php';
    $paciente = $_SESSION['paciente'] ?? [];

    require_once 'retornar_exames.php';
    $exames = $informacoes_exames[0];
    $num_exames = $informacoes_exames[1];

    unset($_SESSION['paciente']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tela Inicial - MedInova</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="./imgs/icon.png">
    <link rel="stylesheet" href="style.css">
    <script src="script_papaya.js"></script>

    <style>
        body {
            background-color: #e9e7e4;
        }
    </style>

</head>
    
<body>
    
    <nav class="navbar navbar-custom navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="imgs/logo.png" alt="Logo MedBrainScan" width="200" height="80.50" class="mb-3 mt-3">
            </a>
            <form class="d-flex me-auto" role="search" action="" method="POST">
                <input class="form-control me-2" type="search" id="cpf_paciente" name="cpf_paciente" placeholder="Digite o CPF do paciente" aria-label="Pesquisar" style="border: 2px solid; border-color: #309ea1; width: 17rem;">
                <button class="btn btn-custom-verde" type="submit">Buscar</button>
            </form>
            <div class="navbar-nav ms-auto">
                <span class="nav-link me-3">
                    <strong>Dr. <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>
                </span>
                <a class="nav-link btn btn-custom-sair" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i><strong>Sair</strong>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-3 col-lg-2 d-md-block sidebar sidebar-custom p-3">
                <div class="text-center mt-5 mb-5">
                    <h5 class="mt-2"><strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></h5>
                    <small class="text"><strong>CRM: <?php echo htmlspecialchars($_SESSION['usuario_crm']); ?></strong></small>
                </div>
                <div class="col mb-5">
                    <div class="card card-custom">
                        <div class="card-body">
                            <p class="card-title"><strong>Pacientes Atendidos</strong></p>
                            <h5 class="mb-0"><?php echo $quantitativos_medico['total_pacientes'] ?? '0'; ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col mb-5">
                    <div class="card card-custom">
                        <div class="card-body">
                            <p class="card-title"><strong>Total de Exames Realizados</strong></p>
                            <h5 class="mb-0"><?php echo $quantitativos_medico['total_exames'] ?? '0'; ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2" style="color: #11314d;"><strong>Tela Inicial</strong></h1>
                    <button class="btn btn-custom-azul" data-bs-toggle="modal" data-bs-target="#novoExameModal">
                        <i class="bi bi-upload me-1"></i> Novo Exame
                    </button>
                </div>

                <div class="row mb-4">
                    <div class="col-md-5">
                        <div class="card card-custom">
                            <div class="card-body">
                                <h5 class="card-title">Exames de <?php echo $paciente['nome'] ?? '0'; ?> </h5>
                                <h2 class="mb-0"><?php echo $num_exames['COUNT(*)'] ?? '0'; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="card mb-4">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-image me-2"></i> Visualizador de Exames</h5>
                    </div>
                    <div class="card-body d-flex justify-content-center">
                        
                        <div id="viewerContainer">
                            
                            <div id="viewer3D">
                                <div class="papaya"></div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card">
                    
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i> Últimos Exames de <?php echo $paciente['nome'] ?? '0'; ?> </h5>
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

                        <div class="row">
                            <?php if (empty($exames)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Nenhum exame encontrado. Faça upload do primeiro exame!
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($exames as $exame): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card card-exame" 
                                            onclick="carregarExame(
                                                '<?php echo !empty($exame['nome_arquivo_segmentada']) 
                                                    ? '/sistema/imagens/imagens_segmentadas/' . $exame['nome_arquivo_segmentada'] 
                                                    : '/sistema/imagens/imagens_originais/' . $exame['nome_arquivo_original']; ?>',
                                                '<?php echo $exame['tipo_arquivo'] === 'DICOM' ? 'dcm' : 'nii.gz'; ?>'
                                            )">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($exame['nome_paciente']); ?></h5>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y H:i', strtotime($exame['data_upload'])); ?>
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        Tipo: <?php echo htmlspecialchars($exame['tipo_arquivo']); ?>
                                                    </small>
                                                </p>
                                                
                                                <?php if ($exame['diagnostico_ia']): ?>
                                                    <p class="card-text"> 
                                                        <small>Diagnóstico:<br><?php echo nl2br(htmlspecialchars($exame['diagnostico_ia'])); ?></small>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if ($exame['observacoes']): ?>
                                                    <p class="card-text">
                                                        <small class="text-muted">Obs: <?php echo htmlspecialchars(substr($exame['observacoes'], 0, 50)); ?><?php echo strlen($exame['observacoes']) > 50 ? '...' : ''; ?></small>
                                                    </p>
                                                <?php endif; ?>

                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </main>

        </div>
    </div>

    <!-- Modal para novo exame -->
    <div class="modal fade" id="novoExameModal" tabindex="-1" aria-labelledby="novoExameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title" id="novoExameModalLabel">Enviar Novo Exame</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">

                    <?php if (!empty($erros)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($erros as $erro): ?>
                                    <li><?php echo htmlspecialchars($erro); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="formExame" enctype="multipart/form-data" action="processar_exame.php" method="POST">
                        <div class="mb-3">
                            <label for="cpfPaciente" class="form-label">CPF do Paciente</label>
                            <input type="text" class="form-control" id="cpfPaciente" name="cpfPaciente" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="nomePaciente" class="form-label">Nome do Paciente</label>
                            <input type="text" class="form-control" id="nomePaciente" name="nomePaciente" 
                                   value="<?php echo htmlspecialchars($nome ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="arquivoExame" class="form-label">Arquivo do Exame (DICOM/NIfTI)</label>
                            <input type="file" class="form-control" id="arquivoExame" name="arquivoExame" accept=".dcm,.nii,.nii.gz" required>
                            <div class="form-text">Formatos aceitos: .dcm (DICOM), .nii, .nii.gz (NIfTI)</div>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                      placeholder="Observações clínicas sobre o exame..."></textarea>
                        </div>
                    </form>
                </div>

                <div class="modal-footer d-flex gap-2">
                    <button type="button" class="btn btn-custom-cinza flex-fill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formExame" class="btn btn-custom-azul flex-fill" id="btnEnviarExame">Enviar Exame</button>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>