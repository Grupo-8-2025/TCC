<?php

    session_start();

    require_once 'conexao.php';

    function testarCamposObrigatorios(&$erros, $nome, $cpf, $imagem){
        if (empty($nome) || empty($cpf)) {
            $erros[] = "Todos os campos são obrigatórios.";
        }
        if (!isset($imagem) || $imagem['error'] !== UPLOAD_ERR_OK) {
            $erros[] = "Erro no upload do arquivo.";
        }
    }

    function validarCPF($cpf, &$erros) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11) {
            # throw new Exception("CPF inválido");
            $erros[] = "CPF inválido";
        }
        return $cpf;
    }

    function criarPaciente($pdo, $cpf, $nome) {
        $stmt = $pdo->prepare("SELECT 1 FROM pacientes WHERE cpf = :cpf LIMIT 1");
        $stmt->execute(['cpf' => $cpf]);

        if (!$stmt->fetchColumn()) {
            $stmt = $pdo->prepare("INSERT INTO pacientes (cpf, nome) VALUES (:cpf, :nome)");
            $stmt->execute(['cpf' => $cpf, 'nome' => $nome]);
        }
    }

    function obterIDpaciente($pdo, $cpf){
        $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE cpf = :cpf");
        $stmt->execute(['cpf' => $cpf]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['id'] : null;
    }

    function obterIDmedico(){
        $id = $_SESSION['usuario_id'];
        return $id;
    }

    function obterQuantExamesPaciente($pdo, $id_paciente){
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM exames
            WHERE id_paciente = :id_paciente;
        ");
        $stmt->execute(['id_paciente' => $id_paciente]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['total'] : 0; // agora retorna número
    }

    function obterTipoImagem(&$erros, $imagem, &$extensao, &$tipo_imagem){
        $nome_lower = strtolower($imagem['name']);

        $is_dcm = str_ends_with($nome_lower, '.dcm');
        $is_nii = str_ends_with($nome_lower, '.nii');
        $is_niigz = str_ends_with($nome_lower, '.nii.gz');

        if (!($is_dcm || $is_nii || $is_niigz)) {
            $erros[] = "Tipo de arquivo não permitido. Use DICOM (.dcm) ou NIfTI (.nii, .nii.gz).";
            throw new Exception("Tipo de arquivo não permitido.");
        }

        if($is_dcm){ $extensao = '.dcm'; $tipo_imagem = 'DICOM'; }
        if($is_nii){ $extensao = '.nii'; $tipo_imagem = 'NIfTI'; }
        if($is_niigz){ $extensao = '.nii.gz'; $tipo_imagem = 'NIfTI'; }
    }

    function salvarImagemOriginal($imagem, $id_paciente, $id_medico, $quant_exames, $extensao, $tipo_imagem) {
        $diretorio_imagens_originais = '../imagens/imagens_originais/';     
        $nome_imagem_original = "imagem_original_medico_id_".$id_medico. "_paciente_id_" .$id_paciente."_exame_".($quant_exames+1).$extensao;
        $caminho_imagem_original = $diretorio_imagens_originais . '/' . $nome_imagem_original;

        if (!move_uploaded_file($imagem['tmp_name'], $caminho_imagem_original)) {
            throw new Exception("Falha ao mover o arquivo enviado 1");
        }

        return [
            'nome' => $nome_imagem_original,
            'caminho' => $caminho_imagem_original,
            'tipo' => $tipo_imagem
        ];
    }

    function gerarPredicao($nome_imagem_original){
        $script_python = __DIR__ . '\\..\\ias\\ia1\\inferencia.py';
        $caminho_modelo = __DIR__ . '\\..\\ias\\ia1\\melhor_modelo.pth';
        $caminho_imagem = __DIR__ . '\\..\\imagens\\imagens_originais\\' . $nome_imagem_original;

        $cmd = "python \"$script_python\" --modelo \"$caminho_modelo\" --imagem \"$caminho_imagem\"";

        $resultado_exame = shell_exec($cmd);

        if ($resultado_exame === null) {
            die("Erro ao executar o comando: $cmd");
        }
        
        $resultado_exame = json_decode($resultado_exame, true);

        return $resultado_exame;
    }

    function gerarImagemSegmentada($nome_imagem_original, $id_paciente, $id_medico, $quant_exames){
        $script_python = __DIR__ . '\\..\\ias\\ia2\\inferencia.py';
        $caminho_imagem = __DIR__ . '\\..\\imagens\\imagens_originais\\' . $nome_imagem_original; 
        $extensao = '.nii.gz';
    
        $cmd = "python " . escapeshellarg($script_python) . " --imagem " . escapeshellarg($caminho_imagem);
    
        $output = shell_exec($cmd . " 2>&1");
        if ($output === null) {
            throw new Exception("Erro ao executar o script Python");
        }
    
        $caminho_inicial_imagem_segmentada = __DIR__ . '/../imagens/imagens_segmentadas/imagem_segmentada.nii.gz';
    
        if (!file_exists($caminho_inicial_imagem_segmentada)) {
            throw new Exception("A imagem segmentada não foi gerada. Saída do Python: $output");
        }
    
        $diretorio_imagens_segmentadas = __DIR__ . '/../imagens/imagens_segmentadas/';
        $nome_imagem_segmentada = "imagem_segmentada_medico_id_".$id_medico. "_paciente_id_" .$id_paciente."_exame_".($quant_exames+1).$extensao;
        $caminho_final_imagem_segmentada = $diretorio_imagens_segmentadas . '/' . $nome_imagem_segmentada;
    
        if (!rename($caminho_inicial_imagem_segmentada, $caminho_final_imagem_segmentada)) {
            throw new Exception("Falha ao mover a imagem segmentada");
        }
    
        return [
            'nome' => $nome_imagem_segmentada,
            'caminho' => $caminho_final_imagem_segmentada,
        ];
    }

    function gerarImagemSobreposta($caminho_imagem_original, $caminho_imagem_segmentada){
        $script_python = __DIR__ . '\\..\\ias\\ia2\\sobrepor_imagem.py';
    
        $cmd = "python " . escapeshellarg($script_python) . " --caminho_imagem_original " . escapeshellarg($caminho_imagem_original) 
        . " --caminho_imagem_segmentada " . escapeshellarg($caminho_imagem_segmentada);

        $output = shell_exec($cmd . " 2>&1");
        if ($output === null) {
            throw new Exception("Erro ao executar o script Python de gerar imagem sobreposta");
        }
        return $output;
    }

    function salvarExame($pdo, $id_medico, $id_paciente, $dados_imagem, $observacoes, $resultado_exame, $nome_imagem_segmentada) {
        $stmt = $pdo->prepare("
            INSERT INTO exames 
            (id_paciente, id_medico, nome_arquivo_original, nome_arquivo_segmentada, tipo_arquivo, observacoes, diagnostico_ia) 
            VALUES 
            (:id_paciente, :id_medico, :nome_arquivo_original, :nome_arquivo_segmentada, :tipo_arquivo, :observacoes, :diagnostico_ia)
        ");

        $diagnostico = "Predição: ".$resultado_exame['predicao']. "\nProbabilidade de ser tumor:\n".$resultado_exame['probabilidade_tumor']. "\nProbabilidade de não ser tumor:\n".$resultado_exame['probabilidade_no_tumor'];

        $stmt->execute([
            'id_paciente' => $id_paciente,
            'id_medico' => $id_medico,
            'nome_arquivo_original' => $dados_imagem['nome'],
            'nome_arquivo_segmentada' => $nome_imagem_segmentada,
            'tipo_arquivo' => $dados_imagem['tipo'],
            'observacoes' => $observacoes,
            'diagnostico_ia' => $diagnostico
        ]);
    }

    function redirecionarParaDashboard($erros, $nome, $cpf){
        if (!empty($erros)) {
            $_SESSION['erros_exame'] = $erros;
            $_SESSION['dados_exame'] = [ 'nome' => $nome, 'email' => $cpf ];
            header("Location: dashboard.php");
            exit;
        }
    }

    function main($pdo){
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['usuario_logado'])) {
            try {

                $pdo->beginTransaction();

                $erros = [];

                $cpf = validarCPF($_POST['cpfPaciente'], $erros);
                $nome = trim($_POST['nomePaciente']);
                $observacoes = trim($_POST['observacoes']);
                $imagem = $_FILES['arquivoExame'];
                
                testarCamposObrigatorios($erros, $nome, $cpf, $imagem);

                criarPaciente($pdo, $cpf, $nome);
                
                $id_paciente = obterIDpaciente($pdo, $cpf);
                $id_medico = obterIDmedico();

                $quant_exames = obterQuantExamesPaciente($pdo, $id_paciente);

                $extensao = '';
                $tipo_imagem = '';
                obterTipoImagem($erros, $imagem, $extensao, $tipo_imagem);

                $dados_imagem_original = salvarImagemOriginal($imagem, $id_paciente, $id_medico, $quant_exames, $extensao, $tipo_imagem);

                $resultado_exame = gerarPredicao($dados_imagem_original['nome']);

                $saida = '';
                if($resultado_exame['probabilidade_tumor'] > 90.0){
                    $dados_imagem_segmentada = gerarImagemSegmentada($dados_imagem_original['nome'], $id_paciente, $id_medico, $quant_exames);
                    salvarExame($pdo, $id_medico, $id_paciente, $dados_imagem_original, $observacoes, $resultado_exame, $dados_imagem_segmentada['nome']);
                    $saida = gerarImagemSobreposta($dados_imagem_original['caminho'], $dados_imagem_segmentada['caminho']);
                }else{
                    salvarExame($pdo, $id_medico, $id_paciente, $dados_imagem_original, $observacoes, $resultado_exame, null);
                }

                redirecionarParaDashboard($erros, $nome, $cpf);

                $pdo->commit();
                echo json_encode(['success' => true]);
                echo ($saida);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
        }
    }

    main($pdo);

?>
