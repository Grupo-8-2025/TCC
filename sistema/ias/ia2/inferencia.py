import os
import nibabel as nib
import numpy as np
from skimage import exposure
from scipy.ndimage import zoom
import torch 
from nilearn import image
from monai.networks.nets import UNet
import argparse

class Preprocessamento:

    def __init__(self, imagem_original):
        self.imagem_original = imagem_original

    def pre_processar_nifti(self, imagem, fator_brilho=2, dimensoes_imagem=(128,128,128)):
        dados_imagem = imagem.get_fdata() 
        dados_imagem = exposure.adjust_gamma(dados_imagem, gamma=fator_brilho) 

        dados_imagem = self.normalizar_imagem(dados_imagem)
        dados_imagem = self.redimensionar_volume(dados_imagem, dimensoes_imagem)

        return nib.Nifti1Image(dados_imagem, imagem.affine)

    def normalizar_imagem(self, dados_imagem):
        return (dados_imagem - np.min(dados_imagem)) / (np.max(dados_imagem) - np.min(dados_imagem))

    def redimensionar_volume(self, dados_imagem, dimensoes_imagem=(128, 128, 128)):
        fatores_zoom = [t / s for t, s in zip(dimensoes_imagem, dados_imagem.shape)]
        return zoom(dados_imagem, zoom=fatores_zoom, order=1)

    def substituir_valores(dados_imagem, old_value=4, new_value=3):
        dados_imagem[dados_imagem == old_value] = new_value
        return dados_imagem
    
    def processar_imagens_medicas(self):
        if self.imagem_original.endswith('t1c.nii') or self.imagem_original.endswith('t1c.nii.gz'):
            try:
                imagem = nib.load(self.imagem_original)
                imagem_processada = self.pre_processar_nifti(imagem, fator_brilho=2, dimensoes_imagem=(128,128,128))
                nome_imagem_original = os.path.basename(self.imagem_original)
                pasta_salva = os.path.join(os.path.dirname(self.imagem_original), "processada_" + nome_imagem_original)
                nib.save(imagem_processada, pasta_salva)

                print(f'Processado: {self.imagem_original}')
                return pasta_salva
                
            except Exception as e:
                print(f'Erro ao processar {self.imagem_original}: {str(e)}')

        return self.imagem_original
                

class InteligenciaArtificial(Preprocessamento):

    def __init__(self, imagem_original):
        self.imagem_original = imagem_original

    def definir_device(self):
        return torch.device("cuda" if torch.cuda.is_available() else "cpu")
    
    def escolha_epoch(self, arquivo_treinamento, device):
        try:
            melhor_checkpoint = torch.load(arquivo_treinamento, map_location=device)
            return melhor_checkpoint
        except FileNotFoundError:
            print("Arquivo de treinamento não encontrado.")
            return None
        
    def teste_modelo(self, treinamento, device):
        modelo_teste = UNet(
            spatial_dims=3,
            in_channels=1,
            out_channels=4,      
            channels=(16, 32, 64, 128, 256),
            strides=(2, 2, 2, 2),
            num_res_units=2,
        )

        modelo_teste.load_state_dict(treinamento['model_state_dict'])
        modelo_teste = modelo_teste.to(device) 
        modelo_teste.eval() 
        return modelo_teste
        
    def processar_treinamento(self):
        device = self.definir_device()
        nome_arquivo_treinamento = 'C:/xampp/htdocs/sistema/ias/ia2/melhor_modelo.pth'

        treinamento = self.escolha_epoch(nome_arquivo_treinamento, device)

        if treinamento:
            modelo = self.teste_modelo(treinamento, device)
            return modelo

    def processar_imagem(self, pasta_imagem):
        imagem = nib.load(pasta_imagem)
        imagem = self.pre_processar_nifti(imagem, fator_brilho=2, dimensoes_imagem=(128, 128, 128))
        return imagem.get_fdata()

    def salvar_predicoes(self, array_seg):
        destino = "C:/xampp/htdocs/sistema/imagens/imagens_segmentadas"
        os.makedirs(destino, exist_ok=True)

        nib_seg = nib.Nifti1Image(array_seg.astype(np.float32), np.eye(4))
        predicao = image.load_img(nib_seg)
        predicao.to_filename(os.path.join(destino, "imagem_segmentada.nii.gz"))

    def imagem_processada(self):
        modelo = self.processar_treinamento()
        if modelo is None:
            print("Modelo não carregado corretamente.")
            return

        imagem = self.processar_imagem(self.imagem_original)
        imagem_tensor = torch.from_numpy(imagem).float().unsqueeze(0).unsqueeze(0)
        imagem_tensor = imagem_tensor.to(self.definir_device())
        outputs = modelo(imagem_tensor)
        outputs = torch.softmax(outputs, dim=1)

        predicao_array = torch.argmax(outputs[0], dim=0).cpu().numpy().astype(np.float32)
        self.salvar_predicoes(predicao_array)
        print("Predição salva com sucesso.")


if __name__ == "__main__":  
    ap = argparse.ArgumentParser()
    ap.add_argument("--imagem", required=True)
    argumento = ap.parse_args()
    caminho_imagem = argumento.imagem
    imagem_pre_processada = Preprocessamento(caminho_imagem)
    minha_ia = InteligenciaArtificial(imagem_pre_processada.processar_imagens_medicas())
    minha_ia.imagem_processada()
