document.addEventListener("DOMContentLoaded", function() {
    var papayaPronto = false;

    document.querySelectorAll('.card-exame').forEach(card => card.style.pointerEvents = 'none');

    var script = document.createElement("script");
    script.src = "Papaya-master/release/current/standard/papaya.js";
    script.onload = function () {
        papayaPronto = true;
        console.log("Papaya carregado!");

        document.querySelectorAll('.card-exame').forEach(card => card.style.pointerEvents = 'auto');
    };
    document.head.appendChild(script);

    function carregarExame(caminho, tipo) {
        if (!papayaPronto) {
            alert("Papaya ainda não carregou. Recarregue a página em alguns segundos.");
            return;
        }

        var params = [];
        params["images"] = [caminho];
        params["showControlBar"] = true;
        params["showRuler"] = true;
        params["kioskMode"] = false;

        papaya.Container.addViewer("viewer3D", params);
    }
    
    window.carregarExame = carregarExame;
});