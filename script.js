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

// --- MODAL NOVA META ---
const modalMeta = document.getElementById("modalMeta");
const btnNovaMeta = document.getElementById("btnNovaMeta");
// Selecionamos o botão de fechar específico de dentro da modal meta
const closeMeta = modalMeta ? modalMeta.querySelector(".close-btn") : null;

if (btnNovaMeta && modalMeta) {
  btnNovaMeta.onclick = function () {
    modalMeta.style.display = "block";
  };
  if (closeMeta) {
    closeMeta.onclick = function () {
      modalMeta.style.display = "none";
    };
  }
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

// Adicionar ao fechar global (window.onclick)
// ATENÇÃO: Adicione isso dentro do seu window.onclick existente!
/*
    if (modalConfirm && event.target == modalConfirm) {
        modalConfirm.style.display = "none";
    }
*/
