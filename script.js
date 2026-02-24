/* ==================================================================
   SCRIPT UNIFICADO - DISCIPLINA APP
   ================================================================== */

/* ------------------------------------------------------------------
   1. SELETORES GLOBAIS (Para evitar erros de elemento não encontrado)
   ------------------------------------------------------------------ */
// Menu
const hamburger = document.querySelector(".hamburger");
const navMenu = document.querySelector(".nav-links");
const btnPerfil = document.getElementById("btnPerfil");
const myDropdown = document.getElementById("myDropdown");
const arrowIcon = document.querySelector(".arrow");

// Modais Principais
const modalMeta = document.getElementById("modalMeta");         // Criar/Editar Rotina
const modalImportante = document.getElementById("modalImportante");
const modalGamificacao = document.getElementById("modalGamificacao");
const modalConfirm = document.getElementById("modalConfirmacao"); // Check
const modalDelete = document.getElementById("modalDelete");       // Lixeira
const modalRecompensa = document.getElementById("modalRecompensa"); // Criar Item Loja
const modalCompra = document.getElementById("modalCompra");       // Comprar Item

/* ------------------------------------------------------------------
   2. NAVEGAÇÃO (MOBILE & DROPDOWN)
   ------------------------------------------------------------------ */
if (hamburger && navMenu) {
    hamburger.addEventListener("click", () => {
        hamburger.classList.toggle("active");
        navMenu.classList.toggle("active");
    });
    // Fecha ao clicar num link
    document.querySelectorAll(".nav-links a").forEach(n => n.addEventListener("click", () => {
        hamburger.classList.remove("active");
        navMenu.classList.remove("active");
    }));
}

if (btnPerfil && myDropdown) {
    btnPerfil.addEventListener("click", function (e) {
        e.preventDefault();
        myDropdown.classList.toggle("show");
        // Gira a setinha
        if (arrowIcon) {
            arrowIcon.style.transform = myDropdown.classList.contains("show") 
                ? "rotate(-135deg)" : "rotate(45deg)";
        }
    });
}

/* ------------------------------------------------------------------
   3. SISTEMA DE ABAS
   ------------------------------------------------------------------ */
function abrirTab(evt, tabName) {
    // 1. Esconde todos os conteúdos
    const tabcontents = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabcontents.length; i++) {
        tabcontents[i].style.display = "none";
    }

    // 2. Desativa todos os botões
    const tablinks = document.getElementsByClassName("tab-btn");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // 3. Mostra o conteúdo clicado
    const target = document.getElementById(tabName);
    if(target) target.style.display = "block";

    // 4. Ativa o botão visualmente
    if (evt && evt.currentTarget && evt.currentTarget.classList.contains('tab-btn')) {
        evt.currentTarget.className += " active";
    } else {
        // Se veio de um botão interno (ex: "Ir para Loja"), procura o botão da aba correspondente
        for (let i = 0; i < tablinks.length; i++) {
            if (tablinks[i].getAttribute('onclick') && tablinks[i].getAttribute('onclick').includes(tabName)) {
                tablinks[i].classList.add("active");
            }
        }
    }
}

/* ------------------------------------------------------------------
   4. MODAL DE ROTINA (CRIAR E EDITAR)
   ------------------------------------------------------------------ */
const formRotina = document.getElementById("formRotina");
const modalTitulo = document.getElementById("modalTitulo");
const btnSalvarRotina = document.getElementById("btnSalvarRotina");
const inputIdRotinaEdit = document.getElementById("inputIdRotina"); // ID para edição
const inputTipoMeta = document.getElementById("tipoMeta");
const divPersonalizado = document.getElementById("inputPersonalizado");
const inputPersonalizado = document.getElementById("metaTexto");
const inputMinutosMeta = document.getElementById("inputMinutos");
const checkboxesDias = document.querySelectorAll('input[name="dias[]"]');
const pontosPreview = document.getElementById("pontosPreview");

// Função para limpar a modal (Reseta para modo "Criar")
function resetarModal() {
    if(!formRotina) return;
    formRotina.reset();
    modalTitulo.innerText = "Configurar Rotina";
    btnSalvarRotina.innerText = "CRIAR ROTINA";
    formRotina.action = "salvar_rotina.php";
    if(inputIdRotinaEdit) inputIdRotinaEdit.value = "";
    if(divPersonalizado) divPersonalizado.style.display = "none";
    if(pontosPreview) pontosPreview.innerText = "0";
}

// Botões "+ Nova Rotina"
const btnsCriar = document.querySelectorAll(".btn-add-task");
btnsCriar.forEach(btn => {
    btn.onclick = function() {
        resetarModal();
        if(modalMeta) modalMeta.style.display = "block";
    }
});

// Função de EDIÇÃO (Chamada pelo botão Lápis no HTML)
function abrirModalEditar(id, tipo, desc, duracao, diasString) {
    if(!modalMeta) return;
    resetarModal(); // Limpa antes
    
    // Configura para edição
    modalTitulo.innerText = "Editar Rotina";
    btnSalvarRotina.innerText = "ATUALIZAR ROTINA";
    formRotina.action = "atualizar_rotina.php";
    if(inputIdRotinaEdit) inputIdRotinaEdit.value = id;

    // Preenche campos
    if(inputTipoMeta) inputTipoMeta.value = tipo;
    if(inputMinutosMeta) inputMinutosMeta.value = duracao;
    
    // Lógica do campo personalizado
    if (tipo === 'outro') {
        if(divPersonalizado) divPersonalizado.style.display = "block";
        if(inputPersonalizado) inputPersonalizado.value = desc;
    } else {
        if(divPersonalizado) divPersonalizado.style.display = "none";
    }

    // Marca os dias
    const diasArray = diasString.split(',');
    checkboxesDias.forEach(chk => {
        if (diasArray.includes(chk.value)) chk.checked = true;
    });

    // Atualiza preview de pontos
    if(pontosPreview) pontosPreview.innerText = duracao * 3;

    modalMeta.style.display = "block";
}

// Lógica do Select "Outro"
function verificarOutro(selectObject) {
    if (selectObject.value === "outro") {
        if(divPersonalizado) divPersonalizado.style.display = "block";
        if (inputPersonalizado) inputPersonalizado.focus();
    } else {
        if(divPersonalizado) divPersonalizado.style.display = "none";
    }
}

// Cálculo de Pontos em Tempo Real (Limite 1440)
if (inputMinutosMeta && pontosPreview) {
    inputMinutosMeta.addEventListener("input", function () {
        let minutos = parseInt(this.value);
        if (isNaN(minutos) || minutos < 0) minutes = 0;
        
        // Trava 24h
        if (minutos > 1440) {
            minutos = 1440;
            this.value = 1440;
            alert("O dia só tem 1440 minutos! 😂");
        }
        pontosPreview.innerText = minutos * 3;
    });
}

/* ------------------------------------------------------------------
   5. MODAL DE CONFIRMAÇÃO (CHECK / CONCLUIR)
   ------------------------------------------------------------------ */
const inputRotinaIdCheck = document.getElementById("inputRotinaId"); // ID para o check
const previewTaskConfirm = document.getElementById("previewTaskConfirm");

function abrirConfirmacao(id, nome, minutos, pontos, desc) {
    if (!modalConfirm) return;

    if(inputRotinaIdCheck) inputRotinaIdCheck.value = id;

    let descHtml = desc ? `<small style="color: #888;">"${desc}"</small>` : "";

    if(previewTaskConfirm) {
        previewTaskConfirm.innerHTML = `
            <div class="task-card" style="margin: 0; border-left: 4px solid var(--primary-color);">
                <div class="task-info">
                    <h4 style="margin: 0; color: white; font-size: 1.1rem;">${nome}</h4>
                    <p style="margin: 5px 0 0; font-size: 0.9rem; color: #bbb;">
                        ${minutos} min • <span style="color: gold">+${pontos} pts</span>
                    </p>
                    ${descHtml}
                </div>
            </div>
        `;
    }
    modalConfirm.style.display = "block";
}

/* ------------------------------------------------------------------
   6. MODAL DE EXCLUSÃO (LIXEIRA)
   ------------------------------------------------------------------ */
const btnConfirmarDelete = document.getElementById("btnConfirmarDelete");

function abrirModalDelete(idRotina) {
    if (modalDelete && btnConfirmarDelete) {
        btnConfirmarDelete.href = "excluir_rotina.php?id=" + idRotina;
        modalDelete.style.display = "block";
    }
}

/* ------------------------------------------------------------------
   7. LOJA E RECOMPENSAS
   ------------------------------------------------------------------ */
// Modal Criar Recompensa
const btnNovaRecompensa = document.getElementById("btnNovaRecompensa");
const checkTemTempo = document.getElementById("checkTemTempo");
const divTempoRecompensa = document.getElementById("divTempoRecompensa");

if (btnNovaRecompensa && modalRecompensa) {
    btnNovaRecompensa.onclick = () => modalRecompensa.style.display = "block";
    
    if (checkTemTempo) {
        checkTemTempo.addEventListener('change', function() {
            if(divTempoRecompensa) divTempoRecompensa.style.display = this.checked ? "block" : "none";
        });
    }
}

// Modal Comprar
const textoConfirmacaoCompra = document.getElementById("textoConfirmacaoCompra");
const idRecompensaCompra = document.getElementById("idRecompensaCompra");

function abrirModalCompra(id, nome, preco) {
    if (modalCompra) {
        if(idRecompensaCompra) idRecompensaCompra.value = id;
        if(textoConfirmacaoCompra) {
            textoConfirmacaoCompra.innerHTML = `Você quer gastar <strong style="color: gold;">${preco} DT Points</strong><br>para obter: <strong>${nome}</strong>?`;
        }
        modalCompra.style.display = "block";
    }
}

/* ------------------------------------------------------------------
   8. INFO MODALS (IMPORTANTE & GAMIFICAÇÃO)
   ------------------------------------------------------------------ */
const btnImportante = document.querySelector(".btn-importante");
const linkGamificacao = document.getElementById("linkGamificacao");
const btnVoltarImportante = document.getElementById("btnVoltarImportante");

if (btnImportante && modalImportante) {
    btnImportante.onclick = (e) => { e.preventDefault(); modalImportante.style.display = "block"; };
}

if (linkGamificacao && modalGamificacao) {
    linkGamificacao.onclick = (e) => {
        e.preventDefault();
        if(modalImportante) modalImportante.style.display = "none";
        modalGamificacao.style.display = "block";
    }
}

if (btnVoltarImportante) {
    btnVoltarImportante.onclick = () => {
        modalGamificacao.style.display = "none";
        if(modalImportante) modalImportante.style.display = "block";
    }
}

// Tooltip
function toggleTooltip(element) {
    element.classList.toggle("active");
}

/* ------------------------------------------------------------------
   9. GERENCIADOR DE FECHAMENTO GLOBAL (WINDOW.ONCLICK)
   ------------------------------------------------------------------ */
// Fecha qualquer modal se clicar fora dela
window.onclick = function (event) {
    const modals = [modalMeta, modalImportante, modalGamificacao, modalConfirm, modalDelete, modalRecompensa, modalCompra];
    
    modals.forEach(m => {
        if (m && event.target == m) m.style.display = "none";
    });

    // Fecha Dropdown Perfil
    if (btnPerfil && !event.target.closest(".dropdown")) {
        if (myDropdown && myDropdown.classList.contains("show")) {
            myDropdown.classList.remove("show");
            if (arrowIcon) arrowIcon.style.transform = "rotate(45deg)";
        }
    }
    
    // Fecha Tooltip
    if (!event.target.closest('.help-icon')) {
        document.querySelectorAll('.help-icon.active').forEach(el => el.classList.remove('active'));
    }
};

// Gerenciador de Botões de Fechar (X, Cancelar, Entendi)
const botoesFechar = document.querySelectorAll(".close-btn, #btnCancelarConfirmacao, #btnCancelarDelete, #btnCancelarCompra, #btnEntendi");
botoesFechar.forEach(btn => {
    btn.addEventListener("click", function() {
        const modalPai = this.closest(".modal");
        if(modalPai) modalPai.style.display = "none";
    });
});