<?php
    require_once 'conexao.php';

    function retornarQuantitativosMedico($pdo, $id_medico){
        $sql = "SELECT COUNT(DISTINCT id_paciente) as total_pacientes,
                COUNT(*) as total_exames
                FROM exames 
                WHERE id_medico = :id_medico";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_medico' => $id_medico]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function main_quantitativos($pdo){
        $quantitativos_medico = [];

        try{
            $quantitativos_medico = retornarQuantitativosMedico($pdo, $_SESSION['usuario_id']);
        }catch(PDOException $e){
            die("Erro ao buscar dados: " . $e->getMessage());
        }
        return $quantitativos_medico;
    }

    $quantitativos_medico = main_quantitativos($pdo);
?>