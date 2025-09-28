import json
import argparse
import torch
import torch.nn.functional as functional
from monai.networks.nets import DenseNet121
from monai.transforms import Compose, LoadImaged, EnsureChannelFirstd, Spacingd, Orientationd, ScaleIntensityRangePercentilesd, Resized, EnsureTyped


def pre_processamento_imagem(dimensoes_imagem: int):
    return Compose([
        LoadImaged(keys=["imagem"]),
        EnsureChannelFirstd(keys=["imagem"]),
        Spacingd(keys=["imagem"], pixdim=(1.0, 1.0, 1.0), mode=("bilinear")),
        Orientationd(keys=["imagem"], axcodes="RAS"),
        ScaleIntensityRangePercentilesd( keys=["imagem"], lower=1, upper=99, b_min=0.0, b_max=1.0, clip=True),
        Resized(keys=["imagem"], spatial_size=(dimensoes_imagem, dimensoes_imagem, dimensoes_imagem)),
        EnsureTyped(keys=["imagem"]),
    ])


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--modelo", required=True)
    ap.add_argument("--imagem", required=True)
    ap.add_argument("--dimensoes_imagem", type=int, default=128)
    argumentos = ap.parse_args()

    device = "cuda" if torch.cuda.is_available() else "cpu"

    modelo = DenseNet121(spatial_dims=3, in_channels=1, out_channels=2).to(device)
    modelo.load_state_dict(torch.load(argumentos.modelo, map_location=device))
    modelo.eval()

    compose = pre_processamento_imagem(argumentos.dimensoes_imagem)
    dicionario_imagem = {"imagem": argumentos.imagem}
    dicionario_imagem = compose(dicionario_imagem)
    tensor_modelo = dicionario_imagem["imagem"].unsqueeze(0).to(device)

    with torch.no_grad():
        outputs_modelo = modelo(tensor_modelo)
        probabilidades = functional.softmax(outputs_modelo, dim=1)[0]
        probabilidade_no_tumor, probabilidade_tumor = probabilidades[0].item(), probabilidades[1].item()
        label = "Tumor" if probabilidade_tumor >= 0.5 else "Não tumor"
        resultado = {
            'predicao' : label,
            'probabilidade_tumor' : f"{probabilidade_tumor:.4%}",
            'probabilidade_no_tumor' : f"{probabilidade_no_tumor:.4%}"
        }
        print(json.dumps(resultado))

if __name__ == "__main__":
    main()

