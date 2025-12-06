<?php

?>
<style>
/* Estilos do painel de acessibilidade - com escopo específico */
.painel-flutuante-acessibilidade {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
  font-family: Arial, sans-serif !important;
  font-size: 16px !important;
  box-sizing: border-box !important;
}

.painel-flutuante-acessibilidade #btnAbrirAcess {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: none;
  background-color: #0c7534;
  color: #fff;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  transition: all 0.2s;
  box-sizing: border-box !important;
  font-family: Arial, sans-serif !important;
}

.painel-flutuante-acessibilidade #btnAbrirAcess:hover {
  background-color: #0b7d44;
}

.painel-flutuante-acessibilidade .painel-acessibilidade-conteudo {
  display: none;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 10px;
  background: #ffffff;
  color: rgb(11, 66, 5);
  padding: 12px 15px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  box-sizing: border-box !important;
  font-family: Arial, sans-serif !important;
}

.painel-flutuante-acessibilidade .painel-acessibilidade-conteudo button {
  padding: 8px 12px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: bold;
  font-size: 14px;
  transition: all 0.2s;
  background-color: #f0f0f0;
  color: #333;
  box-sizing: border-box !important;
  font-family: Arial, sans-serif !important;
}

.painel-flutuante-acessibilidade .painel-acessibilidade-conteudo button:hover {
  background-color: #d4d4d4;
}

/* Modo alto contraste */
.modo-contraste-acessibilidade,
.modo-contraste-acessibilidade * {
  background-color: #000 !important;
  color: #fff !important;
  border-color: #fff !important;
  fill: #fff !important;
  stroke: #fff !important;
}

.modo-contraste-acessibilidade a,
.modo-contraste-acessibilidade a * {
  color: #FFD700 !important;
  text-decoration: underline !important;
}

.modo-contraste-acessibilidade * {
  background-image: none !important;
}

.modo-contraste-acessibilidade img,
.modo-contraste-acessibilidade [style*="background-image"] {
  filter: grayscale(1) brightness(0.4) !important;
}

.modo-contraste-acessibilidade .card,
.modo-contraste-acessibilidade .container,
.modo-contraste-acessibilidade section,
.modo-contraste-acessibilidade .row,
.modo-contraste-acessibilidade .col,
.modo-contraste-acessibilidade footer,
.modo-contraste-acessibilidade header,
.modo-contraste-acessibilidade nav {
  background-color: #000 !important;
  color: #fff !important;
}

.modo-contraste-acessibilidade button,
.modo-contraste-acessibilidade .btn {
  background-color: #222 !important;
  color: #fff !important;
  border: 1px solid #fff !important;
}

.modo-contraste-acessibilidade .bi,
.modo-contraste-acessibilidade i {
  color: #fff !important;
}

.modo-contraste-acessibilidade .carousel-item,
.modo-contraste-acessibilidade .carousel-caption {
  background-color: #000 !important;
}

.modo-contraste-acessibilidade img,
.modo-contraste-acessibilidade [style*="background-image"] {
  filter: grayscale(0.2) brightness(0.8) !important;
}
</style>

<div class="painel-flutuante-acessibilidade">
    <button id="btnAbrirAcess">⚙️</button>
    <div class="painel-acessibilidade-conteudo" id="painelAcessibilidade">
        <h4>Painel de Acessibilidade</h4>
        <button onclick="contrasteAcessibilidade()"><i class="bi bi-brightness-high-fill"> </i>Alto contraste</button>
        <button onclick="fonteMaisAcessibilidade()"><i class="bi bi-type-bold"></i></button>
        <button onclick="fonteMenosAcessibilidade()"><i class="bi bi-type"></i></button>
        <button onclick="resetarAcessibilidade()"><i class="bi bi-arrow-counterclockwise"></i> Padrão</button>
    </div>
</div>

<script>
(function() {
    // Escopo isolado para evitar conflitos
    let tamanhoFonteAcess = localStorage.getItem("fonte_acessibilidade") || 16;
    document.body.style.fontSize = tamanhoFonteAcess + "px";

    if (localStorage.getItem("contraste_acessibilidade") === "ativo") {
        document.body.classList.add("modo-contraste-acessibilidade");
    }

    window.contrasteAcessibilidade = function() {
        document.body.classList.toggle("modo-contraste-acessibilidade");
        let ativo = document.body.classList.contains("modo-contraste-acessibilidade");
        localStorage.setItem("contraste_acessibilidade", ativo ? "ativo" : "inativo");
    }

    window.fonteMaisAcessibilidade = function() {
        tamanhoFonteAcess = parseInt(tamanhoFonteAcess) + 2;
        if (tamanhoFonteAcess > 30) tamanhoFonteAcess = 30;
        document.body.style.fontSize = tamanhoFonteAcess + "px";
        localStorage.setItem("fonte_acessibilidade", tamanhoFonteAcess);
    }

    window.fonteMenosAcessibilidade = function() {
        tamanhoFonteAcess = parseInt(tamanhoFonteAcess) - 2;
        if (tamanhoFonteAcess < 12) tamanhoFonteAcess = 12;
        document.body.style.fontSize = tamanhoFonteAcess + "px";
        localStorage.setItem("fonte_acessibilidade", tamanhoFonteAcess);
    }

    window.resetarAcessibilidade = function() {
        document.body.classList.remove("modo-contraste-acessibilidade");
        document.body.style.fontSize = "16px";
        localStorage.removeItem("contraste_acessibilidade");
        localStorage.removeItem("fonte_acessibilidade");
        tamanhoFonteAcess = 16;
    }

    const btnAbrirAcess = document.getElementById('btnAbrirAcess');
    const painelAcess = document.getElementById('painelAcessibilidade');

    if (btnAbrirAcess && painelAcess) {
        btnAbrirAcess.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = painelAcess.style.display === 'flex';
            painelAcess.style.display = isVisible ? 'none' : 'flex';
        });

        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (!painelAcess.contains(e.target) && e.target !== btnAbrirAcess) {
                painelAcess.style.display = 'none';
            }
        });
    }
})();
</script>