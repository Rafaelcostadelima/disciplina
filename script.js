/* ==================================================================
   1. MENU MOBILE (HAMBURGUER)
   ================================================================== */
const hamburger = document.querySelector(".hamburger");
const navMenu = document.querySelector(".nav-links");

if (hamburger && navMenu) {
  hamburger.addEventListener("click", () => {
    hamburger.classList.toggle("active");
    navMenu.classList.toggle("active");
  });

  // Fecha o menu ao clicar em um link
  document.querySelectorAll(".nav-links a").forEach((n) =>
    n.addEventListener("click", () => {
      hamburger.classList.remove("active");
      navMenu.classList.remove("active");
    }),
  );
}

/* ==================================================================
   2. DROPDOWN DO PERFIL (CLIQUE)
   ================================================================== */
const btnPerfil = document.getElementById("btnPerfil");
const myDropdown = document.getElementById("myDropdown");
const arrowIcon = document.querySelector(".arrow");

if (btnPerfil && myDropdown) {
  btnPerfil.addEventListener("click", function (e) {
    e.preventDefault(); // Impede a página de pular
    myDropdown.classList.toggle("show");

    // Gira a setinha
    if (myDropdown.classList.contains("show")) {
      if (arrowIcon) arrowIcon.style.transform = "rotate(-135deg)";
    } else {
      if (arrowIcon) arrowIcon.style.transform = "rotate(45deg)";
    }
  });
}

/* ==================================================================
   3. GERENCIAMENTO DAS MODAIS (META E IMPORTANTE)
   ================================================================== */

/* ==================================================================
   GERENCIAMENTO DA MODAL DE ROTINA (CRIAR E EDITAR)
   ================================================================== */
const modalMeta = document.getElementById("modalMeta");
const formRotina = document.getElementById("formRotina");
const modalTitulo = document.getElementById("modalTitulo");
const btnSalvarRotina = document.getElementById("btnSalvarRotina");
const inputIdRotina = document.getElementById("inputIdRotina");
const inputTipoMeta = document.getElementById("tipoMeta");
const inputPersonalizado = document.getElementById("metaTexto");
const divPersonalizado = document.getElementById("inputPersonalizado");
const inputMinutosMeta = document.getElementById("inputMinutos");
const checkboxesDias = document.querySelectorAll('input[name="dias[]"]');
const closeMeta = modalMeta ? modalMeta.querySelector(".close-btn") : null;

// Botões que abrem a modal para CRIAR (existem 2, um em cada aba)
const btnsCriar = document.querySelectorAll(".btn-add-task");

// 1. FUNÇÃO PARA ABRIR EM MODO "CRIAR"
if (btnsCriar) {
    btnsCriar.forEach(btn => {
        btn.onclick = function() {
            resetarModal(); // Limpa tudo
            modalMeta.style.display = "block";
        }
    });
}

// 2. FUNÇÃO PARA ABRIR EM MODO "EDITAR" (Chamada pelo botão lápis no PHP)
function abrirModalEditar(id, tipo, desc, duracao, diasString) {
    resetarModal(); // Limpa antes de preencher
    
    // Muda visual para "Edição"
    modalTitulo.innerText = "Editar Rotina";
    btnSalvarRotina.innerText = "ATUALIZAR ROTINA";
    formRotina.action = "atualizar_rotina.php"; // Aponta para o arquivo de update
    inputIdRotina.value = id; // Define o ID escondido

    // Preenche os campos
    inputTipoMeta.value = tipo;
    inputMinutosMeta.value = duracao;
    
    // Se for personalizado, mostra o campo
    if (tipo === 'outro') {
        divPersonalizado.style.display = "block";
        inputPersonalizado.value = desc;
    } else {
        divPersonalizado.style.display = "none";
    }

    // Marca os dias da semana
    // diasString vem como "Seg,Qua,Sex"
    const diasArray = diasString.split(',');
    checkboxesDias.forEach(chk => {
        if (diasArray.includes(chk.value)) {
            chk.checked = true;
        }
    });

    // Atualiza preview de pontos
    const event = new Event('input');
    inputMinutosMeta.dispatchEvent(event); // Força o recálculo dos pontos

    modalMeta.style.display = "block";
}

// Função auxiliar para limpar a modal
function resetarModal() {
    formRotina.reset(); // Limpa inputs
    modalTitulo.innerText = "Configurar Rotina";
    btnSalvarRotina.innerText = "CRIAR ROTINA";
    formRotina.action = "salvar_rotina.php"; // Volta para o arquivo de salvar
    inputIdRotina.value = "";
    divPersonalizado.style.display = "none";
    document.getElementById("pontosPreview").innerText = "0";
}

// Fechar Modal
if (closeMeta) {
    closeMeta.onclick = () => modalMeta.style.display = "none";
}

// --- MODAL IMPORTANTE ---
const modalImportante = document.getElementById("modalImportante");
const btnImportante = document.querySelector(".btn-importante");
const closeImportante = document.getElementById("fecharImportante");
const btnEntendi = document.getElementById("btnEntendi");

if (btnImportante && modalImportante) {
  btnImportante.onclick = function (e) {
    e.preventDefault();
    modalImportante.style.display = "block";
  };

  // Fecha no X
  if (closeImportante) {
    closeImportante.onclick = function () {
      modalImportante.style.display = "none";
    };
  }

  // Fecha no botão Entendi
  if (btnEntendi) {
    btnEntendi.onclick = function () {
      modalImportante.style.display = "none";
    };
  }
}

/* ==================================================================
   4. FUNÇÃO MESTRA: FECHAR TUDO AO CLICAR FORA
   ================================================================== */
window.onclick = function (event) {
  // 1. Fecha Modal de Meta se clicar no fundo
  if (modalMeta && event.target == modalMeta) {
    modalMeta.style.display = "none";
  }

  // 2. Fecha Modal Importante se clicar no fundo
  if (modalImportante && event.target == modalImportante) {
    modalImportante.style.display = "none";
  }

  // 3. Fecha Dropdown se clicar fora dele
  if (btnPerfil && !event.target.closest(".dropdown")) {
    if (myDropdown && myDropdown.classList.contains("show")) {
      myDropdown.classList.remove("show");
      if (arrowIcon) arrowIcon.style.transform = "rotate(45deg)"; // Reseta a seta
    }
  }
};

/* ==================================================================
   5. LÓGICA DO SELECT "OUTRO"
   ================================================================== */
// Esta função é chamada direto no HTML pelo onchange="verificarOutro(this)"
function verificarOutro(selectObject) {
  const divPersonalizada = document.getElementById("inputPersonalizado");
  const inputTexto = document.getElementById("metaTexto");

  if (selectObject.value === "outro") {
    divPersonalizada.style.display = "block";
    if (inputTexto) inputTexto.focus();
  } else {
    divPersonalizada.style.display = "none";
  }
}

/* ==================================================================
   CÁLCULO DE PONTOS EM TEMPO REAL (INPUT NUMBER)
   ================================================================== */
const inputMinutos = document.getElementById("inputMinutos");
const pontosPreview = document.getElementById("pontosPreview");

if (inputMinutos && pontosPreview) {
  inputMinutos.addEventListener("input", function () {
    let minutos = parseInt(this.value);

    if (isNaN(minutos) || minutos < 0) {
      minutos = 0;
    }

    // Regra: 3 pontos por minuto
    const pontos = minutos * 3;

    pontosPreview.innerText = pontos;
  });
}

/* ==================================================================
   SISTEMA DE ABAS (HOJE / SEMANA)
   ================================================================== */
function abrirTab(evt, tabName) {
  // 1. Esconde todos os conteúdos
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tab-content");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }

  // 2. Tira a classe 'active' de todos os botões
  tablinks = document.getElementsByClassName("tab-btn");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }

  // 3. Mostra o conteúdo clicado e ativa o botão
  document.getElementById(tabName).style.display = "block";
  evt.currentTarget.className += " active";
}

/* ==================================================================
   CONFIRMAÇÃO DE CONCLUSÃO (CHECK)
   ================================================================== */
const modalConfirm = document.getElementById("modalConfirmacao");
const inputRotinaId = document.getElementById("inputRotinaId");
const previewTask = document.getElementById("previewTaskConfirm");
const closeConfirm = document.getElementById("fecharConfirmacao");
const cancelConfirm = document.getElementById("btnCancelarConfirmacao");

// Função chamada pelo botão ✔ do HTML
function abrirConfirmacao(id, nome, minutos, pontos, desc) {
  if (!modalConfirm) return;

  // 1. Preenche o ID no formulário escondido
  inputRotinaId.value = id;

  // 2. Monta o HTML do Card igualzinho ao da tela principal
  // Usamos acento grave (`) para criar template string
  let descHtml = desc ? `<small style="color: #888;">"${desc}"</small>` : "";

  previewTask.innerHTML = `
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

  // 3. Abre a modal
  modalConfirm.style.display = "block";
}

// Fechar Modal (X ou Botão Não)
if (modalConfirm) {
  if (closeConfirm)
    closeConfirm.onclick = () => (modalConfirm.style.display = "none");
  if (cancelConfirm)
    cancelConfirm.onclick = () => (modalConfirm.style.display = "none");
}

/* ==================================================================
   MODAL DE EXCLUSÃO (LIXEIRA)
   ================================================================== */
const modalDelete = document.getElementById("modalDelete");
const btnConfirmarDelete = document.getElementById("btnConfirmarDelete");
const fecharDelete = document.getElementById("fecharDelete");
const btnCancelarDelete = document.getElementById("btnCancelarDelete");

function abrirModalDelete(idRotina) {
    if (modalDelete && btnConfirmarDelete) {
        // Atualiza o link do botão vermelho com o ID certo
        btnConfirmarDelete.href = "excluir_rotina.php?id=" + idRotina;
        modalDelete.style.display = "block";
    }
}

// Fechar modal de delete
if (modalDelete) {
    if(fecharDelete) fecharDelete.onclick = () => modalDelete.style.display = "none";
    if(btnCancelarDelete) btnCancelarDelete.onclick = () => modalDelete.style.display = "none";
}
