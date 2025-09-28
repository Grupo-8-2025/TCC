<?php
    require_once 'conexao.php';

    function retornarNumeroExamesMedicoPaciente($pdo, $id_medico, $id_paciente){
        $sql = "SELECT COUNT(*)
                FROM exames exame
                JOIN pacientes paciente ON exame.id_paciente = paciente.id
                WHERE exame.id_medico = :id_medico AND exame.id_paciente = :id_paciente";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_medico' => $id_medico,
            'id_paciente' => $id_paciente
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function retornarExamesMedicoPaciente($pdo, $id_medico, $id_paciente){
        $sql = "SELECT exame.*, paciente.nome as nome_paciente
                FROM exames exame
                JOIN pacientes paciente ON exame.id_paciente = paciente.id
                WHERE exame.id_medico = :id_medico AND exame.id_paciente = :id_paciente
                ORDER BY exame.data_upload DESC
                LIMIT 6";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_medico' => $id_medico,
            'id_paciente' => $id_paciente
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function main_exames($pdo, $paciente){
        $exames = [];
        $num_exames = [];

        try{
            $exames = retornarExamesMedicoPaciente($pdo, $_SESSION['usuario_id'], $paciente['id']);
            $num_exames = retornarNumeroExamesMedicoPaciente($pdo, $_SESSION['usuario_id'], $paciente['id']);
        }catch(PDOException $e){
            die("Erro ao buscar dados: " . $e->getMessage());
        }
        return [$exames, $num_exames];
    }

    $informacoes_exames = main_exames($pdo, $paciente);
?>