import nibabel as nib
import numpy as np
import argparse
import os
from scipy.ndimage import zoom

def sobrepor_segmentacao(caminho_imagem_original, caminho_imagem_segmentada):

    # Carregar imagens
    imagem_original = nib.load(caminho_imagem_original)
    imagem_segmentada = nib.load(caminho_imagem_segmentada)

    print(f"Arquivo segmentação existe? {os.path.exists(caminho_imagem_segmentada)}")

    dados_imagem_original = imagem_original.get_fdata()
    dados_imagem_segmentada = imagem_segmentada.get_fdata()

    # Ajustar dimensões da segmentação se necessário
    if dados_imagem_original.shape != dados_imagem_segmentada.shape:
        fatores = np.array(dados_imagem_original.shape) / np.array(dados_imagem_segmentada.shape)
        dados_imagem_segmentada = zoom(dados_imagem_segmentada, fatores, order=0)  # order=0 mantém valores exatos

    # Ajustar a faixa da segmentação para a faixa da imagem original (para visualização)
    max_val_original = dados_imagem_original.max()
    max_val_segmentacao = dados_imagem_segmentada.max()
    if max_val_segmentacao > 0:
        dados_segmentacao_ajustada = (dados_imagem_segmentada / max_val_segmentacao) * max_val_original
    else:
        dados_segmentacao_ajustada = dados_imagem_segmentada

    # Criar sobreposição
    sobreposicao_imagem = dados_imagem_original.copy()
    mask = dados_imagem_segmentada > 0
    sobreposicao_imagem[mask] = dados_segmentacao_ajustada[mask]

    # Criar NIfTI mantendo tipo float32
    sobreposicao_imagem_nifti = nib.Nifti1Image(sobreposicao_imagem.astype(np.float32),
                                                affine=imagem_original.affine)

    # Salvar sobreposição
    caminho_temp = caminho_imagem_segmentada.replace(".nii.gz", "_temp.nii.gz")
    nib.save(sobreposicao_imagem_nifti, caminho_temp)
    os.replace(caminho_temp, caminho_imagem_segmentada)

    print("Sobreposição criada com sucesso!")

if __name__ == "__main__": 
    ap = argparse.ArgumentParser()
    ap.add_argument("--caminho_imagem_original", required=True)
    ap.add_argument("--caminho_imagem_segmentada", required=True)
    argumentos = ap.parse_args()

    sobrepor_segmentacao(argumentos.caminho_imagem_original, argumentos.caminho_imagem_segmentada)
