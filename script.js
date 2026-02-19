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
    document.querySelectorAll(".nav-links a").forEach(n => n.addEventListener("click", () => {
        hamburger.classList.remove("active");
        navMenu.classList.remove("active");
    }));
}

/* ==================================================================
   2. DROPDOWN DO PERFIL (CLIQUE)
   ================================================================== */
const btnPerfil = document.getElementById("btnPerfil");
const myDropdown = document.getElementById("myDropdown");
const arrowIcon = document.querySelector(".arrow");

if (btnPerfil && myDropdown) {
    btnPerfil.addEventListener("click", function(e) {
        e.preventDefault(); // Impede a página de pular
        myDropdown.classList.toggle("show");
        
        // Gira a setinha
        if (myDropdown.classList.contains("show")) {
            if(arrowIcon) arrowIcon.style.transform = "rotate(-135deg)";
        } else {
            if(arrowIcon) arrowIcon.style.transform = "rotate(45deg)";
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
    btnNovaMeta.onclick = function() {
        modalMeta.style.display = "block";
    }
    if (closeMeta) {
        closeMeta.onclick = function() {
            modalMeta.style.display = "none";
        }
    }
}

// --- MODAL IMPORTANTE ---
const modalImportante = document.getElementById("modalImportante");
const btnImportante = document.querySelector(".btn-importante");
const closeImportante = document.getElementById("fecharImportante");
const btnEntendi = document.getElementById("btnEntendi");

if (btnImportante && modalImportante) {
    btnImportante.onclick = function(e) {
        e.preventDefault();
        modalImportante.style.display = "block";
    }
    
    // Fecha no X
    if (closeImportante) {
        closeImportante.onclick = function() {
            modalImportante.style.display = "none";
        }
    }
    
    // Fecha no botão Entendi
    if (btnEntendi) {
        btnEntendi.onclick = function() {
            modalImportante.style.display = "none";
        }
    }
}

/* ==================================================================
   4. FUNÇÃO MESTRA: FECHAR TUDO AO CLICAR FORA
   ================================================================== */
window.onclick = function(event) {
    
    // 1. Fecha Modal de Meta se clicar no fundo
    if (modalMeta && event.target == modalMeta) {
        modalMeta.style.display = "none";
    }

    // 2. Fecha Modal Importante se clicar no fundo
    if (modalImportante && event.target == modalImportante) {
        modalImportante.style.display = "none";
    }

    // 3. Fecha Dropdown se clicar fora dele
    if (btnPerfil && !event.target.closest('.dropdown')) {
        if (myDropdown && myDropdown.classList.contains('show')) {
            myDropdown.classList.remove('show');
            if(arrowIcon) arrowIcon.style.transform = "rotate(45deg)"; // Reseta a seta
        }
    }
}

/* ==================================================================
   5. LÓGICA DO SELECT "OUTRO"
   ================================================================== */
// Esta função é chamada direto no HTML pelo onchange="verificarOutro(this)"
function verificarOutro(selectObject) {
    const divPersonalizada = document.getElementById("inputPersonalizado");
    const inputTexto = document.getElementById("metaTexto");

    if (selectObject.value === "outro") {
        divPersonalizada.style.display = "block";
        if(inputTexto) inputTexto.focus();
    } else {
        divPersonalizada.style.display = "none";
    }
}