import nibabel as nib
img = nib.load("C:/xampp/htdocs/sistema/imagens/imagens_segmentadas/imagem_segmentada_medico_id_1_paciente_id_7_exame_2.nii")
print(img.shape)