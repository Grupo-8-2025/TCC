import torch
import monai
from monai.networks.nets import UNet
from monai.losses import DiceLoss
from monai.transforms import (
    LoadImaged, EnsureChannelFirstd, ScaleIntensityd,
    ToTensord
)
from monai.data import Dataset, DataLoader
import os
import numpy as np
from monai.metrics import DiceMetric
import matplotlib.pyplot as plt
from nilearn import image, plotting
import nibabel as nib

def get_data_dicts(folder):
    flair_files = [f for f in os.listdir(folder) if f.endswith('flair.nii.gz')]
    data_dicts = []
    for flair in flair_files:
        base = flair.replace('_flair.nii.gz', '')
        seg = f"{base}_seg.nii.gz"
        seg_path = os.path.join(folder, seg)
        flair_path = os.path.join(folder, flair)
        if os.path.exists(seg_path):
            data_dicts.append({'image': flair_path, 'label': seg_path})
    return data_dicts

def calcular_taxa_erro_pixel(model, loader, num_amostras=10):
    model.eval()
    total_pixels = 0
    total_erros = 0
    with torch.no_grad():
        for i, batch in enumerate(loader):
            if i >= num_amostras:
                break
            images, labels = batch['image'], batch['label']
            outputs = model(images)
            outputs = torch.softmax(outputs, dim=1)
            pred_mask = torch.argmax(outputs[0], dim=0).cpu().numpy()
            true_mask = labels[0, 0].cpu().numpy()

            mask_pred_bin = (pred_mask >= 1).astype(np.uint8)
            mask_true_bin = (true_mask >= 1).astype(np.uint8)
            iguais = (mask_pred_bin == mask_true_bin)
            total_pixels += iguais.size
            total_erros += np.sum(~iguais)
    taxa_erro = total_erros / total_pixels if total_pixels > 0 else 0
    #print(f'Taxa de erro dos pixels (>=1): {taxa_erro:.4f}')


def salvarPredicoes(array_seg, pathName):
    # Create directories if they don't exist
    os.makedirs(f"Predicoes/{pathName}", exist_ok=True)
    nib_seg = nib.Nifti1Image(array_seg.astype(np.float32), np.eye(4))
    
    nimask = image.load_img(nib_seg)
    nimask.to_filename(f"Predicoes/{pathName}/predicao_mask.nii.gz")

def visualizar_predicao(model, loader, num_amostras=2):
    device = next(model.parameters()).device  # Get the device of the model
    model.eval()
    with torch.no_grad():
        for i, batch in enumerate(loader):
            if i >= num_amostras:
                break

            images, labels = batch['image'].to(device), batch['label'].to(device)
            
            #print(f"Images shape: {images.shape}, Labels shape: {labels.shape}")
            #print(f"Image min/max: {images.min().item():.4f}/{images.max().item():.4f}")
            #print(f"Labels min/max: {labels.min().item():.4f}/{labels.max().item():.4f}")

            outputs = model(images)
            outputs = torch.softmax(outputs, dim=1)

            pred_mask_array = torch.argmax(outputs[0], dim=0).cpu().numpy().astype(np.float32)
            salvarPredicoes(pred_mask_array, str(i))



#calcular_taxa_erro_pixel(test_model, val_loader, num_amostras=10)

transforms = monai.transforms.Compose([
    LoadImaged(keys=['image', 'label']),
    EnsureChannelFirstd(keys=['image', 'label']),
    ScaleIntensityd(keys=['image']),
    ToTensord(keys=['image', 'label']),
])

train_folder = 'dataset_final/treino'
val_folder = 'dataset_final/validacao'

train_data = get_data_dicts(train_folder)
val_data = get_data_dicts(val_folder)

dataset = Dataset(data=train_data, transform=transforms)
loader = DataLoader(dataset, batch_size=1, shuffle=True)

device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
#print(f"Using device: {device}")

val_dataset = Dataset(data=val_data, transform=transforms)
val_loader = DataLoader(val_dataset, batch_size=1, shuffle=False)

best_checkpoint = torch.load('ModeloIA.pth', map_location=device) #<-- Trocar para o com menor epoch loss

test_model = UNet(
    spatial_dims=3,
    in_channels=1,
    out_channels=4,      
    channels=(16, 32, 64, 128, 256),
    strides=(2, 2, 2, 2),
    num_res_units=2,
)
test_model.load_state_dict(best_checkpoint['model_state_dict'])
test_model = test_model.to(device)  
test_model.eval() 

dice_metric = DiceMetric(include_background=False, reduction="mean")

print("Testando o modelo...")
dice_scores = []

with torch.no_grad():  
    for batch in val_loader:
        images, labels = batch['image'].to(device), batch['label'].to(device)  
        
        outputs = test_model(images)
        outputs = torch.softmax(outputs, dim=1)  
        
        dice_score = dice_metric(y_pred=outputs, y=labels)
        mean_dice_score = dice_score.mean().item()  
        dice_scores.append(mean_dice_score)

mean_dice = np.mean(dice_scores)
print(f"\nDice Score médio no conjunto de validação: {mean_dice:.4f}")
print(f"Melhor Dice Score: {max(dice_scores):.4f}")
print(f"Pior Dice Score: {min(dice_scores):.4f}")

visualizar_predicao(test_model, val_loader, num_amostras=3)

