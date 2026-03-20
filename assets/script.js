/* ============================================================
   IDEASTAGE — main.js
   Page : Détail d'une offre
   Fonctionnalités :
     1. Navbar mobile (menu burger)
     2. Bouton Sauvegarder / favoris (toggle visuel)
     3. Modal de candidature (formulaire complet)
     4. Barre de recherche rapide (filtre visuel)
   ============================================================ */


/* ============================================================
   1. NAVBAR MOBILE — Menu burger
   ============================================================
   Ajoute un bouton burger dans la navbar sur mobile.
   Au clic, les liens s'affichent / se cachent avec animation.
   ============================================================ */

function initNavbarMobile() {

  const navbar     = document.querySelector('.navbar-inner');
  const navLinks   = document.querySelector('.nav-links');
  const navRight   = document.querySelector('.nav-right');

  if (!navbar || !navLinks) return;

  // --- Créer le bouton burger ---
  const burger = document.createElement('button');
  burger.className   = 'burger-btn';
  burger.setAttribute('aria-label', 'Ouvrir le menu');
  burger.setAttribute('aria-expanded', 'false');
  burger.innerHTML   = `
    <span class="burger-line"></span>
    <span class="burger-line"></span>
    <span class="burger-line"></span>
  `;
  navbar.appendChild(burger);

  // --- Créer le menu mobile overlay ---
  const mobileMenu = document.createElement('div');
  mobileMenu.className = 'mobile-menu';
  mobileMenu.setAttribute('aria-hidden', 'true');
  mobileMenu.innerHTML = `
    <div class="mobile-menu-inner">
      <nav class="mobile-nav-links">
        <a href="#">Accueil</a>
        <a href="#" class="active">Offres</a>
        <a href="#">Espace Entreprise</a>
        <a href="#">Ressources</a>
        <a href="#">Espace Candidat</a>
      </nav>
      <div class="mobile-nav-actions">
        <button class="btn-login" onclick="closeMobileMenu()">Connexion</button>
        <button class="btn-signup" onclick="closeMobileMenu()">S'inscrire</button>
      </div>
    </div>
  `;
  document.body.appendChild(mobileMenu);

  // --- Overlay sombre derrière le menu ---
  const overlay = document.createElement('div');
  overlay.className = 'mobile-overlay';
  document.body.appendChild(overlay);

  // --- Injecter les styles du burger et menu mobile ---
  injectStyles('burger-styles', `
    .burger-btn {
      display: none;
      flex-direction: column;
      justify-content: center;
      gap: 5px;
      width: 40px;
      height: 40px;
      background: none;
      border: 1.5px solid var(--border);
      border-radius: 9px;
      cursor: pointer;
      padding: 8px;
      margin-left: auto;
      transition: border-color .15s;
    }
    .burger-btn:hover { border-color: var(--primary); }
    .burger-line {
      display: block;
      width: 100%;
      height: 2px;
      background: var(--text);
      border-radius: 2px;
      transition: transform .25s ease, opacity .2s ease;
    }
    /* Burger animé → croix */
    .burger-btn.is-open .burger-line:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .burger-btn.is-open .burger-line:nth-child(2) { opacity: 0; transform: scaleX(0); }
    .burger-btn.is-open .burger-line:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    .mobile-menu {
      position: fixed;
      top: 64px;
      left: 0;
      right: 0;
      background: white;
      border-bottom: 1px solid var(--border);
      z-index: 99;
      transform: translateY(-10px);
      opacity: 0;
      pointer-events: none;
      transition: transform .25s ease, opacity .2s ease;
      box-shadow: 0 8px 24px rgba(0,0,0,.08);
    }
    .mobile-menu.is-open {
      transform: translateY(0);
      opacity: 1;
      pointer-events: all;
    }
    .mobile-menu-inner { padding: 20px 24px 24px; }
    .mobile-nav-links {
      display: flex;
      flex-direction: column;
      gap: 4px;
      margin-bottom: 20px;
    }
    .mobile-nav-links a {
      text-decoration: none;
      color: var(--text-2);
      font-weight: 500;
      font-size: 15px;
      padding: 10px 14px;
      border-radius: 10px;
      transition: background .15s;
    }
    .mobile-nav-links a:hover,
    .mobile-nav-links a.active { background: var(--primary-light); color: var(--primary); }
    .mobile-nav-actions { display: flex; gap: 10px; }
    .mobile-nav-actions .btn-login,
    .mobile-nav-actions .btn-signup { flex: 1; justify-content: center; }

    .mobile-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.3);
      z-index: 98;
      opacity: 0;
      pointer-events: none;
      transition: opacity .2s;
    }
    .mobile-overlay.is-open { opacity: 1; pointer-events: all; }

    @media (max-width: 768px) {
      .burger-btn { display: flex; }
      .nav-links, .nav-right { display: none !important; }
    }
  `);

  // --- Ouvrir / fermer ---
  function openMenu() {
    burger.classList.add('is-open');
    mobileMenu.classList.add('is-open');
    overlay.classList.add('is-open');
    burger.setAttribute('aria-expanded', 'true');
    mobileMenu.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeMenu() {
    burger.classList.remove('is-open');
    mobileMenu.classList.remove('is-open');
    overlay.classList.remove('is-open');
    burger.setAttribute('aria-expanded', 'false');
    mobileMenu.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  // Exposer pour les boutons inline dans le template
  window.closeMobileMenu = closeMenu;

  burger.addEventListener('click', () => {
    burger.classList.contains('is-open') ? closeMenu() : openMenu();
  });
  overlay.addEventListener('click', closeMenu);

  // Fermer au resize si on passe en desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) closeMenu();
  });
}


/* ============================================================
   2. SAUVEGARDER / FAVORIS — Toggle visuel
   ============================================================
   Le bouton "Sauvegarder" bascule entre actif et inactif.
   Visuellement : cœur plein + couleur + texte mis à jour.
   ============================================================ */

function initSaveButton() {

  const saveBtn = document.querySelector('.btn-save');
  if (!saveBtn) return;

  let saved = false;

  // Icône cœur vide (par défaut)
  const iconEmpty = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;

  // Icône cœur plein (sauvegardé)
  const iconFull  = `<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;

  injectStyles('save-styles', `
    .btn-save.is-saved {
      background: #fff1f2;
      border-color: #fda4af;
      color: #e11d48;
    }
    .btn-save {
      transition: background .2s, border-color .2s, color .2s, transform .15s;
    }
    .btn-save:active { transform: scale(.96); }

    /* Petite animation de rebond au clic */
    @keyframes heartPop {
      0%   { transform: scale(1); }
      40%  { transform: scale(1.3); }
      70%  { transform: scale(.9); }
      100% { transform: scale(1); }
    }
    .btn-save.heart-pop svg { animation: heartPop .35s ease forwards; }
  `);

  saveBtn.addEventListener('click', () => {
    saved = !saved;

    if (saved) {
      saveBtn.classList.add('is-saved');
      saveBtn.innerHTML = iconFull + ' Sauvegardé';
    } else {
      saveBtn.classList.remove('is-saved');
      saveBtn.innerHTML = iconEmpty + ' Sauvegarder';
    }

    // Déclencher l'animation rebond
    saveBtn.classList.remove('heart-pop');
    void saveBtn.offsetWidth; // force reflow pour relancer l'animation
    saveBtn.classList.add('heart-pop');
    setTimeout(() => saveBtn.classList.remove('heart-pop'), 400);

    // Toast de confirmation
    showToast(saved ? '❤️ Offre sauvegardée !' : 'Offre retirée des favoris');
  });
}


/* ============================================================
   3. MODAL DE CANDIDATURE — Formulaire complet
   ============================================================
   Clic sur "Postuler maintenant" → modal avec :
     - Nom, Prénom, Email, Téléphone
     - Message de motivation
     - Upload CV (visuel)
     - Validation basique + message de succès
   ============================================================ */

function initCandidatureModal() {

  const applyBtn = document.querySelector('.btn-apply');
  if (!applyBtn) return;

  // --- Créer la modal ---
  const modal = document.createElement('div');
  modal.className = 'modal-backdrop';
  modal.setAttribute('aria-hidden', 'true');
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
  modal.setAttribute('aria-labelledby', 'modal-title');

  modal.innerHTML = `
    <div class="modal-box">

      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="modal-title">Postuler à l'offre</h2>
          <p class="modal-subtitle">Développeur Full-Stack React/Node · TechVision</p>
        </div>
        <button class="modal-close" aria-label="Fermer">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="modal-body">

        <!-- Étape 1 : formulaire -->
        <div class="modal-step" id="step-form">
          <div class="form-row">
            <div class="form-group">
              <label for="f-prenom">Prénom <span class="required">*</span></label>
              <input type="text" id="f-prenom" placeholder="Jean" autocomplete="given-name">
              <span class="field-error" id="err-prenom"></span>
            </div>
            <div class="form-group">
              <label for="f-nom">Nom <span class="required">*</span></label>
              <input type="text" id="f-nom" placeholder="Dupont" autocomplete="family-name">
              <span class="field-error" id="err-nom"></span>
            </div>
          </div>

          <div class="form-group">
            <label for="f-email">Email <span class="required">*</span></label>
            <input type="email" id="f-email" placeholder="jean.dupont@email.com" autocomplete="email">
            <span class="field-error" id="err-email"></span>
          </div>

          <div class="form-group">
            <label for="f-tel">Téléphone</label>
            <input type="tel" id="f-tel" placeholder="06 12 34 56 78" autocomplete="tel">
          </div>

          <div class="form-group">
            <label for="f-message">Lettre de motivation <span class="required">*</span></label>
            <textarea id="f-message" rows="4" placeholder="Parlez-nous de votre motivation pour ce poste..."></textarea>
            <div class="char-count"><span id="char-current">0</span> / 500 caractères</div>
            <span class="field-error" id="err-message"></span>
          </div>

          <div class="form-group">
            <label>CV <span class="required">*</span></label>
            <div class="upload-zone" id="upload-zone">
              <div class="upload-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <p class="upload-text">Glissez votre CV ici ou <span class="upload-link">parcourir</span></p>
              <p class="upload-hint">PDF, DOC, DOCX · 5 Mo max</p>
              <input type="file" id="f-cv" accept=".pdf,.doc,.docx" hidden>
            </div>
            <div class="file-preview" id="file-preview" style="display:none;"></div>
            <span class="field-error" id="err-cv"></span>
          </div>

          <button class="modal-submit" id="btn-submit" type="button">
            Envoyer ma candidature
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </div>

        <!-- Étape 2 : succès -->
        <div class="modal-step" id="step-success" style="display:none;">
          <div class="success-content">
            <div class="success-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h3 class="success-title">Candidature envoyée !</h3>
            <p class="success-text">Votre candidature a bien été transmise à <strong>TechVision</strong>. Vous recevrez une réponse sous 5 à 10 jours ouvrés.</p>
            <button class="modal-close-btn" type="button">Fermer</button>
          </div>
        </div>

      </div>
    </div>
  `;

  document.body.appendChild(modal);

  // --- Styles de la modal ---
  injectStyles('modal-styles', `
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.45);
      backdrop-filter: blur(4px);
      z-index: 200;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0;
      pointer-events: none;
      transition: opacity .2s ease;
    }
    .modal-backdrop.is-open {
      opacity: 1;
      pointer-events: all;
    }
    .modal-box {
      background: white;
      border-radius: 20px;
      width: 100%;
      max-width: 560px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 24px 80px rgba(0,0,0,.2);
      transform: translateY(20px) scale(.97);
      transition: transform .25s ease;
    }
    .modal-backdrop.is-open .modal-box {
      transform: translateY(0) scale(1);
    }
    .modal-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      padding: 24px 28px 0;
    }
    .modal-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--text);
      font-family: 'DM Serif Display', serif;
    }
    .modal-subtitle {
      font-size: 13.5px;
      color: var(--text-3);
      margin-top: 2px;
    }
    .modal-close {
      width: 34px; height: 34px;
      border-radius: 9px;
      border: 1.5px solid var(--border);
      background: white;
      cursor: pointer;
      display: grid;
      place-items: center;
      color: var(--text-3);
      transition: .15s;
      flex-shrink: 0;
    }
    .modal-close:hover { border-color: var(--text); color: var(--text); }

    .modal-body { padding: 20px 28px 28px; }

    /* Formulaire */
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
    .form-group label {
      font-size: 13.5px; font-weight: 600; color: var(--text-2);
    }
    .required { color: var(--primary); }
    .form-group input,
    .form-group textarea {
      padding: 10px 14px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 14px;
      font-family: inherit;
      color: var(--text);
      outline: none;
      transition: border-color .15s, box-shadow .15s;
      resize: vertical;
    }
    .form-group input:focus,
    .form-group textarea:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79,70,229,.1);
    }
    .form-group input.is-error,
    .form-group textarea.is-error {
      border-color: #ef4444;
    }
    .field-error {
      font-size: 12px;
      color: #ef4444;
      font-weight: 500;
      min-height: 16px;
      display: block;
    }
    .char-count {
      font-size: 12px;
      color: var(--text-4);
      text-align: right;
      margin-top: 4px;
    }
    .char-count.over { color: #ef4444; }

    /* Upload zone */
    .upload-zone {
      border: 2px dashed var(--border);
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      cursor: pointer;
      transition: border-color .15s, background .15s;
    }
    .upload-zone:hover,
    .upload-zone.drag-over {
      border-color: var(--primary);
      background: var(--primary-light);
    }
    .upload-icon {
      width: 44px; height: 44px;
      background: var(--border-2);
      border-radius: 12px;
      display: grid;
      place-items: center;
      margin: 0 auto 10px;
      color: var(--text-3);
    }
    .upload-text { font-size: 14px; color: var(--text-2); font-weight: 500; }
    .upload-link { color: var(--primary); text-decoration: underline; cursor: pointer; }
    .upload-hint { font-size: 12px; color: var(--text-4); margin-top: 4px; }

    .file-preview {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--border-2);
      border-radius: 10px;
      padding: 10px 14px;
      margin-top: 8px;
      font-size: 13.5px;
      color: var(--text-2);
      font-weight: 500;
    }
    .file-remove {
      margin-left: auto;
      background: none;
      border: none;
      color: var(--text-4);
      cursor: pointer;
      font-size: 18px;
      line-height: 1;
      padding: 0 4px;
      transition: color .15s;
    }
    .file-remove:hover { color: #ef4444; }

    /* Bouton soumettre */
    .modal-submit {
      width: 100%;
      background: var(--primary);
      color: white;
      font-weight: 700;
      font-size: 15px;
      padding: 14px;
      border-radius: 12px;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background .15s, transform .1s;
      font-family: inherit;
      margin-top: 8px;
    }
    .modal-submit:hover { background: var(--primary-dark); }
    .modal-submit:active { transform: scale(.98); }
    .modal-submit:disabled { opacity: .6; cursor: not-allowed; }

    /* Succès */
    .success-content { text-align: center; padding: 16px 0 8px; }
    .success-icon {
      width: 64px; height: 64px;
      background: var(--green-light);
      border-radius: 50%;
      display: grid;
      place-items: center;
      margin: 0 auto 16px;
      color: var(--green);
    }
    .success-title {
      font-size: 22px;
      font-weight: 700;
      font-family: 'DM Serif Display', serif;
      color: var(--text);
      margin-bottom: 10px;
    }
    .success-text { font-size: 14.5px; color: var(--text-3); line-height: 1.7; margin-bottom: 24px; }
    .modal-close-btn {
      background: var(--primary);
      color: white;
      font-weight: 600;
      font-size: 14px;
      padding: 12px 32px;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      font-family: inherit;
      transition: background .15s;
    }
    .modal-close-btn:hover { background: var(--primary-dark); }

    @media (max-width: 560px) {
      .form-row { grid-template-columns: 1fr; }
      .modal-box { border-radius: 16px 16px 0 0; align-self: flex-end; max-height: 95vh; }
    }
  `);

  // --- Ouvrir la modal ---
  function openModal() {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    // Focus sur le premier champ
    setTimeout(() => {
      const firstInput = modal.querySelector('input');
      if (firstInput) firstInput.focus();
    }, 250);
  }

  // --- Fermer la modal ---
  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  // --- Événements d'ouverture / fermeture ---
  applyBtn.addEventListener('click', openModal);

  modal.querySelector('.modal-close').addEventListener('click', closeModal);
  modal.querySelector('.modal-close-btn').addEventListener('click', closeModal);

  // Clic en dehors de la box
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  // Touche Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
  });

  // --- Compteur de caractères pour la motivation ---
  const textarea    = modal.querySelector('#f-message');
  const charCurrent = modal.querySelector('#char-current');
  const MAX_CHARS   = 500;

  textarea.addEventListener('input', () => {
    const len = textarea.value.length;
    charCurrent.textContent = len;
    charCurrent.parentElement.classList.toggle('over', len > MAX_CHARS);
  });

  // --- Upload CV ---
  const uploadZone  = modal.querySelector('#upload-zone');
  const fileInput   = modal.querySelector('#f-cv');
  const filePreview = modal.querySelector('#file-preview');
  let   uploadedFile = null;

  // Clic sur la zone ou le lien "parcourir"
  uploadZone.addEventListener('click', () => fileInput.click());

  // Drag & drop
  uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('drag-over');
  });
  uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
  uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) handleFileSelected(file);
  });

  fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) handleFileSelected(fileInput.files[0]);
  });

  function handleFileSelected(file) {
    const allowed = ['application/pdf', 'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!allowed.includes(file.type)) {
      showFieldError('err-cv', 'Format non supporté. Utilisez PDF ou Word.');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      showFieldError('err-cv', 'Fichier trop lourd. Maximum 5 Mo.');
      return;
    }

    uploadedFile = file;
    clearFieldError('err-cv');
    uploadZone.style.display = 'none';

    const ext  = file.name.split('.').pop().toUpperCase();
    const size = (file.size / 1024).toFixed(0) + ' Ko';
    filePreview.style.display = 'flex';
    filePreview.innerHTML = `
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <div>
        <div style="font-weight:600;font-size:13.5px;">${file.name}</div>
        <div style="font-size:12px;color:var(--text-4);">${ext} · ${size}</div>
      </div>
      <button class="file-remove" title="Supprimer">×</button>
    `;

    filePreview.querySelector('.file-remove').addEventListener('click', () => {
      uploadedFile = null;
      fileInput.value = '';
      filePreview.style.display = 'none';
      uploadZone.style.display = '';
    });
  }

  // --- Validation du formulaire ---
  function validateForm() {
    let valid = true;

    const prenom  = modal.querySelector('#f-prenom');
    const nom     = modal.querySelector('#f-nom');
    const email   = modal.querySelector('#f-email');
    const message = modal.querySelector('#f-message');

    // Réinitialiser les erreurs
    ['prenom','nom','email','message','cv'].forEach(clearFieldError);
    [prenom, nom, email, message].forEach(el => el.classList.remove('is-error'));

    // Prénom
    if (!prenom.value.trim()) {
      showFieldError('err-prenom', 'Le prénom est requis.');
      prenom.classList.add('is-error');
      valid = false;
    }

    // Nom
    if (!nom.value.trim()) {
      showFieldError('err-nom', 'Le nom est requis.');
      nom.classList.add('is-error');
      valid = false;
    }

    // Email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email.value.trim()) {
      showFieldError('err-email', 'L\'email est requis.');
      email.classList.add('is-error');
      valid = false;
    } else if (!emailRegex.test(email.value.trim())) {
      showFieldError('err-email', 'Adresse email invalide.');
      email.classList.add('is-error');
      valid = false;
    }

    // Message
    if (!message.value.trim()) {
      showFieldError('err-message', 'La lettre de motivation est requise.');
      message.classList.add('is-error');
      valid = false;
    } else if (message.value.length > MAX_CHARS) {
      showFieldError('err-message', `Maximum ${MAX_CHARS} caractères.`);
      message.classList.add('is-error');
      valid = false;
    }

    // CV
    if (!uploadedFile) {
      showFieldError('err-cv', 'Veuillez joindre votre CV.');
      valid = false;
    }

    return valid;
  }

  // --- Soumettre ---
  modal.querySelector('#btn-submit').addEventListener('click', () => {
    if (!validateForm()) return;

    const submitBtn = modal.querySelector('#btn-submit');
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation:spin .8s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
      Envoi en cours...
    `;
    injectStyles('spin', `@keyframes spin { to { transform: rotate(360deg); } }`);

    // Simuler un délai réseau (1.5s) puis afficher le succès
    setTimeout(() => {
      modal.querySelector('#step-form').style.display   = 'none';
      modal.querySelector('#step-success').style.display = 'block';
    }, 1500);
  });

  // Effacer les erreurs inline au focus
  modal.querySelectorAll('input, textarea').forEach(input => {
    input.addEventListener('focus', () => {
      input.classList.remove('is-error');
      const errId = 'err-' + input.id.replace('f-', '');
      clearFieldError(errId);
    });
  });
}


/* ============================================================
   4. BARRE DE RECHERCHE RAPIDE
   ============================================================
   Une barre flottante apparaît dans le header de l'offre
   pour permettre de relancer une recherche rapide
   sans quitter la page (redirection simulée).
   ============================================================ */

function initQuickSearch() {

  // Chercher la zone header de l'offre pour y injecter la barre
  const offerCard = document.querySelector('.offer-header-card');
  if (!offerCard) return;

  // Créer la barre
  const searchWrap = document.createElement('div');
  searchWrap.className = 'quick-search';
  searchWrap.innerHTML = `
    <div class="quick-search-inner">
      <div class="qs-field">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="qs-keywords" placeholder="Titre, compétence, entreprise...">
      </div>
      <div class="qs-divider"></div>
      <div class="qs-field">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <input type="text" id="qs-location" placeholder="Ville, région...">
      </div>
      <button class="qs-btn" id="qs-submit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Rechercher
      </button>
    </div>
  `;

  // Insérer avant la card d'offre
  offerCard.parentNode.insertBefore(searchWrap, offerCard);

  injectStyles('search-styles', `
    .quick-search {
      margin-bottom: 16px;
      animation: fadeUp .4s ease both;
    }
    .quick-search-inner {
      background: white;
      border: 1.5px solid var(--border);
      border-radius: 14px;
      display: flex;
      align-items: center;
      padding: 6px 6px 6px 16px;
      gap: 0;
      box-shadow: var(--shadow-sm);
      transition: box-shadow .2s, border-color .2s;
    }
    .quick-search-inner:focus-within {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79,70,229,.1);
    }
    .qs-field {
      display: flex;
      align-items: center;
      gap: 8px;
      flex: 1;
      color: var(--text-4);
    }
    .qs-field input {
      border: none;
      outline: none;
      font-size: 14px;
      font-family: inherit;
      color: var(--text);
      width: 100%;
      background: transparent;
    }
    .qs-field input::placeholder { color: var(--text-4); }
    .qs-divider {
      width: 1px;
      height: 24px;
      background: var(--border);
      margin: 0 16px;
      flex-shrink: 0;
    }
    .qs-btn {
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 10px;
      padding: 10px 20px;
      font-size: 14px;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 7px;
      transition: background .15s;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .qs-btn:hover { background: var(--primary-dark); }

    @media (max-width: 580px) {
      .quick-search-inner { flex-direction: column; padding: 12px; gap: 10px; align-items: stretch; }
      .qs-divider { display: none; }
      .qs-btn { justify-content: center; }
    }
  `);

  // Au clic sur Rechercher → toast de simulation
  document.querySelector('#qs-submit').addEventListener('click', () => {
    const kw  = document.querySelector('#qs-keywords').value.trim();
    const loc = document.querySelector('#qs-location').value.trim();

    if (!kw && !loc) {
      showToast('💡 Entrez un mot-clé ou une ville pour rechercher.');
      return;
    }

    const parts = [kw && `"${kw}"`, loc && `📍 ${loc}`].filter(Boolean).join(' · ');
    showToast(`🔍 Recherche : ${parts}`);
  });

  // Soumettre avec Entrée
  [document.querySelector('#qs-keywords'), document.querySelector('#qs-location')]
    .forEach(input => {
      input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') document.querySelector('#qs-submit').click();
      });
    });
}


/* ============================================================
   UTILITAIRES
   ============================================================ */

/**
 * Injecte un bloc <style> dans le <head> une seule fois.
 * @param {string} id  — identifiant unique du style
 * @param {string} css — contenu CSS
 */
function injectStyles(id, css) {
  if (document.getElementById(id)) return;
  const style = document.createElement('style');
  style.id = id;
  style.textContent = css;
  document.head.appendChild(style);
}

/**
 * Affiche un toast de notification en bas de l'écran.
 * @param {string} message — texte à afficher
 * @param {number} duration — durée en ms (défaut 3000)
 */
function showToast(message, duration = 3000) {
  injectStyles('toast-styles', `
    #toast-container {
      position: fixed;
      bottom: 24px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 999;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      pointer-events: none;
    }
    .toast {
      background: #111827;
      color: white;
      font-size: 14px;
      font-weight: 500;
      padding: 12px 20px;
      border-radius: 99px;
      box-shadow: 0 8px 24px rgba(0,0,0,.2);
      opacity: 0;
      transform: translateY(12px);
      transition: opacity .25s ease, transform .25s ease;
      white-space: nowrap;
      pointer-events: none;
    }
    .toast.visible { opacity: 1; transform: translateY(0); }
  `);

  // Créer ou récupérer le container
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.textContent = message;
  container.appendChild(toast);

  // Animer
  requestAnimationFrame(() => {
    requestAnimationFrame(() => toast.classList.add('visible'));
  });

  setTimeout(() => {
    toast.classList.remove('visible');
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

/**
 * Affiche un message d'erreur sous un champ.
 * @param {string} errId   — id de l'élément <span> d'erreur
 * @param {string} message — texte d'erreur
 */
function showFieldError(errId, message) {
  const el = document.getElementById(errId);
  if (el) el.textContent = message;
}

/**
 * Efface un message d'erreur.
 * @param {string} errId — id de l'élément <span> d'erreur
 */
function clearFieldError(errId) {
  const el = document.getElementById(errId);
  if (el) el.textContent = '';
}


/* ============================================================
   INIT — Lancer tout au chargement du DOM
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  initNavbarMobile();
  initSaveButton();
  initCandidatureModal();
  initQuickSearch();
});