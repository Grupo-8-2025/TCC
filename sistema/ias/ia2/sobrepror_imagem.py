import nibabel as nib
import numpy as np
import argparse
import os
from scipy.ndimage import zoom

def sobrepor_segmentacao(caminho_imagem_original, caminho_imagem_segmentada):

    imagem_original = nib.load(caminho_imagem_original)
    imagem_segmentada = nib.load(caminho_imagem_segmentada)
    print(os.path.exists(caminho_imagem_segmentada))

    dados_imagem_original = imagem_original.get_fdata()
    dados_imagem_segmentada = imagem_segmentada.get_fdata()
    
    if dados_imagem_original.shape != dados_imagem_segmentada.shape:
        fatores = np.array(dados_imagem_original.shape) / np.array(dados_imagem_segmentada.shape)
        dados_imagem_segmentada = zoom(dados_imagem_segmentada, fatores, order=0)
    
    sobreposicao_imagem = dados_imagem_original.copy()
    sobreposicao_imagem[dados_imagem_segmentada > 0] = dados_imagem_segmentada[dados_imagem_segmentada > 0]
    
    sobreposicao_imagem = sobreposicao_imagem.astype(np.uint8)
    
    sobreposicao_imagem = nib.Nifti1Image(sobreposicao_imagem, affine=imagem_original.affine)
    
    
    
    caminho_temp = caminho_imagem_segmentada.replace(".nii.gz", "_temp.nii.gz")
    nib.save(sobreposicao_imagem, caminho_temp)

    os.replace(caminho_temp, caminho_imagem_segmentada)
    print("Sucesso!")
    
    
if __name__ == "__main__": 
    ap = argparse.ArgumentParser()
    ap.add_argument("--caminho_imagem_original", required = True)
    ap.add_argument("--caminho_imagem_segmentada", required = True)
    argumentos = ap.parse_args()
    sobrepor_segmentacao(argumentos.caminho_imagem_original, argumentos.caminho_imagem_segmentada)

