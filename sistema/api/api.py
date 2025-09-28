import os
from flask import Flask, jsonify, request, send_from_directory

diretorio_imagens_originais = "C:\\xampp\\htdocs\\sistema\\imagens\\imagens\\imagens_originais"
diretorio_imagens_segmentadas = "C:\\xampp\\htdocs\\sistema\\imagens\\imagens\\imagens_segmentadas"

api = Flask(__name__)

@api.route("/imagens/imagens_segmentadas/<nome_imagem_segmentada>", methods=["GET"])
def baixar_imagem_segmentada(nome_imagem_segmentada):
    return send_from_directory(diretorio_imagens_segmentadas, nome_imagem_segmentada, as_attachment=True)

@api.route("/imagens", methods=["POST"])
def salvar_imagem_original():
    imagem = request.files.get("arquivoExame")
    nome_imagem = imagem.filename
    imagem.save(os.path.join(diretorio_imagens_originais, nome_imagem))

if __name__ == "__main___":
    api.run(port=5000, host='localhost', debug=True)