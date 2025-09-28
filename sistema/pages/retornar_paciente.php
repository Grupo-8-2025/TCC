<?php
    require_once 'conexao.php';

    function validarCampoCPF(&$erros, $cpf_paciente) {
        $cpf_paciente = preg_replace('/[^0-9]/', '', $cpf_paciente);
        if (strlen($cpf_paciente) != 11) {
            $erros[] = "CPF inválido";
        }
        return $cpf_paciente;
    }

    function retornarPacienteDoBancoDados($pdo, $cpf_paciente){
        $sql = "SELECT * FROM pacientes WHERE cpf = :cpf";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['cpf' => $cpf_paciente]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function retornarUltimoPacienteDoBancoDados($pdo){
        $sql = "SELECT * FROM pacientes ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function main_paciente($pdo){
        $paciente = [];
        $erros = [];

        if(isset($_POST['cpf_paciente'])){
            $cpf_paciente = validarCampoCPF($erros, $_POST['cpf_paciente']);

            try{
                $paciente = retornarPacienteDoBancoDados($pdo, $cpf_paciente);
            }catch(PDOException $e){
                $erros[] = "Erro ao deletar produto: " . $e->getMessage();
            }
        }else{
            try{
                $paciente = retornarUltimoPacienteDoBancoDados($pdo);
            }catch(PDOException $e){
                $erros[] = "Erro ao deletar produto: " . $e->getMessage();
            }
        }
        return $paciente;
    }

    $_SESSION['paciente'] = main_paciente($pdo);
    
?>